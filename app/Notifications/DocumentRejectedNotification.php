<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Document $document;
    protected User $rejector;
    protected string $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document, User $rejector, string $reason)
    {
        $this->document = $document;
        $this->rejector = $rejector;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->hasInAppNotificationEnabled('document_rejected')) {
            $channels[] = 'database';
        }

        if ($notifiable->hasEmailNotificationEnabled('document_rejected')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Document Rejected: ' . $this->document->subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Unfortunately, your document has been rejected.')
            ->line('**Document Number:** ' . $this->document->number)
            ->line('**Subject:** ' . $this->document->subject)
            ->line('**Rejected by:** ' . $this->rejector->name . ' (' . $this->rejector->rank . ')')
            ->line('**Reason:** ' . $this->reason)
            ->action('View Document', route('documents.show', $this->document->id))
            ->line('Please review the rejection reason and take appropriate action.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_rejected',
            'document_id' => $this->document->id,
            'document_number' => $this->document->number,
            'document_subject' => $this->document->subject,
            'document_type' => $this->document->type,
            'rejector_id' => $this->rejector->id,
            'rejector_name' => $this->rejector->name,
            'rejector_rank' => $this->rejector->rank,
            'reason' => $this->reason,
            'message' => "Document '{$this->document->subject}' has been rejected by {$this->rejector->name}",
            'url' => route('documents.show', $this->document->id),
        ];
    }
}
