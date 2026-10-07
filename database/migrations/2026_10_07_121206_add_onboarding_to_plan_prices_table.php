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
        Schema::table('plan_prices', function (Blueprint $table) {
            $table->decimal('onboarding_fee', 10, 2)->nullable()->after('annual_price');
            $table->boolean('onboard')->default(false)->after('onboarding_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_prices', function (Blueprint $table) {
            $table->dropColumn(['onboarding_fee', 'onboard']);
        });
    }
};
