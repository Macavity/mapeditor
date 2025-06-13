<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\Tile;
use App\ValueObjects\Brush;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class TileArrayCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        $tiles = json_decode($value, true) ?? [];
        return array_map(function ($tile) {
            return new Tile(
                $tile['x'],
                $tile['y'],
                new Brush(
                    $tile['brush']['tileset'],
                    $tile['brush']['tileX'],
                    $tile['brush']['tileY'],
                )
            );
        }, $tiles);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        // $value is expected to be an array of Tile objects
        $tiles = array_map(function (Tile $tile) {
            return [
                'x' => $tile->x,
                'y' => $tile->y,
                'brush' => [
                    'tileset' => $tile->brush->tileset,
                    'tileX' => $tile->brush->tileX,
                    'tileY' => $tile->brush->tileY,
                ],
            ];
        }, $value);

        return json_encode($tiles);
    }
}
