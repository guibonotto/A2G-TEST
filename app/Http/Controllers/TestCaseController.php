<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestCases\StoreTestCaseRequest;
use App\Http\Requests\TestCases\UpdateTestCaseRequest;
use App\Models\Classification;
use App\Models\TestCase;
use App\Models\TestTemplate;
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

        $testCases = TestCase::query()
            ->with(['classification:id,name', 'creator:id,name'])
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
            ->latest()
            ->get(['id', 'title', 'classification_id', 'created_by', 'created_at']);

        return Inertia::render('test-cases/index', [
            'testCases' => $testCases,
            'classifications' => Classification::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'classification_id' => $classificationId,
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
        ]);
    }

    /**
     * Update the specified test case in storage.
     */
    public function update(UpdateTestCaseRequest $request, TestCase $testCase): RedirectResponse
    {
        DB::transaction(function () use ($request, $testCase) {
            $testCase->update(
                $request->safe()->only(['title', 'description', 'classification_id', 'template_id'])
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

    public function test_index_can_be_filtered_by_search_term(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $matching = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        TestCaseModel::create([
            'title' => 'Cadastro de usuário',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('test-cases.index', ['search' => 'login']));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/index')
            ->has('testCases', 1)
            ->where('testCases.0.id', $matching->id)
        );
    }

    public function test_index_can_be_filtered_by_id(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Funcional']);

        $matching = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        TestCaseModel::create([
            'title' => 'Cadastro de usuário',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('test-cases.index', ['search' => (string) $matching->id]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/index')
            ->has('testCases', 1)
            ->where('testCases.0.id', $matching->id)
        );
    }

    public function test_index_can_be_filtered_by_classification(): void
    {
        $user = User::factory()->create();
        $classification = Classification::create(['name' => 'Unitário']);
        $otherClassification = Classification::create(['name' => 'Integração']);

        $matching = TestCaseModel::create([
            'title' => 'Login com credenciais válidas',
            'classification_id' => $classification->id,
            'created_by' => $user->id,
        ]);
        TestCaseModel::create([
            'title' => 'Cadastro de usuário',
            'classification_id' => $otherClassification->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('test-cases.index', ['classification_id' => $classification->id]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('test-cases/index')
            ->has('testCases', 1)
            ->where('testCases.0.id', $matching->id)
        );
    }
}
