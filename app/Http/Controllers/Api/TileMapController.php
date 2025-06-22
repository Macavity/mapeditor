<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LayerResource;
use App\Http\Resources\TileMapResource;
use App\Models\Layer;
use App\Models\TileMap;
use App\Enums\LayerType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Tile Map API',
    description: 'API for managing tile maps and their layers'
)]
#[OA\Server(
    url: '/api',
    description: 'API Base URL'
)]
class TileMapController extends Controller
{
    /**
     * Layer type configurations for creation
     */
    private const LAYER_CONFIGS = [
        LayerType::Sky->value => [
            'name_prefix' => 'Sky Layer',
            'z_index_strategy' => 'top', // Always on top
            'affected_types' => [], // No other layers affected
        ],
        LayerType::Floor->value => [
            'name_prefix' => 'Floor Layer',
            'z_index_strategy' => 'above_types', // Above specific types
            'affected_types' => [LayerType::Background->value, LayerType::Floor->value],
            'shift_types' => [LayerType::Sky->value], // Shift sky layers up
        ],
        LayerType::Object->value => [
            'name_prefix' => 'Object Layer',
            'z_index_strategy' => 'above_floor', // Above floor layers specifically
            'affected_types' => [LayerType::Floor->value], // Only consider floor layers
            'shift_types' => [LayerType::Sky->value], // Shift sky layers up
        ],
        LayerType::FieldType->value => [
            'name_prefix' => 'Field Type Layer',
            'z_index_strategy' => 'top', // Always on top of all other layers
            'affected_types' => [], // No other layers affected
        ],
    ];

    /**
     * Validation rules for layer creation
     */
    private const LAYER_VALIDATION_RULES = [
        'name' => 'sometimes|string|max:255',
        'x' => 'sometimes|integer',
        'y' => 'sometimes|integer',
        'visible' => 'sometimes|boolean',
        'opacity' => 'sometimes|numeric|min:0|max:1',
    ];

