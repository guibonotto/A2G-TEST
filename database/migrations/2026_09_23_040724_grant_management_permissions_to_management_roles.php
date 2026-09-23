<?php

use App\Enums\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * The earlier backfill only touched roles whose permission set was null, so a
     * management role that already held an empty (or partial) set kept it and its
     * users hit a 403 on every screen behind a permission. Grant the management
     * roles the full set, keeping anything already stored.
     */
    public function up(): void
    {
        $allPermissions = array_column(Permission::cases(), 'value');

        DB::table('roles')
            ->whereIn('slug', ['qa', 'admin'])
            ->get(['id', 'slug', 'permissions'])
            ->each(function (object $role) use ($allPermissions): void {
                $current = json_decode($role->permissions ?? '[]', true) ?: [];
                $granted = array_values(array_unique([...$current, ...$allPermissions]));

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($granted)]);

                Log::info('Granted management permissions.', [
                    'role' => $role->slug,
                    'before' => $current,
                    'after' => $granted,
                ]);
            });
    }

    public function down(): void
    {
        // Data backfill; nothing to revert.
    }
};
