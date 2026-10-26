<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\Requirements\StoreRequirementRequest;
use App\Http\Requests\Requirements\UpdateRequirementRequest;
use App\Models\JiraExcludedIssue;
use App\Models\Project;
use App\Models\Requirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequirementController extends Controller
{
    /**
     * Display a listing of the current project's requirements.
     */
    public function index(Request $request): Response
    {
        $project = Project::current();

        /**
         * Requirements that came from Jira keep a link back to their issue,
         * and stop being refreshed once the site is disconnected. The list
         * says both things so nobody edits an imported requirement expecting
         * it to travel back to Jira.
         */
        $integration = $project->jiraIntegration;

        return Inertia::render('requirements/index', [
            /** Every member reads the list; changing it needs the permission. */
            'canManage' => $request->user()->hasPermission(Permission::ManageRequirements),
            'jiraConnected' => $integration !== null,
            'requirements' => $project->requirements()
                ->withCount([
                    'testCases',
                    'testCases as passed_test_cases_count' => fn ($query) => $query
                        ->whereHas('latestExecution', fn ($query) => $query->where('status', 'APROVADO')),
                ])
                ->with([
                    'creator:id,name',
                    /** Named in the edit modal so the impact of a change is concrete. */
                    'testCases' => fn ($query) => $query
                        ->whereNull('test_cases.deleted_at')
                        ->orderBy('title')
                        ->select(['test_cases.id', 'test_cases.title']),
                ])
                ->get(['id', 'code', 'type', 'jira_issue_type', 'jira_synced_at', 'title', 'description', 'priority', 'created_by'])
                ->map(fn (Requirement $requirement): array => [
                    ...$requirement->toArray(),
                    'status' => Requirement::coverageStatus($requirement->test_cases_count, $requirement->passed_test_cases_count),
                    'jira_url' => $requirement->jira_synced_at !== null
                        ? $integration?->issueUrl($requirement->code)
                        : null,
                ])
                ->sortBy('code', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
        ]);
    }

    /**
     * Store a newly created requirement in storage.
     */
    public function store(StoreRequirementRequest $request): RedirectResponse
    {
        $requirement = Requirement::create([
            ...$request->validated(),
            'project_id' => Project::current()->id,
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requirement ":code" created in :project.', [
                'code' => $requirement->code,
                'project' => Project::current()->name,
            ]),
        ]);

        return back();
    }

    /**
     * Update the specified requirement in storage.
     */
    public function update(UpdateRequirementRequest $request, Requirement $requirement): RedirectResponse
    {
        $this->ensureBelongsToCurrentProject($requirement);

        $requirement->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requirement ":code" updated in :project.', [
                'code' => $requirement->code,
                'project' => Project::current()->name,
            ]),
        ]);

        return back();
    }

    /**
     * Archive the requirement when test cases are linked to it, so the links
     * survive and the action can be undone; delete it for good otherwise.
     */
    public function destroy(Requirement $requirement): RedirectResponse
    {
        $this->ensureBelongsToCurrentProject($requirement);

        $code = $requirement->code;
        $fromJira = $requirement->jira_synced_at !== null;

        /**
         * Removing an imported requirement only removes it here. The issue
         * stays in Jira, and it is recorded as excluded so the next import
         * does not bring it back.
         */
        $jiraNote = $fromJira
            ? ' '.__('The Jira issue is untouched, and it will not be imported again.')
            : '';

        if ($fromJira) {
            JiraExcludedIssue::query()->firstOrCreate([
                'project_id' => $requirement->project_id,
                'issue_key' => $code,
            ]);
        }

        if ($requirement->testCases()->doesntExist()) {
            $requirement->forceDelete();

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Requirement ":code" deleted.', ['code' => $code]).$jiraNote,
            ]);

            return back();
        }

        $requirement->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requirement ":code" archived.', ['code' => $code]).$jiraNote,
            /** Undoing restores the requirement, and with it every link it had. */
            'undo' => [
                'url' => route('requirements.restore', $requirement, false),
                'method' => 'patch',
                'data' => [],
            ],
        ]);

        return back();
    }

    /**
     * Bring an archived requirement back, together with its test case links.
     */
    public function restore(Requirement $requirement): RedirectResponse
    {
        $this->ensureBelongsToCurrentProject($requirement);

        $requirement->restore();

        /** Archiving hid the Jira issue from imports; restoring makes it importable again. */
        JiraExcludedIssue::query()
            ->where('project_id', $requirement->project_id)
            ->where('issue_key', $requirement->code)
            ->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requirement ":code" restored.', ['code' => $requirement->code]),
        ]);

        return back();
    }

    /**
     * Requirements of other projects are treated as if they did not exist.
     */
    private function ensureBelongsToCurrentProject(Requirement $requirement): void
    {
        abort_unless($requirement->project_id === Project::current()?->id, 404);
    }
}
