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
        $templates = DB::table('email_templates')->get();

        $headerHtml = '<div class="header" style="background: #002244; background-color: #002244; padding: 25px 35px; color: #ffffff;">
            <table class="header-table" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; width: 100%;">
                <tbody><tr>
                    <td width="60%" valign="middle" style="vertical-align: middle;">
                        <table cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0;">
                            <tbody><tr>
                                <td valign="middle" style="vertical-align: middle;">
                                    <a href="{{ config(\'app.url\') }}" style="text-decoration: none; display: inline-block;">
                                        <div style="background-color: #FFFFFF; width: 42px; height: 42px; border-radius: 8px; text-align: center; line-height: 42px; overflow: hidden; display: inline-block; vertical-align: middle;">
                                            <img src="{{ !empty($settings[\'company_logo\']) ? url($settings[\'company_logo\']) : asset(\'images/logo.png\') }}" alt="Logo" width="42" height="42" style="display: block; width: 42px; height: 42px; max-width: 42px; max-height: 42px; object-fit: contain;">
                                        </div>
                                    </a>
                                </td>
                                <td valign="middle" style="padding-left: 12px; vertical-align: middle;">
                                    <a href="{{ config(\'app.url\') }}" style="text-decoration: none; color: #ffffff;">
                                        <div style="font-size: 20px; font-weight: 800; color: #FFFFFF; line-height: 1.2;">{{ $settings[\'company_name\'] ?? \'TidCraft\' }}</div>
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
        </div>';

        foreach ($templates as $template) {
            $content = $template->content;
            if (!$content) {
                continue;
            }

            // Decode HTML entities
            $cleaned = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Decode [LARAVEL_VAR_...]
            $cleaned = preg_replace_callback('/(?:<!--|&lt;!--)\s*\[LARAVEL_VAR_([a-zA-Z0-9+\/]+=*)\]\s*(?:-->|--&gt;)/i', function($matches) {
                return base64_decode($matches[1]);
            }, $cleaned);

            // Fix mangled company_logo
            $cleaned = preg_replace(
                '/\{\{\s*url\(\$settings\[["\'\s]*company_logo[^}]*\}\}(?:="[^"]*"|=""|"")*/i',
                '{{ !empty($settings[\'company_logo\']) ? url($settings[\'company_logo\']) : \'\' }}"',
                $cleaned
            );

            // Fix mangled companyLogo / logoToUse
            $cleaned = preg_replace(
                '/\{\{\s*asset\(ltrim\(\$(?:companyLogo|logoToUse)[^}]+\}\}(?:="[^"]*"|=""|"")*/i',
                '{{ !empty($logoToUse) ? url($logoToUse) : (!empty($companyLogo) ? url($companyLogo) : (!empty($settings[\'company_logo\']) ? url($settings[\'company_logo\']) : \'\')) }}"',
                $cleaned
            );

            // Fix mangled email_images
            $cleaned = preg_replace_callback(
                '/\{\{\s*(?:url|asset)\([^}]*?email_images[^}]*?([a-zA-Z0-9_\-\.]+\.(?:png|jpg|jpeg|svg|gif|webp))[^}]*\}\}(?:="[^"]*"|=""|"")*/i',
                function($m) {
                    return '{{ url(\'email_images/' . trim($m[1]) . '\') }}"';
                },
                $cleaned
            );

            $cleaned = str_replace(['}}""', '}}"=""', '}}="""', '"\'', '\'"'], ['}}"', '}}"', '}}"', '\'', '\''], $cleaned);
            $cleaned = preg_replace('/(\{\{[^}]+\}\})"+/i', '$1"', $cleaned);
            $cleaned = str_replace('&nbsp;', ' ', $cleaned);

            // Standardize header layout across templates 3, 6, 7 (and any using .header-table with .brand-logo)
            // This ensures a compact 42x42 logo with company title, subtitle, and clearly readable high-contrast motto
            if (strpos($cleaned, 'class="header-table"') !== false && strpos($cleaned, 'class="brand-logo"') !== false) {
                $cleaned = preg_replace('/<div class="header">.*?<\/table>\s*<\/div>/is', $headerHtml, $cleaned);
                $cleaned = str_replace(
                    '.header-motto { font-size: 12px; text-align: right; line-height: 1.4; }',
                    '.header-motto { font-size: 12px; text-align: right; line-height: 1.4; color: #cbd5e1; }',
                    $cleaned
                );
                $cleaned = str_replace(
                    '.header-motto strong { display: block; font-size: 13px; font-weight: 600; }',
                    '.header-motto strong { display: block; font-size: 13px; font-weight: 600; color: #ffffff; }',
                    $cleaned
                );
            }

            if ($cleaned !== $content) {
                DB::table('email_templates')
                    ->where('id', $template->id)
                    ->update(['content' => $cleaned]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
