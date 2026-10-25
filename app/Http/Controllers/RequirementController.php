<?php

namespace App\Http\Controllers;

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
        return Inertia::render('requirements/index', [
            /** Every member reads the list; only QA and admins change it. */
            'canManage' => $request->user()->canManageAccess(),
            'requirements' => Project::current()->requirements()
                ->withCount([
                    'testCases',
                    'testCases as passed_test_cases_count' => fn ($query) => $query
                        ->whereHas('latestExecution', fn ($query) => $query->where('status', 'APROVADO')),
                ])
                ->with('creator:id,name')
                ->get(['id', 'code', 'type', 'title', 'description', 'priority', 'created_by'])
                ->map(fn (Requirement $requirement): array => [
                    ...$requirement->toArray(),
                    'status' => Requirement::coverageStatus($requirement->test_cases_count, $requirement->passed_test_cases_count),
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

        if ($requirement->jira_synced_at !== null) {
            JiraExcludedIssue::query()->firstOrCreate([
                'project_id' => $requirement->project_id,
                'issue_key' => $code,
            ]);
        }

        if ($requirement->testCases()->doesntExist()) {
            $requirement->forceDelete();

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Requirement ":code" deleted.', ['code' => $code]),
            ]);

            return back();
        }

        $requirement->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requirement ":code" archived.', ['code' => $code]),
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
