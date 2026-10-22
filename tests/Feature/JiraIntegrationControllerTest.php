<?php

namespace Tests\Feature;

use App\Models\JiraExcludedIssue;
use App\Models\JiraIntegration;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JiraIntegrationControllerTest extends TestCase
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

    private function createIntegrationFor(User $user): JiraIntegration
    {
        return JiraIntegration::create([
            'project_id' => $this->project->id,
            'site_url' => 'https://example.atlassian.net',
            'cloud_id' => 'cloud-123',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => now()->addHour(),
            'connected_by' => $user->id,
            'connected_at' => now(),
        ]);
    }

    public function test_guests_cannot_access_jira_management(): void
    {
        $this->get(route('jira.edit'))->assertRedirect(route('login'));
        $this->delete(route('jira.destroy'))->assertRedirect(route('login'));
        $this->post(route('jira.import'))->assertRedirect(route('login'));
    }

    public function test_non_qa_users_cannot_access_jira_management(): void
    {
        $developer = $this->createProjectMember('developer');

        $this->actingAs($developer)->get(route('jira.edit'))->assertForbidden();
        $this->actingAs($developer)->delete(route('jira.destroy'))->assertForbidden();
        $this->actingAs($developer)->post(route('jira.import'))->assertForbidden();
    }

    public function test_redirect_sends_the_user_to_atlassian_with_a_state(): void
    {
        $admin = $this->createProjectMember('admin');

        $response = $this->actingAs($admin)->get(route('jira.redirect'));

        $response->assertRedirectContains('https://auth.atlassian.com/authorize');
        $this->assertNotNull(session('jira_oauth_state'));
    }

    public function test_callback_rejects_a_mismatched_state(): void
    {
        $admin = $this->createProjectMember('admin');

        $response = $this->actingAs($admin)
            ->withSession(['jira_oauth_state' => 'expected-state'])
            ->get(route('jira.callback', ['state' => 'wrong-state', 'code' => 'abc']));

        $response->assertForbidden();
    }

    public function test_callback_stores_the_connection_on_success(): void
    {
        $admin = $this->createProjectMember('admin');

        Http::fake([
            'auth.atlassian.com/oauth/token' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
            ]),
            'api.atlassian.com/oauth/token/accessible-resources' => Http::response([
                ['id' => 'cloud-123', 'url' => 'https://example.atlassian.net', 'name' => 'Example'],
            ]),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['jira_oauth_state' => 'expected-state'])
            ->get(route('jira.callback', ['state' => 'expected-state', 'code' => 'abc']));

        $response->assertRedirect(route('jira.edit'));
        $this->assertDatabaseHas('jira_integrations', [
            'project_id' => $this->project->id,
            'site_url' => 'https://example.atlassian.net',
            'cloud_id' => 'cloud-123',
            'connected_by' => $admin->id,
        ]);
    }

    public function test_connecting_keeps_the_connection_of_other_projects(): void
    {
        $admin = $this->createProjectMember('admin');
        $otherIntegration = JiraIntegration::factory()->create();

        Http::fake([
            'auth.atlassian.com/oauth/token' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
            ]),
            'api.atlassian.com/oauth/token/accessible-resources' => Http::response([
                ['id' => 'cloud-123', 'url' => 'https://example.atlassian.net', 'name' => 'Example'],
            ]),
        ]);

        $this->actingAs($admin)
            ->withSession(['jira_oauth_state' => 'expected-state'])
            ->get(route('jira.callback', ['state' => 'expected-state', 'code' => 'abc']));

        $this->assertDatabaseHas('jira_integrations', ['id' => $otherIntegration->id]);
        $this->assertDatabaseCount('jira_integrations', 2);
    }

    public function test_the_page_ignores_the_jira_connection_of_other_projects(): void
    {
        $qa = $this->createProjectMember('qa');
        JiraIntegration::factory()->create();

        $this->actingAs($qa)->get(route('jira.edit'))
            ->assertInertia(fn (Assert $page) => $page->where('integration', null));
    }

    public function test_disconnecting_only_removes_the_current_project_connection(): void
    {
        $admin = $this->createProjectMember('admin');
        $this->createIntegrationFor($admin);
        $otherIntegration = JiraIntegration::factory()->create();

        $this->actingAs($admin)->delete(route('jira.destroy'))->assertRedirect(route('jira.edit'));

        $this->assertDatabaseMissing('jira_integrations', ['project_id' => $this->project->id]);
        $this->assertDatabaseHas('jira_integrations', ['id' => $otherIntegration->id]);
    }

    public function test_import_treats_requirements_of_other_projects_as_new(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createIntegrationFor($qa);
        $otherProject = Project::factory()->create();
        Requirement::create(['project_id' => $otherProject->id, 'code' => 'PROJ-1', 'type' => 'funcional', 'title' => 'De outro projeto']);
        JiraExcludedIssue::create(['project_id' => $otherProject->id, 'issue_key' => 'PROJ-2']);

        Http::fake(['api.atlassian.com/*' => Http::response(['issues' => [
            ['key' => 'PROJ-1', 'fields' => ['summary' => 'Um']],
            ['key' => 'PROJ-2', 'fields' => ['summary' => 'Dois']],
        ]])]);

        $this->actingAs($qa)
            ->getJson(route('jira.preview', ['projectKey' => 'PROJ', 'issue_types' => ['Story']]))
            ->assertJsonPath('issues.0.state', 'new')
            ->assertJsonPath('issues.1.state', 'new');

        $this->actingAs($qa)->post(route('jira.import'), [
            'project_key' => 'PROJ',
            'issue_types' => ['Story'],
            'issue_keys' => ['PROJ-1', 'PROJ-2'],
        ]);

        $this->assertDatabaseHas('requirements', ['project_id' => $this->project->id, 'code' => 'PROJ-1', 'title' => 'Um']);
        $this->assertDatabaseHas('requirements', ['project_id' => $otherProject->id, 'code' => 'PROJ-1', 'title' => 'De outro projeto']);
        $this->assertDatabaseHas('jira_excluded_issues', ['project_id' => $otherProject->id, 'issue_key' => 'PROJ-2']);
    }

    public function test_qa_can_view_the_integration_page_when_connected(): void
    {
        $qa = $this->createProjectMember('qa');
        JiraIntegration::create([
            'project_id' => $this->project->id,
            'site_url' => 'https://example.atlassian.net',
            'cloud_id' => 'cloud-123',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => now()->addHour(),
            'connected_by' => $qa->id,
            'connected_at' => now(),
        ]);

        Http::fake([
            'api.atlassian.com/*' => Http::response([
                'values' => [['key' => 'PROJ', 'name' => 'Project']],
            ]),
        ]);

        $this->actingAs($qa)->get(route('jira.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('management/jira/edit')
                ->where('integration.site_url', 'https://example.atlassian.net')
                ->has('projects', 1)
                ->where('projects.0.key', 'PROJ')
            );
    }

    public function test_qa_cannot_connect_jira(): void
    {
        $qa = $this->createProjectMember('qa');

        $this->actingAs($qa)->get(route('jira.redirect'))->assertForbidden();
        $this->actingAs($qa)->get(route('jira.callback', ['code' => 'abc', 'state' => 'xyz']))->assertForbidden();

        $this->assertDatabaseCount('jira_integrations', 0);
    }

    public function test_qa_cannot_disconnect_jira_even_if_they_connected_it(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createIntegrationFor($qa);

        $this->actingAs($qa)->delete(route('jira.destroy'))->assertForbidden();

        $this->assertDatabaseCount('jira_integrations', 1);
    }

    public function test_the_page_tells_whether_the_user_can_connect(): void
    {
        $qa = $this->createProjectMember('qa');
        $admin = $this->createProjectMember('admin');

        $this->actingAs($qa)->get(route('jira.edit'))
            ->assertInertia(fn (Assert $page) => $page->where('can_connect', false));
        $this->actingAs($admin)->get(route('jira.edit'))
            ->assertInertia(fn (Assert $page) => $page->where('can_connect', true));
    }

    public function test_qa_cannot_disconnect_an_integration_connected_by_someone_else(): void
    {
        $connector = $this->createProjectMember('qa');
        $otherQa = $this->createProjectMember('qa');
        $this->createIntegrationFor($connector);

        $this->actingAs($otherQa)->delete(route('jira.destroy'))->assertForbidden();

        $this->assertDatabaseCount('jira_integrations', 1);
    }

    public function test_admin_can_disconnect_an_integration_connected_by_someone_else(): void
    {
        $connector = $this->createProjectMember('qa');
        $admin = $this->createProjectMember('admin');
        $this->createIntegrationFor($connector);

        $this->actingAs($admin)->delete(route('jira.destroy'))
            ->assertRedirect(route('jira.edit'));

        $this->assertDatabaseCount('jira_integrations', 0);
    }

    public function test_integration_page_tells_whether_the_user_can_disconnect(): void
    {
        $connector = $this->createProjectMember('qa');
        $this->createIntegrationFor($connector);

        Http::fake(['api.atlassian.com/*' => Http::response(['values' => []])]);

        $this->actingAs($connector)->get(route('jira.edit'))
            ->assertInertia(fn (Assert $page) => $page->where('integration.can_disconnect', false));

        $this->actingAs($this->createProjectMember('admin'))->get(route('jira.edit'))
            ->assertInertia(fn (Assert $page) => $page->where('integration.can_disconnect', true));
    }

    public function test_import_creates_requirements_from_jira_issues(): void
    {
        $qa = $this->createProjectMember('qa');
        JiraIntegration::create([
            'project_id' => $this->project->id,
            'site_url' => 'https://example.atlassian.net',
            'cloud_id' => 'cloud-123',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => now()->addHour(),
            'connected_by' => $qa->id,
            'connected_at' => now(),
        ]);

        Http::fake([
            'api.atlassian.com/*' => Http::response([
                'issues' => [
                    [
                        'key' => 'PROJ-1',
                        'fields' => [
                            'summary' => 'Login do usuário',
                            'description' => [
                                'content' => [
                                    ['content' => [['type' => 'text', 'text' => 'Deve permitir login com email e senha.']]],
                                ],
                            ],
                            'priority' => ['name' => 'High'],
                            'status' => ['statusCategory' => ['key' => 'indeterminate']],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($qa)->post(route('jira.import'), [
            'project_key' => 'PROJ',
            'issue_types' => ['Task', 'Story'],
            'issue_keys' => ['PROJ-1'],
        ]);

        $response->assertRedirect(route('jira.edit'));
        $this->assertDatabaseHas('requirements', [
            'code' => 'PROJ-1',
            'title' => 'Login do usuário',
            'description' => 'Deve permitir login com email e senha.',
            'priority' => 'alta',
            'status' => 'em_andamento',
            'created_by' => $qa->id,
        ]);
    }

    public function test_import_updates_an_existing_requirement_without_changing_its_creator(): void
    {
        $qa = $this->createProjectMember('qa');
        $originalCreator = $this->createProjectMember('developer');
        Requirement::create([
            'project_id' => $this->project->id,
            'code' => 'PROJ-1',
            'type' => 'funcional',
            'title' => 'Título antigo',
            'created_by' => $originalCreator->id,
        ]);

        JiraIntegration::create([
            'project_id' => $this->project->id,
            'site_url' => 'https://example.atlassian.net',
            'cloud_id' => 'cloud-123',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => now()->addHour(),
            'connected_by' => $qa->id,
            'connected_at' => now(),
        ]);

        Http::fake([
            'api.atlassian.com/*' => Http::response([
                'issues' => [
                    ['key' => 'PROJ-1', 'fields' => ['summary' => 'Título atualizado']],
                ],
            ]),
        ]);

        $this->actingAs($qa)->post(route('jira.import'), [
            'project_key' => 'PROJ',
            'issue_types' => ['Task'],
            'issue_keys' => ['PROJ-1'],
        ]);

        $this->assertDatabaseHas('requirements', [
            'code' => 'PROJ-1',
            'title' => 'Título atualizado',
            'created_by' => $originalCreator->id,
        ]);
        $this->assertDatabaseCount('requirements', 1);
    }

    public function test_import_requires_at_least_one_issue_type(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createIntegrationFor($qa);

        $response = $this->actingAs($qa)->post(route('jira.import'), [
            'project_key' => 'PROJ',
            'issue_types' => [],
        ]);

        $response->assertSessionHasErrors('issue_types');
        $this->assertDatabaseCount('requirements', 0);
    }

    public function test_import_only_asks_jira_for_the_selected_issue_types(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createIntegrationFor($qa);

        Http::fake([
            'api.atlassian.com/*' => Http::response(['issues' => []]),
        ]);

        $this->actingAs($qa)->post(route('jira.import'), [
            'project_key' => 'PROJ',
            'issue_types' => ['Epic', 'Story'],
            'issue_keys' => ['PROJ-1'],
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'search/jql')
                && str_contains(urldecode($request->url()), 'issuetype in ("Epic","Story")');
        });
    }

    public function test_preview_classifies_issues_as_new_existing_or_deleted_here(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createIntegrationFor($qa);
        Requirement::create(['project_id' => $this->project->id, 'code' => 'PROJ-1', 'type' => 'funcional', 'title' => 'Já importado']);
        JiraExcludedIssue::create(['project_id' => $this->project->id, 'issue_key' => 'PROJ-3']);

        Http::fake(['api.atlassian.com/*' => Http::response(['issues' => [
            ['key' => 'PROJ-1', 'fields' => ['summary' => 'Um', 'issuetype' => ['name' => 'Story']]],
            ['key' => 'PROJ-2', 'fields' => ['summary' => 'Dois', 'issuetype' => ['name' => 'Story']]],
            ['key' => 'PROJ-3', 'fields' => ['summary' => 'Três', 'issuetype' => ['name' => 'Story']]],
        ]])]);

        $this->actingAs($qa)
            ->getJson(route('jira.preview', ['projectKey' => 'PROJ', 'issue_types' => ['Story']]))
            ->assertOk()
            ->assertJsonPath('issues.0.state', 'existing')
            ->assertJsonPath('issues.1.state', 'new')
            ->assertJsonPath('issues.1.title', 'Dois')
            ->assertJsonPath('issues.2.state', 'deleted')
            ->assertJsonPath('truncated', false);

        $this->assertDatabaseMissing('requirements', ['code' => 'PROJ-2']);
    }

    public function test_preview_requires_issue_types(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createIntegrationFor($qa);

        $this->actingAs($qa)
            ->get(route('jira.preview', ['projectKey' => 'PROJ']))
            ->assertSessionHasErrors('issue_types');
    }

    public function test_non_qa_users_cannot_preview_an_import(): void
    {
        $developer = $this->createProjectMember('developer');

        $this->actingAs($developer)
            ->getJson(route('jira.preview', ['projectKey' => 'PROJ', 'issue_types' => ['Story']]))
            ->assertForbidden();
    }

    public function test_import_only_brings_the_selected_issues_and_records_the_sync_date(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createIntegrationFor($qa);

        Http::fake(['api.atlassian.com/*' => Http::response(['issues' => [
            ['key' => 'PROJ-1', 'fields' => ['summary' => 'Um']],
            ['key' => 'PROJ-2', 'fields' => ['summary' => 'Dois']],
        ]])]);

        $this->actingAs($qa)->post(route('jira.import'), [
            'project_key' => 'PROJ',
            'issue_types' => ['Story'],
            'issue_keys' => ['PROJ-2'],
        ])->assertRedirect(route('jira.edit'));

        $this->assertDatabaseMissing('requirements', ['code' => 'PROJ-1']);
        $this->assertNotNull(Requirement::query()->where('code', 'PROJ-2')->value('jira_synced_at'));
    }

    public function test_import_requires_at_least_one_selected_issue(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createIntegrationFor($qa);

        $this->actingAs($qa)->post(route('jira.import'), [
            'project_key' => 'PROJ',
            'issue_types' => ['Story'],
            'issue_keys' => [],
        ])->assertSessionHasErrors('issue_keys');
    }

    public function test_ticking_an_issue_deleted_here_brings_it_back(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createIntegrationFor($qa);
        JiraExcludedIssue::create(['project_id' => $this->project->id, 'issue_key' => 'PROJ-3']);

        Http::fake(['api.atlassian.com/*' => Http::response(['issues' => [
            ['key' => 'PROJ-3', 'fields' => ['summary' => 'Três']],
        ]])]);

        $this->actingAs($qa)->post(route('jira.import'), [
            'project_key' => 'PROJ',
            'issue_types' => ['Story'],
            'issue_keys' => ['PROJ-3'],
        ]);

        $this->assertDatabaseHas('requirements', ['code' => 'PROJ-3']);
        $this->assertDatabaseMissing('jira_excluded_issues', ['issue_key' => 'PROJ-3']);
    }

    public function test_integration_page_shows_the_last_import_date(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createIntegrationFor($qa);
        Requirement::create(['project_id' => $this->project->id, 'code' => 'PROJ-1', 'type' => 'funcional', 'title' => 'Um'])
            ->forceFill(['jira_synced_at' => now()])->save();

        Http::fake(['api.atlassian.com/*' => Http::response(['values' => []])]);

        $this->actingAs($qa)->get(route('jira.edit'))
            ->assertInertia(fn (Assert $page) => $page->whereNot('last_synced_at', null));
    }

    public function test_qa_can_fetch_issue_types_for_a_project(): void
    {
        $qa = $this->createProjectMember('qa');
        $this->createIntegrationFor($qa);

        Http::fake([
            'api.atlassian.com/*/project/PROJ' => Http::response(['id' => '10000', 'key' => 'PROJ']),
            'api.atlassian.com/*/issuetype/project*' => Http::response([
                ['id' => '1', 'name' => 'Epic'],
                ['id' => '2', 'name' => 'Story'],
                ['id' => '3', 'name' => 'Bug'],
                ['id' => '4', 'name' => 'Sub-task'],
            ]),
        ]);

        $response = $this->actingAs($qa)->get(route('jira.issue-types', ['projectKey' => 'PROJ']));

        $response->assertOk();
        $response->assertExactJson([
            'issue_types' => [
                ['name' => 'Epic', 'selected_by_default' => true],
                ['name' => 'Story', 'selected_by_default' => true],
                ['name' => 'Bug', 'selected_by_default' => false],
                ['name' => 'Sub-task', 'selected_by_default' => false],
            ],
        ]);
    }

    public function test_non_qa_users_cannot_fetch_issue_types(): void
    {
        $developer = $this->createProjectMember('developer');

        $this->actingAs($developer)
            ->get(route('jira.issue-types', ['projectKey' => 'PROJ']))
            ->assertForbidden();
    }

    public function test_callback_returns_to_settings_when_the_user_cancels_on_atlassian(): void
    {
        $admin = $this->createProjectMember('admin');
        Http::fake();

        $response = $this->actingAs($admin)
            ->withSession(['jira_oauth_state' => 'expected-state'])
            ->get(route('jira.callback', [
                'state' => 'expected-state',
                'error' => 'access_denied',
                'error_description' => 'User did not authorize the request',
            ]));

        $response->assertRedirect(route('jira.edit'));
        Http::assertNothingSent();
        $this->assertDatabaseCount('jira_integrations', 0);
    }

    public function test_callback_returns_to_settings_when_no_jira_site_is_accessible(): void
    {
        $admin = $this->createProjectMember('admin');

        Http::fake([
            'auth.atlassian.com/oauth/token' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
            ]),
            'api.atlassian.com/oauth/token/accessible-resources' => Http::response([]),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['jira_oauth_state' => 'expected-state'])
            ->get(route('jira.callback', ['state' => 'expected-state', 'code' => 'abc']));

        $response->assertRedirect(route('jira.edit'));
        $this->assertDatabaseCount('jira_integrations', 0);
    }
}
