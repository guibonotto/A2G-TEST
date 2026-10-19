<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['name' => 'Draft', 'color' => 'warning'],
            ['name' => 'Ready', 'color' => 'success'],
            ['name' => 'Obsolete', 'color' => 'secondary'],
        ] as $situation) {
            DB::table('test_case_statuses')->updateOrInsert(
                ['name' => $situation['name']],
                ['color' => $situation['color']],
            );
        }

        $readyId = DB::table('test_case_statuses')->where('name', 'Ready')->value('id');
        $legacyIds = DB::table('test_case_statuses')
            ->whereIn('name', ['Passed', 'Failed', 'Pending', 'Cancelled'])
            ->pluck('id');

        DB::table('test_cases')->whereIn('status_id', $legacyIds)->update(['status_id' => $readyId]);
        DB::table('test_case_statuses')->whereIn('id', $legacyIds)->delete();
    }

    public function down(): void
    {
        DB::table('test_case_statuses')->whereIn('name', ['Draft', 'Ready', 'Obsolete'])->delete();
    }
};