<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestCases\AssignTestCaseRequest;
use App\Http\Requests\TestCases\BulkUpdateTestCaseStatusRequest;
use App\Http\Requests\TestCases\LinkRequirementRequest;
use App\Http\Requests\TestCases\StoreExecutionRequest;
use App\Http\Requests\TestCases\StoreTestCaseRequest;
use App\Http\Requests\TestCases\UpdateTestCaseRequest;
use App\Models\Classification;
use App\Models\Requirement;
use App\Models\TestCase;
use App\Models\TestCaseStatus;
use App\Models\TestTemplate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TestCaseController extends Controller
{
    /**
     * Display a listing of the test cases.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $classificationId = $request->integer('classification_id') ?: null;
        $statusId = $request->integer('status_id') ?: null;
        $assignedToMe = $request->boolean('assigned_to_me');

        $testCases = TestCase::query()
            ->with(['classification:id,name', 'status:id,name,color', 'creator:id,name', 'assignee:id,name'])
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
            ->when($statusId, fn ($query) => $query->where('status_id', $statusId))
            ->when($assignedToMe, fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->latest()
            ->get(['id', 'title', 'classification_id', 'status_id', 'assigned_to', 'created_by', 'created_at']);

        return Inertia::render('test-cases/index', [
            'testCases' => $testCases,
            'classifications' => Classification::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => TestCaseStatus::query()->orderBy('name')->get(['id', 'name', 'color']),
            'filters' => [
                'search' => $search,
                'classification_id' => $classificationId,
                'status_id' => $statusId,
                'assigned_to_me' => $assignedToMe,
            ],
        ]);
    }

    /**
     * Show the form for creating a new test case.
     */
    public function create(): Response
    {
        return Inertia::render('test-cases/create', [
            'classifications' => Classification::query()->orderBy('name')->get(['id', 'name']),
            'templates' => TestTemplate::query()->orderBy('title')->get(['id', 'title']),
            'statuses' => TestCaseStatus::query()->orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    /**
     * Store a newly created test case in storage.
     */
    public function store(StoreTestCaseRequest $request): RedirectResponse
    {
        $testCase = DB::transaction(function () use ($request) {
            $testCase = TestCase::create([
                ...$request->safe()->only(['title', 'description', 'classification_id', 'template_id', 'status_id']),
                'created_by' => $request->user()->id,
            ]);

            foreach ($request->safe()->array('steps') as $index => $step) {
                $testCase->steps()->create([
                    'order' => $index + 1,
                    'description' => $step['description'],
                    'expected_result' => $step['expected_result'] ?? null,
                ]);
            }

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
        $testCase->load([
            'classification:id,name',
            'template:id,title',
            'status:id,name,color',
            'creator:id,name',
            'assignee:id,name',
            'steps',
            'executions.executor:id,name',
            'requirements' => fn ($query) => $query->orderBy('code'),
        ]);

        return Inertia::render('test-cases/show', [
            'testCase' => $testCase,
            'assignableUsers' => $request->user()->hasRole('qa')
                ? User::query()
                    ->whereHas('role', fn ($query) => $query->whereIn('slug', ['qa', 'developer']))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
            'executionStatuses' => ['APROVADO', 'REPROVADO', 'BLOQUEADO', 'PENDENTE'],
            'availableRequirements' => Requirement::query()->orderBy('code')->get(['id', 'code', 'title']),
        ]);
    }

    public function storeExecution(StoreExecutionRequest $request, TestCase $testCase): RedirectResponse
    {
        $testCase->executions()->create([
            ...$request->validated(),
            'executed_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Execution registered.'),
        ]);

        return to_route('test-cases.show', $testCase);
    }

    /**
     * Show the form for editing the specified test case.
     */
    public function edit(TestCase $testCase): Response
    {
        $testCase->load('steps');

        return Inertia::render('test-cases/edit', [
            'testCase' => $testCase,
            'classifications' => Classification::query()->orderBy('name')->get(['id', 'name']),
            'templates' => TestTemplate::query()->orderBy('title')->get(['id', 'title']),
            'statuses' => TestCaseStatus::query()->orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    /**
     * Update the specified test case in storage.
     */
    public function update(UpdateTestCaseRequest $request, TestCase $testCase): RedirectResponse
    {
        DB::transaction(function () use ($request, $testCase) {
            $testCase->update(
                $request->safe()->only(['title', 'description', 'classification_id', 'template_id', 'status_id'])
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
     * Update the status of multiple test cases at once.
     */
    public function bulkUpdateStatus(BulkUpdateTestCaseStatusRequest $request): RedirectResponse
    {
        $count = TestCase::query()
            ->whereIn('id', $request->validated('ids'))
            ->update(['status_id' => $request->validated('status_id')]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':count test case(s) updated.', ['count' => $count]),
        ]);

        return back();
    }

    /**
     * Assign the specified test case to another user.
     */
    public function assign(AssignTestCaseRequest $request, TestCase $testCase): RedirectResponse
    {
        $testCase->update($request->safe()->only(['assigned_to']));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $request->validated('assigned_to')
                ? __('Test case assigned.')
                : __('Test case unassigned.'),
        ]);

        return to_route('test-cases.show', $testCase);
    }

    /**
     * Remove the specified test case from storage.
     */
    public function deleteTestCase(TestCase $testCase): RedirectResponse
    {
        $title = $testCase->title;

        $testCase->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Test case ":title" deleted.', ['title' => $title]),
        ]);

        return to_route('test-cases.index');
    }

    /**
     * Link the test case to a requirement (RF017).
     */
    public function linkRequirement(LinkRequirementRequest $request, TestCase $testCase): RedirectResponse
    {
        $testCase->requirements()->syncWithoutDetaching([$request->validated('requirement_id')]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requisito vinculado.'),
        ]);

        return back();
    }

    /**
     * Remove the link between the test case and a requirement.
     */
    public function unlinkRequirement(Request $request, TestCase $testCase): RedirectResponse
    {
        abort_unless($request->user()->hasRole('qa'), 403);

        $testCase->requirements()->detach($request->integer('requirement_id'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requisito desvinculado.'),
        ]);

        return back();
    }
}
