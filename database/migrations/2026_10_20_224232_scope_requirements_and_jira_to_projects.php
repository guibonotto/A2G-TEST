<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Requirements and the Jira connection used to be global; they now belong to
 * a project. Existing requirements move to the project of the test cases
 * linked to them; everything else goes to the oldest project.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['requirements', 'jira_integrations', 'jira_excluded_issues'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        $oldestProjectId = DB::table('projects')->min('id');

        DB::table('requirements')->orderBy('id')->each(function (object $requirement) use ($oldestProjectId): void {
            $linkedProjectId = DB::table('test_case_requirements')
                ->join('test_cases', 'test_cases.id', '=', 'test_case_requirements.test_case_id')
                ->where('test_case_requirements.requirement_id', $requirement->id)
                ->min('test_cases.project_id');

            DB::table('requirements')
                ->where('id', $requirement->id)
                ->update(['project_id' => $linkedProjectId ?? $oldestProjectId]);
        });

        DB::table('jira_integrations')->update(['project_id' => $oldestProjectId]);
        DB::table('jira_excluded_issues')->update(['project_id' => $oldestProjectId]);

        /** Without any project there is nowhere to keep these rows. */
        foreach (['requirements', 'jira_integrations', 'jira_excluded_issues'] as $tableName) {
            DB::table($tableName)->whereNull('project_id')->delete();
        }

        Schema::table('requirements', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
            $table->dropUnique(['code']);
            $table->unique(['project_id', 'code']);
        });

        Schema::table('jira_integrations', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
            $table->unique('project_id');
        });

        Schema::table('jira_excluded_issues', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
            $table->dropUnique(['issue_key']);
            $table->unique(['project_id', 'issue_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'code']);
            $table->unique('code');
        });

        Schema::table('jira_integrations', function (Blueprint $table) {
            $table->dropUnique(['project_id']);
        });

        Schema::table('jira_excluded_issues', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'issue_key']);
            $table->unique('issue_key');
        });

        foreach (['requirements', 'jira_integrations', 'jira_excluded_issues'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('project_id');
            });
        }
    }
};
