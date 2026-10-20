<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_cases', function (Blueprint $table): void {
            $table->foreignId('classification_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table): void {
            $table->foreignId('classification_id')->nullable(false)->change();
        });
    }
};