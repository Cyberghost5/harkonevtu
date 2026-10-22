<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProviderLowBalanceAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array $alertData Contains:
     *  - service
     *  - provider
     *  - amount
     *  - customer_name
     *  - customer_email
     *  - customer_phone
     *  - customer_id
     *  - customer_wallet_balance
     *  - recipient
     *  - raw_error
     *  - reference
     *  - attempted_at
     */
    public function __construct(
        public array $alertData
    ) {}

    public function envelope(): Envelope
    {
        $service = $this->alertData['service'] ?? 'Service';
        return new Envelope(
            subject: "[URGENT] Provider Low Balance Alert: {$service} Purchase Failed"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provider-low-balance-alert',
            with: [
                'alert' => $this->alertData,
            ]
        );
    }
}
