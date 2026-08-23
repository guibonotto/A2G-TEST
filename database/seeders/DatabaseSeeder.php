<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\Classification;
use App\Models\Role;
use App\Models\TestCaseStatus;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $allPermissions = array_map(fn (Permission $permission) => $permission->value, Permission::cases());

        $roles = [
            ['name' => 'QA / Tester',   'slug' => 'qa', 'permissions' => $allPermissions],
            ['name' => 'Desenvolvedor',  'slug' => 'developer', 'permissions' => []],
            ['name' => 'Administrador',  'slug' => 'admin', 'permissions' => $allPermissions],
            ['name' => 'Visualizador',   'slug' => 'viewer', 'permissions' => []],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                ['name' => $role['name'], 'permissions' => $role['permissions']]
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
