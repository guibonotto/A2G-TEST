<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\RolePermissions\UpdateRolePermissionsRequest;
use App\Models\Project;
use App\Models\ProjectRolePermission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RolePermissionController extends Controller
{
    /**
     * Display the roles and the permissions each one holds in the current project.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('management/permissions/index', [
            'roles' => Role::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'permissions'])
                ->map(fn (Role $role): array => [
                    ...$role->only(['id', 'name', 'slug']),
                    'permissions' => $role->effectivePermissions(),
                    'effective_permissions' => $role->effectivePermissions(),
                    'can_edit' => $user->canEditPermissionsOf($role),
                ]),
            'availablePermissions' => collect(Permission::cases())
                ->map(fn (Permission $permission) => [
                    'value' => $permission->value,
                    'label' => $permission->label(),
                ])
                ->values(),
        ]);
    }

    /**
     * Update what the role may do in the current project, leaving its
     * permissions in every other project untouched.
     */
    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        ProjectRolePermission::updateOrCreate(
            ['project_id' => Project::current()->id, 'role_id' => $role->id],
            ['permissions' => $request->validated('permissions')],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Permissions of ":name" updated.', ['name' => $role->name]),
        ]);

        return back();
    }
}
