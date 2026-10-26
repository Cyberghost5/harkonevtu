<?php

namespace App\Mail;

use App\Models\AppSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $overview,
        public array $services,
        public string $periodLabel,
        public string $pdfContent,
        public string $filename = 'Platform_Report.pdf'
    ) {}

    public function envelope(): Envelope
    {
        $siteName = AppSetting::get('site_name', 'PayPulse');
        return new Envelope(
            subject: "[{$siteName}] Executive Analytics & System Audit Report - {$this->periodLabel}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.system-report',
            with: [
                'overview'    => $this->overview,
                'services'    => $this->services,
                'periodLabel' => $this->periodLabel,
                'siteName'    => AppSetting::get('site_name', 'PayPulse'),
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}
