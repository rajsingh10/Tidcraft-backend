<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class ClientRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $settings;

    public function __construct($user)
    {
        $this->user = $user;
        
        // Fetch general settings
        $keys = [
            'company_name',
            'company_favicon',
            'company_short_logo',
            'company_logo',
            'company_tagline',
        ];
        
        $settingsData = Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
        $this->settings = $settingsData;
    }

    public function build()
    {
        $companyName = $this->settings['company_name'] ?? 'Tidcraft';
        return $this->subject('Welcome to ' . $companyName)
                    ->view('emails.client_registered');
    }
}
