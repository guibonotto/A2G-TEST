<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_case_id')->constrained('test_cases')->cascadeOnDelete();
            $table->foreignId('executed_by')->constrained('users')->cascadeOnDelete();
            $table->string('status');
            $table->text('comment')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->dateTime('execution_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executions');
    }
};