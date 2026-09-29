<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminSupportTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $user;
    public $companyName;

    public function __construct($ticket, $user)
    {
        $this->ticket = $ticket;
        $this->user = $user;
        $this->companyName = \App\Models\Setting::where('key', 'company_name')->value('value') ?? config('app.name', 'TidCraft');
    }

    public function build()
    {
        return $this->subject('New Support Ticket Created')
                    ->view('emails.admin_support_ticket');
    }
}
