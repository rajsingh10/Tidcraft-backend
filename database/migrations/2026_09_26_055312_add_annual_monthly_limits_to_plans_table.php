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
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('max_users_annual')->nullable()->after('max_users');
            $table->integer('storage_gb_annual')->nullable()->after('storage_gb');
            $table->integer('max_orders_monthly')->nullable()->after('max_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_users_annual', 'storage_gb_annual', 'max_orders_monthly']);
        });
    }
};
