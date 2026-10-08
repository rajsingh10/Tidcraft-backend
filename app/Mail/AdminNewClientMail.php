<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class AdminNewClientMail extends Mailable
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
        return $this->subject('New Client Registered')
                    ->view('emails.admin_new_client');
    }
}
