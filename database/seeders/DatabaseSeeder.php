<?php

namespace Database\Seeders;

use App\Models\Classification;
use App\Models\Role;
use App\Models\TestCaseStatus;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'QA / Tester',   'slug' => 'qa'],
            ['name' => 'Desenvolvedor',  'slug' => 'developer'],
            ['name' => 'Administrador',  'slug' => 'admin'],
            ['name' => 'Visualizador',   'slug' => 'viewer'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                ['uuid' => Str::uuid(), 'name' => $role['name']]
            );
        }

        $demoUsers = [
            ['name' => 'Administrador Demo', 'email' => 'admin@a2gtest.com', 'role' => 'admin'],
            ['name' => 'QA Demo', 'email' => 'qa@a2gtest.com', 'role' => 'qa'],
            ['name' => 'Visualizador Demo', 'email' => 'viewer@a2gtest.com', 'role' => 'viewer'],
        ];

        foreach ($demoUsers as $demoUser) {
            User::firstOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'role_id' => Role::where('slug', $demoUser['role'])->value('id'),
                ]
            );
        }

        $classifications = [
            ['name' => 'Unitário', 'description' => 'Testes de unidade verificam o comportamento de componentes individuais do sistema.'],
            ['name' => 'Integração', 'description' => 'Testes de integração avaliam a interação entre diferentes módulos ou serviços.'],
        ];
        foreach ($classifications as $classification) {
            Classification::firstOrCreate(['name' => $classification['name']], $classification);
        }

        $statuses = [
            ['name' => 'Aprovado', 'color' => 'success'],
            ['name' => 'Reprovado', 'color' => 'destructive'],
            ['name' => 'Pendente', 'color' => 'warning'],
            ['name' => 'Cancelado', 'color' => 'secondary'],
            ['name' => 'Regressão', 'color' => 'info'],
        ];
        foreach ($statuses as $status) {
            TestCaseStatus::firstOrCreate(['name' => $status['name']], $status);
        }
    }
}
