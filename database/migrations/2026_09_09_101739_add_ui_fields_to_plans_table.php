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
        Schema::table('plans', function (Blueprint $table) {
            $table->json('integrations')->nullable()->after('features');
            $table->decimal('additional_order_price', 10, 2)->nullable()->after('max_orders');
            $table->string('store_configuration')->nullable()->after('additional_order_price');
            $table->boolean('has_hybrid_customer_app')->default(false)->after('store_configuration');
            $table->boolean('has_hybrid_customer_merchant_app')->default(false)->after('has_hybrid_customer_app');
            $table->boolean('has_unlimited_users_listings')->default(false)->after('has_hybrid_customer_merchant_app');
            $table->boolean('has_white_labeled_solution')->default(false)->after('has_unlimited_users_listings');
            $table->boolean('has_white_labeled_dashboard')->default(false)->after('has_white_labeled_solution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'integrations',
                'additional_order_price',
                'store_configuration',
                'has_hybrid_customer_app',
                'has_hybrid_customer_merchant_app',
                'has_unlimited_users_listings',
                'has_white_labeled_solution',
                'has_white_labeled_dashboard',
            ]);
        });
    }
};
