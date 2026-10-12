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
        $this->addColumnIfNotExists('plans', 'show_monthly', function (Blueprint $table) {
            $table->boolean('show_monthly')->default(true)->after('annual_price');
        });

        $this->addColumnIfNotExists('plans', 'show_yearly', function (Blueprint $table) {
            $table->boolean('show_yearly')->default(true)->after('show_monthly');
        });

        $this->addColumnIfNotExists('plan_prices', 'show_monthly', function (Blueprint $table) {
            $table->boolean('show_monthly')->default(true)->after('onboard');
        });

        $this->addColumnIfNotExists('plan_prices', 'show_yearly', function (Blueprint $table) {
            $table->boolean('show_yearly')->default(true)->after('show_monthly');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropColumnIfExists('plans', 'show_yearly');
        $this->dropColumnIfExists('plans', 'show_monthly');
        $this->dropColumnIfExists('plan_prices', 'show_yearly');
        $this->dropColumnIfExists('plan_prices', 'show_monthly');
    }

    /**
     * Safely add column only if it doesn't already exist in database.
     */
    private function addColumnIfNotExists(string $tableName, string $columnName, \Closure $callback): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $exists = false;
        try {
            $exists = Schema::hasColumn($tableName, $columnName);
        } catch (\Throwable $e) {
            $exists = false;
        }

        if (!$exists) {
            try {
                $count = DB::table('information_schema.COLUMNS')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('TABLE_NAME', $tableName)
                    ->where('COLUMN_NAME', $columnName)
                    ->count();
                $exists = ($count > 0);
            } catch (\Throwable $e) {
                // Ignore if query fails
            }
        }

        if (!$exists) {
            try {
                Schema::table($tableName, $callback);
            } catch (\Throwable $e) {
                // If MySQL reports duplicate column (1060 / 42S21), ignore safely
                if (!str_contains($e->getMessage(), '1060') && !str_contains($e->getMessage(), 'Duplicate column')) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Safely drop column if exists.
     */
    private function dropColumnIfExists(string $tableName, string $columnName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $exists = false;
        try {
            $exists = Schema::hasColumn($tableName, $columnName);
        } catch (\Throwable $e) {
            $exists = false;
        }

        if ($exists) {
            try {
                Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                    $table->dropColumn($columnName);
                });
            } catch (\Throwable $e) {
                // Ignore if column drop fails or not found
            }
        }
    }
};
