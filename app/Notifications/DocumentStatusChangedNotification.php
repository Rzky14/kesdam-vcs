<?php

namespace App\Notifications;

use App\Models\Surat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Surat $document;
    protected string $oldStatus;
    protected string $newStatus;

    /**
    * Buat instance notifikasi baru.
     */
    public function __construct(Surat $document, string $oldStatus, string $newStatus)
    {
        $this->document = $document;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
    * Tentukan kanal pengiriman notifikasi.
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
     * Representasi email dari notifikasi.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'draft' => 'Draft',
            'pending_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'correction_requested' => 'Butuh Koreksi',
            'archived' => 'Diarsipkan',
        ];

        $oldLabel = $statusLabels[$this->oldStatus] ?? $this->oldStatus;
        $newLabel = $statusLabels[$this->newStatus] ?? $this->newStatus;

        return (new MailMessage)
            ->subject('Status Dokumen Diperbarui: ' . $this->document->subject)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Status dokumen Anda telah diperbarui.')
            ->line('**Nomor Dokumen:** ' . $this->document->number)
            ->line('**Perihal:** ' . $this->document->subject)
            ->line('**Status Sebelumnya:** ' . $oldLabel)
            ->line('**Status Baru:** ' . $newLabel)
            ->action('Lihat Dokumen', route('documents.show', $this->document->id))
            ->line('Terima kasih telah menggunakan aplikasi KESDAM VCS.');
    }

    /**
     * Representasi array dari notifikasi.
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
            'message' => "Status dokumen '{$this->document->subject}' berubah dari {$this->oldStatus} menjadi {$this->newStatus}",
            'url' => route('documents.show', $this->document->id),
        ];
    }
}



