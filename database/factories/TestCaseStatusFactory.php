<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TestCaseStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestCaseStatus>
 */
class TestCaseStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->unique()->word(),
            'color' => fake()->randomElement(['success', 'destructive', 'warning', 'secondary', 'info']),
        ];
    }
}
