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
     * Mendapatkan dokumen yang terkait dengan riwayat persetujuan ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dokumen()
    {
        return $this->belongsTo(Dokumen::class, 'document_id');
    }

    /**
     * Alias untuk dokumen() - kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document()
    {
        return $this->dokumen();
    }

    /**
     * Mendapatkan pengguna yang melakukan aksi persetujuan ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan tanda tangan yang terkait dengan persetujuan ini (jika ada).
     */
    public function signature()
    {
        return $this->hasOne(ApprovalSignature::class);
    }

    /**
     * Scope untuk mendapatkan riwayat untuk dokumen tertentu.
     */
    public function scopeForDocument($query, $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    /**
     * Scope untuk mendapatkan riwayat berdasarkan aksi tertentu.
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope untuk mendapatkan persetujuan yang tertunda.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope untuk mendapatkan item yang disetujui.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope untuk mendapatkan item yang ditolak.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope untuk mendapatkan permintaan koreksi.
     */
    public function scopeCorrectionRequested($query)
    {
        return $query->where('status', 'correction_requested');
    }

    /**
     * Mendapatkan riwayat persetujuan yang diurutkan berdasarkan tingkat persetujuan.
     */
    public function scopeOrderedByLevel($query)
    {
        return $query->orderBy('approval_level', 'asc');
    }

    /**
     * Mendapatkan detail peran penyetuju.
     */
    public function approverRole()
    {
        return Role::find($this->current_approver_role);
    }

    /**
     * Cek apakah ini persetujuan terakhir.
     */
    public function isFinalApproval($totalLevels)
    {
        return $this->approval_level >= $totalLevels;
    }

    /**
     * Mendapatkan label aksi yang terformat.
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
     * Mendapatkan warna badge status yang terformat.
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
