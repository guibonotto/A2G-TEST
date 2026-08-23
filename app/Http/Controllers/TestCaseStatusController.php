<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestCaseStatuses\StoreTestCaseStatusRequest;
use App\Http\Requests\TestCaseStatuses\UpdateTestCaseStatusRequest;
use App\Models\TestCaseStatus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TestCaseStatusController extends Controller
{
    /**
     * Display a listing of the test case statuses.
     */
    public function index(): Response
    {
        return Inertia::render('management/statuses/index', [
            'statuses' => TestCaseStatus::query()
                ->withCount('testCases')
                ->orderBy('name')
                ->get(['id', 'name', 'color']),
        ]);
    }

    /**
     * Store a newly created test case status in storage.
     */
    public function store(StoreTestCaseStatusRequest $request): RedirectResponse
    {
        $status = TestCaseStatus::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Status ":name" created.', ['name' => $status->name]),
        ]);

        return back();
    }

    /**
     * Update the specified test case status in storage.
     */
    public function update(UpdateTestCaseStatusRequest $request, TestCaseStatus $testCaseStatus): RedirectResponse
    {
        $testCaseStatus->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Status ":name" updated.', ['name' => $testCaseStatus->name]),
        ]);

        return back();
    }

    /**
     * Remove the specified test case status from storage.
     */
    public function destroy(TestCaseStatus $testCaseStatus): RedirectResponse
    {
        $name = $testCaseStatus->name;

        $testCaseStatus->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Status ":name" deleted.', ['name' => $name]),
        ]);

        return back();
    }
}
