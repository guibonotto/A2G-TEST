<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Requirement>
 */
class RequirementFactory extends Factory
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
            'code' => fake()->unique()->numerify('RF###'),
            'type' => 'funcional',
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['alta', 'media', 'baixa']),
            'status' => fake()->randomElement(['pendente', 'em_andamento', 'concluido']),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the requirement came from a Jira issue, which uses the
     * issue key as its code.
     */
    public function fromJiraIssue(string $issueKey): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => $issueKey,
        ]);
    }
}
