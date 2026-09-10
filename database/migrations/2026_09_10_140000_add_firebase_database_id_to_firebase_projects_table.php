<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('firebase_projects')) {
            return;
        }

        Schema::table('firebase_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('firebase_projects', 'firebase_database_id')) {
                $table->string('firebase_database_id')->nullable()->after('firebase_project_id');
            }
        });

        $indexNames = collect(Schema::getIndexes('firebase_projects'))->pluck('name')->all();

        Schema::table('firebase_projects', function (Blueprint $table) use ($indexNames) {
            if (in_array('client_product_firebase_unique', $indexNames, true)) {
                $table->dropUnique('client_product_firebase_unique');
            }
            if (!in_array('firebase_projects_tenant_id_unique', $indexNames, true)) {
                $table->unique('tenant_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('firebase_projects')) {
            return;
        }

        Schema::table('firebase_projects', function (Blueprint $table) {
            $indexNames = collect(Schema::getIndexes('firebase_projects'))->pluck('name')->all();
            if (in_array('firebase_projects_tenant_id_unique', $indexNames, true)) {
                $table->dropUnique(['tenant_id']);
            }
            if (Schema::hasColumn('firebase_projects', 'firebase_database_id')) {
                $table->dropColumn('firebase_database_id');
            }
        });
    }
};
