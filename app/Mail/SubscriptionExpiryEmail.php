<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiryEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $title;
    public $messageStr;
    public $domainUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($tenant, $title, $messageStr)
    {
        $this->tenant = $tenant;
        $this->title = $title;
        $this->messageStr = $messageStr;
        
        $domainObj = $tenant->domains()->first();
        $this->domainUrl = 'https://' . ($domainObj ? $domainObj->domain : 'tidcraft.com');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title . ' - ' . $this->tenant->business_name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant.subscription_expiry',
            with: [
                'tenant' => $this->tenant,
                'title' => $this->title,
                'messageStr' => $this->messageStr,
                'domainUrl' => $this->domainUrl,
            ],
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
