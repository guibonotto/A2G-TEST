<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Project;
use App\Models\ProjectRolePermission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RolePermissionControllerTest extends TestCase
{
    use RefreshDatabase;

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
        $this->actingAs($developer)->put(route('role-permissions.update', $this->roleOf($developer)))->assertForbidden();
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
        $this->assertSame(
            [Permission::AssignTestCases->value],
            $developerRole->fresh()->effectivePermissions($this->sessionProject()),
        );
    }

    public function test_changing_permissions_only_affects_the_current_project(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developerRole = Role::firstOrCreate(['slug' => 'developer'], ['name' => 'developer', 'permissions' => []]);
        $otherProject = Project::factory()->create();

        $this->actingAs($qa)->put(route('role-permissions.update', $developerRole), [
            'permissions' => [Permission::AssignTestCases->value],
        ]);

        $this->assertSame([Permission::AssignTestCases->value], $developerRole->fresh()->effectivePermissions($this->sessionProject()));
        $this->assertSame([], $developerRole->fresh()->effectivePermissions($otherProject));
        $this->assertSame([], $developerRole->fresh()->permissions);
    }

    public function test_a_permission_granted_in_one_project_is_only_enforced_there(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developer = $this->createUserWithRole('developer');
        $project = $this->sessionProject();
        $otherProject = Project::factory()->create();
        $otherProject->addMember($developer, 'developer');
        $developerRole = Role::where('slug', 'developer')->firstOrFail();

        $this->actingAs($qa)->put(route('role-permissions.update', $developerRole), [
            'permissions' => [Permission::AssignTestCases->value],
        ]);

        $this->assertTrue($developer->fresh()->hasPermission(Permission::AssignTestCases, $project));
        $this->assertFalse($developer->fresh()->hasPermission(Permission::AssignTestCases, $otherProject));
    }

    public function test_the_permission_list_shows_the_current_project_set(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developerRole = Role::firstOrCreate(['slug' => 'developer'], ['name' => 'developer', 'permissions' => []]);
        ProjectRolePermission::create([
            'project_id' => Project::factory()->create()->id,
            'role_id' => $developerRole->id,
            'permissions' => [Permission::ManageStatuses->value],
        ]);
        ProjectRolePermission::create([
            'project_id' => $this->sessionProject()->id,
            'role_id' => $developerRole->id,
            'permissions' => [Permission::AssignTestCases->value],
        ]);

        $this->actingAs($qa)->get(route('role-permissions.index'))
            ->assertInertia(fn (Assert $page) => $page->where(
                'roles',
                fn ($roles) => collect($roles)->firstWhere('slug', 'developer')['effective_permissions'] === [Permission::AssignTestCases->value],
            ));
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

        $this->actingAs($qa)->put(route('role-permissions.update', $this->roleOf($qa)), ['permissions' => []])->assertForbidden();
        $this->actingAs($qa)->put(route('role-permissions.update', $adminRole), ['permissions' => []])->assertForbidden();
    }

    public function test_nobody_can_update_the_admin_role_permissions(): void
    {
        $admin = $this->createUserWithRole('admin');

        $this->actingAs($admin)->put(route('role-permissions.update', $this->roleOf($admin)), ['permissions' => []])->assertForbidden();
    }

    public function test_qa_without_the_manage_roles_permission_is_blocked(): void
    {
        $qa = $this->createUserWithRole('qa');
        $this->roleOf($qa)->update(['permissions' => []]);

        $this->actingAs($qa)->get(route('role-permissions.index'))->assertForbidden();
    }

    public function test_admins_keep_access_even_when_their_stored_permissions_are_empty(): void
    {
        $admin = $this->createUserWithRole('admin');
        $this->roleOf($admin)->update(['permissions' => []]);

        $this->actingAs($admin)->get(route('role-permissions.index'))->assertOk();
        $this->actingAs($admin)->get(route('test-case-statuses.index'))->assertOk();
        $this->actingAs($admin)->get(route('accounts.index'))->assertOk();
    }

    public function test_management_roles_that_were_never_configured_keep_full_access(): void
    {
        $qa = $this->createUserWithRole('qa');
        $this->roleOf($qa)->update(['permissions' => null]);

        $this->actingAs($qa)->get(route('role-permissions.index'))->assertOk();
        $this->actingAs($qa)->get(route('test-case-statuses.index'))->assertOk();
        $this->actingAs($qa)->get(route('accounts.index'))->assertOk();
    }

    public function test_roles_below_management_that_were_never_configured_get_nothing(): void
    {
        $developer = $this->createUserWithRole('developer');
        $this->roleOf($developer)->update(['permissions' => null]);

        $this->assertFalse($developer->fresh()->hasPermission(Permission::ManageStatuses));
    }

    public function test_an_explicitly_empty_set_is_still_honoured(): void
    {
        $qa = $this->createUserWithRole('qa');
        $this->roleOf($qa)->update(['permissions' => []]);

        $this->actingAs($qa)->get(route('role-permissions.index'))->assertForbidden();
    }

    public function test_the_permission_list_exposes_the_enforced_permission_set(): void
    {
        $qa = $this->createUserWithRole('qa');
        $developer = $this->createUserWithRole('developer');
        $this->roleOf($developer)->update(['permissions' => null]);

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
