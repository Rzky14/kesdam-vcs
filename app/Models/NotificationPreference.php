<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'in_app_enabled',
        'email_enabled',
    ];

    protected $casts = [
        'in_app_enabled' => 'boolean',
        'email_enabled' => 'boolean',
    ];

    /**
     * Notification types available in the system
     */
    public const TYPES = [
        'schedule_reminder' => 'Schedule Reminder',
        'approval_request' => 'Approval Request',
        'document_approved' => 'Document Approved',
        'document_rejected' => 'Document Rejected',
        'correction_requested' => 'Correction Requested',
        'document_status_changed' => 'Document Status Changed',
    ];

    /**
     * Relationship: Preference belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if notification type is enabled for in-app
     */
    public function isInAppEnabled(): bool
    {
        return $this->in_app_enabled;
    }

    /**
     * Check if notification type is enabled for email
     */
    public function isEmailEnabled(): bool
    {
        return $this->email_enabled;
    }

    /**
     * Get human-readable notification type name
     */
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->notification_type] ?? $this->notification_type;
    }
}
