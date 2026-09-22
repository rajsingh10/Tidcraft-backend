<?php

namespace App\Mail;

use App\Models\Domain;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomDomainDnsSetupMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $domain;
    public $serverIp;
    public $dnsRecords;
    public $settings;

    public function __construct(Tenant $tenant, Domain $domain, string $serverIp, array $dnsRecords)
    {
        $this->tenant = $tenant;
        $this->domain = $domain;
        $this->serverIp = $serverIp;
        $this->dnsRecords = $dnsRecords;

        $keys = [
            'company_name',
            'company_favicon',
            'company_short_logo',
            'company_logo',
            'company_email',
            'company_address',
            'company_tagline'
        ];

        $this->settings = Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
    }

    public function build()
    {
        $companyName = $this->settings['company_name'] ?? 'Tidcraft';
        $subject = 'DNS Setup Instructions for ' . $this->domain->domain . ' - ' . $companyName;

        return $this->subject($subject)
                    ->view('emails.custom_domain_dns_setup');
    }
}
