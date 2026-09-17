<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RolePermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    // private function createUserWithRole(string $slug): User
    // {
    //     $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);

    //     return User::factory()->create(['role_id' => $role->id]);
    // }

    public function test_guests_cannot_access_permission_management(): void
    {
        $role = Role::firstOrCreate(['slug' => 'developer'], ['name' => 'developer']);

        $this->get(route('role-permissions.index'))->assertRedirect(route('login'));
        $this->put(route('role-permissions.update', $role))->assertRedirect(route('login'));
    }

    public function test_non_qa_users_cannot_access_permission_management(): void
    {
        $developer = $this->createUserWithRole('developer');

        $this->actingAs($developer)->get(route('role-permissions.index'))->assertForbidden();
        $this->actingAs($developer)->put(route('role-permissions.update', $developer->role))->assertForbidden();
    }

    public function test_qa_can_view_the_role_permission_list(): void
    {
        $qa = $this->createUserWithRole('qa');

        $response = $this->actingAs($qa)->get(route('role-permissions.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('management/permissions/index')
            ->has('roles')
            ->has('availablePermissions', count(Permission::cases()))
        );
    }

    public function test_qa_can_update_a_roles_permissions(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developerRole = Role::firstOrCreate(['slug' => 'developer'], ['name' => 'developer']);

        $response = $this->actingAs($qa)->put(route('role-permissions.update', $developerRole), [
            'permissions' => [Permission::AssignTestCases->value],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['id' => $developerRole->id]);
        $this->assertSame(
            [Permission::AssignTestCases->value],
            $developerRole->fresh()->permissions,
        );
    }

    public function test_permission_update_fails_with_an_unknown_permission(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developerRole = Role::firstOrCreate(['slug' => 'developer'], ['name' => 'developer']);

        $response = $this->actingAs($qa)->put(route('role-permissions.update', $developerRole), [
            'permissions' => ['not-a-real-permission'],
        ]);

        $response->assertSessionHasErrors('permissions.0');
    }

    public function test_admin_can_access_permission_management(): void
    {
        $admin = $this->createUserWithRole('admin');
        $developerRole = Role::firstOrCreate(['slug' => 'developer'], ['name' => 'developer']);

        $this->actingAs($admin)->get(route('role-permissions.index'))->assertOk();
        $this->actingAs($admin)->put(route('role-permissions.update', $developerRole), [
            'permissions' => [],
        ])->assertRedirect();
    }

    public function test_qa_cannot_update_permissions_of_a_role_at_or_above_their_level(): void
    {
        $qa = $this->createUserWithRole('qa');
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'admin']);

        $this->actingAs($qa)->put(route('role-permissions.update', $qa->role), ['permissions' => []])->assertForbidden();
        $this->actingAs($qa)->put(route('role-permissions.update', $adminRole), ['permissions' => []])->assertForbidden();
    }

    public function test_nobody_can_update_the_admin_role_permissions(): void
    {
        $admin = $this->createUserWithRole('admin');

        $this->actingAs($admin)->put(route('role-permissions.update', $admin->role), ['permissions' => []])->assertForbidden();
    }

    public function test_qa_without_the_manage_roles_permission_is_blocked(): void
    {
        $qa = $this->createUserWithRole('qa');
        $qa->role->update(['permissions' => []]);

        $this->actingAs($qa)->get(route('role-permissions.index'))->assertForbidden();
    }
}
