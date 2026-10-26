<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A plain notification e-mail: subject and text come from the stored delivery. */
class NotificationMail extends Mailable
{
    public function __construct(public string $mailSubject, public string $text) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            text: 'emails.notification_text',
            with: ['subjectLine' => $this->mailSubject, 'text' => $this->text],
        );
    }
}
