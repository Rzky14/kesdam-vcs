<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Document $document,
        public User $approver
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[KESDAM VCS] Persetujuan Dokumen Diperlukan: ' . $this->document->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.approval-requested',
            with: [
                'document' => $this->document,
                'approver' => $this->approver,
                'approvalUrl' => route('approvals.show', $this->document),
            ],
        );
    }
}
