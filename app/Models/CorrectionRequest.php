<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'approval_history_id',
        'requested_by_user_id',
        'assigned_to_user_id',
        'correction_notes',
        'status',
        'revision_number',
        'requested_at',
        'corrected_at',
        'due_date',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'corrected_at' => 'datetime',
        'due_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the document that needs correction
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the approval history that triggered this correction request
     */
    public function approvalHistory()
    {
        return $this->belongsTo(ApprovalHistory::class);
    }

    /**
     * Get the user who requested the correction (approver)
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * Get the user assigned to handle the correction (document creator)
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Scope to get pending corrections
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get in-progress corrections
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get completed corrections
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get rejected corrections
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get overdue corrections
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                     ->whereNotIn('status', ['completed', 'rejected']);
    }

    /**
     * Scope to get corrections for specific user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    /**
     * Scope to get corrections requested by specific user
     */
    public function scopeRequestedBy($query, $userId)
    {
        return $query->where('requested_by_user_id', $userId);
    }

    /**
     * Check if correction is overdue
     */
    public function isOverdue()
    {
        if ($this->due_date && $this->status !== 'completed' && $this->status !== 'rejected') {
            return $this->due_date->isPast();
        }
        return false;
    }

    /**
     * Check if correction is upcoming due
     */
    public function isDueSoon($days = 2)
    {
        if ($this->due_date && $this->status !== 'completed' && $this->status !== 'rejected') {
            return $this->due_date->diffInDays(now()) <= $days && !$this->isOverdue();
        }
        return false;
    }

    /**
     * Mark correction as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'corrected_at' => now(),
        ]);
        return $this;
    }

    /**
     * Mark correction as in progress
     */
    public function markAsInProgress()
    {
        $this->update(['status' => 'in_progress']);
        return $this;
    }

    /**
     * Get status label
     */
    public function getStatusLabel()
    {
        $labels = [
            'pending' => 'Menunggu',
            'in_progress' => 'Sedang Diproses',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor()
    {
        $colors = [
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'rejected' => 'danger',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Get days remaining until due date
     */
    public function getDaysRemaining()
    {
        if ($this->due_date) {
            return $this->due_date->diffInDays(now());
        }
        return null;
    }
}
