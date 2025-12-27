<?php

namespace App\Services;

use App\Models\Surat;
use App\Models\Schedule;
use App\Models\User;
use App\Models\CorrectionRequest;
use App\Notifications\ScheduleReminderNotification;
use App\Notifications\ApprovalRequestNotification;
use App\Notifications\DocumentApprovedNotification;
use App\Notifications\DocumentRejectedNotification;
use App\Notifications\CorrectionRequestedNotification;
use App\Notifications\DocumentStatusChangedNotification;
use Illuminate\Support\Collection;
use Illuminate\Notifications\DatabaseNotification;

/**
 * LayananNotifikasi (NotificationService)
 * 
 * Menangani pengiriman dan pengelolaan notifikasi untuk pengguna.
 */
class NotificationService
{
    /**
     * Kirim notifikasi pengingat jadwal.
     *
     * @param Schedule $schedule Jadwal yang akan diingatkan
     * @param User|Collection $users Pengguna yang akan menerima notifikasi
     * @param int $hoursUntilStart Jam sebelum jadwal dimulai
     * @return void
     */
    public function sendScheduleReminder(Schedule $schedule, $users, int $hoursUntilStart = 24): void
    {
        $users = $users instanceof User ? collect([$users]) : $users;

        foreach ($users as $user) {
            if ($user->hasInAppNotificationEnabled('schedule_reminder') || 
                $user->hasEmailNotificationEnabled('schedule_reminder')) {
                $user->notify(new ScheduleReminderNotification($schedule, $hoursUntilStart));
            }
        }
    }

    /**
     * Kirim notifikasi permintaan persetujuan.
     *
     * @param Surat $document Dokumen yang memerlukan persetujuan
     * @param User $approver Pengguna pemberi persetujuan
     * @param User $submitter Pengguna yang mengajukan
     * @param int $currentLevel Level persetujuan saat ini
     * @return void
     */
    public function sendApprovalRequest(Surat $document, User $approver, User $submitter, int $currentLevel): void
    {
        if ($approver->hasInAppNotificationEnabled('approval_request') || 
            $approver->hasEmailNotificationEnabled('approval_request')) {
            $approver->notify(new ApprovalRequestNotification($document, $submitter, $currentLevel));
        }
    }

    /**
     * Kirim notifikasi dokumen disetujui.
     *
     * @param Surat $document Dokumen yang disetujui
     * @param User $recipient Penerima notifikasi
     * @param User $approver Pengguna yang menyetujui
     * @param string|null $notes Catatan tambahan
     * @return void
     */
    public function sendDocumentApproved(Surat $document, User $recipient, User $approver, ?string $notes = null): void
    {
        if ($recipient->hasInAppNotificationEnabled('document_approved') || 
            $recipient->hasEmailNotificationEnabled('document_approved')) {
            $recipient->notify(new DocumentApprovedNotification($document, $approver, $notes));
        }
    }

    /**
     * Kirim notifikasi dokumen ditolak.
     *
     * @param Surat $document Dokumen yang ditolak
     * @param User $recipient Penerima notifikasi
     * @param User $rejector Pengguna yang menolak
     * @param string $reason Alasan penolakan
     * @return void
     */
    public function sendDocumentRejected(Surat $document, User $recipient, User $rejector, string $reason): void
    {
        if ($recipient->hasInAppNotificationEnabled('document_rejected') || 
            $recipient->hasEmailNotificationEnabled('document_rejected')) {
            $recipient->notify(new DocumentRejectedNotification($document, $rejector, $reason));
        }
    }

    /**
     * Kirim notifikasi permintaan koreksi.
     *
     * @param Surat $document Dokumen yang memerlukan koreksi
     * @param CorrectionRequest $correctionRequest Detail permintaan koreksi
     * @param User $recipient Penerima notifikasi
     * @param User $requester Pengguna yang meminta koreksi
     * @return void
     */
    public function sendCorrectionRequested(Surat $document, CorrectionRequest $correctionRequest, User $recipient, User $requester): void
    {
        if ($recipient->hasInAppNotificationEnabled('correction_requested') || 
            $recipient->hasEmailNotificationEnabled('correction_requested')) {
            $recipient->notify(new CorrectionRequestedNotification($document, $correctionRequest, $requester));
        }
    }

    /**
     * Kirim notifikasi perubahan status dokumen.
     *
     * @param Surat $document Dokumen yang statusnya berubah
     * @param User $recipient Penerima notifikasi
     * @param string $oldStatus Status lama
     * @param string $newStatus Status baru
     * @return void
     */
    public function sendDocumentStatusChanged(Surat $document, User $recipient, string $oldStatus, string $newStatus): void
    {
        if ($recipient->hasInAppNotificationEnabled('document_status_changed') || 
            $recipient->hasEmailNotificationEnabled('document_status_changed')) {
            $recipient->notify(new DocumentStatusChangedNotification($document, $oldStatus, $newStatus));
        }
    }

    /**
     * Ambil notifikasi yang belum dibaca untuk pengguna.
     *
     * @param User $user Pengguna
     * @param int|null $limit Batas jumlah notifikasi
     * @return Collection Daftar notifikasi
     */
    public function getUnreadNotifications(User $user, ?int $limit = null): Collection
    {
        $query = $user->unreadNotifications();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Ambil semua notifikasi untuk pengguna.
     *
     * @param User $user Pengguna
     * @param int|null $limit Batas jumlah notifikasi
     * @return Collection Daftar notifikasi
     */
    public function getAllNotifications(User $user, ?int $limit = null): Collection
    {
        $query = $user->notifications();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Ambil jumlah notifikasi yang belum dibaca.
     *
     * @param User $user Pengguna
     * @return int Jumlah notifikasi belum dibaca
     */
    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Tandai notifikasi sebagai sudah dibaca.
     *
     * @param DatabaseNotification $notification Notifikasi yang akan ditandai
     * @return void
     */
    public function markAsRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca untuk pengguna.
     *
     * @param User $user Pengguna
     * @return void
     */
    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    /**
     * Hapus notifikasi.
     *
     * @param DatabaseNotification $notification Notifikasi yang akan dihapus
     * @return void
     */
    public function deleteNotification(DatabaseNotification $notification): void
    {
        $notification->delete();
    }

    /**
     * Hapus semua notifikasi untuk pengguna.
     *
     * @param User $user Pengguna
     * @return void
     */
    public function deleteAllNotifications(User $user): void
    {
        $user->notifications()->delete();
    }

    /**
     * Ambil notifikasi berdasarkan tipe.
     *
     * @param User $user Pengguna
     * @param string $type Tipe notifikasi
     * @param int|null $limit Batas jumlah notifikasi
     * @return Collection Daftar notifikasi
     */
    public function getNotificationsByType(User $user, string $type, ?int $limit = null): Collection
    {
        $query = $user->notifications()->whereJsonContains('data->type', $type);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}



