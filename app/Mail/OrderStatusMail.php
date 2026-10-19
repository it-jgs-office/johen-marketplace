<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $status,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->status === 'success'
            ? 'Pesanan '.$this->order->order_id.' Berhasil - Johen Gaming'
            : 'Pesanan '.$this->order->order_id.' Gagal - Johen Gaming';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-status');
    }
}
