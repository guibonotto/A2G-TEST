<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Requirement;
use App\Models\TestCase as TestCaseModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Make a member with the given role in a fresh project and select that project.
     */
    private function actingAsMember(string $role): Project
    {
        $user = $this->createUserWithRole($role);
        $this->actingAs($user);

        return $this->sessionProject();
    }

    private function createRequirement(Project $project): Requirement
    {
        return Requirement::create(['project_id' => $project->id, 'code' => 'RF001', 'type' => 'funcional', 'title' => 'Cadastro']);
    }

    private function createTestCase(Project $project): TestCaseModel
    {
        return TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'created_by' => auth()->id(),
            'project_id' => $project->id,
        ]);
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('start'))->assertRedirect(route('login'));
    }

    public function test_users_without_a_project_are_sent_to_the_project_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('start'))
            ->assertRedirect(route('projects.index'));
    }

    public function test_an_empty_project_starts_at_requirements_for_whoever_can_register_them(): void
    {
        $this->actingAsMember('admin');

        $this->get(route('start'))->assertRedirect(route('requirements.index'));
    }

    public function test_an_empty_project_starts_at_test_cases_for_members_who_cannot_register_requirements(): void
    {
        $this->actingAsMember('developer');

        $this->get(route('start'))->assertRedirect(route('test-cases.index'));
    }

    public function test_a_project_with_requirements_but_no_test_cases_starts_at_test_cases(): void
    {
        $project = $this->actingAsMember('qa');
        $this->createRequirement($project);

        $this->get(route('start'))->assertRedirect(route('test-cases.index'));
    }

    public function test_a_project_with_test_cases_starts_at_the_dashboard(): void
    {
        $project = $this->actingAsMember('qa');
        $this->createTestCase($project);

        $this->get(route('start'))->assertRedirect(route('dashboard'));
    }

    public function test_archived_test_cases_do_not_count_as_data(): void
    {
        $project = $this->actingAsMember('developer');
        $this->createTestCase($project)->delete();

        $this->get(route('start'))->assertRedirect(route('test-cases.index'));
    }

    public function test_a_new_project_opens_at_its_first_step(): void
    {
        $this->actingAs(User::factory()->create())
            ->followingRedirects()
            ->post(route('projects.store'), ['name' => 'Alpha Project'])
            ->assertInertia(fn ($page) => $page->component('requirements/index'));
    }
}
