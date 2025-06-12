<?php

namespace Database\Factories;

use App\Models\Layer;
use App\Models\TileMap;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Layer>
 */
class LayerFactory extends Factory
{
    protected $model = Layer::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'name' => $this->faker->words(2, true),
            'type' => 'background',
            'x' => 0,
            'y' => 0,
            'z' => 0,
            'width' => 10,
            'height' => 10,
            'visible' => true,
            'opacity' => 1,
            'data' => [],
            'tile_map_id' => TileMap::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
} 