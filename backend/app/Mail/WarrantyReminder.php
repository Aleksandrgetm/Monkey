<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WarrantyReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Document $document, public Notification $notification) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Scan & Save: atgādinājums par garantiju');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.warranty-reminder', with: ['documentUrl' => rtrim(config('app.frontend_url'), '/').'/app/documents/'.$this->document->id]);
    }

    public function attachments(): array
    {
        return [];
    }
}
