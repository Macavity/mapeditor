<?php

declare(strict_types=1);

namespace App\Casts;

use App\Enums\LayerType;
use App\ValueObjects\Tile;
use App\ValueObjects\Brush;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class LayerDataCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        $data = json_decode($value, true) ?? [];
        
        // Handle field type layers differently
        if (isset($attributes['type']) && $attributes['type'] === LayerType::FieldType->value) {
            return array_map(function ($fieldTypeTile) {
                // Skip invalid field type tiles
                if (!is_array($fieldTypeTile) || !isset($fieldTypeTile['x'], $fieldTypeTile['y'], $fieldTypeTile['fieldType'])) {
                    return null;
                }
                
                return [
                    'x' => (int) $fieldTypeTile['x'],
                    'y' => (int) $fieldTypeTile['y'],
                    'fieldType' => (int) $fieldTypeTile['fieldType'],
                ];
            }, array_filter($data)); // Remove null values
        }
        
        // Handle object layers
        if (isset($attributes['type']) && $attributes['type'] === LayerType::Object->value) {
            return array_map(function ($objectTile) {
                // Skip invalid object tiles
                if (!is_array($objectTile) || !isset($objectTile['x'], $objectTile['y'], $objectTile['objectType'])) {
                    return null;
                }
                
                return [
                    'x' => (int) $objectTile['x'],
                    'y' => (int) $objectTile['y'],
                    'objectType' => (int) $objectTile['objectType'],
                ];
            }, array_filter($data)); // Remove null values
        }
        
        // Handle regular tile layers
        return array_map(function ($tile) {
            // Skip invalid tiles
            if (!is_array($tile) || !isset($tile['x'], $tile['y'], $tile['brush'])) {
                return null;
            }
            
            return new Tile(
                (int) $tile['x'],
                (int) $tile['y'],
                new Brush(
                    $tile['brush']['tileset'] ?? '',
                    (int) ($tile['brush']['tileX'] ?? 0),
                    (int) ($tile['brush']['tileY'] ?? 0),
                )
            );
        }, array_filter($data)); // Remove null values
    }

    public function set($model, string $key, $value, array $attributes)
    {
        // Handle field type layers differently
        if (isset($attributes['type']) && $attributes['type'] === LayerType::FieldType->value) {
            $fieldTypeTiles = array_map(function ($fieldTypeTile) {
                if (is_array($fieldTypeTile)) {
                    return [
                        'x' => (int) ($fieldTypeTile['x'] ?? 0),
                        'y' => (int) ($fieldTypeTile['y'] ?? 0),
                        'fieldType' => (int) ($fieldTypeTile['fieldType'] ?? 0),
                    ];
                }
                
                // Handle object input
                return [
                    'x' => (int) ($fieldTypeTile->x ?? 0),
                    'y' => (int) ($fieldTypeTile->y ?? 0),
                    'fieldType' => (int) ($fieldTypeTile->fieldType ?? 0),
                ];
            }, $value);

            return json_encode($fieldTypeTiles);
        }
        
        // Handle object layers
        if (isset($attributes['type']) && $attributes['type'] === LayerType::Object->value) {
            $objectTiles = array_map(function ($objectTile) {
                if (is_array($objectTile)) {
                    return [
                        'x' => (int) ($objectTile['x'] ?? 0),
                        'y' => (int) ($objectTile['y'] ?? 0),
                        'objectType' => (int) ($objectTile['objectType'] ?? 0),
                    ];
                }
                
                // Handle object input
                return [
                    'x' => (int) ($objectTile->x ?? 0),
                    'y' => (int) ($objectTile->y ?? 0),
                    'objectType' => (int) ($objectTile->objectType ?? 0),
                ];
            }, $value);

            return json_encode($objectTiles);
        }
        
        // Handle regular tile layers
        $tiles = array_map(function ($tile) {
            if ($tile instanceof Tile) {
                return [
                    'x' => $tile->x,
                    'y' => $tile->y,
                    'brush' => [
                        'tileset' => $tile->brush->tileset,
                        'tileX' => $tile->brush->tileX,
                        'tileY' => $tile->brush->tileY,
                    ],
                ];
            }
            
            // Handle array input
            return [
                'x' => (int) ($tile['x'] ?? 0),
                'y' => (int) ($tile['y'] ?? 0),
                'brush' => [
                    'tileset' => $tile['brush']['tileset'] ?? '',
                    'tileX' => (int) ($tile['brush']['tileX'] ?? 0),
                    'tileY' => (int) ($tile['brush']['tileY'] ?? 0),
                ],
            ];
        }, $value);

        return json_encode($tiles);
    }
} 