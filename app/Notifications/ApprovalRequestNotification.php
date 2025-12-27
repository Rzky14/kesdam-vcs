<?php

namespace App\Notifications;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Surat $document;
    protected User $submitter;
    protected int $currentLevel;

    /**
    * Buat instance notifikasi baru.
     */
    public function __construct(Surat $document, User $submitter, int $currentLevel)
    {
        $this->document = $document;
        $this->submitter = $submitter;
        $this->currentLevel = $currentLevel;
    }

    /**
    * Tentukan kanal pengiriman notifikasi.
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
     * Representasi email dari notifikasi.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Permintaan Persetujuan: ' . $this->document->subject)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Anda memiliki dokumen baru menunggu persetujuan:')
            ->line('**Nomor Dokumen:** ' . $this->document->number)
            ->line('**Perihal:** ' . $this->document->subject)
            ->line('**Jenis:** ' . $this->document->type_label)
            ->line('**Klasifikasi:** ' . $this->document->classification_label)
            ->line('**Prioritas:** ' . $this->document->priority_label)
            ->line('**Diajukan oleh:** ' . $this->submitter->name . ' (' . $this->submitter->rank . ')')
            ->line('**Level Persetujuan:** ' . $this->currentLevel)
            ->action('Tinjau Dokumen', route('documents.show', $this->document->id))
            ->line('Silakan tinjau dan ambil tindakan yang sesuai.');
    }

    /**
     * Representasi array dari notifikasi.
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
            'message' => "Permintaan persetujuan baru: '{$this->document->subject}' dari {$this->submitter->name}",
            'url' => route('documents.show', $this->document->id),
        ];
    }
}



