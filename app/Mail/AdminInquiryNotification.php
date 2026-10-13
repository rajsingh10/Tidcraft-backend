<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Inquiry;

class AdminInquiryNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $inquiry;
    public $companyName;
    public $projectName;

    /**
     * Create a new message instance.
     */
    public function __construct(Inquiry $inquiry, ?string $projectName = null)
    {
        $this->inquiry = $inquiry;
        $this->inquiry->loadMissing('product');
        $this->companyName = \App\Models\Setting::where('key', 'company_name')->value('value') ?? config('app.name', 'TidCraft');
        $this->projectName = $projectName ?? ($this->inquiry->product?->name ?? $this->inquiry->plan_name ?? ($this->inquiry->product_id ?? 'N/A'));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Inquiry Received - ' . ($this->inquiry->customer_name ?? 'Website Visitor'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-inquiry',
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
