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
            /**
             * Ordered by the hierarchy that actually governs who may edit
             * whom. Alphabetical order put Developer above QA on screen while
             * the rules said the opposite.
             */
            'roles' => Role::query()
                ->get(['id', 'name', 'slug', 'permissions'])
                ->sortBy(fn (Role $role): int => Role::rankOf($role->slug))
                ->values()
                ->map(fn (Role $role): array => [
                    ...$role->only(['id', 'name', 'slug']),
                    'permissions' => $role->effectivePermissions(),
                    'effective_permissions' => $role->effectivePermissions(),
                    'can_edit' => $user->canEditPermissionsOf($role),
                    /** Read-only roles hold nothing, so the boxes are inert. */
                    'read_only' => $role->slug === Role::VIEWER,
                ]),
            'availablePermissions' => collect(Permission::cases())
                ->map(fn (Permission $permission) => [
                    'value' => $permission->value,
                    'label' => $permission->label(),
                    'area' => $permission->area(),
                    'administrative' => $permission->isAdministrative(),
                    /** Permissions that have to be granted alongside this one. */
                    'requires' => array_map(
                        fn (Permission $required): string => $required->value,
                        $permission->requires(),
                    ),
                ])
                ->values(),
            /** Shown on the page so the editing rule is not a guess. */
            'hierarchy' => Role::hierarchy(),
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
