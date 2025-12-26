<?php

namespace App\Notifications;

use App\Models\CorrectionRequest;
use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrectionRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Dokumen $document;
    protected CorrectionRequest $correctionRequest;
    protected User $requester;

    /**
     * Buat instance notifikasi baru.
     */
    public function __construct(Dokumen $document, CorrectionRequest $correctionRequest, User $requester)
    {
        $this->document = $document;
        $this->correctionRequest = $correctionRequest;
        $this->requester = $requester;
    }

    /**
    * Tentukan kanal pengiriman notifikasi.
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
     * Representasi email dari notifikasi.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Permintaan Koreksi: ' . $this->document->subject)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Ada permintaan koreksi untuk dokumen Anda.')
            ->line('**Nomor Dokumen:** ' . $this->document->number)
            ->line('**Perihal:** ' . $this->document->subject)
            ->line('**Diminta oleh:** ' . $this->requester->name . ' (' . $this->requester->rank . ')')
            ->line('**Catatan Koreksi:**')
            ->line($this->correctionRequest->correction_notes)
            ->line('**Batas Waktu:** ' . ($this->correctionRequest->due_date?->format('d M Y H:i') ?? '-'))
            ->action('Lihat Dokumen', route('documents.show', $this->document->id))
            ->line('Silakan lakukan perbaikan yang diperlukan dan kirim ulang.');
    }

    /**
     * Representasi array dari notifikasi.
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
            'notes' => $this->correctionRequest->correction_notes,
            'deadline' => $this->correctionRequest->due_date?->toISOString(),
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'requester_rank' => $this->requester->rank,
            'message' => "Koreksi diminta untuk '{$this->document->subject}' oleh {$this->requester->name}",
            'url' => route('documents.show', $this->document->id),
        ];
    }
}
