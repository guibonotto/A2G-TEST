<?php

namespace App\Http\Controllers;

use App\Http\Requests\Requirements\StoreRequirementRequest;
use App\Http\Requests\Requirements\UpdateRequirementRequest;
use App\Models\JiraExcludedIssue;
use App\Models\Project;
use App\Models\Requirement;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RequirementController extends Controller
{
    /**
     * Display a listing of the current project's requirements.
     */
    public function index(): Response
    {
        return Inertia::render('management/requirements/index', [
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
     * Remove the specified requirement from storage.
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

        $requirement->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requirement ":code" deleted.', ['code' => $code]),
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
