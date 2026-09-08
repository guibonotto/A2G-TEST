<?php

namespace Tests\Feature;

use App\Models\JiraIntegration;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JiraIntegrationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createIntegrationFor(User $user): JiraIntegration
    {
        return JiraIntegration::create([
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
        $developer = $this->createUserWithRole('developer');

        $this->actingAs($developer)->get(route('jira.edit'))->assertForbidden();
        $this->actingAs($developer)->delete(route('jira.destroy'))->assertForbidden();
        $this->actingAs($developer)->post(route('jira.import'))->assertForbidden();
    }

    public function test_redirect_sends_the_user_to_atlassian_with_a_state(): void
    {
        $qa = $this->createUserWithRole('qa');

        $response = $this->actingAs($qa)->get(route('jira.redirect'));

        $response->assertRedirectContains('https://auth.atlassian.com/authorize');
        $this->assertNotNull(session('jira_oauth_state'));
    }

    public function test_callback_rejects_a_mismatched_state(): void
    {
        $qa = $this->createUserWithRole('qa');

        $response = $this->actingAs($qa)
            ->withSession(['jira_oauth_state' => 'expected-state'])
            ->get(route('jira.callback', ['state' => 'wrong-state', 'code' => 'abc']));

        $response->assertForbidden();
    }

    public function test_callback_stores_the_connection_on_success(): void
    {
        $qa = $this->createUserWithRole('qa');

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

        $response = $this->actingAs($qa)
            ->withSession(['jira_oauth_state' => 'expected-state'])
            ->get(route('jira.callback', ['state' => 'expected-state', 'code' => 'abc']));

        $response->assertRedirect(route('jira.edit'));
        $this->assertDatabaseHas('jira_integrations', [
            'site_url' => 'https://example.atlassian.net',
            'cloud_id' => 'cloud-123',
            'connected_by' => $qa->id,
        ]);
    }

    public function test_qa_can_view_the_integration_page_when_connected(): void
    {
        $qa = $this->createUserWithRole('qa');
        JiraIntegration::create([
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

    public function test_qa_can_disconnect_jira(): void
    {
        $qa = $this->createUserWithRole('qa');
        JiraIntegration::create([
            'site_url' => 'https://example.atlassian.net',
            'cloud_id' => 'cloud-123',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => now()->addHour(),
            'connected_by' => $qa->id,
            'connected_at' => now(),
        ]);

        $response = $this->actingAs($qa)->delete(route('jira.destroy'));

        $response->assertRedirect(route('jira.edit'));
        $this->assertDatabaseCount('jira_integrations', 0);
    }

    public function test_import_creates_requirements_from_jira_issues(): void
    {
        $qa = $this->createUserWithRole('qa');
        JiraIntegration::create([
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
        $qa = $this->createUserWithRole('qa');
        $originalCreator = $this->createUserWithRole('developer');
        Requirement::create([
            'code' => 'PROJ-1',
            'type' => 'funcional',
            'title' => 'Título antigo',
            'created_by' => $originalCreator->id,
        ]);

        JiraIntegration::create([
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
        $qa = $this->createUserWithRole('qa');
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
        $qa = $this->createUserWithRole('qa');
        $this->createIntegrationFor($qa);

        Http::fake([
            'api.atlassian.com/*' => Http::response(['issues' => []]),
        ]);

        $this->actingAs($qa)->post(route('jira.import'), [
            'project_key' => 'PROJ',
            'issue_types' => ['Epic', 'Story'],
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'search/jql')
                && str_contains(urldecode($request->url()), 'issuetype in ("Epic","Story")');
        });
    }

    public function test_qa_can_fetch_issue_types_for_a_project(): void
    {
        $qa = $this->createUserWithRole('qa');
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
        $developer = $this->createUserWithRole('developer');

        $this->actingAs($developer)
            ->get(route('jira.issue-types', ['projectKey' => 'PROJ']))
            ->assertForbidden();
    }
}
