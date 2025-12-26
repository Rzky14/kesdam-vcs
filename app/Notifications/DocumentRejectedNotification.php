<?php

namespace App\Notifications;

use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Dokumen $document;
    protected User $rejector;
    protected string $reason;

    /**
    * Buat instance notifikasi baru.
     */
    public function __construct(Dokumen $document, User $rejector, string $reason)
    {
        $this->document = $document;
        $this->rejector = $rejector;
        $this->reason = $reason;
    }

    /**
    * Tentukan kanal pengiriman notifikasi.
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
     * Representasi email dari notifikasi.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Dokumen Ditolak: ' . $this->document->subject)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Mohon maaf, dokumen Anda ditolak.')
            ->line('**Nomor Dokumen:** ' . $this->document->number)
            ->line('**Perihal:** ' . $this->document->subject)
            ->line('**Ditolak oleh:** ' . $this->rejector->name . ' (' . $this->rejector->rank . ')')
            ->line('**Alasan:** ' . $this->reason)
            ->action('Lihat Dokumen', route('documents.show', $this->document->id))
            ->line('Silakan tinjau alasan penolakan dan lakukan tindak lanjut yang diperlukan.');
    }

    /**
    * Representasi array dari notifikasi.
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
            'message' => "Dokumen '{$this->document->subject}' ditolak oleh {$this->rejector->name}",
            'url' => route('documents.show', $this->document->id),
        ];
    }
}
