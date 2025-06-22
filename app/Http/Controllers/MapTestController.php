<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TileMap;
use App\Services\MapDisplayService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapTestController extends Controller
{
    public function __construct(
        private readonly MapDisplayService $mapDisplayService
    ) {}

    public function show(string $uuid): Response
    {
        $tileMap = TileMap::where('uuid', $uuid)
            ->with(['layers' => function ($query) {
                $query->where(function ($q) {
                    $q->where('visible', true)
                      ->orWhere('type', 'field_type'); // Always include field type layers for collision detection
                })
                ->orderBy('z', 'asc');
            }])
            ->firstOrFail();

        // Get all visible layers
        $layers = $tileMap->layers->toArray();

        // Find player object in object layers
        $playerPosition = $this->findPlayerObjectPosition($layers, $tileMap);

        // Determine the highest floor layer z-index and lowest sky layer z-index
        $highestFloorZ = 0;
        $lowestSkyZ = PHP_INT_MAX;
        $hasFloorLayers = false;
        $hasSkyLayers = false;

        foreach ($layers as $layer) {
            if ($layer['type'] === 'floor') {
                $hasFloorLayers = true;
                $highestFloorZ = max($highestFloorZ, $layer['z']);
            } elseif ($layer['type'] === 'sky') {
                $hasSkyLayers = true;
                $lowestSkyZ = min($lowestSkyZ, $layer['z']);
            }
        }

        // Adjust z-indices and insert player layer
        $adjustedLayers = [];
        $playerLayerInserted = false;

        foreach ($layers as $layer) {
            $adjustedLayer = $layer;

            // If we have both floor and sky layers, adjust sky layers and insert player
            if ($hasFloorLayers && $hasSkyLayers) {
                if ($layer['type'] === 'sky') {
                    // Increase sky layer z-index by 1
                    $adjustedLayer['z'] = $layer['z'] + 1;
                }

                // Insert player layer after the highest floor layer
                if (!$playerLayerInserted && $layer['type'] === 'floor' && $layer['z'] === $highestFloorZ) {
                    $adjustedLayers[] = $adjustedLayer;
                    
                    // Insert player layer
                    $adjustedLayers[] = [
                        'id' => 'player-layer',
                        'uuid' => 'player-layer',
                        'name' => 'Player Layer',
                        'type' => 'object',
                        'z' => $highestFloorZ + 1,
                        'visible' => true,
                        'opacity' => 1.0,
                        'data' => [],
                        'x' => 0,
                        'y' => 0,
                        'width' => $tileMap->width,
                        'height' => $tileMap->height,
                        'tile_map_id' => $tileMap->id,
                        'created_at' => null,
                        'updated_at' => null,
                    ];
                    
                    $playerLayerInserted = true;
                    continue;
                }
            } elseif ($hasFloorLayers && !$hasSkyLayers) {
                // Only floor layers exist, insert player layer after the highest floor
                if (!$playerLayerInserted && $layer['type'] === 'floor' && $layer['z'] === $highestFloorZ) {
                    $adjustedLayers[] = $adjustedLayer;
                    
                    // Insert player layer
                    $adjustedLayers[] = [
                        'id' => 'player-layer',
                        'uuid' => 'player-layer',
                        'name' => 'Player Layer',
                        'type' => 'object',
                        'z' => $highestFloorZ + 1,
                        'visible' => true,
                        'opacity' => 1.0,
                        'data' => [],
                        'x' => 0,
                        'y' => 0,
                        'width' => $tileMap->width,
                        'height' => $tileMap->height,
                        'tile_map_id' => $tileMap->id,
                        'created_at' => null,
                        'updated_at' => null,
                    ];
                    
                    $playerLayerInserted = true;
                    continue;
                }
            } elseif (!$hasFloorLayers && $hasSkyLayers) {
                // Only sky layers exist, insert player layer before the lowest sky layer
                if (!$playerLayerInserted && $layer['type'] === 'sky' && $layer['z'] === $lowestSkyZ) {
                    // Insert player layer first
                    $adjustedLayers[] = [
                        'id' => 'player-layer',
                        'uuid' => 'player-layer',
                        'name' => 'Player Layer',
                        'type' => 'object',
                        'z' => $lowestSkyZ,
                        'visible' => true,
                        'opacity' => 1.0,
                        'data' => [],
                        'x' => 0,
                        'y' => 0,
                        'width' => $tileMap->width,
                        'height' => $tileMap->height,
                        'tile_map_id' => $tileMap->id,
                        'created_at' => null,
                        'updated_at' => null,
                    ];
                    
                    // Increase sky layer z-index by 1
                    $adjustedLayer['z'] = $layer['z'] + 1;
                    $playerLayerInserted = true;
                }
            } else {
                // No floor or sky layers, insert player layer at z=0
                if (!$playerLayerInserted) {
                    $adjustedLayers[] = [
                        'id' => 'player-layer',
                        'uuid' => 'player-layer',
                        'name' => 'Player Layer',
                        'type' => 'object',
                        'z' => 0,
                        'visible' => true,
                        'opacity' => 1.0,
                        'data' => [],
                        'x' => 0,
                        'y' => 0,
                        'width' => $tileMap->width,
                        'height' => $tileMap->height,
                        'tile_map_id' => $tileMap->id,
                        'created_at' => null,
                        'updated_at' => null,
                    ];
                    $playerLayerInserted = true;
                }
            }

            $adjustedLayers[] = $adjustedLayer;
        }

        // If no player layer was inserted yet, add it at the end
        if (!$playerLayerInserted) {
            $maxZ = 0;
            foreach ($adjustedLayers as $layer) {
                $maxZ = max($maxZ, $layer['z']);
            }
            
            $adjustedLayers[] = [
                'id' => 'player-layer',
                'uuid' => 'player-layer',
                'name' => 'Player Layer',
                'type' => 'object',
                'z' => $maxZ + 1,
                'visible' => true,
                'opacity' => 1.0,
                'data' => [],
                'x' => 0,
                'y' => 0,
                'width' => $tileMap->width,
                'height' => $tileMap->height,
                'tile_map_id' => $tileMap->id,
                'created_at' => null,
                'updated_at' => null,
            ];
        }

        // Sort layers by z-index
        usort($adjustedLayers, function ($a, $b) {
            return $a['z'] <=> $b['z'];
        });

        return Inertia::render('maps.test', [
            'map' => [
                'uuid' => $tileMap->uuid,
                'name' => $tileMap->name,
                'width' => $tileMap->width,
                'height' => $tileMap->height,
                'tile_width' => $tileMap->tile_width,
                'tile_height' => $tileMap->tile_height,
            ],
            'layers' => $adjustedLayers,
            'playerPosition' => $playerPosition,
        ]);
    }

    /**
     * Find the player object position in object layers
     * 
     * This method looks for objects with type 'player' in object layers.
     * The player object type is identified by having the 'type' field set to 'player'
     * in the object_types table. If no player object is found, it falls back to
     * the center position of the map.
     * 
     * @param array $layers Array of layer data
     * @param TileMap $tileMap The tile map instance
     * @return array Player position with x and y coordinates
     */
    private function findPlayerObjectPosition(array $layers, TileMap $tileMap): array
    {
        // Calculate center tile position as fallback
        $centerTileX = (int) floor($tileMap->width / 2);
        $centerTileY = (int) floor($tileMap->height / 2);

        // Get object types to find the player object type ID
        $playerObjectType = \App\Models\ObjectType::where('type', 'player')->first();
        
        if (!$playerObjectType) {
            // Fallback to center position if no player object type found
            return [
                'x' => $centerTileX,
                'y' => $centerTileY,
            ];
        }

        // Look for object layers with player objects
        foreach ($layers as $layer) {
            if ($layer['type'] === 'object' && isset($layer['data']) && is_array($layer['data'])) {
                foreach ($layer['data'] as $object) {
                    // Check if this is a player object by matching the object type ID
                    if (isset($object['objectType']) && $object['objectType'] === $playerObjectType->id) {
                        return [
                            'x' => (int) $object['x'],
                            'y' => (int) $object['y'],
                        ];
                    }
                }
            }
        }

        // Fallback to center position if no player object found
        return [
            'x' => $centerTileX,
            'y' => $centerTileY,
        ];
    }
}
