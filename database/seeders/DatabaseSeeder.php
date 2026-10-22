<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\Classification;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Model events stay on: roles and projects generate their uuid when created.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $allPermissions = array_map(fn (Permission $permission) => $permission->value, Permission::cases());

        $roles = [
            ['name' => 'QA / Tester',   'slug' => 'qa', 'permissions' => $allPermissions],
            ['name' => 'Developer',  'slug' => 'developer', 'permissions' => []],
            ['name' => 'Administrator',  'slug' => 'admin', 'permissions' => $allPermissions],
            ['name' => 'Viewer',   'slug' => 'viewer', 'permissions' => []],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                ['name' => $role['name'], 'permissions' => $role['permissions']]
            );
        }

        $demoUsers = [
            ['name' => 'Admin Demo', 'email' => 'admin@a2gtest.com', 'role' => 'admin'],
            ['name' => 'QA Demo', 'email' => 'qa@a2gtest.com', 'role' => 'qa'],
            ['name' => 'Viewer Demo', 'email' => 'viewer@a2gtest.com', 'role' => 'viewer'],
        ];

        /** Roles are held per project, so the demo accounts share a demo project where each one has its role. */
        $demoProject = null;

        foreach ($demoUsers as $demoUser) {
            $user = User::firstOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            );

            $demoProject ??= Project::firstOrCreate(['name' => 'Demo Project'], ['owner_id' => $user->id]);
            $demoProject->addMember($user, $demoUser['role']);
        }

        $classifications = [
            ['name' => 'Unit', 'description' => 'Unit tests verify the behavior of individual components of the system.'],
            ['name' => 'Integration', 'description' => 'Integration tests assess the interaction between different modules or services.'],
        ];
        foreach ($classifications as $classification) {
            Classification::firstOrCreate(['name' => $classification['name']], $classification);
        }

        /** Statuses are per project; every project (the demo one included) gets the defaults when created. */
        $this->call(RequirementSeeder::class);
    }
}
