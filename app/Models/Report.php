<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type', // 'schedule', 'document'
        'period_start',
        'period_end',
        'created_by',
        'data',
        'status', // 'generated', 'exported'
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Report belongs to User
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Get reports by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Get reports in date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('period_start', [$start, $end]);
    }

    /**
     * Scope: Get recent reports
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get type label
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'schedule' => 'Laporan Jadwal',
            'document' => 'Laporan Dokumen',
            default => $this->type,
        };
    }
}
