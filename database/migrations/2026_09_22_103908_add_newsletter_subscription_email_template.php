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
        DB::table('email_templates')->insert([
            'title' => 'Newsletter Subscription',
            'slug' => 'Newsletter_Subscription',
            'subject' => 'Thank you for subscribing to Tidcraft Newsletter!',
            'content' => '<p>Hello,</p><p>Thank you for subscribing to our newsletter. You will receive monthly insights straight to your inbox.</p><p>Best regards,<br>The Tidcraft Team</p>',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'Newsletter_Subscription')->delete();
    }
};
