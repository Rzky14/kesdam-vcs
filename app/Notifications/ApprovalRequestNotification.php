<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Document $document;
    protected User $submitter;
    protected int $currentLevel;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document, User $submitter, int $currentLevel)
    {
        $this->document = $document;
        $this->submitter = $submitter;
        $this->currentLevel = $currentLevel;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->hasInAppNotificationEnabled('approval_request')) {
            $channels[] = 'database';
        }

        if ($notifiable->hasEmailNotificationEnabled('approval_request')) {
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
            ->subject('Approval Request: ' . $this->document->subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have a new document awaiting your approval:')
            ->line('**Document Number:** ' . $this->document->number)
            ->line('**Subject:** ' . $this->document->subject)
            ->line('**Type:** ' . $this->document->type_label)
            ->line('**Classification:** ' . $this->document->classification_label)
            ->line('**Priority:** ' . $this->document->priority_label)
            ->line('**Submitted by:** ' . $this->submitter->name . ' (' . $this->submitter->rank . ')')
            ->line('**Approval Level:** ' . $this->currentLevel)
            ->action('Review Document', route('documents.show', $this->document->id))
            ->line('Please review and take appropriate action.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'approval_request',
            'document_id' => $this->document->id,
            'document_number' => $this->document->number,
            'document_subject' => $this->document->subject,
            'document_type' => $this->document->type,
            'document_type_label' => $this->document->type_label,
            'classification' => $this->document->classification,
            'priority' => $this->document->priority,
            'submitter_id' => $this->submitter->id,
            'submitter_name' => $this->submitter->name,
            'current_level' => $this->currentLevel,
            'message' => "New approval request: '{$this->document->subject}' from {$this->submitter->name}",
            'url' => route('documents.show', $this->document->id),
        ];
    }
}
