<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles stop being global: each project membership carries its own role, so a
 * person can be QA in one project and Viewer in another. Every existing member
 * keeps, in each of their projects, the role they had globally.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_user', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->after('user_id')->constrained('roles')->nullOnDelete();
        });

        DB::table('project_user')->update([
            'role_id' => DB::table('users')->select('role_id')->whereColumn('users.id', 'project_user.user_id'),
        ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
        });

        /** A global role can only hold one value, so the highest-ranked project role wins. */
        foreach (['viewer', 'developer', 'qa', 'admin'] as $slug) {
            $roleId = DB::table('roles')->where('slug', $slug)->value('id');

            if ($roleId !== null) {
                DB::table('users')
                    ->whereIn('id', DB::table('project_user')->where('role_id', $roleId)->select('user_id'))
                    ->update(['role_id' => $roleId]);
            }
        }

        Schema::table('project_user', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
