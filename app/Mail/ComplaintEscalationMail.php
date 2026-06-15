<?php

namespace App\Mail;

use App\Models\Asset;
use App\Models\AssetComplaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComplaintEscalationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Asset $asset,
        public readonly AssetComplaint $complaint,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Complaint] [{$this->complaint->priority_label}] {$this->complaint->title} — {$this->asset->asset_code}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.complaint-escalation');
    }
}
