<?php

namespace App\Http\Controllers;

use App\Http\Requests\Accounts\UpdateAccountRoleRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display a listing of the user accounts.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('management/accounts/index', [
            'accounts' => User::query()
                ->with('role:id,name,slug')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role_id', 'created_at'])
                ->map(fn (User $account): array => [
                    ...$account->only(['id', 'name', 'email', 'role_id', 'created_at']),
                    'role' => $account->role?->only(['id', 'name', 'slug']),
                    'can_change_role' => $user->canChangeRoleOf($account),
                ]),
            'roles' => Role::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Role $role): array => [
                    ...$role->only(['id', 'name', 'slug']),
                    'assignable' => $user->canAssignRole($role),
                ]),
        ]);
    }

    /**
     * Update the role assigned to the specified user account.
     */
    public function update(UpdateAccountRoleRequest $request, User $account): RedirectResponse
    {
        $account->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role of ":name" updated.', ['name' => $account->name]),
        ]);

        return back();
    }
}
