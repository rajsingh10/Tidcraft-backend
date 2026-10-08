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
        Schema::table('domains', function (Blueprint $table) {
            $table->boolean('dns_verified')->default(false)->after('status');
            $table->timestamp('dns_verified_at')->nullable()->after('dns_verified');
            $table->boolean('ssl_verified')->default(false)->after('dns_verified_at');
            $table->timestamp('ssl_verified_at')->nullable()->after('ssl_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn([
                'dns_verified',
                'dns_verified_at',
                'ssl_verified',
                'ssl_verified_at',
            ]);
        });
    }
};
