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
        
        // Ensure any random zero-width spaces or non-breaking spaces before blade tags are removed
        $content = str_replace(['&nbsp;', '<p>', '</p>'], [' ', '', '<br>'], $content); // Strip basic wrapping P tags that break block blade directives

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
            $replacements['{' . $k . '}'] = $v;
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
            
            // Then run Blade compilation
            $compiledContent = Blade::render($content, $bladeData);
        } catch (\Exception $e) {
            // Fallback if Blade compilation fails due to bad syntax in WYSIWYG
            $compiledContent = $content;
            \Illuminate\Support\Facades\Log::error('Blade render failed in DynamicEmail: ' . $e->getMessage());
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
        return $this->subject($this->dynamicSubject)
                    ->view('emails.layouts.dynamic', [
                        'dynamicContent' => $this->dynamicContent,
                        'settings' => $this->settingsData,
                    ]);
    }
}
