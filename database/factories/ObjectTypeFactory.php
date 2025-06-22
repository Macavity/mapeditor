<?php

namespace Database\Factories;

use App\Models\ObjectType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ObjectType>
 */
class ObjectTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'type' => '',
            'color' => $this->faker->hexColor(),
            'description' => $this->faker->optional()->sentence(),
            'is_solid' => $this->faker->boolean(80), // 80% chance of being solid
        ];
    }

    /**
     * Indicate that the object type is solid (blocks movement).
     */
    public function solid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_solid' => true,
        ]);
    }

    /**
     * Indicate that the object type is not solid (doesn't block movement).
     */
    public function nonSolid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_solid' => false,
        ]);
    }
} 