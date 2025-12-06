<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'user_id',
        'action',
        'status',
        'comment',
        'current_approver_role',
        'approval_level',
        'ip_address',
        'user_agent',
        'action_date',
    ];

    protected $casts = [
        'action_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the document this approval history belongs to
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user who performed this approval action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the signature associated with this approval (if any)
     */
    public function signature()
    {
        return $this->hasOne(ApprovalSignature::class);
    }

    /**
     * Scope to get histories for specific document
     */
    public function scopeForDocument($query, $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    /**
     * Scope to get histories for specific approval action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get pending approvals
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved items
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected items
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get correction requests
     */
    public function scopeCorrectionRequested($query)
    {
        return $query->where('status', 'correction_requested');
    }

    /**
     * Get the approval history sorted by approval level
     */
    public function scopeOrderedByLevel($query)
    {
        return $query->orderBy('approval_level', 'asc');
    }

    /**
     * Get the approver role details
     */
    public function approverRole()
    {
        return Role::find($this->current_approver_role);
    }

    /**
     * Check if this is the final approval
     */
    public function isFinalApproval($totalLevels)
    {
        return $this->approval_level >= $totalLevels;
    }

    /**
     * Get formatted action label
     */
    public function getActionLabel()
    {
        $labels = [
            'submitted' => 'Diajukan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'correction_requested' => 'Permintaan Koreksi',
            'resubmitted' => 'Diajukan Ulang',
        ];
        return $labels[$this->action] ?? $this->action;
    }

    /**
     * Get formatted status badge color
     */
    public function getStatusBadgeColor()
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'correction_requested' => 'info',
        ];
        return $colors[$this->status] ?? 'secondary';
    }
}
