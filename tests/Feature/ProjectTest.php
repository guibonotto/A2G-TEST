<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('projects.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_projects_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertOk();
    }

    public function test_a_user_can_create_a_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'name' => 'Alpha Project',
        ]);

        $project = Project::whereName('Alpha Project')->first();

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('projects.show', $project));

        $this->assertNotNull($project);
        $this->assertSame($user->id, $project->owner_id);
        $this->assertTrue($project->members()->whereKey($user->id)->exists());
        $this->assertSame($project->id, session('current_project_id'));
    }

    public function test_a_user_without_a_role_becomes_an_administrator_when_creating_a_project(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->actingAs($user)->post(route('projects.store'), ['name' => 'Alpha Project']);

        $this->assertTrue($user->fresh()->hasRole(Role::ADMIN));
    }

    public function test_a_new_administrator_can_reach_the_management_screens(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->actingAs($user)->post(route('projects.store'), ['name' => 'Alpha Project']);

        $this->actingAs($user->fresh())->get(route('accounts.index'))->assertOk();
        $this->actingAs($user->fresh())->get(route('requirements.index'))->assertOk();
    }

    public function test_a_lower_role_is_promoted_to_administrator_when_creating_a_project(): void
    {
        $user = $this->createUserWithRole('developer');

        $this->actingAs($user)->post(route('projects.store'), ['name' => 'Alpha Project']);

        $this->assertTrue($user->fresh()->hasRole(Role::ADMIN));
    }

    public function test_an_administrator_keeps_the_same_role_when_creating_a_project(): void
    {
        $user = $this->createUserWithRole(Role::ADMIN);
        $adminRoleId = $user->role_id;

        $this->actingAs($user)->post(route('projects.store'), ['name' => 'Alpha Project']);

        $this->assertSame($adminRoleId, $user->fresh()->role_id);
        $this->assertSame(1, Role::where('slug', Role::ADMIN)->count());
    }

    public function test_a_failed_project_creation_does_not_change_the_role(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->actingAs($user)->post(route('projects.store'), ['name' => '']);

        $this->assertNull($user->fresh()->role_id);
    }

    public function test_joining_a_project_by_invitation_does_not_grant_a_role(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $token = 'invite-token-for-role-test';
        $project->invitations()->create(['token_hash' => hash('sha256', $token)]);
        $guest = User::factory()->create(['role_id' => null]);

        $this->actingAs($guest)->get(route('projects.invite', $token));

        $this->assertTrue($project->members()->whereKey($guest->id)->exists());
        $this->assertNull($guest->fresh()->role_id);
    }

    public function test_creating_a_project_requires_a_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_a_user_can_join_an_existing_project_with_an_invitation_link(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();

        $user = User::factory()->create();
        $response = $this->actingAs($owner)->post(route('projects.invites.store', $project));
        $link = session('invite_link');
        $token = basename(parse_url($link, PHP_URL_PATH));

        $response = $this->actingAs($user)->get(route('projects.invite', $token));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('projects.show', $project));

        $this->assertTrue($project->members()->whereKey($user->id)->exists());
        $this->assertSame($project->id, session('current_project_id'));
    }

    public function test_an_invitation_link_can_only_be_used_once(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();

        $user = User::factory()->create();
        $this->actingAs($owner)->post(route('projects.invites.store', $project));
        $token = basename(parse_url(session('invite_link'), PHP_URL_PATH));
        $this->actingAs($user)->get(route('projects.invite', $token));

        $response = $this->actingAs(User::factory()->create())->get(route('projects.invite', $token));
        $response->assertNotFound();
    }

    public function test_an_owner_can_revoke_a_pending_invitation(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $this->actingAs($owner)->post(route('projects.invites.store', $project));
        $invitation = $project->invitations()->firstOrFail();

        $response = $this->actingAs($owner)->delete(route('projects.invites.destroy', [$project, $invitation]));

        $response->assertRedirect(route('projects.show', $project));
        $this->assertNotNull($invitation->fresh()->revoked_at);
    }

    public function test_a_user_cannot_join_a_project_they_already_belong_to(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $project->members()->attach($owner);

        $this->assertTrue($project->members()->whereKey($owner->id)->exists());
    }

    public function test_only_members_can_view_a_project(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $project->members()->attach($owner);

        $outsider = User::factory()->create();

        $response = $this->actingAs($outsider)->get(route('projects.show', $project));

        $response->assertForbidden();
    }

    public function test_members_can_view_a_project(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $project->members()->attach($owner);

        $response = $this->actingAs($owner)->get(route('projects.show', $project));

        $response->assertOk();
    }

    public function test_projects_index_lists_the_users_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Alpha Project']);
        $project->members()->attach($user);
        Project::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('projects/index')
            ->has('myProjects', 1)
            ->where('myProjects.0.name', 'Alpha Project')
        );
    }

    public function test_a_member_can_select_a_project_as_active(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user);

        $response = $this->actingAs($user)->post(route('projects.select', $project));

        $response->assertRedirect(route('dashboard'));
        $this->assertSame($project->id, session('current_project_id'));
    }

    public function test_a_non_member_cannot_select_a_project_as_active(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.select', $project));

        $response->assertForbidden();
    }

    public function test_an_owner_can_revoke_a_members_access(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $project->members()->attach([$owner->id, $member->id]);

        $response = $this->actingAs($owner)->delete(route('projects.members.destroy', [$project, $member]));

        $response->assertRedirect(route('projects.show', $project));
        $this->assertFalse($project->members()->whereKey($member->id)->exists());
    }
}
