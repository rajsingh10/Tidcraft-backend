<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('id');
                $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
            }
            if (!Schema::hasColumn('tenants', 'name')) {
                $table->string('name')->nullable()->after('uuid');
            }
            if (!Schema::hasColumn('tenants', 'tenant_key')) {
                $table->string('tenant_key')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'client_id')) {
                $table->dropForeign(['client_id']);
            }
            $cols = array_values(array_filter(['client_id', 'name', 'tenant_key'], fn ($c) => Schema::hasColumn('tenants', $c)));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
