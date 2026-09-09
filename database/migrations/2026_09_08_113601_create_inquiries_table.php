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
        if (!Schema::hasTable('inquiries')) {
            Schema::create('inquiries', function (Blueprint $table) {
                $table->id();
            $table->string('customer_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->text('description')->nullable();
            
            // Custom Timestamps & Soft Deletes
            $table->timestamp('create_at')->nullable();
            $table->timestamp('update_at')->nullable();
            $table->timestamp('delete_at')->nullable();
            
            // Audit fields
            $table->foreignId('create_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('update_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delete_by')->nullable()->constrained('users')->nullOnDelete();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
