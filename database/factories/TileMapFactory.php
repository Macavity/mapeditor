<?php

namespace Database\Factories;

use App\Models\TileMap;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TileMap>
 */
class TileMapFactory extends Factory
{
    protected $model = TileMap::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'name' => $this->faker->words(2, true),
            'width' => 10,
            'height' => 10,
            'tile_width' => 32,
            'tile_height' => 32,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
} 