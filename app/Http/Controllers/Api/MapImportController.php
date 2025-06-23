<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TileMapResource;
use App\Http\Resources\TileSetResource;
use App\Models\TileMap;
use App\Models\TileSet;
use App\Services\MapImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Log;

#[OA\Tag(
    name: 'Map Import',
    description: 'Endpoints for importing maps through the wizard'
)]
class MapImportController extends Controller
{
    public function __construct(
        private MapImportService $importService
    ) {}

    /**
     * Upload a map file for import
     */
    #[OA\Post(
        path: '/map-import/upload',
        summary: 'Upload a map file for import',
        tags: ['Map Import'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'files', type: 'array', items: new OA\Schema(type: 'string', format: 'binary')),
                    ],
                    required: ['files']
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Files uploaded successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'files', type: 'array'),
                    new OA\Property(property: 'main_map_file', type: 'string'),
                    new OA\Property(property: 'field_type_file', type: 'string'),
                ])
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'errors', type: 'object')
                ])
            )
        ]
    )]
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:10240', // 10MB max per file
        ]);

        // Custom validation for file extensions
        $validator->after(function ($validator) use ($request) {
            $files = $request->file('files');
            if ($files) {
                foreach ($files as $file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $allowedExtensions = ['json', 'tmx', 'js'];
                    
                    if (!in_array($extension, $allowedExtensions)) {
                        $validator->errors()->add('files', 'All files must be one of: ' . implode(', ', $allowedExtensions));
                        break;
                    }
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $files = $request->file('files');
        $uploadedFiles = [];
        $mainMapFile = null;
        $fieldTypeFile = null;

        foreach ($files as $file) {
            $fileName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            
            // Generate a unique filename while preserving the original extension
            $uniqueName = uniqid() . '.' . $extension;
            $filePath = 'imports/' . $uniqueName;
            
            // Store the file with the custom path
            Storage::disk('local')->put($filePath, file_get_contents($file->getRealPath()));

            $uploadedFiles[] = [
                'file_path' => $filePath,
                'file_name' => $fileName,
                'extension' => $extension
            ];

            // Identify main map file and field type file
            if ($extension === 'js') {
                if (str_ends_with($fileName, '_ft.js')) {
                    $fieldTypeFile = $filePath;
                } else {
                    $mainMapFile = $filePath;
                }
            } else {
                // For non-JS files, treat as main map file
                $mainMapFile = $filePath;
            }
        }

        return response()->json([
            'message' => 'Files uploaded successfully',
            'files' => $uploadedFiles,
            'main_map_file' => $mainMapFile,
            'field_type_file' => $fieldTypeFile,
        ]);
    }

    /**
     * Parse an uploaded map file and return preview data
     */
    #[OA\Post(
        path: '/map-import/parse',
        summary: 'Parse an uploaded map file and return preview data',
        tags: ['Map Import'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'file_path', type: 'string'),
                new OA\Property(property: 'format', type: 'string', nullable: true),
                new OA\Property(property: 'field_type_file_path', type: 'string', nullable: true),
            ], required: ['file_path'])
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'File parsed successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'map_info', type: 'object'),
                    new OA\Property(property: 'layers', type: 'array'),
                    new OA\Property(property: 'tilesets', type: 'array'),
                    new OA\Property(property: 'detected_format', type: 'string'),
                    new OA\Property(property: 'suggested_tilesets', type: 'array'),
                ])
            ),
            new OA\Response(
                response: 422,
                description: 'Parsing error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string'),
                ])
            )
        ]
    )]
    public function parse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'format' => 'nullable|string|in:json,tmx,js',
            'field_type_file_path' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $filePath = $request->input('file_path');
        $format = $request->input('format');
        $fieldTypeFilePath = $request->input('field_type_file_path');

        // Verify main file exists
        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'message' => 'File not found'
            ], 422);
        }

        // Verify field type file exists if provided
        if ($fieldTypeFilePath && !Storage::disk('local')->exists($fieldTypeFilePath)) {
            return response()->json([
                'message' => 'Field type file not found'
            ], 422);
        }

        try {
            // Parse the file using the wizard-optimized service method
            $result = $this->importService->parseFileForWizard($filePath, $format);
            $wizardData = $result['data'];
            $detectedFormat = $result['format'];

            // If this is a JS file and we have a field type file, copy it to the expected location
            if ($detectedFormat === 'js' && $fieldTypeFilePath) {
                $this->copyFieldTypeFile($filePath, $fieldTypeFilePath);
            }

            // Get suggested tilesets for each tileset that requires upload
            $suggestedTilesets = $this->getSuggestedTilesetsForWizard($wizardData['tilesets']);

            return response()->json([
                'map_info' => $wizardData['map_info'],
                'tilesets' => $wizardData['tilesets'],
                'detected_format' => $detectedFormat,
                'suggested_tilesets' => $suggestedTilesets,
                'field_type_file' => $wizardData['field_type_file'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to parse file: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Complete the import process with user selections
     */
    #[OA\Post(
        path: '/map-import/complete',
        summary: 'Complete the import process with user selections',
        tags: ['Map Import'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'file_path', type: 'string'),
                        new OA\Property(property: 'format', type: 'string'),
                        new OA\Property(property: 'tileset_mappings', type: 'string'), // JSON string
                        new OA\Property(property: 'field_type_file_path', type: 'string', nullable: true),
                        new OA\Property(property: 'tileset_images', type: 'array', items: new OA\Schema(type: 'string', format: 'binary')),
                    ],
                    required: ['file_path', 'format', 'tileset_mappings']
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Import completed successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'map', ref: '#/components/schemas/TileMap'),
                    new OA\Property(property: 'created_tilesets', type: 'array'),
                ])
            ),
            new OA\Response(
                response: 422,
                description: 'Import error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string'),
                ])
            )
        ]
    )]
    public function complete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'format' => 'required|string|in:json,tmx,js',
            'tileset_mappings' => 'required|string', // JSON string
            'field_type_file_path' => 'nullable|string',
            'tileset_images.*' => 'nullable|file|image|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $filePath = $request->input('file_path');
        $format = $request->input('format');
        $tilesetMappingsJson = $request->input('tileset_mappings');
        $fieldTypeFilePath = $request->input('field_type_file_path');
        $tilesetImages = $request->file('tileset_images', []);

        // Decode tileset mappings
        $tilesetMappings = json_decode($tilesetMappingsJson, true);
        if (!is_array($tilesetMappings)) {
            return response()->json([
                'message' => 'Invalid tileset mappings format'
            ], 422);
        }

        // Verify main file exists
        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'message' => 'File not found'
            ], 422);
        }

        // Verify field type file exists if provided
        if ($fieldTypeFilePath && !Storage::disk('local')->exists($fieldTypeFilePath)) {
            return response()->json([
                'message' => 'Field type file not found'
            ], 422);
        }

        try {
            // If this is a JS file and we have a field type file, copy it to the expected location
            if ($format === 'js' && $fieldTypeFilePath) {
                $this->copyFieldTypeFile($filePath, $fieldTypeFilePath);
            }

            // 1. Save all uploaded tileset images to public/tilesets/ with their original filenames
            foreach ($tilesetImages as $tilesetKey => $imageFile) {
                if ($imageFile && $imageFile->isValid()) {
                    $filename = $imageFile->getClientOriginalName();
                    $path = 'tilesets/' . $filename;
                    Storage::disk('public')->put($path, file_get_contents($imageFile->getRealPath()));
                }
            }

            // 2. Create tilesets for 'create_new' mappings before import
            $wizardCreatedTilesets = [];
            foreach ($tilesetMappings as $tilesetName => $mapping) {
                if ($mapping === 'create_new') {
                    // Check if we have an uploaded image for this tileset
                    $hasImage = false;
                    foreach ($tilesetImages as $tilesetKey => $imageFile) {
                        if ($tilesetKey === $tilesetName && $imageFile && $imageFile->isValid()) {
                            $hasImage = true;
                            break;
                        }
                    }
                    
                    if ($hasImage) {
                        // Create a minimal tileset record so the import service can find it
                        $filename = $tilesetImages[$tilesetName]->getClientOriginalName();
                        $imagePath = 'tilesets/' . $filename;
                        
                        // Get image dimensions
                        $imageInfo = getimagesize(Storage::disk('public')->path($imagePath));
                        if ($imageInfo) {
                            $imageWidth = $imageInfo[0];
                            $imageHeight = $imageInfo[1];
                            $tileWidth = 32; // Default assumption
                            $tileHeight = 32; // Default assumption
                            $tilesPerRow = intval($imageWidth / $tileWidth);
                            $tileCount = $tilesPerRow * intval($imageHeight / $tileHeight);
                            
                            $tileset = TileSet::create([
                                'name' => $tilesetName,
                                'image_path' => $imagePath,
                                'image_width' => $imageWidth,
                                'image_height' => $imageHeight,
                                'tile_width' => $tileWidth,
                                'tile_height' => $tileHeight,
                                'tile_count' => $tileCount,
                                'first_gid' => 1,
                                'margin' => 0,
                                'spacing' => 0,
                            ]);
                            
                            $wizardCreatedTilesets[] = $tileset;
                        }
                    }
                }
            }

            // 3. Read the file content
            $fileContent = Storage::disk('local')->get($filePath);

            // 4. Use the standard MapImportService to handle the complete import
            $importResult = $this->importService->importFromString(
                $fileContent,
                $format,
                Auth::user(),
                ['auto_create_tilesets' => true]
            );

            $map = $importResult['map'];
            $tilesetResults = $importResult['tilesets'];

            // 5. Clean up the uploaded files
            Storage::disk('local')->delete($filePath);
            if ($fieldTypeFilePath) {
                Storage::disk('local')->delete($fieldTypeFilePath);
            }

            // Combine tilesets created by the wizard with those created by the import service
            $allCreatedTilesets = array_merge(
                $wizardCreatedTilesets,
                $tilesetResults['created'] ?? []
            );

            return response()->json([
                'message' => 'Map imported successfully',
                'map' => new TileMapResource($map),
                'created_tilesets' => collect($allCreatedTilesets)->map(fn($ts) => new TileSetResource($ts)),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Copy field type file to the expected location for JS imports.
     */
    private function copyFieldTypeFile(string $mainFilePath, string $fieldTypeFilePath): void
    {
        $mainFileDir = dirname($mainFilePath);
        $fieldTypeFileName = basename($fieldTypeFilePath);
        $targetPath = $mainFileDir . '/' . $fieldTypeFileName;
        
        if (!Storage::disk('local')->exists($targetPath)) {
            $fieldTypeContent = Storage::disk('local')->get($fieldTypeFilePath);
            Storage::disk('local')->put($targetPath, $fieldTypeContent);
        }
    }

    /**
     * Get suggested tilesets for each imported tileset based on name similarity.
     */
    private function getSuggestedTilesetsForWizard(array $tilesets): array
    {
        $suggestions = [];
        $existingTilesets = TileSet::all();

        foreach ($tilesets as $tileset) {
            $tilesetKey = $tileset['original_name'] ?? $tileset['name'];
            $suggestions[$tilesetKey] = [];

            foreach ($existingTilesets as $existingTileset) {
                $similarity = $this->calculateNameSimilarity(
                    $tileset['name'] ?? '',
                    $existingTileset->name
                );

                if ($similarity > 0.3) { // Only include if similarity is above 30%
                    $suggestions[$tilesetKey][] = [
                        'uuid' => $existingTileset->uuid,
                        'name' => $existingTileset->name,
                        'similarity' => $similarity
                    ];
                }
            }

            // Sort by similarity (highest first) and limit to top 5
            usort($suggestions[$tilesetKey], function ($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });
            $suggestions[$tilesetKey] = array_slice($suggestions[$tilesetKey], 0, 5);
        }

        return $suggestions;
    }

    /**
     * Calculate similarity between two strings using Levenshtein distance.
     */
    private function calculateNameSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1.0;
        }

        $maxLength = max(strlen($str1), strlen($str2));
        if ($maxLength === 0) {
            return 0.0;
        }

        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLength);
    }
} 