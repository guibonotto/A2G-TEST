<?php

namespace App\Http\Controllers;

use App\Enums\TestCaseStatus;
use App\Http\Requests\TestCases\AssignTestCaseRequest;
use App\Http\Requests\TestCases\BulkUpdateTestCaseStatusRequest;
use App\Http\Requests\TestCases\StoreTestCaseRequest;
use App\Http\Requests\TestCases\UpdateTestCaseRequest;
use App\Models\Classification;
use App\Models\TestCase;
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
        $status = $request->enum('status', TestCaseStatus::class);
        $assignedToMe = $request->boolean('assigned_to_me');

        $testCases = TestCase::query()
            ->with(['classification:id,name', 'creator:id,name', 'assignee:id,name'])
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
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($assignedToMe, fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->latest()
            ->get(['id', 'title', 'classification_id', 'status', 'assigned_to', 'created_by', 'created_at']);

        return Inertia::render('test-cases/index', [
            'testCases' => $testCases,
            'classifications' => Classification::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => TestCaseStatus::cases(),
            'filters' => [
                'search' => $search,
                'classification_id' => $classificationId,
                'status' => $status,
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
            'statuses' => TestCaseStatus::cases(),
        ]);
    }

    /**
     * Store a newly created test case in storage.
     */
    public function store(StoreTestCaseRequest $request): RedirectResponse
    {
        $testCase = DB::transaction(function () use ($request) {
            $testCase = TestCase::create([
                ...$request->safe()->only(['title', 'description', 'classification_id', 'template_id', 'status']),
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
        $testCase->load(['classification:id,name', 'template:id,title', 'creator:id,name', 'assignee:id,name', 'steps']);

        return Inertia::render('test-cases/show', [
            'testCase' => $testCase,
            'assignableUsers' => $request->user()->hasRole('qa')
                ? User::query()
                    ->whereHas('role', fn ($query) => $query->whereIn('slug', ['qa', 'developer']))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
        ]);
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
            'statuses' => TestCaseStatus::cases(),
        ]);
    }

    /**
     * Update the specified test case in storage.
     */
    public function update(UpdateTestCaseRequest $request, TestCase $testCase): RedirectResponse
    {
        DB::transaction(function () use ($request, $testCase) {
            $testCase->update(
                $request->safe()->only(['title', 'description', 'classification_id', 'template_id', 'status'])
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
            ->update(['status' => $request->validated('status')]);

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
}
