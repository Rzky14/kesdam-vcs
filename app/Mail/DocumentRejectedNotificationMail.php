<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentRejectedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Document $document,
        public User $rejector,
        public string $reason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[KESDAM VCS] Dokumen Ditolak: ' . $this->document->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-rejected',
            with: [
                'document' => $this->document,
                'rejector' => $this->rejector,
                'reason' => $this->reason,
                'documentUrl' => route('documents.show', $this->document),
            ],
        );
    }
}
