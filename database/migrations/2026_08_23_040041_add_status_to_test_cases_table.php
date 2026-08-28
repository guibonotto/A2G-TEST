<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The enum values here are hardcoded because the TestCaseStatus enum they
     * originally referenced was later replaced by the test_case_statuses table
     * (see the migrate_test_case_status_column_to_status_id migration).
     */
    public function up(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->enum('status', ['APROVADO', 'REPROVADO', 'PENDENTE', 'CANCELADO', 'REGRESSÃO'])
                ->default('PENDENTE')
                ->after('template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
