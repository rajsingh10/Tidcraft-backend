<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\EmailTemplate;
use App\Models\Setting;

class DynamicEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $template;
    public $dynamicContent;
    public $dynamicSubject;

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
        $content = $template->content;

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

        // Apply all replacements

        foreach ($replacements as $key => $value) {
            $subject = str_replace($key, $value, $subject);
            $content = str_replace($key, $value, $content);
        }

        $this->dynamicSubject = $subject;
        $this->dynamicContent = $content;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->dynamicSubject)
                    ->html($this->dynamicContent);
    }
}
