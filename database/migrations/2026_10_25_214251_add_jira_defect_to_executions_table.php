<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A failed execution can be reported as a Bug in Jira. Keep the key of
     * that issue and the last status read from Jira, so the test case knows
     * whether the defect is still open when it is run again.
     */
    public function up(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->string('jira_defect_key', 50)->nullable()->after('obtained_output');
            $table->string('jira_defect_status', 100)->nullable()->after('jira_defect_key');
            $table->boolean('jira_defect_resolved')->default(false)->after('jira_defect_status');
            $table->foreignId('jira_defect_reported_by')->nullable()->after('jira_defect_resolved')->constrained('users')->nullOnDelete();
            $table->timestamp('jira_defect_reported_at')->nullable()->after('jira_defect_reported_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jira_defect_reported_by');
            $table->dropColumn(['jira_defect_key', 'jira_defect_status', 'jira_defect_resolved', 'jira_defect_reported_at']);
        });
    }
};
