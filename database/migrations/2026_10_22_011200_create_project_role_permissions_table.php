<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a role may do inside a project. Only the roles a project changed get a
 * row; every other role keeps the default set stored on the role itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->json('permissions');
            $table->timestamps();
            $table->unique(['project_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_role_permissions');
    }
};
