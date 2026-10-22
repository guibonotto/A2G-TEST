<?php

namespace App\Http\Controllers;

use App\Http\Requests\Accounts\UpdateAccountRoleRequest;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display the members of the current project with the role each one holds in it.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $project = Project::current();
        $roles = Role::query()->orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('management/accounts/index', [
            'accounts' => $project->members()
                ->orderBy('name')
                ->get(['users.id', 'users.name', 'users.email', 'users.created_at'])
                ->map(fn (User $account): array => [
                    ...$account->only(['id', 'name', 'email', 'created_at']),
                    'role_id' => $account->pivot->role_id,
                    'role' => $roles->firstWhere('id', $account->pivot->role_id)?->only(['id', 'name', 'slug']),
                    'can_change_role' => $user->canChangeRoleOf($account),
                ]),
            'roles' => $roles->map(fn (Role $role): array => [
                ...$role->only(['id', 'name', 'slug']),
                'assignable' => $user->canAssignRole($role),
            ]),
        ]);
    }

    /**
     * Update the role the member holds in the current project.
     */
    public function update(UpdateAccountRoleRequest $request, User $account): RedirectResponse
    {
        Project::current()->members()->updateExistingPivot($account->id, ['role_id' => $request->validated('role_id')]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role of ":name" updated.', ['name' => $account->name]),
        ]);

        return back();
    }
}
