<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RequirementControllerTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->withSession(['current_project_id' => $this->project->id]);
    }

    private function createProjectMember(string $role): User
    {
        $user = $this->createUserWithRole($role);
        $this->project->members()->syncWithoutDetaching($user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRequirement(array $attributes = [], ?Project $project = null): Requirement
    {
        return Requirement::create([
            'project_id' => ($project ?? $this->project)->id,
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Cadastro',
            ...$attributes,
        ]);
    }

    public function test_requirements_are_listed_in_numeric_order(): void
    {
        $qa = $this->createProjectMember('qa');
        foreach (['PROJ-10', 'PROJ-2', 'PROJ-1', 'PROJ-19'] as $code) {
            $this->createRequirement(['code' => $code]);
        }

        $this->actingAs($qa)->get(route('requirements.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('requirements.0.code', 'PROJ-1')
                ->where('requirements.1.code', 'PROJ-2')
                ->where('requirements.2.code', 'PROJ-10')
                ->where('requirements.3.code', 'PROJ-19')
            );
    }

    public function test_guests_cannot_access_requirement_management(): void
    {
        $requirement = $this->createRequirement();

        $this->get(route('requirements.index'))->assertRedirect(route('login'));
        $this->post(route('requirements.store'))->assertRedirect(route('login'));
        $this->put(route('requirements.update', $requirement))->assertRedirect(route('login'));
        $this->delete(route('requirements.destroy', $requirement))->assertRedirect(route('login'));
    }

    public function test_non_qa_users_cannot_access_requirement_management(): void
    {
        $developer = $this->createProjectMember('developer');
        $requirement = $this->createRequirement();

        $this->actingAs($developer)->get(route('requirements.index'))->assertForbidden();
        $this->actingAs($developer)->post(route('requirements.store'))->assertForbidden();
        $this->actingAs($developer)->put(route('requirements.update', $requirement))->assertForbidden();
        $this->actingAs($developer)->delete(route('requirements.destroy', $requirement))->assertForbidden();
    }

    public function test_requirement_management_needs_a_selected_project(): void
    {
        $qa = $this->createUserWithRole('qa');

        $this->actingAs($qa)->withSession(['current_project_id' => null])
            ->get(route('requirements.index'))->assertRedirect(route('projects.index'));
    }

    public function test_qa_can_view_the_requirement_list(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createRequirement(['title' => 'Cadastro de Caso de Teste']);

        $this->actingAs($qa)->get(route('requirements.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('management/requirements/index')
                ->has('requirements', 1)
                ->where('requirements.0.code', 'RF001')
                ->where('requirements.0.test_cases_count', 0)
            );
    }

    public function test_the_requirement_list_only_shows_the_current_project(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createRequirement(['title' => 'Deste projeto']);
        $this->createRequirement(['title' => 'De outro projeto'], Project::factory()->create());

        $this->actingAs($qa)->get(route('requirements.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('requirements', 1)
                ->where('requirements.0.title', 'Deste projeto')
            );
    }

    public function test_qa_can_create_a_requirement(): void
    {
        $qa = $this->createProjectMember('qa');

        $response = $this->actingAs($qa)->post(route('requirements.store'), [
            'code' => 'RF032',
            'type' => 'funcional',
            'title' => 'Novo requisito',
            'description' => 'Descrição do requisito.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requirements', [
            'code' => 'RF032',
            'created_by' => $qa->id,
            'project_id' => $this->project->id,
        ]);
    }

    public function test_requirement_creation_fails_with_duplicate_code(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createRequirement();

        $response = $this->actingAs($qa)->post(route('requirements.store'), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Duplicado',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_the_same_code_can_exist_in_different_projects(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createRequirement([], Project::factory()->create());

        $this->actingAs($qa)->post(route('requirements.store'), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Mesmo código, outro projeto',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('requirements', 2);
    }

    public function test_requirement_creation_fails_with_invalid_type(): void
    {
        $qa = $this->createProjectMember('qa');

        $response = $this->actingAs($qa)->post(route('requirements.store'), [
            'code' => 'RF033',
            'type' => 'inexistente',
            'title' => 'Requisito inválido',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_qa_can_update_a_requirement(): void
    {
        $qa = $this->createProjectMember('qa');
        $requirement = $this->createRequirement();

        $response = $this->actingAs($qa)->put(route('requirements.update', $requirement), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Cadastro de Caso de Teste',
            'priority' => 'alta',
            'status' => 'em_andamento',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requirements', [
            'id' => $requirement->id,
            'title' => 'Cadastro de Caso de Teste',
            'priority' => 'alta',
            'status' => 'em_andamento',
        ]);
    }

    public function test_qa_can_delete_a_requirement(): void
    {
        $qa = $this->createProjectMember('qa');
        $requirement = $this->createRequirement();

        $response = $this->actingAs($qa)->delete(route('requirements.destroy', $requirement));

        $response->assertRedirect();
        $this->assertDatabaseMissing('requirements', ['id' => $requirement->id]);
    }

    public function test_requirements_of_another_project_cannot_be_changed(): void
    {
        $qa = $this->createProjectMember('qa');
        $foreignRequirement = $this->createRequirement([], Project::factory()->create());

        $this->actingAs($qa)->put(route('requirements.update', $foreignRequirement), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Alterado',
        ])->assertNotFound();
        $this->actingAs($qa)->delete(route('requirements.destroy', $foreignRequirement))->assertNotFound();

        $this->assertDatabaseHas('requirements', ['id' => $foreignRequirement->id, 'title' => 'Cadastro']);
    }

    public function test_deleting_a_requirement_imported_from_jira_remembers_the_issue(): void
    {
        $qa = $this->createProjectMember('qa');
        $requirement = $this->createRequirement(['code' => 'AAAAAAA-1', 'title' => 'Do Jira']);
        $requirement->forceFill(['jira_synced_at' => now()])->save();

        $this->actingAs($qa)->delete(route('requirements.destroy', $requirement));

        $this->assertDatabaseHas('jira_excluded_issues', [
            'project_id' => $this->project->id,
            'issue_key' => 'AAAAAAA-1',
        ]);
    }

    public function test_deleting_a_manual_requirement_does_not_touch_the_jira_exclusions(): void
    {
        $qa = $this->createProjectMember('qa');
        $requirement = $this->createRequirement(['title' => 'Manual']);

        $this->actingAs($qa)->delete(route('requirements.destroy', $requirement));

        $this->assertDatabaseCount('jira_excluded_issues', 0);
    }
}
