<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Execution;
use App\Models\JiraIntegration;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestCaseStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_without_a_project_are_redirected_to_projects(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('projects.index'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user);
        $this->withSession(['current_project_id' => $project->id]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_counts_only_the_active_project_requirements(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user);
        $otherProject = Project::factory()->create();

        Requirement::create(['project_id' => $project->id, 'code' => 'RF001', 'type' => 'funcional', 'title' => 'Cadastro']);
        Requirement::create(['project_id' => $project->id, 'code' => 'RF002', 'type' => 'funcional', 'title' => 'Login']);
        Requirement::create(['project_id' => $otherProject->id, 'code' => 'RF001', 'type' => 'funcional', 'title' => 'De outro projeto']);
        $archived = Requirement::create(['project_id' => $project->id, 'code' => 'RF003', 'type' => 'funcional', 'title' => 'Arquivado']);
        $archived->delete();

        $this->withSession(['current_project_id' => $project->id])
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('stats.total', 0)
                ->where('requirementsCount', 2)
            );
    }

    public function test_an_empty_project_reports_no_requirements(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user);

        $this->withSession(['current_project_id' => $project->id])
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('requirementsCount', 0));
    }

    public function test_classification_breakdown_counts_unclassified_test_cases(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user);
        $this->withSession(['current_project_id' => $project->id]);
        $this->actingAs($user);

        $classification = Classification::firstOrCreate(['name' => 'Unitário']);
        TestCaseModel::create([
            'title' => 'Classificado',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
            'project_id' => $project->id,
        ]);
        TestCaseModel::create([
            'title' => 'Sem classificação',
            'created_by' => $user->id,
            'project_id' => $project->id,
        ]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('classificationBreakdown.0.name', 'Unitário')
                ->where('classificationBreakdown.0.total', 1)
                ->where('classificationBreakdown.1.name', 'Unclassified')
                ->where('classificationBreakdown.1.total', 1)
            );
    }

    public function test_classification_breakdown_has_no_unclassified_entry_when_every_case_is_classified(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user);
        $this->withSession(['current_project_id' => $project->id]);
        $this->actingAs($user);

        $classification = Classification::firstOrCreate(['name' => 'Unitário']);
        TestCaseModel::create([
            'title' => 'Classificado',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
            'project_id' => $project->id,
        ]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('classificationBreakdown', 1));
    }

    public function test_dashboard_exposes_aggregated_metrics(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user);
        $this->withSession(['current_project_id' => $project->id]);
        $this->actingAs($user);

        $approvedStatus = TestCaseStatus::firstOrCreate(['project_id' => $project->id, 'name' => 'Aprovado'], ['color' => 'success']);
        $pendingStatus = TestCaseStatus::firstOrCreate(['project_id' => $project->id, 'name' => 'Pendente'], ['color' => 'warning']);
        $classification = Classification::firstOrCreate(['name' => 'Unitário']);

        TestCaseModel::create([
            'title' => 'Login válido',
            'classification_id' => $classification->id,
            'status_id' => $approvedStatus->id,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
            'project_id' => $project->id,
        ]);

        TestCaseModel::create([
            'title' => 'Cadastro válido',
            'classification_id' => $classification->id,
            'status_id' => $pendingStatus->id,
            'created_by' => $user->id,
            'project_id' => $project->id,
        ]);

        $cases = TestCaseModel::where('project_id', $project->id)->orderBy('id')->get();
        Execution::create([
            'test_case_id' => $cases[0]->id,
            'executed_by' => $user->id,
            'status' => 'APROVADO',
            'execution_date' => now()->subMinute(),
        ]);
        Execution::create([
            'test_case_id' => $cases[1]->id,
            'executed_by' => $user->id,
            'status' => 'REPROVADO',
            'execution_date' => now(),
        ]);
        TestCaseModel::create([
            'title' => 'Logout válido',
            'classification_id' => $classification->id,
            'status_id' => $pendingStatus->id,
            'created_by' => $user->id,
            'project_id' => $project->id,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('stats.total', 3)
                ->where('stats.unassigned', 2)
                ->where('stats.statusesInUse', 2)
                ->has('statusBreakdown', 3)
                ->where('statusBreakdown.0.name', 'Passed')
                ->where('statusBreakdown.0.total', 1)
                ->where('statusBreakdown.1.name', 'Failed')
                ->where('statusBreakdown.1.total', 1)
                ->where('statusBreakdown.2.name', 'Not executed')
                ->where('statusBreakdown.2.total', 1)
                ->where('classificationBreakdown.0.total', 3)
                ->has('workload', 2)
                ->where('workload.0.name', 'Sem responsável')
                ->where('workload.0.total', 2)
                ->where('workload.1.total', 1)
                ->has('creationTrend')
            );
    }

    public function test_dashboard_metrics_are_scoped_to_the_active_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user);
        $this->withSession(['current_project_id' => $project->id]);
        $this->actingAs($user);

        $otherProject = Project::factory()->create();
        $classification = Classification::firstOrCreate(['name' => 'Unitário']);

        TestCaseModel::create([
            'title' => 'Caso de outro projeto',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
            'project_id' => $otherProject->id,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('stats.total', 0)
            );
    }

    /**
     * A project with Jira connected, a member acting in it, and a test case.
     *
     * @return array{0: Project, 1: TestCaseModel}
     */
    private function projectWithJira(): array
    {
        $project = Project::factory()->create();
        JiraIntegration::factory()->create([
            'project_id' => $project->id,
            'site_url' => 'https://example.atlassian.net',
        ]);
        $user = User::factory()->create();
        $project->members()->attach($user);
        $this->withSession(['current_project_id' => $project->id])->actingAs($user);

        $testCase = TestCaseModel::factory()->create(['project_id' => $project->id, 'title' => 'Validates a password']);

        return [$project, $testCase];
    }

    private function reportedDefect(TestCaseModel $testCase, string $key, array $attributes = []): Execution
    {
        return Execution::factory()->failed()->create([
            'test_case_id' => $testCase->id,
            'jira_defect_key' => $key,
            ...$attributes,
        ]);
    }

    public function test_open_defects_are_loaded_after_the_page(): void
    {
        [, $testCase] = $this->projectWithJira();
        $this->reportedDefect($testCase, 'SHOP-12');
        Http::fake();

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->missing('openDefects'));

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'atlassian'));
    }

    public function test_open_defects_are_grouped_by_the_requirement_they_affect(): void
    {
        [$project, $testCase] = $this->projectWithJira();
        $requirement = Requirement::factory()->fromJiraIssue('SHOP-3')->create([
            'project_id' => $project->id,
            'title' => 'Password rules',
            'jira_synced_at' => now(),
        ]);
        $testCase->requirements()->attach($requirement);
        $this->reportedDefect($testCase, 'SHOP-12');

        Http::fake(['*/rest/api/3/search/jql*' => Http::response(['issues' => [[
            'key' => 'SHOP-12',
            'fields' => ['status' => ['name' => 'In Progress', 'statusCategory' => ['key' => 'indeterminate']]],
        ]]])]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('openDefects.total', 1)
                ->where('openDefects.groups.0.requirement.code', 'SHOP-3')
                ->where('openDefects.groups.0.requirement.url', 'https://example.atlassian.net/browse/SHOP-3')
                ->where('openDefects.groups.0.defects.0.key', 'SHOP-12')
                ->where('openDefects.groups.0.defects.0.status', 'In Progress')
                ->where('openDefects.groups.0.defects.0.url', 'https://example.atlassian.net/browse/SHOP-12')
                ->where('openDefects.groups.0.defects.0.testCase.title', 'Validates a password')));
    }

    public function test_defects_resolved_in_jira_leave_the_dashboard(): void
    {
        [, $testCase] = $this->projectWithJira();
        $execution = $this->reportedDefect($testCase, 'SHOP-12');

        Http::fake(['*/rest/api/3/search/jql*' => Http::response(['issues' => [[
            'key' => 'SHOP-12',
            'fields' => ['status' => ['name' => 'Done', 'statusCategory' => ['key' => 'done']]],
        ]]])]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('openDefects.total', 0)
                ->where('openDefects.groups', [])));

        $this->assertTrue($execution->refresh()->jira_defect_resolved);
    }

    public function test_a_defect_counts_once_even_when_it_affects_several_requirements(): void
    {
        [$project, $testCase] = $this->projectWithJira();
        $testCase->requirements()->attach(Requirement::factory()->count(2)->create(['project_id' => $project->id]));
        $this->reportedDefect($testCase, 'SHOP-12');
        Http::fake(['*' => Http::response(['issues' => []])]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('openDefects.total', 1)
                ->has('openDefects.groups', 2)));
    }

    public function test_defects_of_cases_without_requirements_are_listed_last(): void
    {
        [$project, $testCase] = $this->projectWithJira();
        $testCase->requirements()->attach(Requirement::factory()->create(['project_id' => $project->id, 'code' => 'RF-001']));
        $orphanCase = TestCaseModel::factory()->create(['project_id' => $project->id]);
        $this->reportedDefect($orphanCase, 'SHOP-20');
        $this->reportedDefect($testCase, 'SHOP-12');
        Http::fake(['*' => Http::response(['issues' => []])]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('openDefects.groups.0.requirement.code', 'RF-001')
                ->where('openDefects.groups.1.requirement', null)
                ->where('openDefects.groups.1.defects.0.key', 'SHOP-20')));
    }

    public function test_open_defects_keep_the_last_known_status_when_jira_is_unreachable(): void
    {
        [, $testCase] = $this->projectWithJira();
        $this->reportedDefect($testCase, 'SHOP-12', ['jira_defect_status' => 'To Do']);
        Http::fake(['*' => Http::response([], 503)]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('openDefects.total', 1)
                ->where('openDefects.groups.0.defects.0.status', 'To Do')));
    }

    public function test_open_defects_ignore_other_projects_and_archived_cases(): void
    {
        [, $testCase] = $this->projectWithJira();
        $this->reportedDefect(TestCaseModel::factory()->create(), 'OTHER-1');
        $archived = TestCaseModel::factory()->create(['project_id' => $testCase->project_id]);
        $this->reportedDefect($archived, 'SHOP-30');
        $archived->delete();
        Http::fake(['*' => Http::response(['issues' => []])]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('openDefects.total', 0)));
    }

    public function test_guests_cannot_open_the_report(): void
    {
        $this->get(route('dashboard.report'))->assertRedirect(route('login'));
    }

    public function test_users_outside_the_project_cannot_open_its_report(): void
    {
        $project = Project::factory()->create();
        TestCaseModel::factory()->create(['project_id' => $project->id, 'title' => 'Secret case']);

        $this->withSession(['current_project_id' => $project->id])
            ->actingAs(User::factory()->create())
            ->get(route('dashboard.report'))
            ->assertRedirect(route('projects.index'));
    }

    public function test_the_report_shows_the_summary_and_one_row_per_test_case(): void
    {
        [$project] = $this->projectWithFailedRunAndDefect();
        TestCaseModel::factory()->create(['project_id' => $project->id, 'title' => 'Never executed', 'assigned_to' => null]);

        $this->get(route('dashboard.report'))
            ->assertOk()
            ->assertViewIs('reports.test-report')
            ->assertViewHas('summary', fn (array $summary): bool => $summary['Test cases'] === 2
                && $summary['Failed'] === 1
                && $summary['Passed'] === 0
                && $summary['Not executed'] === 1
                && $summary['Open Jira defects'] === 1)
            ->assertSee($project->name)
            ->assertSeeInOrder(['Test cases', 'Passed', 'Failed', 'Blocked', 'Pending', 'Not executed', 'Unassigned', 'Open Jira defects'])
            ->assertSeeInOrder(['Validates a password', 'RF-001', 'Failed', 'Ana Tester', 'SHOP-9', 'To Do'])
            ->assertSeeInOrder(['Never executed', 'Unassigned', 'Not executed'])
            ->assertSee('https://example.atlassian.net/browse/SHOP-9', false);
    }

    public function test_the_report_leaves_out_other_projects_and_archived_cases(): void
    {
        [$project] = $this->projectWithJira();
        TestCaseModel::factory()->create(['title' => 'From another project']);
        TestCaseModel::factory()->create(['project_id' => $project->id, 'title' => 'Archived case'])->delete();

        $this->get(route('dashboard.report'))
            ->assertSee('Validates a password')
            ->assertDontSee('From another project')
            ->assertDontSee('Archived case');
    }

    /**
     * A project whose case failed on its latest run, with an open Bug in Jira.
     *
     * @return array{0: Project, 1: TestCaseModel}
     */
    private function projectWithFailedRunAndDefect(): array
    {
        [$project, $testCase] = $this->projectWithJira();
        $testCase->requirements()->attach(Requirement::factory()->create(['project_id' => $project->id, 'code' => 'RF-001']));
        Execution::factory()->create(['test_case_id' => $testCase->id, 'status' => 'APROVADO', 'execution_date' => now()->subDay()]);
        $executor = User::factory()->create(['name' => 'Ana Tester']);
        $this->reportedDefect($testCase, 'SHOP-9', ['executed_by' => $executor->id, 'jira_defect_status' => 'To Do', 'execution_date' => now()]);

        return [$project, $testCase];
    }
}
