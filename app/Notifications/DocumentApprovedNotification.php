<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Document $document;
    protected User $approver;
    protected ?string $notes;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document, User $approver, ?string $notes = null)
    {
        $this->document = $document;
        $this->approver = $approver;
        $this->notes = $notes;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->hasInAppNotificationEnabled('document_approved')) {
            $channels[] = 'database';
        }

        if ($notifiable->hasEmailNotificationEnabled('document_approved')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Document Approved: ' . $this->document->subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Great news! Your document has been approved.')
            ->line('**Document Number:** ' . $this->document->number)
            ->line('**Subject:** ' . $this->document->subject)
            ->line('**Approved by:** ' . $this->approver->name . ' (' . $this->approver->rank . ')');

        if ($this->notes) {
            $mail->line('**Notes:** ' . $this->notes);
        }

        return $mail
            ->action('View Document', route('documents.show', $this->document->id))
            ->line('The document status has been updated to approved.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_approved',
            'document_id' => $this->document->id,
            'document_number' => $this->document->number,
            'document_subject' => $this->document->subject,
            'document_type' => $this->document->type,
            'approver_id' => $this->approver->id,
            'approver_name' => $this->approver->name,
            'approver_rank' => $this->approver->rank,
            'notes' => $this->notes,
            'message' => "Document '{$this->document->subject}' has been approved by {$this->approver->name}",
            'url' => route('documents.show', $this->document->id),
        ];
    }
}
