<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class ClientPaymentReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $payment;
    public $settings;

    public function __construct($tenant, $payment)
    {
        $this->tenant = $tenant;
        $this->payment = $payment;
        
        $keys = [
            'company_name',
            'company_favicon',
            'company_short_logo',
            'company_logo',
            'company_tagline'
        ];
        
        $settingsData = Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
        $this->settings = $settingsData;
    }

    public function build()
    {
        $companyName = $this->settings['company_name'] ?? 'Tidcraft';
        return $this->subject('Payment Received Successfully - ' . $companyName)
                    ->view('emails.tenant.payment_received');
    }
}
