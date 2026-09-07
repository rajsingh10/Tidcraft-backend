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
        $tables = ['tenants', 'tenant_domains', 'tenant_firebase_configs', 'products', 'plans', 'product_categories'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Check and drop default timestamps if they exist
                if (Schema::hasColumn($tableName, 'created_at')) {
                    $table->dropColumn('created_at');
                }
                if (Schema::hasColumn($tableName, 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->dropColumn('deleted_at');
                }

                // Add the custom fields
                if (!Schema::hasColumn($tableName, 'create_at')) $table->timestamp('create_at')->nullable();
                if (!Schema::hasColumn($tableName, 'update_at')) $table->timestamp('update_at')->nullable();
                if (!Schema::hasColumn($tableName, 'delete_at')) $table->timestamp('delete_at')->nullable();
                
                if (!Schema::hasColumn($tableName, 'create_by')) $table->unsignedBigInteger('create_by')->nullable();
                if (!Schema::hasColumn($tableName, 'update_by')) $table->unsignedBigInteger('update_by')->nullable();
                if (!Schema::hasColumn($tableName, 'delete_by')) $table->unsignedBigInteger('delete_by')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['tenants', 'tenant_domains', 'tenant_firebase_configs', 'products', 'plans', 'product_categories'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropColumn(['create_at', 'update_at', 'delete_at', 'create_by', 'update_by', 'delete_by']);
                $table->timestamps();
            });
        }
    }
};
