<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $table = 'user_notification_preferences';

    protected $fillable = [
        'user_id',
        'approval_request_enabled',
        'approval_request_email',
        'document_status_enabled',
        'document_status_email',
        'schedule_reminder_enabled',
        'schedule_reminder_email',
        'system_notifications_enabled',
        'schedule_reminder_timing',
    ];

    protected $casts = [
        'approval_request_enabled' => 'boolean',
        'approval_request_email' => 'boolean',
        'document_status_enabled' => 'boolean',
        'document_status_email' => 'boolean',
        'schedule_reminder_enabled' => 'boolean',
        'schedule_reminder_email' => 'boolean',
        'system_notifications_enabled' => 'boolean',
    ];

    /**
     * Relationship: Preference belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if notification type is enabled
     */
    public function isNotificationEnabled(string $type): bool
    {
        $field = match($type) {
            'approval_request' => 'approval_request_enabled',
            'document_status' => 'document_status_enabled',
            'schedule_reminder' => 'schedule_reminder_enabled',
            'system' => 'system_notifications_enabled',
            default => null,
        };

        return $field ? $this->getAttribute($field) : false;
    }

    /**
     * Check if email notification is enabled for type
     */
    public function isEmailEnabled(string $type): bool
    {
        $field = match($type) {
            'approval_request' => 'approval_request_email',
            'document_status' => 'document_status_email',
            'schedule_reminder' => 'schedule_reminder_email',
            default => null,
        };

        return $field ? $this->getAttribute($field) : false;
    }

    /**
     * Get schedule reminder timing options
     */
    public static function getTimingOptions(): array
    {
        return [
            '1-day' => '1 Hari Sebelumnya',
            'day-of' => 'Hari Jadwal',
            '1-hour' => '1 Jam Sebelumnya',
        ];
    }
}
