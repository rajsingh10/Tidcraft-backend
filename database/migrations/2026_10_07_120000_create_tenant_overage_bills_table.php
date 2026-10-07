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
        Schema::create('tenant_overage_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number', 50)->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->string('bill_type', 30); // 'bookings' or 'orders'
            $table->string('billing_cycle', 20)->default('monthly');
            $table->string('period_label', 50); // e.g. 'Oct 2026'

            // Quota & Calculation Breakdown
            $table->integer('included_quota')->default(0);
            $table->integer('total_usage')->default(0);
            $table->integer('overage_units')->default(0);
            $table->decimal('rate_per_unit', 10, 2)->default(0.00);
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('INR');

            // Payment Management
            $table->string('status', 30)->default('pending'); // 'pending', 'paid', 'cancelled', 'waived'
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id', 191)->nullable();
            $table->string('order_id', 191)->nullable();
            $table->text('payment_link')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_date')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('client_id');
            $table->index('status');
            $table->index('bill_type');
            $table->index(['tenant_id', 'status']);
        });

        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'overage_bill_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('overage_bill_id')->nullable()->after('metadata');
                $table->index('overage_bill_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'overage_bill_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('overage_bill_id');
            });
        }

        Schema::dropIfExists('tenant_overage_bills');
    }
};
