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
        Schema::table('firebase_projects', function (Blueprint $table) {
            $table->string('firebase_tenant_id')->nullable()->after('firebase_database_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firebase_projects', function (Blueprint $table) {
            $table->dropColumn('firebase_tenant_id');
        });
    }
};
