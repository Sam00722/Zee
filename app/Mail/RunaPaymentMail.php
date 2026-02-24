<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RunaPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public float $amount,
        public ?string $url
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your withdrawal is ready to claim - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.runa',
            with: [
                'email' => $this->email,
                'amount' => $this->amount,
                'url' => $this->url,
            ]
        );
    }
}
