<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestTemplates\StoreTestTemplateRequest;
use App\Http\Requests\TestTemplates\UpdateTestTemplateRequest;
use App\Models\Classification;
use App\Models\TestTemplate;
use App\Models\TestTemplateStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TestTemplateController extends Controller
{
    /**
     * Display a listing of the test templates.
     */
    public function index(): Response
    {
        return Inertia::render('management/templates/index', [
            'templates' => TestTemplate::query()
                ->with(['creator:id,name', 'classification:id,name', 'steps'])
                ->withCount('testCases')
                ->orderBy('title')
                ->get(),
            'classifications' => Classification::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created test template and its steps.
     */
    public function store(StoreTestTemplateRequest $request): RedirectResponse
    {
        $template = DB::transaction(function () use ($request): TestTemplate {
            $template = TestTemplate::create([
                ...$request->safe()->only(['title', 'description', 'classification_id']),
                'created_by' => $request->user()->id,
            ]);

            $this->syncSteps($template, $request->safe()->array('steps'));

            return $template;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Template ":title" created.', ['title' => $template->title]),
        ]);

        return back();
    }

    /**
     * Return the data a test case form needs to apply this template.
     */
    public function show(TestTemplate $testTemplate): JsonResponse
    {
        return response()->json([
            'description' => $testTemplate->description,
            'classification_id' => $testTemplate->classification_id,
            'steps' => $testTemplate->steps
                ->map(fn (TestTemplateStep $step) => [
                    'description' => $step->description,
                    'expected_result' => $step->expected_result,
                ])
                ->values(),
        ]);
    }

    /**
     * Update the specified test template, replacing its steps.
     */
    public function update(UpdateTestTemplateRequest $request, TestTemplate $testTemplate): RedirectResponse
    {
        DB::transaction(function () use ($request, $testTemplate): void {
            $testTemplate->update($request->safe()->only(['title', 'description', 'classification_id']));

            $testTemplate->steps()->delete();
            $this->syncSteps($testTemplate, $request->safe()->array('steps'));
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Template ":title" updated.', ['title' => $testTemplate->title]),
        ]);

        return back();
    }

    /**
     * Remove the specified test template. Test cases created from it keep
     * their own steps and only lose the reference.
     */
    public function destroy(TestTemplate $testTemplate): RedirectResponse
    {
        $title = $testTemplate->title;

        $testTemplate->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Template ":title" deleted.', ['title' => $title]),
        ]);

        return back();
    }

    /**
     * Persist the given steps, numbering them by their position in the list.
     *
     * @param  array<int, array{description: string, expected_result?: string|null}>  $steps
     */
    private function syncSteps(TestTemplate $template, array $steps): void
    {
        foreach ($steps as $index => $step) {
            $template->steps()->create([
                'order' => $index + 1,
                'description' => $step['description'],
                'expected_result' => $step['expected_result'] ?? null,
            ]);
        }
    }
}
