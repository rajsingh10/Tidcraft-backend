<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_firebase_projects')) {
            return;
        }

        Schema::table('product_firebase_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('product_firebase_projects', 'service_account_json')) {
                $table->longText('service_account_json')->nullable()->after('firebase_messaging_sender_id');
            }
            if (!Schema::hasColumn('product_firebase_projects', 'firebase_location_id')) {
                $table->string('firebase_location_id')->nullable()->after('service_account_json');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_firebase_projects')) {
            return;
        }

        Schema::table('product_firebase_projects', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['service_account_json', 'firebase_location_id'],
                fn ($c) => Schema::hasColumn('product_firebase_projects', $c)
            ));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
