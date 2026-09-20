<?php

use App\Enums\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Roles created before permissions were enforced have a null permission set.
     * Grant management roles every permission and leave the others explicitly empty.
     */
    public function up(): void
    {
        $allPermissions = json_encode(array_column(Permission::cases(), 'value'));

        DB::table('roles')
            ->whereNull('permissions')
            ->whereIn('slug', ['qa', 'admin'])
            ->update(['permissions' => $allPermissions]);

        DB::table('roles')
            ->whereNull('permissions')
            ->update(['permissions' => json_encode([])]);
    }

    public function down(): void
    {
        // Data backfill; nothing to revert.
    }
};
