<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class TicketResolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $user;
    public $companyName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($ticket, $user)
    {
        $this->ticket = $ticket;
        $this->user = $user;
        $this->companyName = Setting::where('key', 'company_name')->value('value') ?? config('app.name', 'TidCraft');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Support Ticket Has Been Resolved')
                    ->view('emails.ticket_resolved');
    }
}
