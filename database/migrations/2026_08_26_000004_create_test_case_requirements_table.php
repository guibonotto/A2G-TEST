<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_case_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requirement_id')->constrained()->cascadeOnDelete();
            $table->unique(['test_case_id', 'requirement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_case_requirements');
    }
};
