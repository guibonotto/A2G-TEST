<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The project owner's role is named after the testing process it runs
     * (ISTQB's Test Manager) instead of a generic "Administrator". Only the
     * label changes: the slug stays `admin`, and a name customised by the
     * users is left untouched.
     */
    public function up(): void
    {
        DB::table('roles')->where('slug', Role::ADMIN)->where('name', 'Administrator')->update(['name' => 'Test Manager']);
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', Role::ADMIN)->where('name', 'Test Manager')->update(['name' => 'Administrator']);
    }
};
