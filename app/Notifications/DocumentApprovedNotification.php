<?php

namespace App\Notifications;

use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Dokumen $document;
    protected User $approver;
    protected ?string $notes;

    /**
    * Buat instance notifikasi baru.
     */
    public function __construct(Dokumen $document, User $approver, ?string $notes = null)
    {
        $this->document = $document;
        $this->approver = $approver;
        $this->notes = $notes;
    }

    /**
    * Tentukan kanal pengiriman notifikasi.
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
     * Representasi email dari notifikasi.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Dokumen Disetujui: ' . $this->document->subject)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Kabar baik! Dokumen Anda telah disetujui.')
            ->line('**Nomor Dokumen:** ' . $this->document->number)
            ->line('**Perihal:** ' . $this->document->subject)
            ->line('**Disetujui oleh:** ' . $this->approver->name . ' (' . $this->approver->rank . ')');

        if ($this->notes) {
            $mail->line('**Catatan:** ' . $this->notes);
        }

        return $mail
            ->action('Lihat Dokumen', route('documents.show', $this->document->id))
            ->line('Status dokumen telah diperbarui menjadi disetujui.');
    }

    /**
     * Representasi array dari notifikasi.
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
            'message' => "Dokumen '{$this->document->subject}' telah disetujui oleh {$this->approver->name}",
            'url' => route('documents.show', $this->document->id),
        ];
    }
}
