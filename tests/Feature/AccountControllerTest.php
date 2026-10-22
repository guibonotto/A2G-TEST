<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->withSession(['current_project_id' => $this->project->id]);
    }

    /**
     * Account management only covers the current project, so users are created as its members.
     */
    private function memberWithRole(string $slug): User
    {
        $user = $this->createUserWithRole($slug);
        $this->project->members()->attach($user);

        return $user;
    }

    public function test_only_members_of_the_current_project_are_listed(): void
    {
        $admin = $this->memberWithRole('admin');
        $member = $this->memberWithRole('developer');
        $outsider = $this->createUserWithRole('developer');
        Project::factory()->create()->members()->attach($outsider);

        $this->actingAs($admin)->get(route('accounts.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('accounts', fn ($accounts) => collect($accounts)->pluck('id')->sort()->values()->all() === [$admin->id, $member->id])
            );
    }

    public function test_the_role_of_a_user_outside_the_current_project_cannot_be_changed(): void
    {
        $admin = $this->memberWithRole('admin');
        $outsider = $this->createUserWithRole('developer');
        $viewerRole = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'viewer']);

        $this->actingAs($admin)->put(route('accounts.update', $outsider), ['role_id' => $viewerRole->id])->assertForbidden();
        $this->assertNotSame($viewerRole->id, $outsider->fresh()->role_id);
    }

    public function test_account_management_requires_a_selected_project(): void
    {
        $admin = $this->memberWithRole('admin');

        $this->actingAs($admin)->withSession(['current_project_id' => null])
            ->get(route('accounts.index'))
            ->assertRedirect(route('projects.index'));
    }

    public function test_guests_cannot_access_account_management(): void
    {
        $account = User::factory()->create();

        $this->get(route('accounts.index'))->assertRedirect(route('login'));
        $this->put(route('accounts.update', $account))->assertRedirect(route('login'));
    }

    public function test_non_qa_users_cannot_access_account_management(): void
    {
        $developer = $this->memberWithRole('developer');
        $account = User::factory()->create();

        $this->actingAs($developer)->get(route('accounts.index'))->assertForbidden();
        $this->actingAs($developer)->put(route('accounts.update', $account))->assertForbidden();
    }

    public function test_qa_can_view_the_account_list(): void
    {
        $qa = $this->memberWithRole('qa');
        $developer = $this->memberWithRole('developer');

        $response = $this->actingAs($qa)->get(route('accounts.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('management/accounts/index')
            ->has('accounts', 2)
            ->has('roles')
        );
    }

    public function test_qa_can_change_a_users_role(): void
    {
        $qa = $this->memberWithRole('qa');
        $developer = $this->memberWithRole('developer');
        $viewerRole = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'viewer']);

        $response = $this->actingAs($qa)->put(route('accounts.update', $developer), [
            'role_id' => $viewerRole->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $developer->id, 'role_id' => $viewerRole->id]);
    }

    public function test_qa_can_unset_a_users_role(): void
    {
        $qa = $this->memberWithRole('qa');
        $developer = $this->memberWithRole('developer');

        $response = $this->actingAs($qa)->put(route('accounts.update', $developer), [
            'role_id' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $developer->id, 'role_id' => null]);
    }

    public function test_role_update_fails_with_an_unknown_role(): void
    {
        $qa = $this->memberWithRole('qa');
        $developer = $this->memberWithRole('developer');

        $response = $this->actingAs($qa)->put(route('accounts.update', $developer), [
            'role_id' => 9999,
        ]);

        $response->assertSessionHasErrors('role_id');
    }

    public function test_admin_can_access_account_management(): void
    {
        $admin = $this->memberWithRole('admin');
        $developer = $this->memberWithRole('developer');
        $qaRole = Role::firstOrCreate(['slug' => 'qa'], ['name' => 'qa']);

        $this->actingAs($admin)->get(route('accounts.index'))->assertOk();
        $this->actingAs($admin)->put(route('accounts.update', $developer), ['role_id' => $qaRole->id])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $developer->id, 'role_id' => $qaRole->id]);
    }

    public function test_admin_can_promote_another_user_to_admin(): void
    {
        $admin = $this->memberWithRole('admin');
        $developer = $this->memberWithRole('developer');

        $this->actingAs($admin)->put(route('accounts.update', $developer), ['role_id' => $admin->role_id])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $developer->id, 'role_id' => $admin->role_id]);
    }

    public function test_qa_cannot_promote_a_user_to_qa_or_admin(): void
    {
        $qa = $this->memberWithRole('qa');
        $developer = $this->memberWithRole('developer');
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'admin']);

        $this->actingAs($qa)->put(route('accounts.update', $developer), ['role_id' => $qa->role_id])->assertForbidden();
        $this->actingAs($qa)->put(route('accounts.update', $developer), ['role_id' => $adminRole->id])->assertForbidden();
    }

    public function test_qa_cannot_change_the_role_of_a_qa_or_an_admin(): void
    {
        $qa = $this->memberWithRole('qa');
        $otherQa = $this->memberWithRole('qa');
        $admin = $this->memberWithRole('admin');
        $viewerRole = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'viewer']);

        $this->actingAs($qa)->put(route('accounts.update', $otherQa), ['role_id' => $viewerRole->id])->assertForbidden();
        $this->actingAs($qa)->put(route('accounts.update', $admin), ['role_id' => $viewerRole->id])->assertForbidden();
    }

    public function test_admin_cannot_demote_another_admin(): void
    {
        $admin = $this->memberWithRole('admin');
        $otherAdmin = $this->memberWithRole('admin');
        $viewerRole = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'viewer']);

        $this->actingAs($admin)->put(route('accounts.update', $otherAdmin), ['role_id' => $viewerRole->id])->assertForbidden();
    }

    public function test_users_cannot_change_their_own_role(): void
    {
        $admin = $this->memberWithRole('admin');
        $viewerRole = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'viewer']);

        $this->actingAs($admin)->put(route('accounts.update', $admin), ['role_id' => $viewerRole->id])->assertForbidden();
    }

    public function test_the_account_list_tells_the_viewer_who_they_can_change_and_which_roles_they_can_assign(): void
    {
        $qa = $this->memberWithRole('qa');
        $admin = $this->memberWithRole('admin');
        $developer = $this->memberWithRole('developer');
        Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'viewer']);

        $response = $this->actingAs($qa)->get(route('accounts.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('management/accounts/index')
            ->where('accounts', fn ($accounts) => collect($accounts)->pluck('can_change_role', 'id')->sortKeys()->all() === [
                $qa->id => false,
                $admin->id => false,
                $developer->id => true,
            ])
            ->where('roles', fn ($roles) => collect($roles)->pluck('assignable', 'slug')->all() === [
                'admin' => false,
                'developer' => true,
                'qa' => false,
                'viewer' => true,
            ])
        );
    }
}
