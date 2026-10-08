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
            $table->integer('max_locations')->nullable()->after('max_users_annual');
            $table->integer('max_bookings')->nullable()->after('max_orders_monthly');
            $table->integer('max_bookings_monthly')->nullable()->after('max_bookings');
            $table->decimal('additional_booking_price', 10, 2)->nullable()->after('additional_order_price');
            $table->boolean('has_customer_app')->default(0)->after('has_white_labeled_dashboard');
            $table->boolean('has_merchant_app')->default(0)->after('has_customer_app');
            $table->boolean('has_rider_app')->default(0)->after('has_merchant_app');
            $table->boolean('has_white_labeled_rider_app')->default(0)->after('has_rider_app');
            $table->boolean('has_watchman_app')->default(0)->after('has_white_labeled_rider_app');
            $table->boolean('has_owner_app')->default(0)->after('has_watchman_app');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'max_locations',
                'max_bookings',
                'max_bookings_monthly',
                'additional_booking_price',
                'has_customer_app',
                'has_merchant_app',
                'has_rider_app',
                'has_white_labeled_rider_app',
                'has_watchman_app',
                'has_owner_app'
            ]);
        });
    }
};
