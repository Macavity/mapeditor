<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FieldType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FieldType>
 */
class FieldTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FieldType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = [
            '#FF0000', // Red
            '#00FF00', // Green
            '#0000FF', // Blue
            '#FFFF00', // Yellow
            '#FF00FF', // Magenta
            '#00FFFF', // Cyan
            '#FFA500', // Orange
            '#800080', // Purple
            '#008000', // Dark Green
            '#FFC0CB', // Pink
        ];

        return [
            'name' => $this->faker->unique()->word(),
            'color' => $this->faker->randomElement($colors),
        ];
    }
}
