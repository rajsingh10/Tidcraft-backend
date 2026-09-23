<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantProvisionedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $adminEmail;
    public $adminPassword;
    public $domainUrl;
    public $settings;

    /**
     * Create a new message instance.
     */
    public function __construct($tenant, $adminEmail, $adminPassword, $domainUrl)
    {
        $this->tenant = $tenant;
        $this->adminEmail = $adminEmail;
        $this->adminPassword = $adminPassword;
        $this->domainUrl = $domainUrl;
        
        $keys = [
            'company_name',
            'company_favicon',
            'company_short_logo',
            'company_logo',
            'company_tagline',
        ];
        
        $this->settings = \App\Models\Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();

        foreach (['company_favicon', 'company_short_logo', 'company_logo'] as $fileField) {
            if (!empty($this->settings[$fileField])) {
                $this->settings[$fileField] = \App\Helpers\UrlHelper::getStorageUrl($this->settings[$fileField]);
            }
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Application is Ready! - ' . $this->tenant->business_name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant.provisioned',
            with: [
                'tenant' => $this->tenant,
                'adminEmail' => $this->adminEmail,
                'adminPassword' => $this->adminPassword,
                'domainUrl' => $this->domainUrl,
                'settings' => $this->settings,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
