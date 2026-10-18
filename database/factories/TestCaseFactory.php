<?php

namespace Database\Factories;

use App\Models\Classification;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestCase>
 */
class TestCaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'project_id' => Project::factory(),
            'classification_id' => Classification::factory(),
            'created_by' => User::factory(),
            'template_id' => null,
            'status_id' => null,
            'assigned_to' => null,
        ];
    }

    /**
     * Create the test case with a sequence of steps.
     */
    public function withSteps(int $count = 3): static
    {
        return $this->afterCreating(function (TestCase $testCase) use ($count): void {
            foreach (range(1, $count) as $order) {
                $testCase->steps()->create([
                    'order' => $order,
                    'description' => fake()->sentence(),
                    'expected_result' => fake()->sentence(),
                ]);
            }
        });
    }
}
