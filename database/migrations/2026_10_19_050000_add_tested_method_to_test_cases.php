<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_cases', function (Blueprint $table): void {
            $table->string('tested_method')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table): void {
            $table->dropColumn('tested_method');
        });
    }
};