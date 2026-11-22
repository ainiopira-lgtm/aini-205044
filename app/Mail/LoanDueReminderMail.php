<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoanDueReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $memberName,
        public readonly string $bookTitle,
        public readonly string $dueDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pengingat jatuh tempo peminjaman');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.loans.due-reminder',
            with: [
                'memberName' => $this->memberName,
                'bookTitle' => $this->bookTitle,
                'dueDate' => $this->dueDate,
            ],
        );
    }
}
