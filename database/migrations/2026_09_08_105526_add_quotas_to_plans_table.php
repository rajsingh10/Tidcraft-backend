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
            $table->integer('max_users')->nullable()->comment('Null or -1 for unlimited');
            $table->integer('max_orders')->nullable()->comment('Null or -1 for unlimited');
            $table->integer('storage_gb')->nullable()->comment('Storage in Gigabytes. Null or -1 for unlimited');
            $table->integer('duration_days')->nullable()->comment('Duration in days. e.g. 30, 365. Null for lifetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_users', 'max_orders', 'storage_gb', 'duration_days']);
        });
    }
};
