<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'Draft' => 'Rascunho',
            'Ready' => 'Pronto',
            'Obsolete' => 'Obsoleto',
        ] as $currentName => $newName) {
            $currentId = DB::table('test_case_statuses')
                ->where('name', $currentName)
                ->value('id');
            $newId = DB::table('test_case_statuses')
                ->where('name', $newName)
                ->value('id');

            if ($currentId && $newId) {
                DB::table('test_cases')
                    ->where('status_id', $currentId)
                    ->update(['status_id' => $newId]);
                DB::table('test_case_statuses')->where('id', $currentId)->delete();
            } elseif ($currentId) {
                DB::table('test_case_statuses')
                    ->where('id', $currentId)
                    ->update(['name' => $newName]);
            }
        }
    }

    public function down(): void
    {
        foreach ([
            'Rascunho' => 'Draft',
            'Pronto' => 'Ready',
            'Obsoleto' => 'Obsolete',
        ] as $currentName => $oldName) {
            DB::table('test_case_statuses')
                ->where('name', $currentName)
                ->update(['name' => $oldName]);
        }
    }
};