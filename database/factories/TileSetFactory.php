<?php

namespace Database\Factories;

use App\Models\TileSet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TileSet>
 */
class TileSetFactory extends Factory
{
    protected $model = TileSet::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'name' => $this->faker->words(2, true),
            'tile_width' => 32,
            'tile_height' => 32,
            'image_width' => 128,
            'image_height' => 128,
            'tile_count' => 16,
            'image_path' => 'tilesets/test-tileset.png',
            'first_gid' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
} 