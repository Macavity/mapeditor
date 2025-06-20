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
                $query->where('visible', true)
                    ->orderBy('z', 'asc');
            }])
            ->firstOrFail();

        // Get all visible layers
        $layers = $tileMap->layers->toArray();

        // Calculate center tile position for player
        $centerTileX = (int) floor($tileMap->width / 2);
        $centerTileY = (int) floor($tileMap->height / 2);

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
                        'type' => 'player',
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
                        'type' => 'player',
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
                        'type' => 'player',
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
                        'type' => 'player',
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
                'type' => 'player',
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

        return Inertia::render('MapTest', [
            'map' => [
                'uuid' => $tileMap->uuid,
                'name' => $tileMap->name,
                'width' => $tileMap->width,
                'height' => $tileMap->height,
                'tile_width' => $tileMap->tile_width,
                'tile_height' => $tileMap->tile_height,
            ],
            'layers' => $adjustedLayers,
            'playerPosition' => [
                'x' => $centerTileX,
                'y' => $centerTileY,
            ],
        ]);
    }
}
