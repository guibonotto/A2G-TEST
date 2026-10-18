<?php

namespace Database\Factories;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'name' => $slug,
            'slug' => $slug,
            'permissions' => [],
        ];
    }

    /**
     * Build the role with the given slug, reusing it as the display name so
     * tests that only care about the `role:` middleware stay readable.
     */
    public function slug(string $slug): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $slug,
            'slug' => $slug,
        ]);
    }

    /**
     * Grant the role every permission the application knows about.
     */
    public function withAllPermissions(): static
    {
        return $this->state(fn (array $attributes): array => [
            'permissions' => array_map(fn (Permission $permission): string => $permission->value, Permission::cases()),
        ]);
    }
}
