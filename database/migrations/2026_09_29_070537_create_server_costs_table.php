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
        Schema::create('server_costs', function (Blueprint $table) {
            $table->id();
            $table->string('month_year', 7)->unique(); // format: YYYY-MM
            $table->decimal('aws_cost', 10, 2)->default(0);
            $table->decimal('firebase_cost', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_costs');
    }
};
