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
    public $attachmentsData = [];

    public function __construct($tenant, $payment, array $attachmentsData = [])
    {
        $this->tenant = $tenant;
        $this->payment = $payment;
        $this->attachmentsData = $attachmentsData;
        
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
        $mail = $this->subject('Payment Received Successfully - ' . $companyName)
                     ->view('emails.tenant.payment_received');

        foreach ($this->attachmentsData as $att) {
            if (isset($att['data']) && isset($att['name'])) {
                $mail->attachData($att['data'], $att['name'], ['mime' => $att['mime'] ?? 'application/pdf']);
            }
        }

        return $mail;
    }
}
