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

        $this->qa = $this->createUserWithRole('qa');
        $this->project = Project::factory()->create();
        $this->project->members()->attach($this->qa);
        $this->withSession(['current_project_id' => $this->project->id]);
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
        $this->project->members()->attach($developer);
        $testCase = $this->createCase('Login', ['assigned_to' => null]);

        $this->actingAs($this->qa)
            ->followingRedirects()
            ->patch(route('test-cases.assign', $testCase), ['assigned_to' => $developer->id])
            ->assertInertia(fn (Assert $page) => $page->hasFlash(
                'toast.message',
                'Test case assigned to Fulano de Tal. It now shows up in their "Assigned to me" list.',
            ));
    }
}
