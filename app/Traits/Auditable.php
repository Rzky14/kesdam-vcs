<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Auditable Trait
 * 
 * Automatically tracks create, update, and delete events for models.
 * Usage: Add 'use Auditable;' to any model that needs audit tracking.
 */
trait Auditable
{
    /**
     * Boot the auditable trait for a model.
     *
     * @return void
     */
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->logAudit('created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            $model->logAudit('updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model) {
            $model->logAudit('deleted', $model->getAttributes(), []);
        });
    }

    /**
     * Log an audit entry.
     *
     * @param string $event
     * @param array $oldValues
     * @param array $newValues
     * @param string|null $description
     * @return AuditLog
     */
    protected function logAudit(
        string $event,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description ?? $this->getAuditDescription($event),
        ]);
    }

    /**
     * Get a description for the audit log.
     *
     * @param string $event
     * @return string
     */
    protected function getAuditDescription(string $event): string
    {
        $modelName = class_basename($this);
        $id = $this->id ?? 'unknown';

        return match ($event) {
            'created' => "{$modelName} #{$id} was created",
            'updated' => "{$modelName} #{$id} was updated",
            'deleted' => "{$modelName} #{$id} was deleted",
            default => "{$modelName} #{$id} - {$event}",
        };
    }

    /**
     * Get audit logs for this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
