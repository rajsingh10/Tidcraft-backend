<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminNewPurchaseMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $user;

    public function __construct($tenant, $user)
    {
        $this->tenant = $tenant;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('New Checkout Initiated')
                    ->html("
                        <p>Hello Admin,</p>
                        <p>A new checkout has been initiated by client <strong>{$this->user->name}</strong>.</p>
                        <p><strong>Business Name:</strong> {$this->tenant->business_name}</p>
                        <p><strong>Tenant ID:</strong> {$this->tenant->uuid}</p>
                        <p><strong>Amount:</strong> {$this->tenant->payments()->first()->amount} {$this->tenant->payments()->first()->currency}</p>
                        <p>Please check the admin panel for more details.</p>
                    ");
    }
}
