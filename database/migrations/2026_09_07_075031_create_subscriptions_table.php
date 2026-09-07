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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('status')->default('active');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            $table->timestamp('create_at')->nullable();
            $table->timestamp('update_at')->nullable();
            $table->timestamp('delete_at')->nullable();
            
            $table->unsignedBigInteger('create_by')->nullable();
            $table->unsignedBigInteger('update_by')->nullable();
            $table->unsignedBigInteger('delete_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
