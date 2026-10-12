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
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'apk_file')) {
                $table->json('apk_file')->nullable()->after('industry');
            }
            if (!Schema::hasColumn('tenants', 'doc_file')) {
                $table->json('doc_file')->nullable()->after('apk_file');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'apk_file')) {
                $table->dropColumn('apk_file');
            }
            if (Schema::hasColumn('tenants', 'doc_file')) {
                $table->dropColumn('doc_file');
            }
        });
    }
};
