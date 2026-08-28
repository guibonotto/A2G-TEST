<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            'name' => 'Projeto Alpha',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $project = Project::whereName('Projeto Alpha')->first();

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('projects.show', $project));

        $this->assertNotNull($project);
        $this->assertSame($user->id, $project->owner_id);
        $this->assertTrue($project->members()->whereKey($user->id)->exists());
        $this->assertTrue(Hash::check('password', $project->password));
    }

    public function test_creating_a_project_requires_a_name_and_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'name' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'password']);
    }

    public function test_a_user_can_join_an_existing_project_with_the_correct_id_and_password(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create([
            'password' => Hash::make('secret-password'),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.join'), [
            'uuid' => $project->uuid,
            'password' => 'secret-password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('projects.show', $project));

        $this->assertTrue($project->members()->whereKey($user->id)->exists());
    }

    public function test_a_user_cannot_join_a_project_with_the_wrong_password(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create([
            'password' => Hash::make('secret-password'),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.join'), [
            'uuid' => $project->uuid,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertFalse($project->members()->whereKey($user->id)->exists());
    }

    public function test_joining_a_project_requires_a_valid_project_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.join'), [
            'uuid' => (string) Str::uuid(),
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('uuid');
    }

    public function test_a_user_cannot_join_a_project_they_already_belong_to(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create([
            'password' => Hash::make('secret-password'),
        ]);
        $project->members()->attach($owner);

        $response = $this->actingAs($owner)->post(route('projects.join'), [
            'uuid' => $project->uuid,
            'password' => 'secret-password',
        ]);

        $response->assertSessionHasErrors('uuid');
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
}
