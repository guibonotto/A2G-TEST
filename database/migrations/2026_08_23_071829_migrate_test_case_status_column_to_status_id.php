<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The default statuses, matching the values previously hardcoded in the TestCaseStatus enum.
     *
     * @var array<string, array{name: string, color: string}>
     */
    private array $defaultStatuses = [
        'APROVADO' => ['name' => 'Aprovado', 'color' => 'success'],
        'REPROVADO' => ['name' => 'Reprovado', 'color' => 'destructive'],
        'PENDENTE' => ['name' => 'Pendente', 'color' => 'warning'],
        'CANCELADO' => ['name' => 'Cancelado', 'color' => 'secondary'],
        'REGRESSÃO' => ['name' => 'Regressão', 'color' => 'info'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $statusIds = [];

        foreach ($this->defaultStatuses as $oldValue => $status) {
            $statusIds[$oldValue] = DB::table('test_case_statuses')->insertGetId($status);
        }

        Schema::table('test_cases', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('status')->constrained('test_case_statuses')->nullOnDelete();
        });

        foreach ($statusIds as $oldValue => $statusId) {
            DB::table('test_cases')->where('status', $oldValue)->update(['status_id' => $statusId]);
        }

        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->string('status')->nullable()->after('status_id');
        });

        foreach ($this->defaultStatuses as $oldValue => $status) {
            DB::table('test_cases')
                ->whereIn('status_id', DB::table('test_case_statuses')->where('name', $status['name'])->pluck('id'))
                ->update(['status' => $oldValue]);
        }

        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_id');
        });

        DB::table('test_case_statuses')->whereIn('name', collect($this->defaultStatuses)->pluck('name'))->delete();
    }
};
