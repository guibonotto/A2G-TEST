<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectStoreRequest;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Show the page to create a new project or join an existing one.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('projects/index', [
            'myProjects' => $request->user()->projects()
                ->orderBy('name')
                ->get(['projects.id', 'projects.uuid', 'projects.name', 'projects.owner_id'])
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'uuid' => $project->uuid,
                    'name' => $project->name,
                    'isOwner' => $project->owner_id === $request->user()->id,
                ]),
        ]);
    }

    /**
     * Create a new project owned by the authenticated user.
     *
     * Whoever creates a project becomes its administrator, so they can manage
     * everything the project needs (members, roles, requirements...). The role
     * only applies to this project.
     */
    public function store(ProjectStoreRequest $request): RedirectResponse
    {
        $user = $request->user();

        $project = DB::transaction(function () use ($request, $user): Project {
            $project = $user->ownedProjects()->create([
                'name' => $request->validated('name'),
            ]);

            $project->addMember($user, Role::ADMIN);

            return $project;
        });

        session(['current_project_id' => $project->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created successfully.')]);

        return to_route('start');
    }

    public function invite(Request $request, string $token): RedirectResponse
    {
        $project = DB::transaction(function () use ($request, $token): Project {
            $invitation = ProjectInvitation::query()
                ->where('token_hash', hash('sha256', $token))
                ->whereNull('revoked_at')
                ->whereNull('accepted_at')
                ->with('project')
                ->lockForUpdate()
                ->firstOrFail();
            $project = $invitation->project;

            /** Newcomers start with the least access; the project's admin raises it in Manage accounts. */
            if ($project->members()->whereKey($request->user()->id)->doesntExist()) {
                $project->addMember($request->user(), Role::VIEWER);
            }

            $invitation->forceFill([
                'accepted_at' => now(),
                'accepted_by' => $request->user()->id,
            ])->save();

            return $project;
        });

        session(['current_project_id' => $project->id]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('You joined the ":name" project.', ['name' => $project->name])]);

        return to_route('projects.show', $project);
    }

    public function createInvite(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->owner_id === $request->user()->id, 403);

        $token = Str::random(64);
        $project->invitations()->create([
            'token_hash' => hash('sha256', $token),
        ]);

        session()->flash('invite_link', route('projects.invite', $token));

        return to_route('projects.show', $project);
    }

    public function revokeInvite(Request $request, Project $project, ProjectInvitation $invitation): RedirectResponse
    {
        abort_unless($project->owner_id === $request->user()->id, 403);
        abort_unless($invitation->project_id === $project->id, 404);

        $invitation->update(['revoked_at' => now()]);

        return to_route('projects.show', $project);
    }

    public function removeMember(Request $request, Project $project, User $user): RedirectResponse
    {
        abort_unless($project->owner_id === $request->user()->id, 403);
        abort_unless($user->isNot($request->user()) && $project->members()->whereKey($user->id)->exists(), 404);

        $project->members()->detach($user);

        return to_route('projects.show', $project);
    }

    /**
     * Set the given project (the user is already a member of) as the active project.
     */
    public function select(Request $request, Project $project): RedirectResponse
    {
        abort_unless(
            $project->members()->whereKey($request->user()->id)->exists(),
            403
        );

        session(['current_project_id' => $project->id]);

        return to_route('start');
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

        $isOwner = $project->owner_id === $request->user()->id;

        return Inertia::render('projects/show', [
            'project' => [
                'uuid' => $project->uuid,
                'name' => $project->name,
                'ownerId' => $project->owner_id,
                'isOwner' => $isOwner,
                'members' => $isOwner
                    ? $project->members()->get(['users.id', 'users.name', 'users.email'])
                    : [],
                'pendingInvites' => $isOwner
                    ? $project->invitations()
                        ->whereNull('accepted_at')
                        ->whereNull('revoked_at')
                        ->latest()
                        ->get(['id', 'created_at'])
                    : [],
            ],
            'inviteLink' => session('invite_link'),
        ]);
    }
}
