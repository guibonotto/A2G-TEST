<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\Classification;
use App\Models\Role;
use App\Models\TestCaseStatus;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

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

        foreach ($demoUsers as $demoUser) {
            User::firstOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'role_id' => Role::where('slug', $demoUser['role'])->value('id'),
                ]
            );
        }

        $classifications = [
            ['name' => 'Unit', 'description' => 'Unit tests verify the behavior of individual components of the system.'],
            ['name' => 'Integration', 'description' => 'Integration tests assess the interaction between different modules or services.'],
        ];
        foreach ($classifications as $classification) {
            Classification::firstOrCreate(['name' => $classification['name']], $classification);
        }

        $statuses = [
            ['name' => 'Passed', 'color' => 'success'],
            ['name' => 'Failed', 'color' => 'destructive'],
            ['name' => 'Pending', 'color' => 'warning'],
            ['name' => 'Cancelled', 'color' => 'secondary'],
            ['name' => 'Regression', 'color' => 'info'],
        ];
        foreach ($statuses as $status) {
            TestCaseStatus::firstOrCreate(['name' => $status['name']], $status);
        }

        $this->call(RequirementSeeder::class);
    }
}
