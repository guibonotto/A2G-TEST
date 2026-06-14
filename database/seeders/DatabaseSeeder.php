<?php

namespace Database\Seeders;

use App\Models\Role;
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
    }
}
