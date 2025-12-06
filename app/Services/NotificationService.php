<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NotificationService
{
    /**
     * Buat notifikasi untuk satu atau lebih user
     */
    public function createNotification(
        User|array $users,
        string $type,
        string $title,
        string $message,
        string $icon = 'bell',
        string $actionUrl = null,
        string $relatedModelType = null,
        int $relatedModelId = null
    ): Collection {
        $users = is_array($users) ? $users : [$users];
        $notifications = new Collection();

        foreach ($users as $user) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'icon' => $icon,
                'action_url' => $actionUrl,
                'related_model_type' => $relatedModelType,
                'related_model_id' => $relatedModelId,
            ]);

            $notifications->push($notification);
        }

        return $notifications;
    }

    /**
     * Kirim notifikasi approval request
     */
    public function notifyApprovalRequest(Document $document, User $user): void
    {
        $preferences = $user->getOrCreateNotificationPreferences();

        if (!$preferences->isNotificationEnabled('approval_request')) {
            return;
        }

        $title = 'Persetujuan Dokumen Diperlukan';
        $message = "Dokumen '{$document->subject}' memerlukan persetujuan Anda.";
        $actionUrl = route('approvals.show', $document);

        $this->createNotification(
            $user,
            'approval_request',
            $title,
            $message,
            'file-earmark-check',
            $actionUrl,
            'Document',
            $document->id
        );

        // Log the notification
        $this->logNotification($user, 'in_app', 'sent');
    }

    /**
     * Kirim notifikasi status dokumen berubah
     */
    public function notifyDocumentStatusChange(Document $document, string $status, string $reason = null): void
    {
        $statusLabels = [
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'pending_correction' => 'Koreksi Diminta',
            'resubmitted' => 'Diajukan Ulang',
        ];

        $icons = [
            'approved' => 'check-circle',
            'rejected' => 'x-circle',
            'pending_correction' => 'exclamation-circle',
            'resubmitted' => 'arrow-repeat',
        ];

        $label = $statusLabels[$status] ?? $status;
        $icon = $icons[$status] ?? 'info-circle';

        $title = "Status Dokumen: {$label}";
        $message = "Dokumen '{$document->subject}' telah {$label}.";

        if ($reason) {
            $message .= " Alasan: {$reason}";
        }

        $actionUrl = route('documents.show', $document);

        // Notify document owner
        $this->createNotification(
            $document->created_by,
            'document_status',
            $title,
            $message,
            $icon,
            $actionUrl,
            'Document',
            $document->id
        );

        // Log the notification
        $this->logNotification($document->created_by, 'in_app', 'sent');
    }

    /**
     * Kirim notifikasi schedule reminder
     */
    public function notifyScheduleReminder(Schedule $schedule, string $timing = '1-day'): void
    {
        $timingLabels = [
            '1-day' => '1 hari lagi',
            'day-of' => 'hari ini',
            '1-hour' => '1 jam lagi',
        ];

        $label = $timingLabels[$timing] ?? $timing;
        $title = "Pengingat Jadwal: {$schedule->title}";
        $message = "Jadwal '{$schedule->title}' akan dimulai {$label} pada {$schedule->start_date->format('d/m/Y H:i')}.";
        $actionUrl = route('schedules.show', $schedule);

        // Notify all related users (e.g., those assigned to this schedule)
        $relatedUsers = $schedule->getRelatedUsers();

        foreach ($relatedUsers as $user) {
            $preferences = $user->getOrCreateNotificationPreferences();

            if (!$preferences->isNotificationEnabled('schedule_reminder')) {
                continue;
            }

            $this->createNotification(
                $user,
                'schedule_reminder',
                $title,
                $message,
                'calendar-event',
                $actionUrl,
                'Schedule',
                $schedule->id
            );

            // Log the notification
            $this->logNotification($user, 'in_app', 'sent');
        }
    }

    /**
     * Kirim notifikasi sistem
     */
    public function notifySystem(User $user, string $title, string $message, string $icon = 'info-circle'): void
    {
        $preferences = $user->getOrCreateNotificationPreferences();

        if (!$preferences->isNotificationEnabled('system')) {
            return;
        }

        $this->createNotification(
            $user,
            'system',
            $title,
            $message,
            $icon
        );

        $this->logNotification($user, 'in_app', 'sent');
    }

    /**
     * Mark notifikasi sebagai read
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark semua notifikasi user sebagai read
     */
    public function markAllAsRead(User $user): void
    {
        $user->notifications()->unread()->update(['read_at' => now()]);
    }

    /**
     * Dapatkan unread notifications untuk user
     */
    public function getUnreadNotifications(User $user, int $limit = 10): Collection
    {
        return $user->notifications()
            ->unread()
            ->limit($limit)
            ->get();
    }

    /**
     * Dapatkan semua notifications untuk user
     */
    public function getNotifications(User $user, int $limit = 20): Collection
    {
        return $user->notifications()
            ->limit($limit)
            ->get();
    }

    /**
     * Dapatkan notifications berdasarkan type
     */
    public function getNotificationsByType(User $user, string $type, int $limit = 20): Collection
    {
        return $user->notifications()
            ->byType($type)
            ->limit($limit)
            ->get();
    }

    /**
     * Delete notifikasi
     */
    public function deleteNotification(Notification $notification): void
    {
        $notification->delete();
    }

    /**
     * Delete semua notifikasi user
     */
    public function deleteAllNotifications(User $user): void
    {
        $user->notifications()->delete();
    }

    /**
     * Log notification sending
     */
    public function logNotification(User $user, string $type = 'in_app', string $status = 'sent'): void
    {
        NotificationLog::create([
            'user_id' => $user->id,
            'type' => $type,
            'status' => $status,
        ]);
    }

    /**
     * Get notification count for user
     */
    public function getNotificationCount(User $user): int
    {
        return $user->notifications()->count();
    }

    /**
     * Get unread notification count for user
     */
    public function getUnreadNotificationCount(User $user): int
    {
        return $user->notifications()->unread()->count();
    }

    /**
     * Clear old notifications (older than 30 days)
     */
    public function clearOldNotifications(int $days = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(User $user): array
    {
        return [
            'total' => $this->getNotificationCount($user),
            'unread' => $this->getUnreadNotificationCount($user),
            'approval_requests' => $user->notifications()->byType('approval_request')->count(),
            'document_status_changes' => $user->notifications()->byType('document_status')->count(),
            'schedule_reminders' => $user->notifications()->byType('schedule_reminder')->count(),
            'system' => $user->notifications()->byType('system')->count(),
        ];
    }
}
