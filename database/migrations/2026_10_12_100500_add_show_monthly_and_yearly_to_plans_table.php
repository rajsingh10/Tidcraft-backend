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
        if (Schema::hasTable('plans')) {
            Schema::table('plans', function (Blueprint $table) {
                if (!Schema::hasColumn('plans', 'show_monthly')) {
                    $table->boolean('show_monthly')->default(true)->after('annual_price');
                }
                if (!Schema::hasColumn('plans', 'show_yearly')) {
                    $table->boolean('show_yearly')->default(true)->after('show_monthly');
                }
            });
        }

        if (Schema::hasTable('plan_prices')) {
            Schema::table('plan_prices', function (Blueprint $table) {
                if (!Schema::hasColumn('plan_prices', 'show_monthly')) {
                    $table->boolean('show_monthly')->default(true)->after('onboard');
                }
                if (!Schema::hasColumn('plan_prices', 'show_yearly')) {
                    $table->boolean('show_yearly')->default(true)->after('show_monthly');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('plans')) {
            Schema::table('plans', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('plans', 'show_monthly')) {
                    $columns[] = 'show_monthly';
                }
                if (Schema::hasColumn('plans', 'show_yearly')) {
                    $columns[] = 'show_yearly';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('plan_prices')) {
            Schema::table('plan_prices', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('plan_prices', 'show_monthly')) {
                    $columns[] = 'show_monthly';
                }
                if (Schema::hasColumn('plan_prices', 'show_yearly')) {
                    $columns[] = 'show_yearly';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
