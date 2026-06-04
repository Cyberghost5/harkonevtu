<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PinResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;

    public function __construct(
        public string $token,
        public User   $user,
    ) {
        $this->resetUrl = route('settings.pin.reset.form', ['token' => $token]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset Your Transaction PIN');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pin-reset');
    }
}
