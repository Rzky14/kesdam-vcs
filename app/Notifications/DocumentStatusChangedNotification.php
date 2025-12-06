<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Document $document;
    protected string $oldStatus;
    protected string $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document, string $oldStatus, string $newStatus)
    {
        $this->document = $document;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->hasInAppNotificationEnabled('document_status_changed')) {
            $channels[] = 'database';
        }

        if ($notifiable->hasEmailNotificationEnabled('document_status_changed')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'draft' => 'Draft',
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'correction_needed' => 'Correction Needed',
        ];

        $oldLabel = $statusLabels[$this->oldStatus] ?? $this->oldStatus;
        $newLabel = $statusLabels[$this->newStatus] ?? $this->newStatus;

        return (new MailMessage)
            ->subject('Document Status Updated: ' . $this->document->subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('The status of your document has been updated.')
            ->line('**Document Number:** ' . $this->document->number)
            ->line('**Subject:** ' . $this->document->subject)
            ->line('**Previous Status:** ' . $oldLabel)
            ->line('**New Status:** ' . $newLabel)
            ->action('View Document', route('documents.show', $this->document->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_status_changed',
            'document_id' => $this->document->id,
            'document_number' => $this->document->number,
            'document_subject' => $this->document->subject,
            'document_type' => $this->document->type,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => "Document '{$this->document->subject}' status changed from {$this->oldStatus} to {$this->newStatus}",
            'url' => route('documents.show', $this->document->id),
        ];
    }
}
