<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Document extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'classification',
        'number',
        'date',
        'sender',
        'recipient',
        'subject',
        'description',
        'attachments',
        'status',
        'priority',
        'is_encrypted',
        'archived_at',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'archived_at' => 'date',
        'attachments' => 'array',
        'is_encrypted' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'is_encrypted',
    ];

    /**
     * Get the creator of the document.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the last updater of the document.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all approval histories for this document.
     */
    public function approvalHistories()
    {
        return $this->hasMany(ApprovalHistory::class);
    }

    /**
     * Get all correction requests for this document.
     */
    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    /**
     * Get all approval deadlines for this document.
     */
    public function approvalDeadlines()
    {
        return $this->hasMany(ApprovalDeadline::class);
    }

    /**
     * Get the current approval status
     */
    public function getCurrentApprovalLevel()
    {
        return $this->approvalHistories()
            ->orderBy('approval_level', 'desc')
            ->value('approval_level') ?? 0;
    }

    /**
     * Get the last approval action
     */
    public function getLastApprovalAction()
    {
        return $this->approvalHistories()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Check if document has pending corrections
     */
    public function hasPendingCorrections()
    {
        return $this->correctionRequests()
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Scope a query to only include documents of a given type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include documents with a given classification.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $classification
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfClassification($query, $classification)
    {
        return $query->where('classification', $classification);
    }

    /**
     * Scope a query to only include documents with a given status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include draft documents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include pending approval documents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    /**
     * Scope a query to only include approved documents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include archived documents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope a query to filter by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Check if the document is a draft.
     *
     * @return bool
     */
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the document is pending approval.
     *
     * @return bool
     */
    public function isPendingApproval()
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if the document is approved.
     *
     * @return bool
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the document is rejected.
     *
     * @return bool
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if the document is archived.
     *
     * @return bool
     */
    public function isArchived()
    {
        return $this->status === 'archived';
    }

    /**
     * Check if the document is incoming.
     *
     * @return bool
     */
    public function isIncoming()
    {
        return $this->type === 'masuk';
    }

    /**
     * Check if the document is outgoing.
     *
     * @return bool
     */
    public function isOutgoing()
    {
        return $this->type === 'keluar';
    }

    /**
     * Check if the document is classified.
     *
     * @return bool
     */
    public function isClassified()
    {
        return $this->classification === 'rahasia';
    }

    /**
     * Check if the document is a telegram.
     *
     * @return bool
     */
    public function isTelegram()
    {
        return $this->classification === 'telegram';
    }

    /**
     * Get the human-readable type label.
     *
     * @return string
     */
    public function getTypeLabel()
    {
        return match($this->type) {
            'masuk' => 'Surat Masuk',
            'keluar' => 'Surat Keluar',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get the human-readable classification label.
     *
     * @return string
     */
    public function getClassificationLabel()
    {
        return match($this->classification) {
            'biasa' => 'Biasa',
            'rahasia' => 'Rahasia',
            'telegram' => 'Telegram',
            default => ucfirst($this->classification),
        };
    }

    /**
     * Get the human-readable status label.
     *
     * @return string
     */
    public function getStatusLabel()
    {
        return match($this->status) {
            'draft' => 'Draft',
            'pending_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'archived' => 'Diarsipkan',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the human-readable priority label.
     *
     * @return string
     */
    public function getPriorityLabel()
    {
        return match($this->priority) {
            'normal' => 'Normal',
            'high' => 'Tinggi',
            'urgent' => 'Mendesak',
            default => ucfirst($this->priority),
        };
    }

    /**
     * Get the status badge CSS class.
     *
     * @return string
     */
    public function getStatusBadgeClass()
    {
        return match($this->status) {
            'draft' => 'badge bg-secondary',
            'pending_approval' => 'badge bg-warning',
            'approved' => 'badge bg-success',
            'rejected' => 'badge bg-danger',
            'archived' => 'badge bg-info',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Get the priority badge CSS class.
     *
     * @return string
     */
    public function getPriorityBadgeClass()
    {
        return match($this->priority) {
            'normal' => 'badge bg-secondary',
            'high' => 'badge bg-warning',
            'urgent' => 'badge bg-danger',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Encrypt sensitive fields for classified documents.
     *
     * @param  string  $value
     * @return string
     */
    public function encryptField($value)
    {
        if ($this->isClassified() && !empty($value)) {
            return Crypt::encryptString($value);
        }
        return $value;
    }

    /**
     * Decrypt sensitive fields for classified documents.
     *
     * @param  string  $value
     * @return string
     */
    public function decryptField($value)
    {
        if ($this->isClassified() && !empty($value) && $this->is_encrypted) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    /**
     * Get the next required approver role for this document
     * 
     * @return string|null Role name of next approver (kaur, kasi, pimpinan) or null if fully approved
     */
    public function getNextApproverRole(): ?string
    {
        $currentLevel = $this->getCurrentApprovalLevel();
        
        return match($currentLevel) {
            0 => 'kaur',        // Level 1: Menunggu Kaur
            1 => 'kasi',        // Level 2: Menunggu Kasi (Kaur sudah approve)
            2 => 'pimpinan',    // Level 3: Menunggu Pimpinan (Kasi sudah approve)
            default => null,    // Semua sudah approve
        };
    }

    /**
     * Check if user can approve at current level
     * 
     * @param User $user
     * @return bool
     */
    public function canUserApprove(User $user): bool
    {
        // Document must be pending approval
        if (!$this->isPendingApproval()) {
            return false;
        }

        $nextApproverRole = $this->getNextApproverRole();
        
        if (!$nextApproverRole) {
            return false; // Already fully approved
        }

        // Check if user has the required role
        return $user->hasRole($nextApproverRole);
    }

    /**
     * Get approval level by role name
     * 
     * @param string $role
     * @return int
     */
    protected function getApprovalLevelByRole(string $role): int
    {
        return match($role) {
            'kaur' => 1,
            'kasi' => 2,
            'pimpinan' => 3,
            default => 0,
        };
    }

    /**
     * Approve document by user
     * 
     * @param User $user
     * @param string|null $notes Optional approval notes
     * @return bool
     * @throws \Exception
     */
    public function approve(User $user, ?string $notes = null): bool
    {
        if (!$this->canUserApprove($user)) {
            throw new \Exception('Anda tidak berwenang menyetujui dokumen ini pada level saat ini.');
        }

        $nextRole = $this->getNextApproverRole();
        $approvalLevel = $this->getApprovalLevelByRole($nextRole);

        \DB::beginTransaction();
        try {
            // Create approval history
            $this->approvalHistories()->create([
                'user_id' => $user->id,
                'action' => 'approved',
                'status' => 'approved',
                'approval_level' => $approvalLevel,
                'comment' => $notes,
                'action_date' => now(),
            ]);

            // Check if this is the final approval (Pimpinan)
            if ($nextRole === 'pimpinan') {
                $this->update([
                    'status' => 'approved',
                    'updated_by' => $user->id,
                ]);
            } else {
                // Still need more approvals, keep pending
                $this->touch(); // Update timestamp
                $this->update(['updated_by' => $user->id]);
            }

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject document by user
     * 
     * @param User $user
     * @param string $reason Rejection reason
     * @return bool
     * @throws \Exception
     */
    public function reject(User $user, string $reason): bool
    {
        if (!$this->canUserApprove($user)) {
            throw new \Exception('Anda tidak berwenang menolak dokumen ini pada level saat ini.');
        }

        if (empty($reason)) {
            throw new \Exception('Alasan penolakan harus diisi.');
        }

        $nextRole = $this->getNextApproverRole();
        $approvalLevel = $this->getApprovalLevelByRole($nextRole);

        \DB::beginTransaction();
        try {
            // Create approval history with rejection
            $this->approvalHistories()->create([
                'user_id' => $user->id,
                'action' => 'rejected',
                'status' => 'rejected',
                'approval_level' => $approvalLevel,
                'comment' => $reason,
                'action_date' => now(),
            ]);

            // Update document status to rejected
            $this->update([
                'status' => 'rejected',
                'updated_by' => $user->id,
            ]);

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Request correction for document
     * 
     * @param User $user
     * @param string $reason Correction reason
     * @return bool
     * @throws \Exception
     */
    public function requestCorrection(User $user, string $reason): bool
    {
        if (!$this->canUserApprove($user)) {
            throw new \Exception('Anda tidak berwenang meminta koreksi dokumen ini.');
        }

        if (empty($reason)) {
            throw new \Exception('Alasan permintaan koreksi harus diisi.');
        }

        \DB::beginTransaction();
        try {
            // Create correction request
            $this->correctionRequests()->create([
                'requested_by' => $user->id,
                'reason' => $reason,
                'status' => 'pending',
            ]);

            // Update document status
            $this->update([
                'status' => 'correction_requested',
                'updated_by' => $user->id,
            ]);

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
}

