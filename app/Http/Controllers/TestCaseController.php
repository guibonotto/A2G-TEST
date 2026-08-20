<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestCases\StoreTestCaseRequest;
use App\Models\Classification;
use App\Models\TestCase;
use App\Models\TestTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TestCaseController extends Controller
{
    /**
     * Display a listing of the test cases.
     */
    public function index(): Response
    {
        $testCases = TestCase::query()
            ->with(['classification:id,name', 'creator:id,name'])
            ->withCount('steps')
            ->latest()
            ->get(['id', 'title', 'classification_id', 'created_by', 'created_at']);

        return Inertia::render('test-cases/index', [
            'testCases' => $testCases,
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
        ]);
    }

    /**
     * Store a newly created test case in storage.
     */
    public function store(StoreTestCaseRequest $request): RedirectResponse
    {
        $testCase = DB::transaction(function () use ($request) {
            $testCase = TestCase::create([
                ...$request->safe()->only(['title', 'description', 'classification_id', 'template_id']),
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
    public function show(TestCase $testCase): Response
    {
        $testCase->load(['classification:id,name', 'template:id,title', 'creator:id,name', 'steps']);

        return Inertia::render('test-cases/show', [
            'testCase' => $testCase,
        ]);
    }
}
