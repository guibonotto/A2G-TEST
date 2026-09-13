<?php

namespace Database\Factories;

use App\Models\TestTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestTemplate>
 */
class TestTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'description' => fake()->paragraph(),
            'classification_id' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create the template with a sequence of steps.
     */
    public function withSteps(int $count = 3): static
    {
        return $this->afterCreating(function (TestTemplate $template) use ($count): void {
            foreach (range(1, $count) as $order) {
                $template->steps()->create([
                    'order' => $order,
                    'description' => fake()->sentence(),
                    'expected_result' => fake()->sentence(),
                ]);
            }
        });
    }
}
