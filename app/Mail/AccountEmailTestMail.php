<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountEmailTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientEmail,
        public array $business,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tes Email Pengiriman Akun - Johen Gaming');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-email-test');
    }
}
