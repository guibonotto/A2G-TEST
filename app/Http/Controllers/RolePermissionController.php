<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\RolePermissions\UpdateRolePermissionsRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RolePermissionController extends Controller
{
    /**
     * Display the roles and their assigned permissions.
     */
    public function index(): Response
    {
        return Inertia::render('management/permissions/index', [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name', 'slug', 'permissions']),
            'availablePermissions' => collect(Permission::cases())
                ->map(fn (Permission $permission) => [
                    'value' => $permission->value,
                    'label' => $permission->label(),
                ])
                ->values(),
        ]);
    }

    /**
     * Update the permissions assigned to the specified role.
     */
    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Permissions of ":name" updated.', ['name' => $role->name]),
        ]);

        return back();
    }
}
