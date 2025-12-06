<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalDeadline extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'approval_level',
        'document_type',
        'classification',
        'days_allowed',
        'deadline_at',
        'status',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'days_allowed' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the document this deadline belongs to
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Scope to get active deadlines
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get met deadlines
     */
    public function scopeMet($query)
    {
        return $query->where('status', 'met');
    }

    /**
     * Scope to get missed deadlines
     */
    public function scopeMissed($query)
    {
        return $query->where('status', 'missed');
    }

    /**
     * Scope to get waived deadlines
     */
    public function scopeWaived($query)
    {
        return $query->where('status', 'waived');
    }

    /**
     * Scope to get overdue deadlines
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline_at', '<', now())
                     ->where('status', 'active');
    }

    /**
     * Scope to get upcoming deadlines
     */
    public function scopeUpcoming($query, $days = 3)
    {
        return $query->whereBetween('deadline_at', [now(), now()->addDays($days)])
                     ->where('status', 'active');
    }

    /**
     * Scope to filter by document type
     */
    public function scopeByDocumentType($query, $documentType)
    {
        return $query->where('document_type', $documentType);
    }

    /**
     * Scope to filter by classification
     */
    public function scopeByClassification($query, $classification)
    {
        return $query->where('classification', $classification);
    }

    /**
     * Scope to filter by approval level
     */
    public function scopeByApprovalLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }

    /**
     * Check if deadline is overdue
     */
    public function isOverdue()
    {
        return $this->status === 'active' && $this->deadline_at->isPast();
    }

    /**
     * Check if deadline is coming soon
     */
    public function isComingSoon($days = 2)
    {
        if ($this->status === 'active') {
            $daysRemaining = $this->deadline_at->diffInDays(now());
            return $daysRemaining <= $days && !$this->isOverdue();
        }
        return false;
    }

    /**
     * Mark deadline as met
     */
    public function markAsMet()
    {
        $this->update(['status' => 'met']);
        return $this;
    }

    /**
     * Mark deadline as missed
     */
    public function markAsMissed()
    {
        $this->update(['status' => 'missed']);
        return $this;
    }

    /**
     * Waive deadline
     */
    public function waive()
    {
        $this->update(['status' => 'waived']);
        return $this;
    }

    /**
     * Get days remaining until deadline
     */
    public function getDaysRemaining()
    {
        if ($this->status === 'active') {
            return $this->deadline_at->diffInDays(now());
        }
        return null;
    }

    /**
     * Get hours remaining until deadline
     */
    public function getHoursRemaining()
    {
        if ($this->status === 'active') {
            return $this->deadline_at->diffInHours(now());
        }
        return null;
    }

    /**
     * Get status label
     */
    public function getStatusLabel()
    {
        $labels = [
            'active' => 'Aktif',
            'met' => 'Terpenuhi',
            'missed' => 'Terlewat',
            'waived' => 'Dihapuskan',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor()
    {
        $colors = [
            'active' => 'primary',
            'met' => 'success',
            'missed' => 'danger',
            'waived' => 'secondary',
        ];
        return $colors[$this->status] ?? 'secondary';
    }
}
