<?php

namespace App\Http\Controllers;

use App\Http\Requests\Jira\ImportJiraRequirementsRequest;
use App\Models\JiraExcludedIssue;
use App\Models\JiraIntegration;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\Role;
use App\Services\Jira\JiraClient;
use App\Services\Jira\JiraIssueMapper;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class JiraIntegrationController extends Controller
{
    private const MAX_ISSUES_PER_IMPORT = 500;

    public function __construct(private readonly JiraClient $jira) {}

    /**
     * Display the Jira integration settings page of the current project.
     */
    public function edit(Request $request): Response
    {
        $project = Project::current();
        $integration = $project->jiraIntegration()->with('connector:id,name')->first();

        $projects = [];
        $error = null;

        if ($integration !== null) {
            try {
                $projects = $this->jira->projects($integration);
            } catch (Throwable) {
                $error = 'Could not load Jira projects. Check the connection.';
            }
        }

        $lastSyncedAt = $project->requirements()->max('jira_synced_at');

        return Inertia::render('management/jira/edit', [
            'integration' => $integration === null ? null : [
                'site_url' => $integration->site_url,
                'connected_by' => $integration->connector?->name,
                'connected_at' => $integration->connected_at,
                'can_disconnect' => $integration->canBeDisconnectedBy($request->user()),
                'can_report_defects' => $integration->canCreateIssues(),
            ],
            'can_connect' => $request->user()->hasRole(Role::ADMIN),
            'projects' => $projects,
            'error' => $error,
            'last_synced_at' => $lastSyncedAt === null ? null : Carbon::parse($lastSyncedAt)->toIso8601String(),
        ]);
    }

    /**
     * Redirect the user to Atlassian to authorize the connection.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('jira_oauth_state', $state);

        return redirect()->away($this->jira->authorizationUrl($state));
    }

    /**
     * Handle the callback from Atlassian after the user authorizes the connection.
     */
    public function callback(Request $request): RedirectResponse
    {
        $state = $request->session()->pull('jira_oauth_state');

        abort_if(
            $state === null || ! hash_equals($state, (string) $request->query('state')),
            403,
            'Invalid authorization state.'
        );

        if ($request->filled('error')) {
            if ($request->query('error') === 'access_denied') {
                return $this->backToSettings('info', __('Jira connection cancelled.'));
            }

            /** Atlassian says why it refused (e.g. a scope not enabled in the app), so keep that reason. */
            Log::warning('Jira authorization failed.', $request->only(['error', 'error_description']));

            return $this->backToSettings('error', trim(__('Could not connect to Jira.').' '.$request->query('error_description', $request->query('error'))));
        }

        $request->validate(['code' => ['required', 'string']]);

        $tokens = $this->jira->exchangeCodeForToken($request->query('code'));
        $resources = $this->jira->accessibleResources($tokens['access_token']);

        if ($resources === []) {
            return $this->backToSettings('error', __('No accessible Jira site was found for this account.'));
        }

        $site = $resources[0];
        $project = Project::current();

        $project->jiraIntegration()->delete();

        JiraIntegration::create([
            'project_id' => $project->id,
            'site_url' => $site['url'],
            'cloud_id' => $site['id'],
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_at' => now()->addSeconds($tokens['expires_in']),
            'scopes' => $tokens['scope'] ?? null,
            'connected_by' => $request->user()->id,
            'connected_at' => now(),
        ]);

        return $this->backToSettings('success', __('Connected to :site.', [
            'site' => parse_url($site['url'], PHP_URL_HOST) ?? $site['url'],
        ]));
    }

    /**
     * Redirect back to the Jira settings page with a flash message.
     */
    private function backToSettings(string $type, string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return redirect()->route('jira.edit');
    }

    /**
     * Disconnect the Jira integration.
     */
    public function destroy(): RedirectResponse
    {
        $integration = $this->currentIntegration();
        abort_unless(
            $integration->canBeDisconnectedBy(request()->user()),
            403,
            'Only a project administrator can disconnect Jira.'
        );
        $integration->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Jira integration disconnected.')]);

        return redirect()->route('jira.edit');
    }

    /**
     * List the issue types found in a Jira project, so the user can choose
     * which ones should be imported as requirements.
     */
    public function issueTypes(string $projectKey): JsonResponse
    {
        $integration = $this->currentIntegration();

        $names = $this->jira->issueTypeNamesForProject($integration, $projectKey);

        return response()->json([
            'issue_types' => array_map(
                fn (string $name): array => [
                    'name' => $name,
                    'selected_by_default' => ! Str::of($name)->lower()->contains(['bug', 'sub-task', 'subtask']),
                ],
                $names
            ),
        ]);
    }

    /**
     * Preview what an import would do, without writing anything: which
     * issues are new, which already exist here and would be updated, and
     * which were deleted here.
     */
    public function preview(Request $request, string $projectKey): JsonResponse
    {
        $validated = $request->validate([
            'issue_types' => ['required', 'array', 'min:1'],
            'issue_types.*' => ['required', 'string', 'max:100'],
        ]);

        $integration = $this->currentIntegration();

        $issues = $this->fetchIssues($integration, $projectKey, $validated['issue_types']);
        $keys = array_column($issues, 'key');

        $existing = Requirement::query()
            ->where('project_id', $integration->project_id)
            ->whereIn('code', $keys)
            ->pluck('code')
            ->flip();
        $excluded = JiraExcludedIssue::query()
            ->where('project_id', $integration->project_id)
            ->whereIn('issue_key', $keys)
            ->pluck('issue_key')
            ->flip();

        return response()->json([
            'issues' => array_map(fn (array $issue): array => [
                'key' => $issue['key'],
                'title' => $issue['fields']['summary'] ?? $issue['key'],
                'issue_type' => $issue['fields']['issuetype']['name'] ?? null,
                'state' => match (true) {
                    $existing->has($issue['key']) => 'existing',
                    $excluded->has($issue['key']) => 'deleted',
                    default => 'new',
                },
            ], $issues),
            'truncated' => count($issues) >= self::MAX_ISSUES_PER_IMPORT,
        ]);
    }

    /**
     * Import the issues the user ticked in the preview. Issues are matched by
     * key, so existing requirements are updated instead of duplicated.
     */
    public function import(ImportJiraRequirementsRequest $request): RedirectResponse
    {
        $integration = $this->currentIntegration();

        $selectedKeys = array_flip($request->validated('issue_keys'));

        $issues = array_values(array_filter(
            $this->fetchIssues($integration, $request->validated('project_key'), $request->validated('issue_types')),
            fn (array $issue): bool => isset($selectedKeys[$issue['key']])
        ));

        $created = 0;
        $updated = 0;
        $syncedAt = now();

        foreach ($issues as $issue) {
            $this->upsertRequirement($issue, $integration->project_id, $request->user()->id, $syncedAt) ? $created++ : $updated++;
        }

        JiraExcludedIssue::query()
            ->where('project_id', $integration->project_id)
            ->whereIn('issue_key', array_column($issues, 'key'))
            ->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':created imported, :updated updated.', ['created' => $created, 'updated' => $updated]),
        ]);

        return redirect()->route('jira.edit');
    }

    /**
     * Fetch every issue of the given types in a Jira project, up to the import limit.
     *
     * @param  list<string>  $issueTypes
     * @return list<array<string, mixed>>
     */
    private function fetchIssues(JiraIntegration $integration, string $projectKey, array $issueTypes): array
    {
        $typesClause = collect($issueTypes)->map(fn (string $type): string => self::jqlString($type))->implode(',');
        $jql = 'project = '.self::jqlString($projectKey)." AND issuetype in ({$typesClause}) ORDER BY key ASC";

        $issues = [];
        $pageToken = null;

        do {
            $result = $this->jira->searchIssues(
                $integration,
                $jql,
                $pageToken,
                min(100, self::MAX_ISSUES_PER_IMPORT - count($issues))
            );

            array_push($issues, ...$result['issues']);
            $pageToken = $result['nextPageToken'];
        } while (
            count($result['issues']) > 0
            && $pageToken !== null
            && count($issues) < self::MAX_ISSUES_PER_IMPORT
        );

        return $issues;
    }

    /**
     * The Jira connection of the project the user is working on.
     */
    private function currentIntegration(): JiraIntegration
    {
        $integration = Project::current()?->jiraIntegration;

        abort_if($integration === null, 404, 'No Jira integration found.');

        return $integration;
    }

    private static function jqlString(string $value): string
    {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }

    /**
     * Create or update the requirement for a Jira issue.
     *
     * @param  array<string, mixed>  $issue
     * @return bool Whether the requirement was created (true) or updated (false).
     */
    private function upsertRequirement(array $issue, int $projectId, int $importedByUserId, CarbonInterface $syncedAt): bool
    {
        $attributes = JiraIssueMapper::toRequirementAttributes($issue);
        $code = $attributes['code'];
        unset($attributes['code']);

        /** An archived requirement still holds its code, so re-importing the issue restores it. */
        $requirement = Requirement::withTrashed()->firstOrNew(['project_id' => $projectId, 'code' => $code]);
        $requirement->fill($attributes);
        $requirement->jira_synced_at = $syncedAt;

        $wasCreated = ! $requirement->exists || $requirement->trashed();
        $requirement->deleted_at = null;

        if ($wasCreated) {
            $requirement->created_by = $importedByUserId;
        }

        $requirement->save();

        return $wasCreated;
    }
}
