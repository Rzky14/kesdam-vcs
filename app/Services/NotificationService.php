<?php

namespace App\Services;

use App\Models\Dokumen;
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

class NotificationService
{
    /**
     * Send schedule reminder notification
     *
     * @param Schedule $schedule
     * @param User|Collection $users
     * @param int $hoursUntilStart
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
     * Kirim notifikasi permintaan persetujuan
     *
     * @param Dokumen $document
     * @param User $approver
     * @param User $submitter
     * @param int $currentLevel
     * @return void
     */
    public function sendApprovalRequest(Dokumen $document, User $approver, User $submitter, int $currentLevel): void
    {
        if ($approver->hasInAppNotificationEnabled('approval_request') || 
            $approver->hasEmailNotificationEnabled('approval_request')) {
            $approver->notify(new ApprovalRequestNotification($document, $submitter, $currentLevel));
        }
    }

    /**
     * Kirim notifikasi dokumen disetujui
     *
     * @param Dokumen $document
     * @param User $recipient
     * @param User $approver
     * @param string|null $notes
     * @return void
     */
    public function sendDocumentApproved(Dokumen $document, User $recipient, User $approver, ?string $notes = null): void
    {
        if ($recipient->hasInAppNotificationEnabled('document_approved') || 
            $recipient->hasEmailNotificationEnabled('document_approved')) {
            $recipient->notify(new DocumentApprovedNotification($document, $approver, $notes));
        }
    }

    /**
     * Kirim notifikasi dokumen ditolak
     *
     * @param Dokumen $document
     * @param User $recipient
     * @param User $rejector
     * @param string $reason
     * @return void
     */
    public function sendDocumentRejected(Dokumen $document, User $recipient, User $rejector, string $reason): void
    {
        if ($recipient->hasInAppNotificationEnabled('document_rejected') || 
            $recipient->hasEmailNotificationEnabled('document_rejected')) {
            $recipient->notify(new DocumentRejectedNotification($document, $rejector, $reason));
        }
    }

    /**
     * Kirim notifikasi permintaan koreksi
     *
     * @param Dokumen $document
     * @param CorrectionRequest $correctionRequest
     * @param User $recipient
     * @param User $requester
     * @return void
     */
    public function sendCorrectionRequested(Dokumen $document, CorrectionRequest $correctionRequest, User $recipient, User $requester): void
    {
        if ($recipient->hasInAppNotificationEnabled('correction_requested') || 
            $recipient->hasEmailNotificationEnabled('correction_requested')) {
            $recipient->notify(new CorrectionRequestedNotification($document, $correctionRequest, $requester));
        }
    }

    /**
     * Kirim notifikasi perubahan status dokumen
     *
     * @param Dokumen $document
     * @param User $recipient
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    public function sendDocumentStatusChanged(Dokumen $document, User $recipient, string $oldStatus, string $newStatus): void
    {
        if ($recipient->hasInAppNotificationEnabled('document_status_changed') || 
            $recipient->hasEmailNotificationEnabled('document_status_changed')) {
            $recipient->notify(new DocumentStatusChangedNotification($document, $oldStatus, $newStatus));
        }
    }

    /**
     * Get unread notifications for user
     *
     * @param User $user
     * @param int|null $limit
     * @return Collection
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
     * Get all notifications for user
     *
     * @param User $user
     * @param int|null $limit
     * @return Collection
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
     * Get unread notifications count
     *
     * @param User $user
     * @return int
     */
    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Mark notification as read
     *
     * @param DatabaseNotification $notification
     * @return void
     */
    public function markAsRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for user
     *
     * @param User $user
     * @return void
     */
    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    /**
     * Delete notification
     *
     * @param DatabaseNotification $notification
     * @return void
     */
    public function deleteNotification(DatabaseNotification $notification): void
    {
        $notification->delete();
    }

    /**
     * Delete all notifications for user
     *
     * @param User $user
     * @return void
     */
    public function deleteAllNotifications(User $user): void
    {
        $user->notifications()->delete();
    }

    /**
     * Get notifications by type
     *
     * @param User $user
     * @param string $type
     * @param int|null $limit
     * @return Collection
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
