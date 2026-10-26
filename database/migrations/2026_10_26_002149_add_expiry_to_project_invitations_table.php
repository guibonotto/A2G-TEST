<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An invite link with no end date is a standing key to the project: it
     * keeps working months after it was pasted into a chat. Links now expire,
     * and the ones already out there get a deadline counted from when they
     * were created.
     */
    public function up(): void
    {
        Schema::table('project_invitations', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('role_id');
        });

        DB::table('project_invitations')
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->update([
                'expires_at' => DB::raw("datetime(created_at, '+7 days')"),
            ]);
    }

    public function down(): void
    {
        Schema::table('project_invitations', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
