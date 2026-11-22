<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationReadyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $memberName,
        public readonly string $bookTitle,
        public readonly string $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reservasi buku siap diambil');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservations.ready',
            with: [
                'memberName' => $this->memberName,
                'bookTitle' => $this->bookTitle,
                'expiresAt' => $this->expiresAt,
            ],
        );
    }
}
