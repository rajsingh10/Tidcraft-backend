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
        Schema::create('product_demos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('title'); // e.g., 'Super Admin Panel', 'Customer App'
            $table->string('platform')->nullable(); // e.g., 'web', 'android', 'ios'
            $table->string('demo_url')->nullable();
            $table->string('demo_id')->nullable();
            $table->string('demo_password')->nullable();
            $table->json('screenshots')->nullable(); // Store array of image paths
            $table->string('app_store_url')->nullable();
            $table->string('play_store_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_demos');
    }
};
