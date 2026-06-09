<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssetExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *   asset_code: string,
     *   asset_name: string,
     *   type: string,
     *   detail: string,
     *   expiry_date: string,
     *   days_until_expiry: int,
     *   asset_url: string,
     *   tab: string,
     * } $reminder
     */
    public function __construct(
        public readonly array $reminder,
    ) {}

    public function envelope(): Envelope
    {
        $days = $this->reminder['days_until_expiry'];

        $subject = $days < 0
            ? "[Action Required] {$this->reminder['type']} EXPIRED — {$this->reminder['asset_code']}"
            : "[Reminder] {$this->reminder['type']} expiring in {$days} day(s) — {$this->reminder['asset_code']}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.asset-expiry-reminder',
        );
    }
}
