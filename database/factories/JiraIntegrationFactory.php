<?php

namespace Database\Factories;

use App\Models\JiraIntegration;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JiraIntegration>
 */
class JiraIntegrationFactory extends Factory
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
            'site_url' => 'https://'.fake()->unique()->domainWord().'.atlassian.net',
            'cloud_id' => fake()->uuid(),
            'access_token' => 'access-'.fake()->sha1(),
            'refresh_token' => 'refresh-'.fake()->sha1(),
            'expires_at' => now()->addHour(),
            'connected_by' => User::factory(),
            'connected_at' => now(),
        ];
    }

    /**
     * Indicate that the access token has already expired, so the next API call
     * has to refresh it before talking to Jira.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
