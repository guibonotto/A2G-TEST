<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The default catalog entries seeded in Portuguese, mapped to the English
     * names now used across the interface. Rows renamed by users are left
     * untouched, since only exact matches on the old name are updated.
     *
     * @var array<string, array<string, string>>
     */
    private array $translations = [
        'roles' => [
            'Desenvolvedor' => 'Developer',
            'Administrador' => 'Administrator',
            'Visualizador' => 'Viewer',
        ],
        'classifications' => [
            'Unitário' => 'Unit',
            'Integração' => 'Integration',
        ],
        'test_case_statuses' => [
            'Aprovado' => 'Passed',
            'Reprovado' => 'Failed',
            'Pendente' => 'Pending',
            'Cancelado' => 'Cancelled',
            'Regressão' => 'Regression',
        ],
    ];

    /**
     * The seeded classification descriptions, mapped to their English versions.
     *
     * @var array<string, string>
     */
    private array $classificationDescriptions = [
        'Testes de unidade verificam o comportamento de componentes individuais do sistema.' => 'Unit tests verify the behavior of individual components of the system.',
        'Testes de integração avaliam a interação entre diferentes módulos ou serviços.' => 'Integration tests assess the interaction between different modules or services.',
    ];

    public function up(): void
    {
        $this->rename($this->translations);

        foreach ($this->classificationDescriptions as $portuguese => $english) {
            DB::table('classifications')->where('description', $portuguese)->update(['description' => $english]);
        }
    }

    public function down(): void
    {
        $this->rename(array_map(array_flip(...), $this->translations));

        foreach ($this->classificationDescriptions as $portuguese => $english) {
            DB::table('classifications')->where('description', $english)->update(['description' => $portuguese]);
        }
    }

    /**
     * @param  array<string, array<string, string>>  $translations
     */
    private function rename(array $translations): void
    {
        foreach ($translations as $table => $names) {
            foreach ($names as $from => $to) {
                DB::table($table)->where('name', $from)->update(['name' => $to]);
            }
        }
    }
};
