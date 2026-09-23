<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Helpers\UrlHelper;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $templates = DB::table('email_templates')->get();

        foreach ($templates as $template) {
            if (empty($template->content)) {
                continue;
            }

            $content = $template->content;

            // Correct any frontend storage URLs to backend API storage URLs
            $cleaned = UrlHelper::correctStorageUrl($content);

            // Update template 8 (Your_Application_is_Ready) logo tag to use UrlHelper::getStorageUrl
            if ($template->id == 8 || $template->slug === 'Your_Application_is_Ready') {
                $cleaned = preg_replace(
                    '/<img[^>]*alt=[\'"]Logo[\'"][^>]*>/i',
                    '<img src="{{ !empty($settings[\'company_logo\'] ?? null) ? \App\Helpers\UrlHelper::getStorageUrl($settings[\'company_logo\']) : \App\Helpers\UrlHelper::getStorageUrl(\'settings/lUvNMB4ku94XZPnaGVueDO9rYx3TnakYlcPnoqo6.jpg\') }}" alt="Logo" width="42" height="42" style="display: block; width: 42px; height: 42px; max-width: 42px; max-height: 42px; object-fit: contain;">',
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
