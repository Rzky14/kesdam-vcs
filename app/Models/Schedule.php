<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Schedule Model
 * 
 * Represents schedules for:
 * - Jadwal Dukkes (Health Support Schedule)
 * - Jadwal Jaga (Guard Duty Schedule)
 * - Jadwal Kegiatan Satuan (Unit Activity Schedule)
 */
class Schedule extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'title',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'personnel',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'personnel' => 'array',
    ];

    /**
     * Schedule types
     */
    const TYPE_DUKKES = 'dukkes';
    const TYPE_JAGA = 'jaga';
    const TYPE_KEGIATAN_SATUAN = 'kegiatan_satuan';

    /**
     * Schedule statuses
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the user who created this schedule.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this schedule.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get personnel assigned to this schedule.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getPersonnelUsers()
    {
        if (empty($this->personnel)) {
            return collect();
        }

        return User::whereIn('id', $this->personnel)->get();
    }

    /**
     * Check if schedule is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if schedule is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if schedule is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if schedule is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to filter schedules within date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Scope to search schedules.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        });
    }

    /**
     * Get formatted type label.
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_DUKKES => 'Jadwal Dukkes',
            self::TYPE_JAGA => 'Jadwal Jaga',
            self::TYPE_KEGIATAN_SATUAN => 'Jadwal Kegiatan Satuan',
            default => $this->type,
        };
    }

    /**
     * Get formatted status label.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => $this->status,
        };
    }

    /**
     * Get status badge color.
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_COMPLETED => 'info',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }
}
