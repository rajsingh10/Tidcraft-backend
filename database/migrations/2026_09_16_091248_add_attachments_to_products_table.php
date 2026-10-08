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
        Schema::table('products', function (Blueprint $table) {
            $table->string('source_code_zip')->nullable();
            $table->string('database_zip')->nullable();
            $table->string('assets_zip')->nullable();
            $table->string('setup_document_pdf')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'source_code_zip',
                'database_zip',
                'assets_zip',
                'setup_document_pdf'
            ]);
        });
    }
};
