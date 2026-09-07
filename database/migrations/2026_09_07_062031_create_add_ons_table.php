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
        Schema::create('add_ons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('period')->default('Monthly (/mo)');
            $table->string('limit')->nullable();
            $table->text('description')->nullable();

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
        Schema::dropIfExists('add_ons');
    }
};
