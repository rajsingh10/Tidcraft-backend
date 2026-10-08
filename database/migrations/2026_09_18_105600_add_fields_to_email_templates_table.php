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
        Schema::table('email_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('email_templates', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('images');
            }
            if (!Schema::hasColumn('email_templates', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('status');
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
                
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('email_templates', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
            
            $table->dropColumn([
                'status',
                'created_by',
                'updated_by',
                'deleted_by',
                'deleted_at'
            ]);
        });
    }
};
