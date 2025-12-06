<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

/**
 * AuditLog Model
 * 
 * Tracks all changes and important events in the system for audit trail purposes.
 */
class AuditLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model.
     *
     * @return MorphTo
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create a new audit log entry.
     *
     * @param string $event
     * @param Model|null $model
     * @param array $oldValues
     * @param array $newValues
     * @param string|null $description
     * @return static
     */
    public static function log(
        string $event,
        ?Model $model = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null
    ): static {
        return static::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description,
        ]);
    }
}
