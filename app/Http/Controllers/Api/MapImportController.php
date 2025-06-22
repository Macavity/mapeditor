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
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ],
                    required: ['file']
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'File uploaded successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'file_path', type: 'string'),
                    new OA\Property(property: 'file_name', type: 'string'),
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
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        // Custom validation for file extensions
        $validator->after(function ($validator) use ($request) {
            $file = $request->file('file');
            if ($file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $allowedExtensions = ['json', 'tmx', 'js'];
                
                if (!in_array($extension, $allowedExtensions)) {
                    $validator->errors()->add('file', 'File must be one of: ' . implode(', ', $allowedExtensions));
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->store('imports', 'local');

        return response()->json([
            'message' => 'File uploaded successfully',
            'file_path' => $filePath,
            'file_name' => $fileName,
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $filePath = $request->input('file_path');
        $format = $request->input('format');

        // Verify file exists
        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'message' => 'File not found'
            ], 422);
        }

        try {
            // Parse the file using the service
            $result = $this->importService->parseFile($filePath, $format);
            $mapData = $result['data'];
            $detectedFormat = $result['format'];

            // Get suggested tilesets for each imported tileset
            $suggestedTilesets = $this->getSuggestedTilesets($mapData['tilesets'] ?? []);

            return response()->json([
                'map_info' => $mapData['map'],
                'layers' => $mapData['layers'],
                'tilesets' => $mapData['tilesets'],
                'detected_format' => $detectedFormat,
                'suggested_tilesets' => $suggestedTilesets,
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
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'file_path', type: 'string'),
                new OA\Property(property: 'format', type: 'string'),
                new OA\Property(property: 'map_name', type: 'string'),
                new OA\Property(property: 'tileset_mappings', type: 'object'),
                new OA\Property(property: 'preserve_uuid', type: 'boolean'),
            ], required: ['file_path', 'format', 'map_name', 'tileset_mappings'])
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
            'tileset_mappings' => 'required|array',
            'tileset_mappings.*' => 'required|string|uuid',
            'preserve_uuid' => 'boolean',
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
        $tilesetMappings = $request->input('tileset_mappings');
        $preserveUuid = $request->input('preserve_uuid', false);

        // Verify file exists
        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'message' => 'File not found'
            ], 422);
        }

        try {
            // Parse the file using the service
            $result = $this->importService->parseFile($filePath, $format);
            $mapData = $result['data'];
            $detectedFormat = $result['format'];

            // Update map name
            $mapData['map']['name'] = $mapName;

            // Apply tileset mappings
            $this->applyTilesetMappings($mapData, $tilesetMappings);

            // Import the map
            $options = [
                'preserve_uuid' => $preserveUuid,
                'overwrite' => false,
                'auto_create_tilesets' => false, // We handle this manually in the wizard
            ];

            $importResult = $this->importService->importFromString(
                json_encode($mapData),
                $detectedFormat,
                Auth::user(),
                $options
            );

            $map = $importResult['map'];
            $tilesetResults = $importResult['tilesets'];

            // Clean up the uploaded file
            Storage::disk('local')->delete($filePath);

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
     * Get suggested tilesets for imported tilesets based on name similarity
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
    private function applyTilesetMappings(array &$mapData, array $tilesetMappings): void
    {
        // Create a mapping from imported UUID to target UUID
        $uuidMapping = [];
        foreach ($tilesetMappings as $importedUuid => $targetUuid) {
            if ($targetUuid !== 'create_new') {
                $uuidMapping[$importedUuid] = $targetUuid;
            }
        }

        // Update tileset UUIDs in the map data
        foreach ($mapData['tilesets'] as &$tileset) {
            if (isset($uuidMapping[$tileset['uuid']])) {
                $tileset['uuid'] = $uuidMapping[$tileset['uuid']];
                $tileset['_existing'] = true; // Mark as existing
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
    }
} 