<?php

namespace App\Mail;

use App\Models\AccountOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AccountOrder $accountOrder,
        public string $credentials,
        public array $business,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Data Login Akun '.$this->accountOrder->order_ref.' - Johen Gaming');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-credentials');
    }
}
