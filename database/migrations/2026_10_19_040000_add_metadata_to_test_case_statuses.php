<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_case_statuses', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
            $table->string('category')->default('in_progress')->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('test_case_statuses', function (Blueprint $table): void {
            $table->dropColumn(['description', 'category']);
        });
    }
};