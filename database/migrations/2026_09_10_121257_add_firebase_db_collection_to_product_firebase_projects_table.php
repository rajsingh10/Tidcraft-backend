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
        Schema::table('product_firebase_projects', function (Blueprint $table) {
            $table->string('firebase_db_collection')->nullable()->after('firebase_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_firebase_projects', function (Blueprint $table) {
            $table->dropColumn('firebase_db_collection');
        });
    }
};
