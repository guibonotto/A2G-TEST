<?php

namespace Tests;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Create a user with the given role, seeded with the same permissions as production.
     */
    protected function createUserWithRole(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], [
            'name' => $slug,
            'permissions' => in_array($slug, ['qa', 'admin'], true)
                ? array_column(Permission::cases(), 'value')
                : [],
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
