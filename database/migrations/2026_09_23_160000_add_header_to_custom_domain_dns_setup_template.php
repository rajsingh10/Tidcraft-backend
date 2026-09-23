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
        $template = DB::table('email_templates')
            ->where('slug', 'Custom_Domain_Dns_Setup')
            ->orWhere('id', 14)
            ->first();

        if (!$template) {
            return;
        }

        $htmlContent = <<<'HTML'
<table width="100%" bgcolor="#f4f7f6" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f7f6; margin: 0; padding: 30px 0; font-family: 'Inter', Arial, sans-serif;">
    <tbody><tr>
        <td align="center" style="padding: 0;">
            <div class="container" style="max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; border: 1px solid #e2e8f0; text-align: left;">
                
                <!-- Header -->
                <div class="header" style="background: #002244; background-color: #002244; padding: 25px 35px; color: #ffffff;">
                    <table class="header-table" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; width: 100%; border-collapse: collapse;">
                        <tbody><tr>
                            <td width="60%" valign="middle" style="vertical-align: middle;">
                                <table cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; border-collapse: collapse;">
                                    <tbody><tr>
                                        <td valign="middle" style="vertical-align: middle;">
                                            <a href="{{ config('app.url', url('/')) }}" style="text-decoration: none; display: inline-block;">
                                                <div style="background-color: #FFFFFF; width: 42px; height: 42px; border-radius: 8px; text-align: center; line-height: 42px; overflow: hidden; display: inline-block; vertical-align: middle;">
                                                    <img src="{{ !empty($settings['company_logo'] ?? null) ? \App\Helpers\UrlHelper::getStorageUrl($settings['company_logo']) : \App\Helpers\UrlHelper::getStorageUrl('settings/lUvNMB4ku94XZPnaGVueDO9rYx3TnakYlcPnoqo6.jpg') }}" alt="Logo" width="42" height="42" style="display: block; width: 42px; height: 42px; max-width: 42px; max-height: 42px; object-fit: contain;">
                                                </div>
                                            </a>
                                        </td>
                                        <td valign="middle" style="padding-left: 12px; vertical-align: middle;">
                                            <a href="{{ config('app.url', url('/')) }}" style="text-decoration: none; color: #ffffff;">
                                                <div style="font-size: 20px; font-weight: 800; color: #FFFFFF; line-height: 1.2;">{{ $settings['company_name'] ?? 'TidCraft' }}</div>
                                                <div style="font-size: 11px; color: #93c5fd; margin-top: 2px; letter-spacing: 0.3px;">Manage • Monitor • Grow</div>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody></table>
                            </td>
                            <td width="40%" align="right" valign="middle" class="header-motto" style="font-size: 12px; text-align: right; line-height: 1.4; color: #cbd5e1; vertical-align: middle;">
                                Technology<br>
                                <strong style="display: block; font-size: 13px; font-weight: 600; color: #ffffff;">for a Brighter<br>Tomorrow</strong>
                            </td>
                        </tr>
                    </tbody></table>
                </div>

                <!-- Content -->
                <div class="content" style="padding: 35px 35px 30px; font-family: 'Inter', Arial, sans-serif; color: #334155; line-height: 1.6;">
                    <h2 style="color: #0f172a; margin-top: 0; margin-bottom: 12px; font-size: 22px;">Connect Your Custom Domain: <span style="color: #2563eb;">{domain}</span></h2>
                    <p style="margin: 0 0 12px 0;">Hi {name},</p>
                    <p style="margin: 0 0 16px 0;">Thank you for choosing {product_name}! To link your domain <strong>{domain}</strong> to your application, please update your DNS records at your domain registrar (such as GoDaddy, Namecheap, Cloudflare, or Google Domains).</p>
                    
                    <div style="background-color: #f1f5f9; border-left: 4px solid #2563eb; padding: 12px 16px; margin: 20px 0; border-radius: 4px;">
                        <strong style="color: #1e3a8a;">Server Public IP:</strong> <code style="font-size: 15px; font-weight: bold; color: #0f172a;">{server_ip}</code>
                    </div>

                    <h3 style="color: #0f172a; margin-top: 24px; margin-bottom: 12px; font-size: 16px;">Required DNS Records</h3>
                    {dns_records_table}

                    <div style="background-color: #fefce8; border: 1px solid #fef08a; padding: 14px; border-radius: 6px; margin: 20px 0; font-size: 13px; color: #713f12;">
                        <strong>Note:</strong> DNS changes usually take between 5 to 30 minutes to propagate worldwide, but can take up to 24 hours. Once your DNS records are added, you can verify your domain.
                    </div>

                    <p style="margin-top: 25px; margin-bottom: 0; font-size: 13px; color: #64748b;">
                        If you need assistance configuring your DNS records, please contact our support team.
                    </p>
                </div>

                <!-- Footer -->
                <div class="footer" style="padding: 24px 35px; border-top: 1px solid #f1f5f9; background-color: #f8fafc; font-size: 12px; color: #64748b;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; width: 100%;">
                        <tbody><tr>
                            <td valign="middle" style="color: #64748b;">
                                &copy; {{ date('Y') }} {{ $settings['company_name'] ?? 'TidCraft' }}. All rights reserved.
                            </td>
                            <td align="right" valign="middle">
                                <a href="{{ config('app.url', url('/')) }}" style="color: #2563eb; text-decoration: none; font-weight: 500;">Visit Platform</a>
                            </td>
                        </tr>
                    </tbody></table>
                </div>

            </div>
        </td>
    </tr>
</tbody></table>
HTML;

        DB::table('email_templates')
            ->where('id', $template->id)
            ->update([
                'content' => $htmlContent,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep current content
    }
};
