<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BroadcastEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string      $emailSubject  Email subject line
     * @param string      $body          Main message body (plain text, newlines respected)
     * @param string|null $actionLabel   CTA button label (e.g. "Visit Dashboard")
     * @param string|null $actionUrl     CTA button URL
     * @param string|null $recipientName Personalised greeting name (optional)
     */
    public function __construct(
        public string  $emailSubject,
        public string  $body,
        public ?string $actionLabel = null,
        public ?string $actionUrl   = null,
        public ?string $recipientName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.broadcast',
            with: [
                'emailSubject'   => $this->emailSubject,
                'emailBody'      => $this->body,
                'actionLabel'    => $this->actionLabel,
                'actionUrl'      => $this->actionUrl,
                'recipientName'  => $this->recipientName,
            ]
        );
    }
}
