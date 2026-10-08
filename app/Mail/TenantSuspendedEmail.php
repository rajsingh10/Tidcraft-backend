<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantSuspendedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $suspensionReason;

    /**
     * Create a new message instance.
     *
     * @param Tenant $tenant
     * @param string $suspensionReason
     */
    public function __construct(Tenant $tenant, $suspensionReason = 'Violation of Terms / Payment Issue / Policy Breach')
    {
        $this->tenant = $tenant;
        $this->suspensionReason = $suspensionReason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Suspended - ' . ($this->tenant->business_name ?? 'Tidcraft'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Load general settings
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return new Content(
            view: 'emails.tenant.suspended',
            with: [
                'tenant' => $this->tenant,
                'suspensionReason' => $this->suspensionReason,
                'settings' => $settings,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
