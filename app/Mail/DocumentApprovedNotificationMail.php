<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentApprovedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Document $document,
        public User $approver
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[KESDAM VCS] Dokumen Disetujui: ' . $this->document->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-approved',
            with: [
                'document' => $this->document,
                'approver' => $this->approver,
                'documentUrl' => route('documents.show', $this->document),
            ],
        );
    }
}
