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
        if (!Schema::hasTable('tenant_databases')) {
            Schema::create('tenant_databases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('database_name')->unique();
            $table->string('database_host')->nullable();
            $table->string('database_port')->nullable();
            $table->string('database_username')->nullable();
            $table->text('encrypted_database_password')->nullable();
            $table->string('status')->default('pending'); // pending, creating, ready, failed
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_databases');
    }
};
