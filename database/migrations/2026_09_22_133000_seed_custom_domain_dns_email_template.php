<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existing = DB::table('email_templates')->where('slug', 'Custom_Domain_Dns_Setup')->first();
        if ($existing) {
            return;
        }

        $htmlContent = <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 650px; margin: 0 auto; padding: 20px; color: #334155; line-height: 1.6;">
    <h2 style="color: #0f172a; margin-bottom: 8px;">Connect Your Custom Domain: <span style="color: #2563eb;">{domain}</span></h2>
    <p>Hi {name},</p>
    <p>Thank you for choosing {product_name}! To link your domain <strong>{domain}</strong> to your application, please update your DNS records at your domain registrar (such as GoDaddy, Namecheap, Cloudflare, or Google Domains).</p>
    
    <div style="background-color: #f1f5f9; border-left: 4px solid #2563eb; padding: 12px 16px; margin: 20px 0; border-radius: 4px;">
        <strong style="color: #1e3a8a;">Server Public IP:</strong> <code style="font-size: 15px; font-weight: bold; color: #0f172a;">{server_ip}</code>
    </div>

    <h3 style="color: #0f172a; margin-top: 24px;">Required DNS Records</h3>
    {dns_records_table}

    <div style="background-color: #fefce8; border: 1px solid #fef08a; padding: 14px; border-radius: 6px; margin: 20px 0; font-size: 13px; color: #713f12;">
        <strong>Note:</strong> DNS changes usually take between 5 to 30 minutes to propagate worldwide, but can take up to 24 hours. Once your DNS records are added, you can verify your domain.
    </div>

    <p style="margin-top: 30px; font-size: 13px; color: #64748b;">
        If you need assistance configuring your DNS records, please contact our support team.
    </p>
</div>
HTML;

        DB::table('email_templates')->insert([
            'title' => 'Custom Domain DNS Setup Instructions',
            'slug' => 'Custom_Domain_Dns_Setup',
            'subject' => 'DNS Setup Instructions for Your Custom Domain - {domain}',
            'content' => $htmlContent,
            'images' => json_encode([]),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'Custom_Domain_Dns_Setup')->delete();
    }
};
