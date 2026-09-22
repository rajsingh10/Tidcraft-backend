<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Inquiry;

class ClientInquiryConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $inquiry;
    public $companyName;
    public $companyPhone;
    public $companyEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(Inquiry $inquiry)
    {
        $this->inquiry = $inquiry;
        $this->companyName = \App\Models\Setting::where('key', 'company_name')->value('value') ?? config('app.name', 'TidCraft');
        $this->companyPhone = \App\Models\Setting::where('key', 'company_phone')->value('value');
        $this->companyEmail = \App\Models\Setting::where('key', 'company_email')->value('value') ?? config('mail.from.address');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for contacting us - Your Inquiry Has Been Received',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.client-inquiry',
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