    /**
     * Display a listing of tile maps.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: '/tile-maps',
        summary: 'Get all tile maps',
        tags: ['Tile Maps'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of tile maps',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/TileMap')
                )
            )
        ]
    )]
    public function index(): JsonResponse
    {
        $tileMaps = TileMap::with(['creator', 'layers'])
            ->orderBy('created_at', 'desc')
            ->get();

        return TileMapResource::collection($tileMaps)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Store a newly created tile map in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: '/tile-maps',
        summary: 'Create a new tile map',
        tags: ['Tile Maps'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TileMapStoreRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tile map created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/TileMap')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                    new OA\Property(property: 'errors', type: 'object')
                ])
            )
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
            'tile_width' => 'required|integer|min:1',
            'tile_height' => 'required|integer|min:1',
        ]);

        $tileMap = new TileMap($validated);
        $tileMap->creator()->associate(Auth::user());
        $tileMap->save();

        return (new TileMapResource($tileMap->load(['creator', 'layers'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified tile map.
     *
     * @param  \App\Models\TileMap  $tileMap
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: '/tile-maps/{tileMap}',
        summary: 'Get a specific tile map',
        tags: ['Tile Maps'],
        parameters: [
            new OA\Parameter(
                name: 'tileMap',
                in: 'path',
                required: true,
                description: 'ID of the tile map',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tile map details',
                content: new OA\JsonContent(ref: '#/components/schemas/TileMap')
            ),
            new OA\Response(
                response: 404,
                description: 'Tile map not found',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'No query results for model [App\\Models\\TileMap] 1')
                ])
            )
        ]
    )]
    public function show(TileMap $tileMap): JsonResponse
    {
        $usedTilesets = collect([]);
        foreach ($tileMap->layers as $layer) {
            if (is_a($layer, MapLayer::class)) {
                foreach ($layer->objects as $object) {
                    $tileIndex = floor(round($object->y / $this->tileSize[1]) * $this->numberOfTilesX + round($object->x / $this->tileSize[0]) % $this->numberOfTilesX);

                    if ($mapTiles[$tileIndex]['id'] && !$usedTilesets->has($mapTiles[$tileIndex]['tileset_id'])) {
                        $usedTilesets = $usedTilesets->push([$mapTiles[$tileIndex]['tileset_id']]);
                    }
                }
            }
        }

        return (new TileMapResource($tileMap->load(['creator'])))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update the specified tile map in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TileMap  $tileMap
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Put(
        path: '/tile-maps/{tileMap}',
        summary: 'Update a tile map',
        tags: ['Tile Maps'],
        parameters: [
            new OA\Parameter(
                name: 'tileMap',
                in: 'path',
                required: true,
                description: 'ID of the tile map',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TileMapUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tile map updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/TileMap')
            ),
            new OA\Response(
                response: 404,
                description: 'Tile map not found',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'No query results for model [App\\Models\\TileMap] 1')
                ])
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                    new OA\Property(property: 'errors', type: 'object')
                ])
            )
        ]
    )]
    public function update(Request $request, TileMap $tileMap): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'width' => 'sometimes|integer|min:1',
            'height' => 'sometimes|integer|min:1',
            'tile_width' => 'sometimes|integer|min:1',
            'tile_height' => 'sometimes|integer|min:1',
        ]);

        $tileMap->update($validated);

        return (new TileMapResource($tileMap->load(['creator', 'layers'])))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TileMap $tileMap): JsonResponse
    {
        $tileMap->delete();

        return response()->json(null, 204);
    }

    /**
     * Get layers for a specific tile map.
     */
    public function layers(TileMap $tileMap): JsonResponse
    {
        $layers = $tileMap->layers()->orderBy('z')->get();

        // If no layers exist, create a default background layer
        if ($layers->isEmpty()) {
            $backgroundLayer = Layer::create([
                'tile_map_id' => $tileMap->id,
                'name' => 'Background',
                'type' => LayerType::Background,
                'width' => $tileMap->width,
                'height' => $tileMap->height,
                'x' => 0,
                'y' => 0,
                'z' => 0, // Background layers always start at z=0
                'data' => [],
                'visible' => true,
                'opacity' => 1.0,
            ]);

            $layers = collect([$backgroundLayer]);
        }

        return LayerResource::collection($layers)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update multiple layers for a specific tile map.
     */
    public function updateLayers(Request $request, TileMap $tileMap): JsonResponse
    {
        $validated = $request->validate([
            'layers' => 'required|array',
            'layers.*.uuid' => 'required|string|exists:layers,uuid',
            'layers.*.data' => 'sometimes|array', // Optional field, empty arrays allowed
            'layers.*.visible' => 'sometimes|boolean',
            'layers.*.opacity' => 'sometimes|numeric|min:0|max:1',
            'layers.*.z' => 'sometimes|integer|min:0',
        ]);

        $updatedLayers = DB::transaction(function () use ($validated, $tileMap) {
            $layers = [];
            
            // Get the highest z-index of non-field type layers for validation
            $maxNonFieldTypeZ = $tileMap->layers()
                ->where('type', '!=', LayerType::FieldType->value)
                ->max('z') ?? -1;
            
            foreach ($validated['layers'] as $layerData) {
                $layer = $tileMap->layers()->where('uuid', $layerData['uuid'])->first();
                
                if (!$layer) {
                    throw new \Exception("Layer with UUID {$layerData['uuid']} not found or does not belong to this map");
                }
                
                $updateData = [];
                
                // Only update fields if provided
                if (isset($layerData['data'])) {
                    $updateData['data'] = $layerData['data'];
                }
                if (isset($layerData['visible'])) {
                    $updateData['visible'] = $layerData['visible'];
                }
                if (isset($layerData['opacity'])) {
                    $updateData['opacity'] = $layerData['opacity'];
                }
                if (isset($layerData['z'])) {
                    // Ensure field type layers stay above all other layers
                    if ($layer->type === LayerType::FieldType) {
                        $updateData['z'] = max($layerData['z'], $maxNonFieldTypeZ + 1);
                    } else {
                        $updateData['z'] = $layerData['z'];
                    }
                }
                
                // Only update if there are fields to update
                if (!empty($updateData)) {
                    $layer->update($updateData);
                }
                
                $layers[] = $layer->fresh();
            }
            
            return $layers;
        });

        return LayerResource::collection(collect($updatedLayers))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update a single layer.
     */
    public function updateLayer(Request $request, TileMap $tileMap, Layer $layer): JsonResponse
    {
        // Ensure the layer belongs to the tile map
        if ($layer->tile_map_id !== $tileMap->id) {
            return response()->json(['error' => 'Layer does not belong to this tile map'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'data' => 'sometimes|array',
            'visible' => 'sometimes|boolean',
            'opacity' => 'sometimes|numeric|min:0|max:1',
            'z' => 'sometimes|integer|min:0',
            'x' => 'sometimes|integer',
            'y' => 'sometimes|integer',
            'width' => 'sometimes|integer|min:1',
            'height' => 'sometimes|integer|min:1',
        ]);

        // Ensure field type layers stay above all other layers
        if (isset($validated['z']) && $layer->type === LayerType::FieldType) {
            $maxNonFieldTypeZ = $tileMap->layers()
                ->where('type', '!=', LayerType::FieldType->value)
                ->max('z') ?? -1;
            $validated['z'] = max($validated['z'], $maxNonFieldTypeZ + 1);
        }

        $layer->update($validated);

        return (new LayerResource($layer->fresh()))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update only the data of a specific layer.
     */
    public function updateLayerData(Request $request, TileMap $tileMap, Layer $layer): JsonResponse
    {
        // Ensure the layer belongs to the tile map
        if ($layer->tile_map_id !== $tileMap->id) {
            return response()->json(['error' => 'Layer does not belong to this tile map'], 404);
        }

        $validated = $request->validate([
            'data' => 'required|array',
        ]);

        // Only validate data structure if array is not empty
        if (!empty($validated['data'])) {
            if ($layer->type === LayerType::FieldType) {
                // Validate field type data
                $fieldTypeValidation = $request->validate([
                    'data.*.x' => 'required|integer|min:0',
                    'data.*.y' => 'required|integer|min:0',
                    'data.*.fieldType' => 'required|integer|min:0',
                ]);
                $validated = array_merge($validated, $fieldTypeValidation);
            } elseif ($layer->type === LayerType::Object) {
                // Validate object data
                $objectValidation = $request->validate([
                    'data.*.x' => 'required|integer|min:0',
                    'data.*.y' => 'required|integer|min:0',
                    'data.*.objectType' => 'required|integer|min:0',
                ]);
                $validated = array_merge($validated, $objectValidation);
            } else {
                // Validate tile data
                $tileValidation = $request->validate([
                    'data.*.x' => 'required|integer|min:0',
                    'data.*.y' => 'required|integer|min:0',
                    'data.*.brush' => 'required|array',
                    'data.*.brush.tileset' => 'required|string',
                    'data.*.brush.tileX' => 'required|integer|min:0',
                    'data.*.brush.tileY' => 'required|integer|min:0',
                ]);
                $validated = array_merge($validated, $tileValidation);
            }
        }

        $layer->update(['data' => $validated['data']]);

        return (new LayerResource($layer->fresh()))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Create a new Sky layer for the tile map.
     */
    public function createSkyLayer(Request $request, TileMap $tileMap): JsonResponse
    {
        return $this->createLayer($request, $tileMap, LayerType::Sky);
    }

    /**
     * Create a new Floor layer for the tile map.
     */
    public function createFloorLayer(Request $request, TileMap $tileMap): JsonResponse
    {
        return $this->createLayer($request, $tileMap, LayerType::Floor);
    }

    /**
     * Create a new Object layer for the tile map.
     */
    public function createObjectLayer(Request $request, TileMap $tileMap): JsonResponse
    {
        return $this->createLayer($request, $tileMap, LayerType::Object);
    }

    /**
     * Create a new Field Type layer for the tile map.
     */
    public function createFieldTypeLayer(Request $request, TileMap $tileMap): JsonResponse
    {
        // Check if a FieldType layer already exists
        $existingFieldTypeLayer = $tileMap->layers()->where('type', LayerType::FieldType)->first();
        
        if ($existingFieldTypeLayer) {
            return response()->json([
                'error' => 'A Field Type layer already exists for this map. Only one Field Type layer is allowed per map.'
            ], 422);
        }

        return $this->createLayer($request, $tileMap, LayerType::FieldType);
    }

    /**
     * Generic layer creation method
     */
    private function createLayer(Request $request, TileMap $tileMap, LayerType $layerType): JsonResponse
    {
        $validated = $request->validate(self::LAYER_VALIDATION_RULES);
        $config = self::LAYER_CONFIGS[$layerType->value];
        
        $layerCount = $tileMap->layers()->where('type', $layerType)->count();

        return DB::transaction(function () use ($validated, $tileMap, $layerType, $config, $layerCount) {
            // Calculate z-index based on strategy
            $newZ = $this->calculateZIndex($tileMap, $config);
            
            // Shift other layers if needed
            if (isset($config['shift_types']) && !empty($config['shift_types'])) {
                $this->shiftLayers($tileMap, $config['shift_types']);
            }

            $layer = Layer::create([
                'tile_map_id' => $tileMap->id,
                'name' => $validated['name'] ?? $config['name_prefix'] . ' ' . ($layerCount + 1),
                'type' => $layerType,
                'width' => $tileMap->width,
                'height' => $tileMap->height,
                'x' => $validated['x'] ?? 0,
                'y' => $validated['y'] ?? 0,
                'z' => $newZ,
                'data' => [],
                'visible' => $validated['visible'] ?? true,
                'opacity' => $validated['opacity'] ?? 1.0,
            ]);

            return (new LayerResource($layer))
                ->response()
                ->setStatusCode(201);
        });
    }

    /**
     * Calculate z-index for new layer based on configuration
     */
    private function calculateZIndex(TileMap $tileMap, array $config): int
    {
        if ($config['z_index_strategy'] === 'top') {
            // Always on top of all other layers
            return ($tileMap->layers()->max('z') ?? -1) + 1;
        }
        
        if ($config['z_index_strategy'] === 'above_types') {
            // Above specific layer types
            $maxZ = $tileMap->layers()
                ->whereIn('type', $config['affected_types'])
                ->max('z') ?? -1;
            return $maxZ + 1;
        }
        
        if ($config['z_index_strategy'] === 'above_floor') {
            // Above floor layers specifically
            $maxFloorZ = $tileMap->layers()
                ->where('type', LayerType::Floor)
                ->max('z') ?? -1;
            return $maxFloorZ + 1;
        }
        
        // Default fallback
        return ($tileMap->layers()->max('z') ?? -1) + 1;
    }

    /**
     * Shift layers of specified types up by 1
     */
    private function shiftLayers(TileMap $tileMap, array $typesToShift): void
    {
        $tileMap->layers()
            ->whereIn('type', $typesToShift)
            ->increment('z', 1);
    }

    /**
     * Get layer count by type for a specific tile map.
     */
    public function getLayerCounts(TileMap $tileMap): JsonResponse
    {
        $counts = [
            'background' => $tileMap->layers()->where('type', LayerType::Background)->count(),
            'floor' => $tileMap->layers()->where('type', LayerType::Floor)->count(),
            'sky' => $tileMap->layers()->where('type', LayerType::Sky)->count(),
            'field_type' => $tileMap->layers()->where('type', LayerType::FieldType)->count(),
            'object' => $tileMap->layers()->where('type', LayerType::Object)->count(),
        ];

        return response()->json([
            'counts' => $counts,
        ]);
    }

    /**
     * Delete a layer from the tile map.
     */
    public function deleteLayer(TileMap $tileMap, Layer $layer): JsonResponse
    {
        // Ensure the layer belongs to the tile map
        if ($layer->tile_map_id !== $tileMap->id) {
            return response()->json(['error' => 'Layer does not belong to this tile map'], 404);
        }

        // Prevent deletion of background layers if it's the only one
        if ($layer->type === LayerType::Background) {
            $backgroundCount = $tileMap->layers()->where('type', LayerType::Background)->count();
            if ($backgroundCount <= 1) {
                return response()->json([
                    'error' => 'Cannot delete the last background layer'
                ], 422);
            }
        }

        return DB::transaction(function () use ($tileMap, $layer) {
            $deletedZ = $layer->z;
            
            // Delete the layer
            $layer->delete();

            // Reorder z-indices: decrement all layers with z > deletedZ
            $tileMap->layers()
                ->where('z', '>', $deletedZ)
                ->decrement('z', 1);

            return response()->json(null, 204);
        });
    }
}
