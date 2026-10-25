<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_cases', function (Blueprint $table): void {
            $table->unsignedInteger('version')->default(1);
        });

        Schema::table('executions', function (Blueprint $table): void {
            $table->unsignedInteger('test_case_version')->default(1);
            $table->json('test_case_snapshot')->nullable();
        });

        DB::table('test_cases')
            ->orderBy('id')
            ->each(function (object $testCase): void {
                $step = DB::table('test_steps')
                    ->where('test_case_id', $testCase->id)
                    ->orderBy('order')
                    ->first();

                DB::table('executions')
                    ->where('test_case_id', $testCase->id)
                    ->update([
                        'test_case_snapshot' => json_encode([
                            'title' => $testCase->title,
                            'tested_method' => $testCase->tested_method,
                            'description' => $testCase->description,
                            'input' => $step?->description,
                            'expected_output' => $step?->expected_result,
                        ], JSON_THROW_ON_ERROR),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table): void {
            $table->dropColumn(['test_case_version', 'test_case_snapshot']);
        });

        Schema::table('test_cases', function (Blueprint $table): void {
            $table->dropColumn('version');
        });
    }
};
