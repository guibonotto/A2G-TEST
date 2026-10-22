<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Statuses stop being shared by every project: each project gets its own copy
 * of the statuses that existed, and its test cases are moved to those copies,
 * so a project can add, rename or delete statuses without touching the others.
 */
return new class extends Migration
{
    /**
     * The statuses every project starts with when there is nothing to copy.
     *
     * @var list<array{name: string, color: string}>
     */
    private const DEFAULTS = [
        ['name' => 'Draft', 'color' => 'warning'],
        ['name' => 'Ready', 'color' => 'success'],
        ['name' => 'Obsolete', 'color' => 'secondary'],
    ];

    public function up(): void
    {
        Schema::table('test_case_statuses', function (Blueprint $table): void {
            $table->foreignId('project_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique(['name']);
            $table->unique(['project_id', 'name']);
        });

        $sharedStatuses = DB::table('test_case_statuses')->whereNull('project_id')->get();

        foreach (DB::table('projects')->pluck('id') as $projectId) {
            if ($sharedStatuses->isEmpty()) {
                foreach (self::DEFAULTS as $status) {
                    DB::table('test_case_statuses')->insert([...$status, 'project_id' => $projectId]);
                }

                continue;
            }

            foreach ($sharedStatuses as $status) {
                $copyId = DB::table('test_case_statuses')->insertGetId([
                    'project_id' => $projectId,
                    'name' => $status->name,
                    'description' => $status->description,
                    'color' => $status->color,
                    'category' => $status->category,
                ]);

                DB::table('test_cases')
                    ->where('project_id', $projectId)
                    ->where('status_id', $status->id)
                    ->update(['status_id' => $copyId]);
            }
        }

        DB::table('test_case_statuses')->whereNull('project_id')->delete();
    }

    public function down(): void
    {
        /** Merge every project's status back into a single shared one per name. */
        $sharedIds = [];

        foreach (DB::table('test_case_statuses')->orderBy('id')->get() as $status) {
            $sharedIds[$status->name] ??= $status->id;

            if ($sharedIds[$status->name] !== $status->id) {
                DB::table('test_cases')->where('status_id', $status->id)->update(['status_id' => $sharedIds[$status->name]]);
                DB::table('test_case_statuses')->where('id', $status->id)->delete();
            }
        }

        Schema::table('test_case_statuses', function (Blueprint $table): void {
            $table->dropUnique(['project_id', 'name']);
            $table->dropConstrainedForeignId('project_id');
            $table->unique('name');
        });
    }
};
