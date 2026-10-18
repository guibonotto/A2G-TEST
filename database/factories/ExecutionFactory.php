<?php

namespace Database\Factories;

use App\Models\Execution;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Execution>
 */
class ExecutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'test_case_id' => TestCase::factory(),
            'executed_by' => User::factory(),
            'status' => fake()->randomElement(['Passed', 'Failed', 'Blocked', 'Pending']),
            'comment' => fake()->sentence(),
            'batch_id' => null,
            'execution_date' => now(),
        ];
    }
}
