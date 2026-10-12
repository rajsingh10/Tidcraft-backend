<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasApk = false;
        $hasDoc = false;

        try {
            $hasApk = !empty(DB::select("SHOW COLUMNS FROM `tenants` LIKE 'apk_file'"));
            $hasDoc = !empty(DB::select("SHOW COLUMNS FROM `tenants` LIKE 'doc_file'"));
        } catch (\Throwable $e) {
            $hasApk = Schema::hasColumn('tenants', 'apk_file');
            $hasDoc = Schema::hasColumn('tenants', 'doc_file');
        }

        if (!$hasApk) {
            try {
                Schema::table('tenants', function (Blueprint $table) {
                    $table->json('apk_file')->nullable();
                });
            } catch (\Throwable $e) {
                // Ignore if column already exists
            }
        }

        if (!$hasDoc) {
            try {
                Schema::table('tenants', function (Blueprint $table) {
                    $table->json('doc_file')->nullable();
                });
            } catch (\Throwable $e) {
                // Ignore if column already exists
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            $hasApk = !empty(DB::select("SHOW COLUMNS FROM `tenants` LIKE 'apk_file'"));
            if ($hasApk) {
                Schema::table('tenants', function (Blueprint $table) {
                    $table->dropColumn('apk_file');
                });
            }
        } catch (\Throwable $e) {}

        try {
            $hasDoc = !empty(DB::select("SHOW COLUMNS FROM `tenants` LIKE 'doc_file'"));
            if ($hasDoc) {
                Schema::table('tenants', function (Blueprint $table) {
                    $table->dropColumn('doc_file');
                });
            }
        } catch (\Throwable $e) {}
    }
};
