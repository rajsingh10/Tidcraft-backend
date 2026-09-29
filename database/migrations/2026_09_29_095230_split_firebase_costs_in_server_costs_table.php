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
        Schema::table('server_costs', function (Blueprint $table) {
            $table->renameColumn('firebase_cost', 'firebase_1_cost');
            $table->decimal('firebase_2_cost', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_costs', function (Blueprint $table) {
            $table->renameColumn('firebase_1_cost', 'firebase_cost');
            $table->dropColumn('firebase_2_cost');
        });
    }
};
