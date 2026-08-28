<?php

namespace App\Http\Controllers;

use App\Http\Requests\Accounts\UpdateAccountRoleRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display a listing of the user accounts.
     */
    public function index(): Response
    {
        return Inertia::render('management/accounts/index', [
            'accounts' => User::query()
                ->with('role:id,name,slug')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role_id', 'created_at']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name', 'slug']),
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
