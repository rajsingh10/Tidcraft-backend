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
            $table->decimal('server_infrastructure_cost', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_costs', function (Blueprint $table) {
            $table->dropColumn('server_infrastructure_cost');
        });
    }
};
