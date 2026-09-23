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
    public $loginUrl;
    public $contactUrl;

    public function __construct($user, ?string $loginUrl = null, ?string $contactUrl = null)
    {
        $this->user = $user;
        $this->loginUrl = $loginUrl ?: \App\Helpers\UrlHelper::getLoginUrl();
        $this->contactUrl = $contactUrl ?: \App\Helpers\UrlHelper::getContactUrl();
        
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
                    ->view('emails.client_registered', [
                        'user' => $this->user,
                        'settings' => $this->settings,
                        'loginUrl' => $this->loginUrl,
                        'contactUrl' => $this->contactUrl,
                    ]);
    }
}
