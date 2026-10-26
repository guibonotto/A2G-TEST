<?php

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seeded QA roles were given every permission, which made a tester an
     * administrator in everything but the name: they could manage accounts
     * and hand out roles. This revokes the administrative permissions from
     * every role except the administrator, in the global defaults and in the
     * per-project overrides, and grants the new requirements permission to
     * the roles that already did that work.
     */
    public function up(): void
    {
        $administrative = array_values(array_map(
            fn (Permission $permission): string => $permission->value,
            array_filter(Permission::cases(), fn (Permission $permission): bool => $permission->isAdministrative()),
        ));

        foreach (Role::query()->get(['id', 'slug', 'permissions']) as $role) {
            if ($role->slug === Role::ADMIN) {
                continue;
            }

            $permissions = $role->permissions;

            if ($permissions === null) {
                continue;
            }

            $kept = array_values(array_diff($permissions, $administrative));

            /** Roles that managed requirements before keep doing so. */
            if ($role->slug === 'qa' && ! in_array(Permission::ManageRequirements->value, $kept, true)) {
                $kept[] = Permission::ManageRequirements->value;
            }

            if ($kept !== $permissions) {
                $role->forceFill(['permissions' => $kept])->save();
            }
        }

        if (! DB::getSchemaBuilder()->hasTable('project_role_permissions')) {
            return;
        }

        $adminRoleIds = Role::query()->where('slug', Role::ADMIN)->pluck('id')->all();

        foreach (DB::table('project_role_permissions')->get() as $row) {
            if (in_array($row->role_id, $adminRoleIds, true)) {
                continue;
            }

            $permissions = json_decode((string) $row->permissions, true);

            if (! is_array($permissions)) {
                continue;
            }

            $kept = array_values(array_diff($permissions, $administrative));

            if ($kept !== $permissions) {
                DB::table('project_role_permissions')
                    ->where('id', $row->id)
                    ->update(['permissions' => json_encode($kept)]);
            }
        }
    }

    /**
     * The previous grants cannot be told apart from deliberate ones, so this
     * is not reversed automatically.
     */
    public function down(): void {}
};
