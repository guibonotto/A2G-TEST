<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillRolePermissionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function runMigration(): void
    {
        /** @var Migration $migration */
        $migration = require database_path('migrations/2026_09_17_013755_backfill_role_permissions.php');

        $migration->up();
    }

    public function test_management_roles_without_permissions_receive_every_permission(): void
    {
        $qa = Role::create(['name' => 'qa', 'slug' => 'qa', 'permissions' => null]);
        $admin = Role::create(['name' => 'admin', 'slug' => 'admin', 'permissions' => null]);

        $this->runMigration();

        $allPermissions = array_column(Permission::cases(), 'value');

        $this->assertSame($allPermissions, $qa->fresh()->permissions);
        $this->assertSame($allPermissions, $admin->fresh()->permissions);
    }

    public function test_other_roles_without_permissions_receive_an_empty_set(): void
    {
        $developer = Role::create(['name' => 'developer', 'slug' => 'developer', 'permissions' => null]);
        $viewer = Role::create(['name' => 'viewer', 'slug' => 'viewer', 'permissions' => null]);

        $this->runMigration();

        $this->assertSame([], $developer->fresh()->permissions);
        $this->assertSame([], $viewer->fresh()->permissions);
    }

    public function test_roles_that_already_have_permissions_are_left_untouched(): void
    {
        $qa = Role::create(['name' => 'qa', 'slug' => 'qa', 'permissions' => [Permission::ManageStatuses->value]]);
        $developer = Role::create(['name' => 'developer', 'slug' => 'developer', 'permissions' => [Permission::AssignTestCases->value]]);

        $this->runMigration();

        $this->assertSame([Permission::ManageStatuses->value], $qa->fresh()->permissions);
        $this->assertSame([Permission::AssignTestCases->value], $developer->fresh()->permissions);
    }
}
