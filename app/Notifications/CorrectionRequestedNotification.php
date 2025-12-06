<?php

namespace App\Notifications;

use App\Models\CorrectionRequest;
use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrectionRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Document $document;
    protected CorrectionRequest $correctionRequest;
    protected User $requester;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document, CorrectionRequest $correctionRequest, User $requester)
    {
        $this->document = $document;
        $this->correctionRequest = $correctionRequest;
        $this->requester = $requester;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->hasInAppNotificationEnabled('correction_requested')) {
            $channels[] = 'database';
        }

        if ($notifiable->hasEmailNotificationEnabled('correction_requested')) {
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
            ->subject('Correction Requested: ' . $this->document->subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A correction has been requested for your document.')
            ->line('**Document Number:** ' . $this->document->number)
            ->line('**Subject:** ' . $this->document->subject)
            ->line('**Requested by:** ' . $this->requester->name . ' (' . $this->requester->rank . ')')
            ->line('**Correction Notes:**')
            ->line($this->correctionRequest->notes)
            ->line('**Deadline:** ' . $this->correctionRequest->deadline->format('d M Y H:i'))
            ->action('View Document', route('documents.show', $this->document->id))
            ->line('Please make the necessary corrections and resubmit.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'correction_requested',
            'document_id' => $this->document->id,
            'document_number' => $this->document->number,
            'document_subject' => $this->document->subject,
            'correction_request_id' => $this->correctionRequest->id,
            'notes' => $this->correctionRequest->notes,
            'deadline' => $this->correctionRequest->deadline->toISOString(),
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'requester_rank' => $this->requester->rank,
            'message' => "Correction requested for '{$this->document->subject}' by {$this->requester->name}",
            'url' => route('documents.show', $this->document->id),
        ];
    }
}
