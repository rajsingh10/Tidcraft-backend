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
        // 1. Create currencies table matching Screenshot 1 (Code, Name, Symbol, Active)
        if (!Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();   // e.g. USD, INR
                $table->string('name', 100);             // e.g. US Dollar, Indian Rupee
                $table->string('symbol', 10);            // e.g. $, ₹
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('create_by')->nullable();
                $table->unsignedBigInteger('update_by')->nullable();
                $table->unsignedBigInteger('delete_by')->nullable();
                $table->timestamp('create_at')->nullable();
                $table->timestamp('update_at')->nullable();
                $table->softDeletes('delete_at');
            });

            // 2. Seed default currencies (INR and USD)
            $inrId = DB::table('currencies')->insertGetId([
                'code' => 'INR',
                'name' => 'Indian Rupee',
                'symbol' => '₹',
                'is_active' => true,
                'create_at' => now(),
                'update_at' => now(),
            ]);

            DB::table('currencies')->insert([
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'is_active' => true,
                'create_at' => now(),
                'update_at' => now(),
            ]);
        } else {
            $inrId = DB::table('currencies')->where('code', 'INR')->value('id');
        }

        // 3. Add currency_id and currency_code to plans table matching Screenshot 2 (CURRENCY * dropdown)
        if (!Schema::hasColumn('plans', 'currency_id')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->unsignedBigInteger('currency_id')->nullable()->after('product_id');
                $table->string('currency_code', 10)->default('INR')->after('currency_id');

                $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
            });

            // Link existing plans to INR currency
            if ($inrId) {
                DB::table('plans')->whereNull('currency_id')->update([
                    'currency_id' => $inrId,
                    'currency_code' => 'INR',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('plans', 'currency_id')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropForeign(['currency_id']);
                $table->dropColumn(['currency_id', 'currency_code']);
            });
        }

        Schema::dropIfExists('currencies');
    }
};
