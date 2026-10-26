<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenameAdministratorRoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_26_220746_rename_administrator_role_to_test_manager.php');
    }

    public function test_the_administrator_role_is_renamed_to_test_manager(): void
    {
        $admin = Role::create(['name' => 'Administrator', 'slug' => 'admin', 'permissions' => []]);

        $this->migration()->up();

        $this->assertSame('Test Manager', $admin->fresh()->name);
        $this->assertSame('admin', $admin->fresh()->slug);
    }

    public function test_a_customised_name_and_other_roles_are_left_untouched(): void
    {
        $admin = Role::create(['name' => 'Owner', 'slug' => 'admin', 'permissions' => []]);
        $viewer = Role::create(['name' => 'Administrator', 'slug' => 'viewer', 'permissions' => []]);

        $this->migration()->up();

        $this->assertSame('Owner', $admin->fresh()->name);
        $this->assertSame('Administrator', $viewer->fresh()->name);
    }

    public function test_rolling_back_restores_the_previous_name(): void
    {
        $admin = Role::create(['name' => 'Administrator', 'slug' => 'admin', 'permissions' => []]);

        $this->migration()->up();
        $this->migration()->down();

        $this->assertSame('Administrator', $admin->fresh()->name);
    }

    public function test_the_seeder_names_the_admin_role_test_manager(): void
    {
        $this->seed();

        $this->assertSame('Test Manager', Role::where('slug', 'admin')->value('name'));
    }
}
