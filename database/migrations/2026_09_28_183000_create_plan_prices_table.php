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
        if (!Schema::hasTable('plan_prices')) {
            Schema::create('plan_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('plan_id');
                $table->unsignedBigInteger('currency_id');
                $table->decimal('monthly_price', 12, 2)->default(0);
                $table->decimal('annual_price', 12, 2)->default(0);
                $table->unsignedBigInteger('create_by')->nullable();
                $table->unsignedBigInteger('update_by')->nullable();
                $table->unsignedBigInteger('delete_by')->nullable();
                $table->timestamp('create_at')->nullable();
                $table->timestamp('update_at')->nullable();
                $table->softDeletes('delete_at');

                $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
                $table->foreign('currency_id')->references('id')->on('currencies')->cascadeOnDelete();
                $table->unique(['plan_id', 'currency_id']);
            });

            // Backfill existing plans into plan_prices
            $existingPlans = DB::table('plans')->whereNull('delete_at')->get();
            $defaultCurrencyId = DB::table('currencies')->where('code', 'INR')->value('id') 
                ?? DB::table('currencies')->value('id');

            foreach ($existingPlans as $plan) {
                $currId = $plan->currency_id ?: $defaultCurrencyId;
                if ($currId) {
                    DB::table('plan_prices')->insert([
                        'plan_id' => $plan->id,
                        'currency_id' => $currId,
                        'monthly_price' => (float) ($plan->monthly_price ?? 0),
                        'annual_price' => (float) ($plan->annual_price ?? 0),
                        'create_at' => now(),
                        'update_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }
};
