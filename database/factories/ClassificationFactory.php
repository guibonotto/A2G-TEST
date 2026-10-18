<?php

namespace Database\Factories;

use App\Models\Classification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classification>
 */
class ClassificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
        ];
    }
}
