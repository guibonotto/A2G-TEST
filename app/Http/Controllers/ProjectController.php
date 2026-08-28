<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectJoinRequest;
use App\Http\Requests\ProjectStoreRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Show the page to create a new project or join an existing one.
     */
    public function index(): Response
    {
        return Inertia::render('projects/index');
    }

    /**
     * Create a new project owned by the authenticated user.
     */
    public function store(ProjectStoreRequest $request): RedirectResponse
    {
        $project = $request->user()->ownedProjects()->create([
            'name' => $request->validated('name'),
            'password' => $request->validated('password'),
        ]);

        $project->members()->attach($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Projeto criado com sucesso.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Join an existing project using its id and password.
     */
    public function join(ProjectJoinRequest $request): RedirectResponse
    {
        $project = $request->project();

        $project->members()->attach($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Você entrou no projeto ":name".', ['name' => $project->name])]);

        return to_route('projects.show', $project);
    }

    /**
     * Show a project the authenticated user belongs to.
     */
    public function show(Request $request, Project $project): Response
    {
        abort_unless(
            $project->members()->whereKey($request->user()->id)->exists(),
            403
        );

        return Inertia::render('projects/show', [
            'project' => [
                'uuid' => $project->uuid,
                'name' => $project->name,
                'isOwner' => $project->owner_id === $request->user()->id,
            ],
        ]);
    }
}
