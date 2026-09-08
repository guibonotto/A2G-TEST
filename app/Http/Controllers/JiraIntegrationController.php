<?php

namespace App\Http\Controllers;

use App\Http\Requests\Jira\ImportJiraRequirementsRequest;
use App\Models\JiraIntegration;
use App\Models\Requirement;
use App\Services\Jira\JiraClient;
use App\Services\Jira\JiraIssueMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class JiraIntegrationController extends Controller
{
    private const MAX_ISSUES_PER_IMPORT = 500;

    public function __construct(private readonly JiraClient $jira) {}

    /**
     * Display the Jira integration settings page.
     */
    public function edit(): Response
    {
        $integration = JiraIntegration::query()->with('connector:id,name')->first();

        $projects = [];
        $error = null;

        if ($integration !== null) {
            try {
                $projects = $this->jira->projects($integration);
            } catch (Throwable) {
                $error = 'Could not load Jira projects. Check the connection.';
            }
        }

        return Inertia::render('management/jira/edit', [
            'integration' => $integration === null ? null : [
                'site_url' => $integration->site_url,
                'connected_by' => $integration->connector?->name,
                'connected_at' => $integration->connected_at,
            ],
            'projects' => $projects,
            'error' => $error,
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
     * Handle the OAuth callback from Atlassian and persist the connection.
     */
    public function callback(Request $request): RedirectResponse
    {
        $state = $request->session()->pull('jira_oauth_state');

        abort_if(
            $state === null || ! hash_equals($state, (string) $request->query('state')),
            403,
            'Invalid authorization state.'
        );

        $request->validate(['code' => ['required', 'string']]);

        $tokens = $this->jira->exchangeCodeForToken($request->query('code'));
        $resources = $this->jira->accessibleResources($tokens['access_token']);

        abort_if($resources === [], 422, 'No accessible Jira site was found for this account.');

        $site = $resources[0];

        JiraIntegration::query()->delete();

        JiraIntegration::create([
            'site_url' => $site['url'],
            'cloud_id' => $site['id'],
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_at' => now()->addSeconds($tokens['expires_in']),
            'connected_by' => $request->user()->id,
            'connected_at' => now(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Jira connected successfully.'),
        ]);

        return redirect()->route('jira.edit');
    }

    /**
     * Disconnect the Jira integration.
     */
    public function destroy(): RedirectResponse
    {
        JiraIntegration::query()->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Jira disconnected.'),
        ]);

        return redirect()->route('jira.edit');
    }

    /**
     * List the issue types found in a Jira project, so the user can choose
     * which ones should be imported as requirements.
     */
    public function issueTypes(string $projectKey): JsonResponse
    {
        $integration = JiraIntegration::query()->first();

        abort_if($integration === null, 404, 'No Jira integration found.');

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
     * Import issues from a Jira project as requirements, restricted to the
     * issue types the user selected.
     */
    public function import(ImportJiraRequirementsRequest $request): RedirectResponse
    {
        $integration = JiraIntegration::query()->first();

        abort_if($integration === null, 404, 'No Jira integration found.');

        $projectKey = $request->validated('project_key');
        $issueTypes = $request->validated('issue_types');
        $imported = 0;
        $pageToken = null;

        $typesClause = collect($issueTypes)
            ->map(fn (string $type): string => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $type).'"')
            ->implode(',');

        $jql = "project = \"{$projectKey}\" AND issuetype in ({$typesClause}) ORDER BY key ASC";

        do {
            $result = $this->jira->searchIssues(
                $integration,
                $jql,
                $pageToken,
                min(100, self::MAX_ISSUES_PER_IMPORT - $imported)
            );

            foreach ($result['issues'] as $issue) {
                $this->upsertRequirement($issue, $request->user()->id);
                $imported++;
            }

            $pageToken = $result['nextPageToken'];
        } while (
            count($result['issues']) > 0
            && $pageToken !== null
            && $imported < self::MAX_ISSUES_PER_IMPORT
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':count requirement(s) imported from Jira.', ['count' => $imported]),
        ]);

        return redirect()->route('jira.edit');
    }

    /**
     * @param  array<string, mixed>  $issue
     */
    private function upsertRequirement(array $issue, int $importedByUserId): void
    {
        $attributes = JiraIssueMapper::toRequirementAttributes($issue);
        $code = $attributes['code'];
        unset($attributes['code']);

        $requirement = Requirement::query()->firstOrNew(['code' => $code]);
        $requirement->fill($attributes);

        if (! $requirement->exists) {
            $requirement->created_by = $importedByUserId;
        }

        $requirement->save();
    }
}
