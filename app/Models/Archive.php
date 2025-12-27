<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Archive extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'archiveable_type',
        'archiveable_id',
        'archived_by',
        'archive_date',
        'retention_until',
        'category',
        'tags',
        'is_indexed',
    ];

    protected $casts = [
        'archive_date' => 'datetime',
        'retention_until' => 'datetime',
        'tags' => 'array',
        'is_indexed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Archive belongs to User (archivist)
     */
    public function archivist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Polymorphic relationship to archiveable model
     */
    public function archiveable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Get archives by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Get indexed archives
     */
    public function scopeIndexed($query)
    {
        return $query->where('is_indexed', true);
    }

    /**
     * Scope: Get archives by retention date
     */
    public function scopeRetentionExpiring($query, int $days = 30)
    {
        return $query->whereBetween('retention_until', [
            now(),
            now()->addDays($days)
        ]);
    }

    /**
     * Search in archive
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->orWhereJsonContains('tags', $term);
    }

    /**
     * Mark as indexed
     */
    public function markAsIndexed(): void
    {
        $this->update(['is_indexed' => true]);
    }

    /**
     * Get category label
     */
    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'schedule' => 'Jadwal',
            'document' => 'Dokumen',
            'report' => 'Laporan',
            default => $this->category,
        };
    }
}
