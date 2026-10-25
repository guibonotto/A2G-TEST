<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remember which OAuth scopes Atlassian granted. Connections made before
     * defect reporting existed only have read access, so the app has to know
     * when to ask an administrator to reconnect.
     */
    public function up(): void
    {
        Schema::table('jira_integrations', function (Blueprint $table) {
            $table->string('scopes')->nullable()->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jira_integrations', function (Blueprint $table) {
            $table->dropColumn('scopes');
        });
    }
};
