<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestCases\AssignTestCaseRequest;
use App\Http\Requests\TestCases\LinkRequirementRequest;
use App\Http\Requests\TestCases\StoreEvidenceRequest;
use App\Http\Requests\TestCases\StoreExecutionRequest;
use App\Http\Requests\TestCases\StoreTestCaseRequest;
use App\Http\Requests\TestCases\UpdateTestCaseRequest;
use App\Http\Requests\TestCases\UpdateTestCaseStatusRequest;
use App\Models\Classification;
use App\Models\Evidence;
use App\Models\Execution;
use App\Models\JiraIntegration;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\TestCase;
use App\Models\TestCaseStatus;
use App\Models\User;
use App\Services\Jira\JiraClient;
use App\Services\Jira\JiraDefectStatusSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TestCaseController extends Controller
{
    /**
     * Display a listing of the test cases.
     */
    public function index(Request $request): Response
    {
        $project = Project::current();
        abort_unless($project, 404);

        $search = $request->string('search')->trim()->toString();
        $classificationId = $request->integer('classification_id') ?: null;
        $situationId = $request->integer('situation_id') ?: null;
        $executionResult = $request->string('execution_result')->trim()->toString() ?: null;
        $assignedToMe = $request->boolean('assigned_to_me');

        $testCases = TestCase::query()
            ->where('project_id', $project->id)
            ->with([
                'classification:id,name',
                'status:id,name,color',
                'latestExecution' => fn ($query) => $query->select([
                    'executions.id',
                    'executions.test_case_id',
                    'executions.status',
                    'executions.obtained_output',
                    'executions.execution_date',
                ]),
                'creator:id,name',
                'assignee:id,name',
                'requirements:id,code,title',
            ])
            ->withCount('steps')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%");
                    if (is_numeric($search)) {
                        $query->orWhere('id', (int) $search);
                    }
                });
            })
            ->when($classificationId, fn ($query) => $query->where('classification_id', $classificationId))
            ->when($situationId, fn ($query) => $query->where('status_id', $situationId))
            ->when($executionResult, function ($query) use ($executionResult) {
                if ($executionResult === 'NOT_EXECUTED') {
                    $query->whereDoesntHave('executions');

                    return;
                }

                $query->whereHas('latestExecution', fn ($query) => $query->where('status', $executionResult));
            })
            ->when($assignedToMe, fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->latest()
            ->get(['id', 'title', 'tested_method', 'classification_id', 'status_id', 'assigned_to', 'created_by', 'created_at']);

        return Inertia::render('test-cases/index', [
            'testCases' => $testCases,
            'classifications' => Classification::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => TestCaseStatus::query()
                ->forProject(Project::current())
                ->orderByRaw("CASE name WHEN 'Draft' THEN 1 WHEN 'Obsolete' THEN 2 WHEN 'Ready' THEN 3 ELSE 4 END")
                ->get(['id', 'name', 'description', 'color', 'category']),
            'executionResults' => ['PENDENTE', 'APROVADO', 'REPROVADO', 'BLOQUEADO', 'NOT_EXECUTED'],
            'filters' => [
                'search' => $search,
                'classification_id' => $classificationId,
                'situation_id' => $situationId,
                'execution_result' => $executionResult,
                'assigned_to_me' => $assignedToMe,
            ],
        ]);
    }

    /**
     * The test cases the current user is responsible for in the active
     * project, split between what still needs a run and what already passed.
     */
    public function assigned(Request $request): Response
    {
        $relations = [
            'status:id,name,color',
            'latestExecution' => fn ($query) => $query->select([
                'executions.id',
                'executions.test_case_id',
                'executions.status',
                'executions.execution_date',
            ]),
        ];
        $columns = ['id', 'title', 'tested_method', 'status_id', 'created_at'];
        $projectId = Project::current()->id;

        return Inertia::render('test-cases/assigned', [
            'toRun' => TestCase::query()
                ->assignedTo($request->user(), $projectId)
                ->awaitingExecution()
                ->with($relations)
                ->withExists('executions')
                ->orderBy('executions_exists')
                ->oldest()
                ->get($columns),
            'passed' => TestCase::query()
                ->assignedTo($request->user(), $projectId)
                ->whereHas('latestExecution', fn ($query) => $query->where('status', 'APROVADO'))
                ->with($relations)
                ->latest()
                ->get($columns),
        ]);
    }

    /**
     * Show the form for creating a new test case.
     */
    public function create(Request $request): Response
    {
        $defaultStatus = TestCaseStatus::defaultFor(Project::current());

        return Inertia::render('test-cases/create', [
            'assignableUsers' => $request->user()->canManageAccess()
                ? $this->assignableUsers(Project::current()->id)
                : [],
            'defaultStatusId' => $defaultStatus->id,
            'statuses' => TestCaseStatus::query()
                ->forProject(Project::current())
                ->orderByRaw("CASE name WHEN 'Draft' THEN 1 WHEN 'Obsolete' THEN 2 WHEN 'Ready' THEN 3 ELSE 4 END")
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'color', 'category']),
            'availableRequirements' => ! $request->user()->hasRole('viewer')
                ? $this->linkableRequirements(Project::current()->id)
                : [],
        ]);
    }

    /**
     * Store a newly created test case in storage.
     */
    public function store(StoreTestCaseRequest $request): RedirectResponse
    {
        $project = Project::current();
        abort_unless($project, 404);

        $testCase = DB::transaction(function () use ($request, $project) {
            $testCase = TestCase::create([
                ...$request->safe()->only(['title', 'tested_method', 'description', 'template_id', 'assigned_to']),
                'status_id' => $request->validated('status_id') ?: TestCaseStatus::defaultFor(Project::current())->id,
                'created_by' => $request->user()->id,
                'project_id' => $project->id,
            ]);

            foreach ($request->safe()->array('steps') as $index => $step) {
                $testCase->steps()->create([
                    'order' => $index + 1,
                    'description' => $step['description'],
                    'expected_result' => $step['expected_result'] ?? null,
                ]);
            }

            $testCase->requirements()->sync($request->safe()->array('requirement_ids'));

            return $testCase;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Test case ":title" created.', ['title' => $testCase->title]),
        ]);

        return to_route('test-cases.show', $testCase);
    }

    /**
     * Display the specified test case.
     */
    public function show(Request $request, TestCase $testCase): Response
    {
        abort_unless($request->user()->can('view', $testCase), 403);

        $testCase->load([
            'classification:id,name',
            'template:id,title',
            'status:id,name,color',
            'latestExecution' => fn ($query) => $query->select([
                'executions.id',
                'executions.test_case_id',
                'executions.status',
                'executions.obtained_output',
                'executions.execution_date',
            ]),
            'creator:id,name',
            'assignee:id,name',
            'steps',
            'executions.executor:id,name',
            'executions.evidences:id,execution_id,file_name,mime_type,size,uploaded_at',
            // 'requirements' => fn ($query) => $query->orderBy('code'),
            'requirements',
        ]);
        $testCase->setRelation('requirements', $testCase->requirements->sortBy('code', SORT_NATURAL | SORT_FLAG_CASE)->values());

        $integration = JiraIntegration::query()->where('project_id', $testCase->project_id)->first();
        /** A re-run should show whether the bugs reported from this case are still open. */
        app(JiraDefectStatusSync::class)->refresh($testCase->executions, $integration);

        return Inertia::render('test-cases/show', [
            'jiraDefects' => [
                'canReport' => $integration?->canCreateIssues() === true && $request->user()->can('update', $testCase),
                'connected' => $integration !== null,
                'siteUrl' => $integration?->site_url,
                'defaultProjectKey' => $this->defaultJiraProjectKey($testCase),
            ],
            'jiraProjects' => Inertia::optional(function () use ($integration): array {
                try {
                    return $integration === null ? [] : $this->jira()->projects($integration);
                } catch (Throwable) {
                    return [];
                }
            }),
            'testCase' => $testCase,
            'assignableUsers' => $request->user()->canManageAccess()
                ? $this->assignableUsers($testCase->project_id)
                : [],
            'statuses' => TestCaseStatus::query()
                ->forProject($testCase->project_id)
                ->whereIn('name', ['Draft', 'Obsolete', 'Ready'])
                ->orderByRaw("CASE name WHEN 'Draft' THEN 1 WHEN 'Obsolete' THEN 2 WHEN 'Ready' THEN 3 ELSE 4 END")
                ->get(['id', 'name', 'description', 'color', 'category']),
            'executionStatuses' => ['PENDENTE', 'APROVADO', 'REPROVADO', 'BLOQUEADO'],
            'availableRequirements' => $this->linkableRequirements($testCase->project_id),
        ]);
    }

    /**
     * Record an execution of the test case, along with any evidence files.
     */
    public function storeExecution(StoreExecutionRequest $request, TestCase $testCase): RedirectResponse
    {
        DB::transaction(function () use ($request, $testCase): void {
            $execution = $testCase->executions()->create([
                ...$request->safe()->except('evidences'),
                'executed_by' => $request->user()->id,
                'test_case_version' => $testCase->version,
                'test_case_snapshot' => $testCase->specificationSnapshot(),
            ]);

            $execution->attachEvidences($request->file('evidences') ?? []);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Execution registered.'),
        ]);

        return to_route('test-cases.show', $testCase);
    }

    /**
     * Show the form for editing the specified test case.
     */
    public function edit(Request $request, TestCase $testCase): Response
    {
        abort_unless($request->user()->can('update', $testCase), 403);

        /**
         * The edit form preselects these from the relations, so they have to
         * be loaded; without them every select opened on its placeholder and
         * saving failed on the required classification.
         */
        $testCase->load(['steps', 'classification', 'template', 'status']);

        return Inertia::render('test-cases/edit', [
            'testCase' => $testCase,
            'hasExecutions' => $testCase->executions()->exists(),
            'version' => $testCase->version,
            'assignableUsers' => $request->user()->canManageAccess()
                ? $this->assignableUsers($testCase->project_id)
                : [],
            'statuses' => TestCaseStatus::query()
                ->forProject($testCase->project_id)
                ->whereIn('name', ['Draft', 'Obsolete', 'Ready'])
                ->orderByRaw("CASE name WHEN 'Draft' THEN 1 WHEN 'Obsolete' THEN 2 WHEN 'Ready' THEN 3 ELSE 4 END")
                ->get(['id', 'name', 'description', 'color', 'category']),
        ]);
    }

    /**
     * Update the specified test case in storage.
     */
    public function update(UpdateTestCaseRequest $request, TestCase $testCase): RedirectResponse
    {
        DB::transaction(function () use ($request, $testCase) {
            $validated = $request->validated();
            $nullableId = static fn (string $key): ?int => isset($validated[$key]) && $validated[$key] !== ''
                ? (int) $validated[$key]
                : null;

            $changed = $testCase->title !== $request->validated('title')
                || $testCase->tested_method !== ($request->validated('tested_method') ?: null)
                || $testCase->description !== ($request->validated('description') ?: null)
                || $this->stepsChanged($testCase, $request->validated('steps'))
                || $testCase->status_id !== $nullableId('status_id')
                || $testCase->assigned_to !== $nullableId('assigned_to');

            if ($changed && $testCase->executions()->exists()) {
                $testCase->increment('version');
            }

            $testCase->update(
                $request->safe()->only(['title', 'tested_method', 'description', 'classification_id', 'template_id', 'status_id', 'assigned_to'])
            );

            $testCase->steps()->delete();

            foreach ($request->safe()->array('steps') as $index => $step) {
                $testCase->steps()->create([
                    'order' => $index + 1,
                    'description' => $step['description'],
                    'expected_result' => $step['expected_result'] ?? null,
                ]);
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Test case ":title" updated.', ['title' => $testCase->title]),
        ]);

        return to_route('test-cases.show', $testCase);
    }

    /**
     * Compare the single active input/expected-output pair without relying on
     * array order or on the database representation of nullable strings.
     *
     * @param  array<int, array{description: string, expected_result?: string|null}>  $steps
     */
    private function stepsChanged(TestCase $testCase, array $steps): bool
    {
        $current = $testCase->steps()->first();
        $incoming = $steps[0] ?? [];

        return $current?->description !== ($incoming['description'] ?? null)
            || $current?->expected_result !== ($incoming['expected_result'] ?? null);
    }

    /**
     * Change the status of a single test case without going through the edit form.
     */
    public function updateStatus(UpdateTestCaseStatusRequest $request, TestCase $testCase): RedirectResponse
    {
        $testCase->update($request->safe()->only(['status_id']));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Test case status updated.'),
        ]);

        return back();
    }

    /**
     * Assign the specified test case to another user.
     */
    public function assign(AssignTestCaseRequest $request, TestCase $testCase): RedirectResponse
    {
        $previousAssigneeId = $testCase->assigned_to;

        $testCase->update($request->safe()->only(['assigned_to']));
        $assignee = $testCase->assignee()->value('name');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $assignee !== null
                ? __('Test case assigned to :name. It now shows up in their "Assigned to me" list.', ['name' => $assignee])
                : __('Test case unassigned.'),
            /** Undoing is simply assigning the previous person back through the same, fully validated route. */
            'undo' => $testCase->wasChanged('assigned_to') ? [
                'url' => route('test-cases.assign', $testCase, false),
                'method' => 'patch',
                'data' => ['assigned_to' => $previousAssigneeId],
            ] : null,
        ]);

        return to_route('test-cases.show', $testCase);
    }

    /**
     * Archive the test case when it has executions, so its history, evidence
     * and requirement links survive and the action can be undone; delete it
     * for good otherwise.
     */
    public function deleteTestCase(Request $request, TestCase $testCase): RedirectResponse
    {
        abort_unless($request->user()->can('delete', $testCase), 403);

        $title = $testCase->title;

        if ($testCase->executions()->doesntExist()) {
            $testCase->forceDelete();

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Test case ":title" deleted.', ['title' => $title]),
            ]);

            return to_route('test-cases.index');
        }

        $testCase->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Test case ":title" archived.', ['title' => $title]),
            /** Undoing restores the test case, and with it its executions and links. */
            'undo' => [
                'url' => route('test-cases.restore', $testCase, false),
                'method' => 'patch',
                'data' => [],
            ],
        ]);

        return to_route('test-cases.index');
    }

    /**
     * Bring an archived test case back, together with its history and links.
     */
    public function restore(Request $request, TestCase $testCase): RedirectResponse
    {
        abort_unless($request->user()->can('delete', $testCase), 403);

        $testCase->restore();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Test case ":title" restored.', ['title' => $testCase->title]),
        ]);

        return to_route('test-cases.show', $testCase);
    }

    /**
     * Link the test case to a requirement (RF017).
     */
    public function linkRequirement(LinkRequirementRequest $request, TestCase $testCase): RedirectResponse
    {
        $testCase->requirements()->syncWithoutDetaching([$request->validated('requirement_id')]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requirement linked.'),
        ]);

        return back();
    }

    /**
     * Remove the link between the test case and a requirement.
     */
    public function unlinkRequirement(Request $request, TestCase $testCase): RedirectResponse
    {
        abort_unless($request->user()->can('update', $testCase), 403);

        $testCase->requirements()->detach($request->integer('requirement_id'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requirement unlinked.'),
        ]);

        return back();
    }

    /**
     * Attach additional evidence files to an existing execution.
     */
    public function storeEvidence(StoreEvidenceRequest $request, Execution $execution): RedirectResponse
    {
        $files = $request->file('evidences');

        DB::transaction(fn () => $execution->attachEvidences($files));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':count evidence file(s) attached.', ['count' => count($files)]),
        ]);

        return to_route('test-cases.show', $execution->testCase);
    }

    /**
     * Stream an evidence file to the browser, restricted to members of the project.
     */
    public function showEvidence(Request $request, Evidence $evidence): StreamedResponse
    {
        abort_unless($request->user()->can('view', $evidence->execution->testCase), 403);
        abort_unless(Storage::disk('local')->exists($evidence->file_path), 404);

        return Storage::disk('local')->response($evidence->file_path, $evidence->file_name);
    }

    /**
     * Remove an evidence file from storage.
     */
    public function destroyEvidence(Request $request, Evidence $evidence): RedirectResponse
    {
        $testCase = $evidence->execution->testCase;

        abort_unless($request->user()->can('view', $testCase), 403);

        $evidence->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Evidence deleted.'),
        ]);

        return to_route('test-cases.show', $testCase);
    }

    /**
     * Requirements of a project that a test case can be linked to, with what
     * the picker needs to describe each one: its description, how many test
     * cases already cover it and, when it came from Jira, a link to the issue.
     *
     * @return list<array{id: int, code: string, title: string, description: ?string, test_cases_count: int, jira_url: ?string}>
     */
    private function linkableRequirements(int $projectId): array
    {
        $jiraSiteUrl = JiraIntegration::query()->where('project_id', $projectId)->value('site_url');

        return Requirement::query()
            ->where('project_id', $projectId)
            ->withCount('testCases')
            ->get(['id', 'code', 'jira_issue_type', 'title', 'description', 'jira_synced_at'])
            ->sortBy('code', SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn (Requirement $requirement): array => [
                'id' => $requirement->id,
                'code' => $requirement->code,
                'jira_issue_type' => $requirement->jira_issue_type,
                'title' => $requirement->title,
                'description' => $requirement->description,
                'test_cases_count' => $requirement->test_cases_count,
                'jira_url' => $jiraSiteUrl !== null && $requirement->jira_synced_at !== null
                    ? rtrim($jiraSiteUrl, '/').'/browse/'.rawurlencode($requirement->code)
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Suggest the Jira project of the first linked requirement that came from
     * Jira: a key like "SHOP-12" belongs to the "SHOP" project.
     */
    private function defaultJiraProjectKey(TestCase $testCase): ?string
    {
        $code = $testCase->requirements->firstWhere('jira_synced_at', '!=', null)?->code;

        return $code !== null && str_contains($code, '-') ? Str::beforeLast($code, '-') : null;
    }

    private function jira(): JiraClient
    {
        return app(JiraClient::class);
    }

    /**
     * Project members who can be put in charge of a test case, with their role
     * and email so people with similar names can be told apart.
     *
     * @return list<array{id: int, name: string, email: string, role: ?string}>
     */
    private function assignableUsers(int $projectId): array
    {
        $assignableRoles = Role::query()->whereIn('slug', Role::ASSIGNABLE_SLUGS)->pluck('name', 'id');

        return Project::findOrFail($projectId)->members()
            ->wherePivotIn('role_id', $assignableRoles->keys())
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $assignableRoles[$user->pivot->role_id] ?? null,
            ])
            ->all();
    }
}
