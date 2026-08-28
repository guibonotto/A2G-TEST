<?php

namespace App\Http\Controllers;

use App\Http\Requests\Requirements\StoreRequirementRequest;
use App\Http\Requests\Requirements\UpdateRequirementRequest;
use App\Models\Requirement;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RequirementController extends Controller
{
    /**
     * Display a listing of the requirements.
     */
    public function index(): Response
    {
        return Inertia::render('management/requirements/index', [
            'requirements' => Requirement::query()
                ->withCount('testCases')
                ->with('creator:id,name')
                ->orderBy('code')
                ->get(['id', 'code', 'type', 'title', 'description', 'priority', 'status', 'created_by']),
        ]);
    }

    /**
     * Store a newly created requirement in storage.
     */
    public function store(StoreRequirementRequest $request): RedirectResponse
    {
        $requirement = Requirement::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requisito ":code" criado.', ['code' => $requirement->code]),
        ]);

        return back();
    }

    /**
     * Update the specified requirement in storage.
     */
    public function update(UpdateRequirementRequest $request, Requirement $requirement): RedirectResponse
    {
        $requirement->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requisito ":code" atualizado.', ['code' => $requirement->code]),
        ]);

        return back();
    }

    /**
     * Remove the specified requirement from storage.
     */
    public function destroy(Requirement $requirement): RedirectResponse
    {
        $code = $requirement->code;

        $requirement->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Requisito ":code" removido.', ['code' => $code]),
        ]);

        return back();
    }
}
