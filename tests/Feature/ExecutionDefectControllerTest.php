<?php

namespace Tests\Feature;

use App\Models\Evidence;
use App\Models\Execution;
use App\Models\JiraIntegration;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\TestCase as TestCaseModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExecutionDefectControllerTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private TestCaseModel $testCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->withSession(['current_project_id' => $this->project->id]);

        $this->testCase = TestCaseModel::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Validates a user password',
            'tested_method' => 'UserService.validatePassword()',
        ]);
    }

    private function member(string $role = 'qa'): User
    {
        return $this->createUserWithRole($role, $this->project);
    }

    private function connectJira(bool $withWriteAccess = true): JiraIntegration
    {
        $factory = JiraIntegration::factory();

        return ($withWriteAccess ? $factory->withWriteAccess() : $factory)->create([
            'project_id' => $this->project->id,
            'site_url' => 'https://example.atlassian.net',
        ]);
    }

    private function failedExecution(array $attributes = []): Execution
    {
        return Execution::factory()->failed()->create([
            'test_case_id' => $this->testCase->id,
            'obtained_output' => 'Returned true for an empty password',
            'test_case_version' => 1,
            'test_case_snapshot' => [
                'title' => 'Validates a user password',
                'tested_method' => 'UserService.validatePassword()',
                'description' => null,
                'input' => 'An empty password',
                'expected_output' => 'Returns false',
            ],
            ...$attributes,
        ]);
    }

    private function report(Execution $execution, array $data = []): TestResponse
    {
        return $this->post(route('executions.defect.store', $execution), [
            'jira_project_key' => 'SHOP',
            'summary' => 'Test failed: Validates a user password',
            'attach_evidences' => true,
            ...$data,
        ]);
    }

    public function test_a_failed_execution_is_reported_as_a_jira_bug(): void
    {
        $this->connectJira();
        $execution = $this->failedExecution();
        $requirement = Requirement::factory()->fromJiraIssue('SHOP-3')->create([
            'project_id' => $this->project->id,
            'jira_synced_at' => now(),
        ]);
        $this->testCase->requirements()->attach($requirement);
        $tester = $this->member();

        Http::fake(['*/rest/api/3/issue' => Http::response(['id' => '10001', 'key' => 'SHOP-12'], 201)]);

        $this->actingAs($tester)->report($execution)
            ->assertRedirect(route('test-cases.show', $this->testCase));

        Http::assertSent(function (Request $request): bool {
            $fields = $request['fields'];
            $description = json_encode($fields['description']);

            return $request->method() === 'POST'
                && $fields['project']['key'] === 'SHOP'
                && $fields['issuetype']['name'] === 'Bug'
                && $fields['summary'] === 'Test failed: Validates a user password'
                && str_contains($description, 'UserService.validatePassword()')
                && str_contains($description, 'An empty password')
                && str_contains($description, 'Returns false')
                && str_contains($description, 'Returned true for an empty password')
                && str_contains($description, 'https:\/\/example.atlassian.net\/browse\/SHOP-3');
        });

        $execution->refresh();
        $this->assertSame('SHOP-12', $execution->jira_defect_key);
        $this->assertFalse($execution->jira_defect_resolved);
        $this->assertSame($tester->id, $execution->jira_defect_reported_by);
    }

    public function test_the_evidences_are_attached_to_the_bug(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('evidences/1/screen.png', 'png-bytes');

        $this->connectJira();
        $execution = $this->failedExecution();
        Evidence::factory()->create([
            'execution_id' => $execution->id,
            'file_name' => 'screen.png',
            'file_path' => 'evidences/1/screen.png',
        ]);

        Http::fake([
            '*/rest/api/3/issue/SHOP-12/attachments' => Http::response([]),
            '*/rest/api/3/issue' => Http::response(['key' => 'SHOP-12'], 201),
        ]);

        $this->actingAs($this->member())->report($execution);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/issue/SHOP-12/attachments')
            && $request->hasHeader('X-Atlassian-Token', 'no-check'));
    }

    public function test_a_failed_upload_keeps_the_bug_and_warns(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('evidences/1/screen.png', 'png-bytes');

        $this->connectJira();
        $execution = $this->failedExecution();
        Evidence::factory()->create(['execution_id' => $execution->id, 'file_path' => 'evidences/1/screen.png']);

        Http::fake([
            '*/rest/api/3/issue/SHOP-12/attachments' => Http::response([], 500),
            '*/rest/api/3/issue' => Http::response(['key' => 'SHOP-12'], 201),
        ]);

        $this->actingAs($this->member())->followingRedirects()->report($execution)
            ->assertInertia(fn (Assert $page) => $page
                ->hasFlash('toast.type', 'warning')
                ->hasFlash('toast.message', 'Defect SHOP-12 created in Jira, but 1 evidence file(s) could not be attached.'));

        $this->assertSame('SHOP-12', $execution->refresh()->jira_defect_key);
    }

    public function test_evidences_are_not_sent_when_the_tester_unticks_them(): void
    {
        $this->connectJira();
        $execution = $this->failedExecution();
        Evidence::factory()->create(['execution_id' => $execution->id]);

        Http::fake(['*/rest/api/3/issue' => Http::response(['key' => 'SHOP-12'], 201)]);

        $this->actingAs($this->member())->report($execution, ['attach_evidences' => false]);

        Http::assertSentCount(1);
    }

    public function test_viewers_cannot_report_defects(): void
    {
        $this->connectJira();
        $execution = $this->failedExecution();
        Http::fake();

        $this->actingAs($this->member('viewer'))->report($execution)->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_only_failed_executions_can_be_reported(): void
    {
        $this->connectJira();
        $execution = $this->failedExecution(['status' => 'APROVADO']);
        Http::fake();

        $this->actingAs($this->member())->report($execution)->assertSessionHasErrors('execution');

        Http::assertNothingSent();
    }

    public function test_an_execution_is_reported_only_once(): void
    {
        $this->connectJira();
        $execution = $this->failedExecution(['jira_defect_key' => 'SHOP-12']);
        Http::fake();

        $this->actingAs($this->member())->report($execution)->assertSessionHasErrors('execution');

        Http::assertNothingSent();
    }

    public function test_a_read_only_connection_cannot_report_defects(): void
    {
        $this->connectJira(withWriteAccess: false);
        $execution = $this->failedExecution();
        Http::fake();

        $this->actingAs($this->member())->report($execution)->assertSessionHasErrors('execution');

        Http::assertNothingSent();
    }

    public function test_the_jira_error_is_shown_when_the_bug_is_rejected(): void
    {
        $this->connectJira();
        $execution = $this->failedExecution();

        Http::fake(['*/rest/api/3/issue' => Http::response([
            'errorMessages' => [],
            'errors' => ['issuetype' => 'Specify a valid issue type'],
        ], 400)]);

        $this->actingAs($this->member())->report($execution)
            ->assertSessionHasErrors(['execution' => 'Jira could not create the defect. Specify a valid issue type']);

        $this->assertNull($execution->refresh()->jira_defect_key);
    }

    public function test_the_project_key_and_summary_are_required(): void
    {
        $this->connectJira();
        $execution = $this->failedExecution();

        $this->actingAs($this->member())
            ->report($execution, ['jira_project_key' => '', 'summary' => ''])
            ->assertSessionHasErrors(['jira_project_key', 'summary']);
    }

    public function test_the_test_case_page_reads_the_defect_status_from_jira(): void
    {
        $this->connectJira();
        $execution = $this->failedExecution(['jira_defect_key' => 'SHOP-12']);

        Http::fake(['*/rest/api/3/search/jql*' => Http::response(['issues' => [[
            'key' => 'SHOP-12',
            'fields' => ['status' => ['name' => 'Done', 'statusCategory' => ['key' => 'done']]],
        ]]])]);

        $this->actingAs($this->member())->get(route('test-cases.show', $this->testCase))
            ->assertInertia(fn (Assert $page) => $page
                ->where('testCase.executions.0.jira_defect_status', 'Done')
                ->where('testCase.executions.0.jira_defect_resolved', true));

        $this->assertTrue($execution->refresh()->jira_defect_resolved);
    }

    public function test_the_test_case_page_still_opens_when_jira_is_unreachable(): void
    {
        $this->connectJira();
        $this->failedExecution(['jira_defect_key' => 'SHOP-12', 'jira_defect_status' => 'To Do']);

        Http::fake(['*' => Http::response([], 503)]);

        $this->actingAs($this->member())->get(route('test-cases.show', $this->testCase))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('testCase.executions.0.jira_defect_status', 'To Do'));
    }

    public function test_the_test_case_page_tells_whether_defects_can_be_reported(): void
    {
        $this->connectJira(withWriteAccess: false);
        $requirement = Requirement::factory()->fromJiraIssue('SHOP-3')->create([
            'project_id' => $this->project->id,
            'jira_synced_at' => now(),
        ]);
        $this->testCase->requirements()->attach($requirement);

        $this->actingAs($this->member())->get(route('test-cases.show', $this->testCase))
            ->assertInertia(fn (Assert $page) => $page
                ->where('jiraDefects.canReport', false)
                ->where('jiraDefects.connected', true)
                ->where('jiraDefects.defaultProjectKey', 'SHOP')
                ->missing('jiraProjects'));
    }

    public function test_jira_projects_are_loaded_only_when_asked_for(): void
    {
        $this->connectJira();
        Http::fake(['*/rest/api/3/project/search' => Http::response(['values' => [
            ['key' => 'SHOP', 'name' => 'Shop'],
        ]])]);

        $this->actingAs($this->member())->get(route('test-cases.show', $this->testCase))
            ->assertInertia(fn (Assert $page) => $page
                ->where('jiraDefects.canReport', true)
                ->missing('jiraProjects')
                ->reloadOnly('jiraProjects', fn (Assert $reload) => $reload
                    ->where('jiraProjects.0.key', 'SHOP')));
    }
}
