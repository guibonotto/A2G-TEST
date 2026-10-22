<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestCaseStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssignedToMeTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private User $qa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->withSession(['current_project_id' => $this->project->id]);
        $this->qa = $this->createUserWithRole('qa', $this->project);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCase(string $title, array $attributes = []): TestCaseModel
    {
        return TestCaseModel::create([
            'title' => $title,
            'created_by' => $this->qa->id,
            'project_id' => $this->project->id,
            'assigned_to' => $this->qa->id,
            ...$attributes,
        ]);
    }

    private function recordExecution(TestCaseModel $testCase, string $status): void
    {
        $testCase->executions()->create([
            'executed_by' => $this->qa->id,
            'status' => $status,
            'execution_date' => now(),
        ]);
    }

    public function test_guests_cannot_see_the_page(): void
    {
        $this->get(route('test-cases.assigned'))->assertRedirect(route('login'));
    }

    public function test_cases_still_to_run_are_separated_from_passed_ones(): void
    {
        $notRun = $this->createCase('Nunca executado');
        $failed = $this->createCase('Falhou');
        $this->recordExecution($failed, 'REPROVADO');
        $blocked = $this->createCase('Bloqueado');
        $this->recordExecution($blocked, 'BLOQUEADO');
        $passed = $this->createCase('Passou');
        $this->recordExecution($passed, 'APROVADO');

        $this->actingAs($this->qa)->get(route('test-cases.assigned'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('test-cases/assigned')
                ->has('toRun', 3)
                ->where('toRun.0.id', $notRun->id)
                ->has('passed', 1)
                ->where('passed.0.id', $passed->id)
            );
    }

    public function test_a_failed_case_that_later_passes_is_no_longer_to_run(): void
    {
        $testCase = $this->createCase('Corrigido');
        $this->recordExecution($testCase, 'REPROVADO');
        $testCase->executions()->create([
            'executed_by' => $this->qa->id,
            'status' => 'APROVADO',
            'execution_date' => now()->addMinute(),
        ]);

        $this->actingAs($this->qa)->get(route('test-cases.assigned'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('toRun', 0)
                ->has('passed', 1)
            );
    }

    public function test_only_my_cases_in_the_current_project_are_listed(): void
    {
        $this->createCase('Meu');
        $this->createCase('De outra pessoa', ['assigned_to' => $this->createUserWithRole('qa')->id]);
        $this->createCase('Sem responsável', ['assigned_to' => null]);
        $this->createCase('De outro projeto', ['project_id' => Project::factory()->create()->id]);

        $this->actingAs($this->qa)->get(route('test-cases.assigned'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('toRun', 1)
                ->where('toRun.0.title', 'Meu')
            );
    }

    public function test_obsolete_cases_are_left_out(): void
    {
        $obsolete = TestCaseStatus::firstOrCreate(['name' => 'Obsolete'], ['color' => 'secondary']);
        $this->createCase('Obsoleto', ['status_id' => $obsolete->id]);

        $this->actingAs($this->qa)->get(route('test-cases.assigned'))
            ->assertInertia(fn (Assert $page) => $page->has('toRun', 0));
    }

    public function test_the_sidebar_counts_what_is_still_to_run(): void
    {
        $this->createCase('A executar');
        $passed = $this->createCase('Passou');
        $this->recordExecution($passed, 'APROVADO');

        $this->actingAs($this->qa)->get(route('test-cases.assigned'))
            ->assertInertia(fn (Assert $page) => $page->where('assignedToMeCount', 1));
    }

    public function test_assigning_tells_who_received_the_case(): void
    {
        $developer = $this->createUserWithRole('developer');
        $developer->update(['name' => 'Fulano de Tal']);
        $this->project->members()->syncWithoutDetaching($developer);
        $testCase = $this->createCase('Login', ['assigned_to' => null]);

        $this->actingAs($this->qa)
            ->followingRedirects()
            ->patch(route('test-cases.assign', $testCase), ['assigned_to' => $developer->id])
            ->assertInertia(fn (Assert $page) => $page->hasFlash(
                'toast.message',
                'Test case assigned to Fulano de Tal. It now shows up in their "Assigned to me" list.',
            ));
    }

    public function test_the_assignment_toast_offers_to_restore_the_previous_assignee(): void
    {
        $developer = $this->createUserWithRole('developer');
        $this->project->members()->syncWithoutDetaching($developer);
        $testCase = $this->createCase('Login');

        $this->actingAs($this->qa)
            ->followingRedirects()
            ->patch(route('test-cases.assign', $testCase), ['assigned_to' => $developer->id])
            ->assertInertia(fn (Assert $page) => $page
                ->hasFlash('toast.undo.url', route('test-cases.assign', $testCase, false))
                ->hasFlash('toast.undo.method', 'patch')
                ->hasFlash('toast.undo.data.assigned_to', $this->qa->id)
            );
    }

    public function test_undoing_restores_the_previous_assignee(): void
    {
        $developer = $this->createUserWithRole('developer');
        $this->project->members()->syncWithoutDetaching($developer);
        $testCase = $this->createCase('Login');

        $this->actingAs($this->qa)->patch(route('test-cases.assign', $testCase), ['assigned_to' => $developer->id]);
        $this->actingAs($this->qa)->patch(route('test-cases.assign', $testCase), ['assigned_to' => $this->qa->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->qa->id, $testCase->fresh()->assigned_to);
    }

    public function test_undoing_an_assignment_to_nobody_leaves_the_case_unassigned(): void
    {
        $developer = $this->createUserWithRole('developer');
        $this->project->members()->syncWithoutDetaching($developer);
        $testCase = $this->createCase('Login', ['assigned_to' => null]);

        $this->actingAs($this->qa)
            ->followingRedirects()
            ->patch(route('test-cases.assign', $testCase), ['assigned_to' => $developer->id])
            ->assertInertia(fn (Assert $page) => $page->hasFlash('toast.undo.data.assigned_to', null));
    }

    public function test_no_undo_is_offered_when_the_assignee_did_not_change(): void
    {
        $testCase = $this->createCase('Login');

        $this->actingAs($this->qa)
            ->followingRedirects()
            ->patch(route('test-cases.assign', $testCase), ['assigned_to' => $this->qa->id])
            ->assertInertia(fn (Assert $page) => $page->hasFlash('toast.undo', null));
    }

    public function test_undo_is_refused_when_the_previous_assignee_left_the_project(): void
    {
        $leaver = $this->createUserWithRole('developer');
        $this->project->members()->syncWithoutDetaching($leaver);
        $testCase = $this->createCase('Login', ['assigned_to' => $leaver->id]);

        $this->actingAs($this->qa)->patch(route('test-cases.assign', $testCase), ['assigned_to' => $this->qa->id]);
        $this->project->members()->detach($leaver);

        $this->actingAs($this->qa)->patch(route('test-cases.assign', $testCase), ['assigned_to' => $leaver->id])
            ->assertSessionHasErrors('assigned_to');

        $this->assertSame($this->qa->id, $testCase->fresh()->assigned_to);
    }
}
