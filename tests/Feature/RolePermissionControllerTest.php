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

    public function test_admins_keep_access_even_when_their_stored_permissions_are_empty(): void
    {
        $admin = $this->createUserWithRole('admin');
        $admin->role->update(['permissions' => []]);

        $this->actingAs($admin)->get(route('role-permissions.index'))->assertOk();
        $this->actingAs($admin)->get(route('test-case-statuses.index'))->assertOk();
        $this->actingAs($admin)->get(route('accounts.index'))->assertOk();
    }

    public function test_management_roles_that_were_never_configured_keep_full_access(): void
    {
        $qa = $this->createUserWithRole('qa');
        $qa->role->update(['permissions' => null]);

        $this->actingAs($qa)->get(route('role-permissions.index'))->assertOk();
        $this->actingAs($qa)->get(route('test-case-statuses.index'))->assertOk();
        $this->actingAs($qa)->get(route('accounts.index'))->assertOk();
    }

    public function test_roles_below_management_that_were_never_configured_get_nothing(): void
    {
        $developer = $this->createUserWithRole('developer');
        $developer->role->update(['permissions' => null]);

        $this->assertFalse($developer->fresh()->hasPermission(Permission::ManageStatuses));
    }

    public function test_an_explicitly_empty_set_is_still_honoured(): void
    {
        $qa = $this->createUserWithRole('qa');
        $qa->role->update(['permissions' => []]);

        $this->actingAs($qa)->get(route('role-permissions.index'))->assertForbidden();
    }

    public function test_the_permission_list_exposes_the_enforced_permission_set(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developer = $this->createUserWithRole('developer');
        $developer->role->update(['permissions' => null]);

        $response = $this->actingAs($qa)->get(route('role-permissions.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('management/permissions/index')
            ->where('roles', fn ($roles) => collect($roles)
                ->pluck('effective_permissions', 'slug')
                ->all() === [
                    'developer' => [],
                    'qa' => array_column(Permission::cases(), 'value'),
                ])
        );
    }

    public function test_the_permission_list_tells_the_viewer_which_roles_they_can_edit(): void
    {
        $qa = $this->createUserWithRole('qa');
        $this->createUserWithRole('admin');
        $this->createUserWithRole('developer');

        $response = $this->actingAs($qa)->get(route('role-permissions.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('management/permissions/index')
            ->where('roles', fn ($roles) => collect($roles)->pluck('can_edit', 'slug')->all() === [
                'admin' => false,
                'developer' => true,
                'qa' => false,
            ])
        );
    }
}
