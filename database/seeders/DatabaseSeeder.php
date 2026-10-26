<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\Classification;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * The catalogue a deployment cannot work without: the roles and the test case
 * classifications. It creates no accounts, so running it on a live deployment
 * cannot hand anybody a way in. The demo project and its accounts live in
 * {@see DemoSeeder}, which has to be asked for by name.
 *
 * Model events stay on: roles generate their uuid when created.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /**
         * Only the administrator gets the administrative permissions. QA keeps
         * the testing ones: handing a tester the ability to manage accounts
         * and roles made the two roles identical in everything but the label.
         */
        $roles = [
            ['name' => 'QA / Tester',   'slug' => 'qa', 'permissions' => Permission::forTesters()],
            ['name' => 'Developer',  'slug' => 'developer', 'permissions' => Permission::forDevelopers()],
            ['name' => 'Test Manager',  'slug' => 'admin', 'permissions' => Permission::values()],
            /** Read-only by definition: the viewer holds no permission at all. */
            ['name' => 'Viewer',   'slug' => 'viewer', 'permissions' => []],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                ['name' => $role['name'], 'permissions' => $role['permissions']]
            );
        }

        $classifications = [
            ['name' => 'Unit', 'description' => 'Unit tests verify the behavior of individual components of the system.'],
            ['name' => 'Integration', 'description' => 'Integration tests assess the interaction between different modules or services.'],
        ];
        foreach ($classifications as $classification) {
            Classification::firstOrCreate(['name' => $classification['name']], $classification);
        }
    }
}
