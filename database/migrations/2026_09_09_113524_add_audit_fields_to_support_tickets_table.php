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
        Schema::table('support_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('support_tickets', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('support_tickets', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
            if (Schema::hasColumn('support_tickets', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }

            if (!Schema::hasColumn('support_tickets', 'create_at')) $table->timestamp('create_at')->nullable();
            if (!Schema::hasColumn('support_tickets', 'update_at')) $table->timestamp('update_at')->nullable();
            if (!Schema::hasColumn('support_tickets', 'delete_at')) $table->timestamp('delete_at')->nullable();
            
            if (!Schema::hasColumn('support_tickets', 'create_by')) $table->unsignedBigInteger('create_by')->nullable();
            if (!Schema::hasColumn('support_tickets', 'update_by')) $table->unsignedBigInteger('update_by')->nullable();
            if (!Schema::hasColumn('support_tickets', 'delete_by')) $table->unsignedBigInteger('delete_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['create_at', 'update_at', 'delete_at', 'create_by', 'update_by', 'delete_by']);
            $table->timestamps();
        });
    }
};
