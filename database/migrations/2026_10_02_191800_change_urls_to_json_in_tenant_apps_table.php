<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_apps', function (Blueprint $table) {
            $table->dropColumn(['apk_url', 'web_url']);
        });

        Schema::table('tenant_apps', function (Blueprint $table) {
            $table->json('apk_url')->nullable();
            $table->json('web_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_apps', function (Blueprint $table) {
            $table->dropColumn(['apk_url', 'web_url']);
        });
        
        Schema::table('tenant_apps', function (Blueprint $table) {
            $table->string('apk_url')->nullable();
            $table->string('web_url')->nullable();
        });
    }
};
