<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\EmailTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\Blade;

class DynamicEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $template;
    public $dynamicContent;
    public $dynamicSubject;
    public $settingsData;

    /**
     * Create a new message instance.
     *
     * @param EmailTemplate $template
     * @param array $replacements Key-Value pairs to replace in the content and subject
     */
    public function __construct(EmailTemplate $template, array $replacements = [])
    {
        $this->template = $template;

        // Replace variables in subject
        $subject = $template->subject;
        
        // Replace variables in content
        $content = html_entity_decode($template->content); // Decode in case WYSIWYG encoded tags
        
        // Ensure any random non-breaking spaces before blade tags are removed
        $content = str_replace('&nbsp;', ' ', $content);

        // Automatically fetch and merge global settings for easy replacements
        $keys = [
            'company_name',
            'company_favicon',
            'company_short_logo',
            'company_logo',
            'company_tagline',
        ];
        $settingsData = Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
        
        foreach ($settingsData as $k => $v) {
            // Ensure logo and image URLs are absolute for emails
            if (in_array($k, ['company_logo', 'company_favicon', 'company_short_logo']) && $v && !str_starts_with($v, 'http')) {
                $v = asset($v);
            }
            $replacements['{' . $k . '}'] = $v;
        }

        // Automatically replace {year}
        if (!isset($replacements['{year}'])) {
            $replacements['{year}'] = date('Y');
        }

        // We also want to support raw Blade syntax since the user pasted Blade code.
        // We will pass the replacements as array data to Blade::render.
        // Convert placeholders like '{name}' to just 'name' for the data array
        $bladeData = [
            'settings' => $settingsData
        ];
        
        foreach ($replacements as $key => $value) {
            $subject = str_replace($key, $value, $subject);
            // We also add the variables to blade data so they can use {{ $name }}
            $cleanKey = trim($key, '{}');
            $bladeData[$cleanKey] = $value;
        }

        try {
            // First run standard string replacements in case they used {name}
            $content = str_replace(array_keys($replacements), array_values($replacements), $content);
            
            // Inject max-width inline style to all images to prevent them from blowing up in Gmail
            // This regex safely ignores > inside double/single quotes to avoid breaking blade syntax like $message->embed()
            $content = preg_replace_callback('/<img\s+(?:[^>"\']|"[^"]*"|\'[^\']*\')+>/i', function($matches) {
                $imgTag = $matches[0];
                
                // Determine sensible max-width: 200px for logos, 100% for everything else
                $maxWidth = (stripos($imgTag, 'alt="logo"') !== false || stripos($imgTag, "alt='logo'") !== false) ? '200px' : '100%';
                
                if (stripos($imgTag, 'style=') !== false) {
                    return preg_replace('/style=([\'"])/i', 'style=$1max-width: ' . $maxWidth . '; height: auto; ', $imgTag);
                } else {
                    return rtrim($imgTag, '>') . ' style="max-width: ' . $maxWidth . '; height: auto;">';
                }
            }, $content);

            // Then run Blade compilation
            $compiledContent = Blade::render($content, $bladeData);
        } catch (\Throwable $e) {
            // Fallback if Blade compilation fails due to bad syntax in WYSIWYG
            \Illuminate\Support\Facades\Log::error('Blade render failed in DynamicEmail: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Failing Blade Content: ' . $content);
            $compiledContent = "<div style='background: #ffebe8; border: 1px solid #cc0000; padding: 15px; margin-bottom: 20px; color: #cc0000; font-family: monospace;'>
                <strong>Blade Syntax Error Detected!</strong><br><br>
                <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>
                Please check your email template for typos like using '=' instead of ',' inside a function, or missing closing brackets.
                </div>" . $content;
        }

        $this->dynamicSubject = $subject;
        $this->dynamicContent = $compiledContent;
        $this->settingsData = $settingsData;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // Detect if the content already has a full HTML structure or layout
        $isFullHtml = stripos($this->dynamicContent, '<html') !== false 
                   || stripos($this->dynamicContent, '<body') !== false 
                   || stripos($this->dynamicContent, '<table') !== false // Email templates almost always use layout tables
                   || stripos($this->dynamicContent, '<style') !== false 
                   || stripos($this->dynamicContent, 'class="email-container"') !== false;

        $viewName = $isFullHtml ? 'emails.layouts.raw' : 'emails.layouts.dynamic';

        $html = view($viewName, [
            'dynamicContent' => $this->dynamicContent,
            'settings' => $this->settingsData,
        ])->render();

        // Convert any <style> blocks to inline CSS automatically for perfect email rendering across all clients
        try {
            if (class_exists(\TijsVerkoyen\CssToInlineStyles\CssToInlineStyles::class)) {
                $inliner = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
                $html = $inliner->convert($html);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CSS Inlining failed: ' . $e->getMessage());
        }

        return $this->subject($this->dynamicSubject)
                    ->html($html);
    }
}
