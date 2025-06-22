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
                        new OA\Property(property: 'map_name', type: 'string'),
                        new OA\Property(property: 'tileset_mappings', type: 'string'), // JSON string
                        new OA\Property(property: 'preserve_uuid', type: 'boolean'),
                        new OA\Property(property: 'field_type_file_path', type: 'string', nullable: true),
                        new OA\Property(property: 'tileset_images', type: 'array', items: new OA\Schema(type: 'string', format: 'binary')),
                    ],
                    required: ['file_path', 'format', 'map_name', 'tileset_mappings']
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
            'map_name' => 'required|string|max:255',
            'tileset_mappings' => 'required|string', // JSON string
            'preserve_uuid' => 'boolean',
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
        $mapName = $request->input('map_name');
        $tilesetMappingsJson = $request->input('tileset_mappings');
        $preserveUuid = $request->input('preserve_uuid', false);
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
            // Upload tileset images first
            $uploadedTilesetImages = $this->uploadTilesetImages($tilesetImages);

            // If this is a JS file and we have a field type file, copy it to the expected location
            if ($format === 'js' && $fieldTypeFilePath) {
                $this->copyFieldTypeFile($filePath, $fieldTypeFilePath);
            }

            // Parse the file using the service
            $result = $this->importService->parseFile($filePath, $format);
            $mapData = $result['data'];
            $detectedFormat = $result['format'];

            // Update map name
            $mapData['map']['name'] = $mapName;

            // Apply tileset mappings and uploaded images
            $this->applyTilesetMappings($mapData, $tilesetMappings, $uploadedTilesetImages);

            // Import the map
            $options = [
                'preserve_uuid' => $preserveUuid,
                'overwrite' => false,
                'auto_create_tilesets' => true, // Enable tileset creation for wizard
            ];

            $importResult = $this->importService->importFromString(
                json_encode($mapData),
                $detectedFormat,
                Auth::user(),
                $options
            );

            $map = $importResult['map'];
            $tilesetResults = $importResult['tilesets'];

            // Clean up the uploaded files
            Storage::disk('local')->delete($filePath);
            if ($fieldTypeFilePath) {
                Storage::disk('local')->delete($fieldTypeFilePath);
            }

            return response()->json([
                'message' => 'Map imported successfully',
                'map' => new TileMapResource($map),
                'created_tilesets' => collect($tilesetResults['created'] ?? [])->map(fn($ts) => new TileSetResource($ts)),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get suggested tilesets for wizard tileset data.
     */
    private function getSuggestedTilesetsForWizard(array $wizardTilesets): array
    {
        $suggestions = [];
        
        foreach ($wizardTilesets as $wizardTileset) {
            if (!$wizardTileset['requires_upload']) {
                continue; // Skip tilesets that don't need upload
            }
            
            $originalName = $wizardTileset['original_name'];
            $formattedName = $wizardTileset['formatted_name'];
            
            // Get all existing tilesets for comparison
            $existingTilesets = TileSet::all();
            $similarTilesets = [];
            
            foreach ($existingTilesets as $existingTileset) {
                $similarity = max(
                    $this->calculateNameSimilarity($originalName, $existingTileset->name),
                    $this->calculateNameSimilarity($formattedName, $existingTileset->name)
                );
                
                if ($similarity > 0.3) { // Lower threshold for wizard suggestions
                    $similarTilesets[] = [
                        'uuid' => $existingTileset->uuid,
                        'name' => $existingTileset->name,
                        'similarity' => $similarity,
                        'image_path' => $existingTileset->image_path,
                    ];
                }
            }
            
            // Sort by similarity (highest first)
            usort($similarTilesets, function ($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });
            
            // Take top 3 suggestions
            $suggestions[$originalName] = array_slice($similarTilesets, 0, 3);
        }
        
        return $suggestions;
    }

    /**
     * Calculate similarity between two strings using various methods
     */
    private function calculateNameSimilarity(string $str1, string $str2): float
    {
        // Remove common words and normalize
        $commonWords = ['tileset', 'tile', 'set', 'map', 'the', 'a', 'an', 'and', 'or', 'but'];
        $str1 = str_replace($commonWords, '', $str1);
        $str2 = str_replace($commonWords, '', $str2);
        
        $str1 = trim(preg_replace('/\s+/', ' ', $str1));
        $str2 = trim(preg_replace('/\s+/', ' ', $str2));

        if (empty($str1) || empty($str2)) {
            return 0.0;
        }

        // Use multiple similarity metrics
        $levenshtein = 1 - (levenshtein($str1, $str2) / max(strlen($str1), strlen($str2)));
        $similarText = similar_text($str1, $str2, $percent) / 100;
        
        // Check if one contains the other
        $contains = (strpos($str1, $str2) !== false || strpos($str2, $str1) !== false) ? 0.8 : 0.0;
        
        // Average the scores
        return ($levenshtein + $similarText + $contains) / 3;
    }

    /**
     * Apply tileset mappings to the map data
     */
    private function applyTilesetMappings(array &$mapData, array $tilesetMappings, array $uploadedImages = []): void
    {
        // Create a mapping from imported original_name to target UUID
        $uuidMapping = [];
        foreach ($tilesetMappings as $originalName => $targetUuid) {
            // Find the tileset by original_name to get its UUID
            $importedTileset = null;
            foreach ($mapData['tilesets'] as $tileset) {
                if ($tileset['original_name'] === $originalName) {
                    $importedTileset = $tileset;
                    break;
                }
            }
            
            if ($importedTileset) {
                $importedUuid = $importedTileset['uuid'];
                if ($targetUuid === 'create_new') {
                    // Generate a new UUID for tilesets that should be created
                    $uuidMapping[$importedUuid] = (string) \Illuminate\Support\Str::uuid();
                } else {
                    $uuidMapping[$importedUuid] = $targetUuid;
                }
            }
        }

        // Update tileset UUIDs in the map data
        foreach ($mapData['tilesets'] as &$tileset) {
            if (isset($uuidMapping[$tileset['uuid']])) {
                $originalUuid = $tileset['uuid'];
                $tileset['uuid'] = $uuidMapping[$tileset['uuid']];
                // Only mark as existing if it's not a 'create_new' mapping
                $originalName = $tileset['original_name'];
                if ($tilesetMappings[$originalName] !== 'create_new') {
                    $tileset['_existing'] = true;
                }
            }
        }

        // Update brush tileset references in layers
        foreach ($mapData['layers'] as &$layer) {
            if (isset($layer['data']) && is_array($layer['data'])) {
                foreach ($layer['data'] as &$tile) {
                    if (isset($tile['brush']['tileset']) && isset($uuidMapping[$tile['brush']['tileset']])) {
                        $tile['brush']['tileset'] = $uuidMapping[$tile['brush']['tileset']];
                    }
                }
            }
        }

        // Apply uploaded images to tilesets that need them
        foreach ($uploadedImages as $image) {
            $tilesetKey = $image['tileset_key'];
            $imagePath = $image['image_path'];
            
            // Find the tileset by original name and update its image path
            foreach ($mapData['tilesets'] as &$tileset) {
                if ($tileset['original_name'] === $tilesetKey) {
                    $tileset['image_path'] = $imagePath;
                    break;
                }
            }
        }
    }

    /**
     * Copy field type file to the location expected by the LaxLegacyImporter
     */
    private function copyFieldTypeFile(string $mainFilePath, string $fieldTypeFilePath): void
    {
        // Get the directory and filename of the main file
        $pathInfo = pathinfo($mainFilePath);
        $expectedFieldTypePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_ft.js';
        
        // Copy the field type file to the expected location
        $fieldTypeContent = Storage::disk('local')->get($fieldTypeFilePath);
        Storage::disk('local')->put($expectedFieldTypePath, $fieldTypeContent);
    }

    /**
     * Get suggested tilesets for each imported tileset.
     */
    private function getSuggestedTilesets(array $importedTilesets): array
    {
        $suggestions = [];
        $allTilesets = TileSet::all();

        foreach ($importedTilesets as $importedTileset) {
            $importedName = strtolower($importedTileset['name'] ?? '');
            $suggestions[$importedTileset['uuid']] = [];
            
            foreach ($allTilesets as $existingTileset) {
                $existingName = strtolower($existingTileset->name);
                
                // Calculate similarity score
                $similarity = $this->calculateNameSimilarity($importedName, $existingName);
                
                if ($similarity > 0.3) { // Threshold for suggestions
                    $suggestions[$importedTileset['uuid']][] = [
                        'uuid' => $existingTileset->uuid,
                        'name' => $existingTileset->name,
                        'similarity' => $similarity,
                    ];
                }
            }
            
            // Sort by similarity (highest first)
            usort($suggestions[$importedTileset['uuid']], function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });
            
            // Limit to top 5 suggestions
            $suggestions[$importedTileset['uuid']] = array_slice($suggestions[$importedTileset['uuid']], 0, 5);
        }
        
        return $suggestions;
    }

    /**
     * Upload tileset images and return their paths
     */
    private function uploadTilesetImages(array $images): array
    {
        $uploadedImages = [];
        
        foreach ($images as $tilesetKey => $image) {
            if (!$image || !$image->isValid()) {
                continue;
            }
            
            $fileName = $image->getClientOriginalName();
            $extension = $image->getClientOriginalExtension();
            
            // Generate a unique filename while preserving the original extension
            $uniqueName = uniqid() . '.' . $extension;
            $filePath = 'imports/tilesets/' . $uniqueName;
            
            // Store the file with the custom path
            Storage::disk('local')->put($filePath, file_get_contents($image->getRealPath()));

            $uploadedImages[] = [
                'tileset_key' => $tilesetKey,
                'image_path' => $filePath,
                'file_name' => $fileName,
                'extension' => $extension
            ];
        }
        
        return $uploadedImages;
    }
} 