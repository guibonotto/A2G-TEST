<?php

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permissions now cover the testing work, not only the administration of
     * the project. This realigns the seeded roles with that split:
     *
     * - the administrator keeps everything;
     * - the tester gains the test case and execution permissions and loses
     *   the requirements one, since requirement scope belongs to whoever owns
     *   the product rather than to whoever tests it;
     * - the developer runs tests;
     * - the viewer holds nothing, which is what its name promises.
     *
     * Per-project overrides are realigned the same way, except that a project
     * which deliberately granted its tester the requirements permission keeps
     * it: that is a local decision, not the seeded default.
     */
    public function up(): void
    {
        $byRole = [
            'qa' => Permission::forTesters(),
            'developer' => Permission::forDevelopers(),
            'viewer' => [],
        ];

        foreach (Role::query()->get(['id', 'slug', 'permissions']) as $role) {
            if ($role->slug === Role::ADMIN) {
                $role->forceFill(['permissions' => Permission::values()])->save();

                continue;
            }

            if (! array_key_exists($role->slug, $byRole)) {
                continue;
            }

            $role->forceFill(['permissions' => $byRole[$role->slug]])->save();
        }

        if (! DB::getSchemaBuilder()->hasTable('project_role_permissions')) {
            return;
        }

        $slugsById = Role::query()->pluck('slug', 'id')->all();

        foreach (DB::table('project_role_permissions')->get() as $row) {
            $slug = $slugsById[$row->role_id] ?? null;

            if ($slug === null) {
                continue;
            }

            if ($slug === Role::ADMIN) {
                DB::table('project_role_permissions')
                    ->where('id', $row->id)
                    ->update(['permissions' => json_encode(Permission::values())]);

                continue;
            }

            if (! array_key_exists($slug, $byRole)) {
                continue;
            }

            $existing = json_decode((string) $row->permissions, true);
            $existing = is_array($existing) ? $existing : [];

            $realigned = $byRole[$slug];

            /** A project that chose to let its testers own requirements keeps that. */
            if ($slug === 'qa' && in_array(Permission::ManageRequirements->value, $existing, true)) {
                $realigned[] = Permission::ManageRequirements->value;
            }

            DB::table('project_role_permissions')
                ->where('id', $row->id)
                ->update(['permissions' => json_encode(array_values(array_unique($realigned)))]);
        }
    }

    /**
     * The previous sets cannot be told apart from deliberate ones, so this is
     * not reversed automatically.
     */
    public function down(): void {}
};
