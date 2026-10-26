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
        /**
         * The list showed names only, so two projects looked identical. The
         * count and the last activity are what tell them apart at a glance.
         */
        $projects = $request->user()->projects()
            ->withCount(['testCases' => fn ($query) => $query->whereNull('test_cases.deleted_at')])
            ->withMax(['testCases as last_activity_at' => fn ($query) => $query->whereNull('test_cases.deleted_at')], 'updated_at')
            ->orderBy('name')
            ->get(['projects.id', 'projects.uuid', 'projects.name', 'projects.owner_id']);
        $roles = Role::query()
            ->whereIn('id', $projects->pluck('pivot.role_id')->filter())
            ->get(['id', 'name', 'slug'])
            ->keyBy('id');

        return Inertia::render('projects/index', [
            'currentProject' => optional(Project::current())->only(['id', 'uuid', 'name', 'owner_id']),
            'myProjects' => $projects->map(fn (Project $project): array => [
                'id' => $project->id,
                'uuid' => $project->uuid,
                'name' => $project->name,
                'isOwner' => $project->owner_id === $request->user()->id,
                'role' => $roles->get($project->pivot->role_id)?->only(['name', 'slug']),
                'test_cases_count' => $project->test_cases_count,
                'last_activity_at' => $project->last_activity_at,
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
                /** An expired link is as good as revoked. */
                ->where(fn ($query) => $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now()))
                ->with('project')
                ->lockForUpdate()
                ->firstOrFail();
            $project = $invitation->project;
            $role = $invitation->role ?? Role::firstOrCreate(
                ['slug' => Role::VIEWER],
                ['name' => 'Viewer'],
            );

            if ($project->members()->whereKey($request->user()->id)->doesntExist()) {
                $project->addMember($request->user(), $role->slug);
            }

            $invitation->forceFill([
                'accepted_at' => now(),
                'accepted_by' => $request->user()->id,
            ])->save();

            return $project;
        });

        $role = $project->members()
            ->whereKey($request->user()->id)
            ->firstOrFail()
            ->roleIn($project);

        session(['current_project_id' => $project->id]);
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('You joined the ":project" project as ":role".', [
                'project' => $project->name,
                'role' => $role?->name ?? __('Viewer'),
            ]),
        ]);

        return to_route('projects.show', $project);
    }

    public function createInvite(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->owner_id === $request->user()->id, 403);

        $roleSlug = $request->validate([
            'role' => ['sometimes', 'string', 'in:'.implode(',', array_keys(Role::LEVELS))],
        ])['role'] ?? Role::VIEWER;
        $role = Role::firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => Str::headline($roleSlug)],
        );
        $token = Str::random(64);
        $project->invitations()->create([
            'role_id' => $role->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(ProjectInvitation::LIFETIME_IN_DAYS),
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

        /** Drives the onboarding card: a project with cases is not "ready to start". */
        $testCaseCount = $project->testCases()->count();

        return Inertia::render('projects/show', [
            'project' => [
                'uuid' => $project->uuid,
                'name' => $project->name,
                'testCaseCount' => $testCaseCount,
                'ownerId' => $project->owner_id,
                'isOwner' => $isOwner,
                'myRole' => $request->user()->roleIn($project)?->only(['id', 'name', 'slug']),
                'members' => $isOwner
                    ? $project->members()->get(['users.id', 'users.name', 'users.email'])
                    : [],
                'pendingInvites' => $isOwner
                    ? $project->invitations()
                        ->whereNull('accepted_at')
                        ->whereNull('revoked_at')
                        ->latest()
                        ->with('role:id,name,slug')
                        ->get(['id', 'role_id', 'created_at'])
                        ->map(fn (ProjectInvitation $invitation): array => [
                            'id' => $invitation->id,
                            'created_at' => $invitation->created_at,
                            'role' => $invitation->role?->only(['name', 'slug']),
                        ])
                    : [],
            ],
            'inviteLink' => session('invite_link'),
            'roles' => $isOwner
                ? Role::query()->orderByDesc('id')->get(['id', 'name', 'slug'])
                : [],
        ]);
    }
}
