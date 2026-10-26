<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Requirement;
use App\Models\TestCase as TestCaseModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_non_qa_users_cannot_change_requirements(): void
    {
        $developer = $this->createProjectMember('developer');
        $requirement = $this->createRequirement();

        $this->actingAs($developer)->post(route('requirements.store'))->assertForbidden();
        $this->actingAs($developer)->put(route('requirements.update', $requirement))->assertForbidden();
        $this->actingAs($developer)->delete(route('requirements.destroy', $requirement))->assertForbidden();
        $this->actingAs($developer)->patch(route('requirements.restore', $requirement))->assertForbidden();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function readOnlyRoles(): array
    {
        return [
            'developer' => ['developer'],
            'viewer' => ['viewer'],
        ];
    }

    #[DataProvider('readOnlyRoles')]
    public function test_every_project_member_can_read_the_requirement_list(string $role): void
    {
        $member = $this->createProjectMember($role);
        $this->createRequirement();

        $this->actingAs($member)->get(route('requirements.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('requirements/index')
                ->has('requirements', 1)
                ->where('canManage', false)
            );
    }

    public function test_users_outside_the_project_cannot_read_its_requirements(): void
    {
        $this->createRequirement();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get(route('requirements.index'))
            ->assertRedirect(route('projects.index'));
    }

    public function test_requirement_management_needs_a_selected_project(): void
    {
        $qa = $this->createUserWithRole('qa');

        $this->actingAs($qa)->withSession(['current_project_id' => null])
            ->get(route('requirements.index'))->assertRedirect(route('projects.index'));
    }

    public function test_an_administrator_can_view_the_requirement_list(): void
    {
        $admin = $this->createProjectMember('admin');
        $this->createRequirement(['title' => 'Cadastro de Caso de Teste']);

        $this->actingAs($admin)->get(route('requirements.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('requirements/index')
                ->where('canManage', true)
                ->has('requirements', 1)
                ->where('requirements.0.code', 'RF001')
                ->where('requirements.0.test_cases_count', 0)
            );
    }

    public function test_the_requirement_list_shows_the_jira_issue_type(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createRequirement(['code' => 'PROJ-1', 'jira_issue_type' => 'Story']);
        $this->createRequirement(['code' => 'RF002']);

        $this->actingAs($qa)->get(route('requirements.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('requirements.0.code', 'PROJ-1')
                ->where('requirements.0.jira_issue_type', 'Story')
                ->where('requirements.1.code', 'RF002')
                ->where('requirements.1.jira_issue_type', null)
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

    public function test_an_administrator_can_create_a_requirement(): void
    {
        $admin = $this->createProjectMember('admin');

        $response = $this->actingAs($admin)->post(route('requirements.store'), [
            'code' => 'RF032',
            'type' => 'funcional',
            'title' => 'Novo requisito',
            'description' => 'Descrição do requisito.',
            'priority' => 'media',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requirements', [
            'code' => 'RF032',
            'created_by' => $admin->id,
            'project_id' => $this->project->id,
        ]);
    }

    public function test_requirement_creation_fails_with_duplicate_code(): void
    {
        $admin = $this->createProjectMember('admin');
        $this->createRequirement();

        $response = $this->actingAs($admin)->post(route('requirements.store'), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Duplicado',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_the_same_code_can_exist_in_different_projects(): void
    {
        $admin = $this->createProjectMember('admin');
        $this->createRequirement([], Project::factory()->create());

        $this->actingAs($admin)->post(route('requirements.store'), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Mesmo código, outro projeto',
            'priority' => 'media',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('requirements', 2);
    }

    public function test_requirement_creation_fails_with_invalid_type(): void
    {
        $admin = $this->createProjectMember('admin');

        $response = $this->actingAs($admin)->post(route('requirements.store'), [
            'code' => 'RF033',
            'type' => 'inexistente',
            'title' => 'Requisito inválido',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_an_administrator_can_update_a_requirement(): void
    {
        $admin = $this->createProjectMember('admin');
        $requirement = $this->createRequirement();

        $response = $this->actingAs($admin)->put(route('requirements.update', $requirement), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Cadastro de Caso de Teste',
            'priority' => 'alta',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requirements', [
            'id' => $requirement->id,
            'title' => 'Cadastro de Caso de Teste',
            'priority' => 'alta',
        ]);
    }

    public function test_the_creation_toast_names_the_project(): void
    {
        $admin = $this->createProjectMember('admin');
        $this->project->update(['name' => 'Projeto 2']);

        $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('requirements.store'), [
                'code' => 'RF050',
                'type' => 'funcional',
                'title' => 'Novo',
                'priority' => 'media',
            ])
            ->assertInertia(fn (Assert $page) => $page->hasFlash(
                'toast.message',
                'Requirement "RF050" created in Projeto 2.',
            ));
    }

    public function test_the_update_toast_names_the_project(): void
    {
        $admin = $this->createProjectMember('admin');
        $this->project->update(['name' => 'Projeto 2']);
        $requirement = $this->createRequirement();

        $this->actingAs($admin)
            ->followingRedirects()
            ->put(route('requirements.update', $requirement), [
                'code' => 'RF001',
                'type' => 'funcional',
                'title' => 'Cadastro',
                'priority' => 'media',
            ])
            ->assertInertia(fn (Assert $page) => $page->hasFlash(
                'toast.message',
                'Requirement "RF001" updated in Projeto 2.',
            ));
    }

    public function test_a_requirement_needs_a_priority(): void
    {
        $admin = $this->createProjectMember('admin');

        $this->actingAs($admin)->post(route('requirements.store'), [
            'code' => 'RF040',
            'type' => 'funcional',
            'title' => 'Sem prioridade',
        ])->assertSessionHasErrors('priority');

        $this->actingAs($admin)->put(route('requirements.update', $this->createRequirement()), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Cadastro',
            'priority' => '',
        ])->assertSessionHasErrors('priority');
    }

    public function test_the_status_cannot_be_typed_in(): void
    {
        $qa = $this->createProjectMember('qa');
        $requirement = $this->createRequirement();

        $this->actingAs($qa)->put(route('requirements.update', $requirement), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Cadastro',
            'priority' => 'media',
            'status' => 'concluido',
        ])->assertSessionHasNoErrors();

        $this->assertNull($requirement->fresh()->status);
    }

    public function test_the_status_follows_the_coverage(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createRequirement(['code' => 'RF001']);
        $inProgress = $this->createRequirement(['code' => 'RF002']);
        $done = $this->createRequirement(['code' => 'RF003']);

        $passing = $this->createLinkedTestCase($done, $qa, 'APROVADO');
        $this->createLinkedTestCase($inProgress, $qa, 'APROVADO');
        $this->createLinkedTestCase($inProgress, $qa, 'REPROVADO');
        $passing->requirements()->attach($inProgress);

        $this->actingAs($qa)->get(route('requirements.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('requirements.0.status', 'pendente')
                ->where('requirements.1.status', 'em_andamento')
                ->where('requirements.2.status', 'concluido')
            );
    }

    public function test_a_case_that_failed_and_then_passed_counts_as_passed(): void
    {
        $qa = $this->createProjectMember('qa');
        $requirement = $this->createRequirement();
        $testCase = $this->createLinkedTestCase($requirement, $qa, 'REPROVADO');
        $testCase->executions()->create(['executed_by' => $qa->id, 'status' => 'APROVADO', 'execution_date' => now()->addMinute()]);

        $this->actingAs($qa)->get(route('requirements.index'))
            ->assertInertia(fn (Assert $page) => $page->where('requirements.0.status', 'concluido'));
    }

    /**
     * Link a new test case to the requirement, with one run of the given result.
     */
    private function createLinkedTestCase(Requirement $requirement, User $user, string $result): TestCaseModel
    {
        $testCase = TestCaseModel::create(['title' => 'Caso', 'created_by' => $user->id, 'project_id' => $this->project->id]);
        $testCase->requirements()->attach($requirement);
        $testCase->executions()->create(['executed_by' => $user->id, 'status' => $result, 'execution_date' => now()]);

        return $testCase;
    }

    public function test_an_administrator_can_delete_a_requirement(): void
    {
        $admin = $this->createProjectMember('admin');
        $requirement = $this->createRequirement();

        $response = $this->actingAs($admin)->delete(route('requirements.destroy', $requirement));

        $response->assertRedirect();
        $this->assertDatabaseMissing('requirements', ['id' => $requirement->id]);
    }

    public function test_a_requirement_without_linked_cases_is_deleted_for_good(): void
    {
        $admin = $this->createProjectMember('admin');
        $requirement = $this->createRequirement();

        $this->actingAs($admin)
            ->followingRedirects()
            ->delete(route('requirements.destroy', $requirement))
            ->assertInertia(fn (Assert $page) => $page
                ->hasFlash('toast.message', 'Requirement "RF001" deleted.')
                ->missing('flash.toast.undo')
            );

        $this->assertNull(Requirement::withTrashed()->find($requirement->id));
    }

    public function test_a_requirement_with_linked_cases_is_archived_and_keeps_its_links(): void
    {
        $admin = $this->createProjectMember('admin');
        $requirement = $this->createRequirement();
        $testCase = $this->createLinkedTestCase($requirement, $admin, 'APROVADO');

        $this->actingAs($admin)
            ->from(route('requirements.index'))
            ->followingRedirects()
            ->delete(route('requirements.destroy', $requirement))
            ->assertInertia(fn (Assert $page) => $page
                ->hasFlash('toast.message', 'Requirement "RF001" archived.')
                ->hasFlash('toast.undo.url', route('requirements.restore', $requirement, false))
                ->hasFlash('toast.undo.method', 'patch')
                ->has('requirements', 0)
            );

        $this->assertSoftDeleted($requirement);
        $this->assertDatabaseHas('test_case_requirements', ['test_case_id' => $testCase->id, 'requirement_id' => $requirement->id]);
        $this->assertCount(0, $testCase->fresh()->requirements);
    }

    public function test_restoring_brings_the_requirement_and_its_links_back(): void
    {
        $admin = $this->createProjectMember('admin');
        $requirement = $this->createRequirement(['code' => 'AAAAAAA-1']);
        $requirement->forceFill(['jira_synced_at' => now()])->save();
        $testCase = $this->createLinkedTestCase($requirement, $admin, 'APROVADO');
        $this->actingAs($admin)->delete(route('requirements.destroy', $requirement));

        $this->actingAs($admin)
            ->followingRedirects()
            ->patch(route('requirements.restore', $requirement))
            ->assertInertia(fn (Assert $page) => $page->hasFlash('toast.message', 'Requirement "AAAAAAA-1" restored.'));

        $this->assertNotSoftDeleted($requirement);
        $this->assertSame([$requirement->id], $testCase->fresh()->requirements->pluck('id')->all());
        $this->assertDatabaseCount('jira_excluded_issues', 0);
    }

    public function test_requirements_of_another_project_cannot_be_restored(): void
    {
        $admin = $this->createProjectMember('admin');
        $foreignRequirement = $this->createRequirement([], Project::factory()->create());
        $foreignRequirement->delete();

        $this->actingAs($admin)->patch(route('requirements.restore', $foreignRequirement))->assertNotFound();

        $this->assertSoftDeleted($foreignRequirement);
    }

    public function test_an_archived_requirement_cannot_be_linked_to_a_test_case(): void
    {
        $qa = $this->createProjectMember('qa');
        $requirement = $this->createRequirement();
        $requirement->delete();

        $this->actingAs($qa)->post(route('test-cases.store'), [
            'title' => 'Login',
            'tested_method' => 'AuthController@login',
            'steps' => [['description' => 'Entrar', 'expected_result' => 'Logado']],
            'requirement_ids' => [$requirement->id],
        ])->assertSessionHasErrors('requirement_ids.0');
    }

    public function test_requirements_of_another_project_cannot_be_changed(): void
    {
        $admin = $this->createProjectMember('admin');
        $foreignRequirement = $this->createRequirement([], Project::factory()->create());

        $this->actingAs($admin)->put(route('requirements.update', $foreignRequirement), [
            'code' => 'RF001',
            'type' => 'funcional',
            'title' => 'Alterado',
            'priority' => 'media',
        ])->assertNotFound();
        $this->actingAs($admin)->delete(route('requirements.destroy', $foreignRequirement))->assertNotFound();

        $this->assertDatabaseHas('requirements', ['id' => $foreignRequirement->id, 'title' => 'Cadastro']);
    }

    public function test_deleting_a_requirement_imported_from_jira_remembers_the_issue(): void
    {
        $admin = $this->createProjectMember('admin');
        $requirement = $this->createRequirement(['code' => 'AAAAAAA-1', 'title' => 'Do Jira']);
        $requirement->forceFill(['jira_synced_at' => now()])->save();

        $this->actingAs($admin)->delete(route('requirements.destroy', $requirement));

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
