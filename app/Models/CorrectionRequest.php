<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model PermintaanKoreksi (CorrectionRequest)
 * 
 * Merepresentasikan permintaan koreksi dokumen yang diminta oleh penyetuju.
 * Mencakup catatan koreksi, status, dan tenggat waktu.
 */
class CorrectionRequest extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi massal.
     *
     * @var array<int, string>
     */
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

    /**
     * Cast atribut ke tipe yang sesuai.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_at' => 'datetime',
        'corrected_at' => 'datetime',
        'due_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELASI
    // ==========================================

    /**
     * Mendapatkan dokumen yang terkait dengan permintaan koreksi ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dokumen()
    {
        return $this->belongsTo(Dokumen::class, 'document_id');
    }

    /**
     * Alias: document() untuk kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document()
    {
        return $this->dokumen();
    }

    /**
     * Mendapatkan riwayat persetujuan yang memicu permintaan koreksi ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function riwayatPersetujuan()
    {
        return $this->belongsTo(ApprovalHistory::class, 'approval_history_id');
    }

    /**
     * Alias: approvalHistory() untuk kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approvalHistory()
    {
        return $this->riwayatPersetujuan();
    }

    /**
     * Mendapatkan pengguna yang meminta koreksi (penyetuju).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dimintaOleh()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * Alias: requestedBy() untuk kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function requestedBy()
    {
        return $this->dimintaOleh();
    }

    /**
     * Mendapatkan pengguna yang ditugaskan menangani koreksi (pembuat dokumen).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ditugaskanKepada()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Alias: assignedTo() untuk kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedTo()
    {
        return $this->ditugaskanKepada();
    }

    // ==========================================
    // SCOPE QUERIES
    // ==========================================

    /**
     * Scope untuk koreksi yang menunggu.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope untuk koreksi yang sedang diproses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope untuk koreksi yang sudah selesai.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope untuk koreksi yang ditolak.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope untuk koreksi yang sudah lewat tenggat.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                     ->whereNotIn('status', ['completed', 'rejected']);
    }

    /**
     * Scope untuk koreksi yang ditugaskan ke pengguna tertentu.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $idPengguna ID pengguna
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssignedTo($query, $idPengguna)
    {
        return $query->where('assigned_to_user_id', $idPengguna);
    }

    /**
     * Scope untuk koreksi yang diminta oleh pengguna tertentu.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $idPengguna ID pengguna
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequestedBy($query, $idPengguna)
    {
        return $query->where('requested_by_user_id', $idPengguna);
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Memeriksa apakah koreksi sudah melewati tenggat.
     *
     * @return bool True jika sudah melewati tenggat
     */
    public function apakahTerlewat()
    {
        if ($this->due_date && $this->status !== 'completed' && $this->status !== 'rejected') {
            return $this->due_date->isPast();
        }
        return false;
    }

    /**
     * Alias: isOverdue() untuk kompatibilitas.
     *
     * @return bool
     */
    public function isOverdue()
    {
        return $this->apakahTerlewat();
    }

    /**
     * Memeriksa apakah koreksi akan segera jatuh tempo.
     *
     * @param int $hari Jumlah hari toleransi
     * @return bool True jika akan segera jatuh tempo
     */
    public function apakahAkanSegeraJatuhTempo($hari = 2)
    {
        if ($this->due_date && $this->status !== 'completed' && $this->status !== 'rejected') {
            return $this->due_date->diffInDays(now()) <= $hari && !$this->apakahTerlewat();
        }
        return false;
    }

    /**
     * Alias: isDueSoon() untuk kompatibilitas.
     *
     * @param int $days Jumlah hari
     * @return bool
     */
    public function isDueSoon($days = 2)
    {
        return $this->apakahAkanSegeraJatuhTempo($days);
    }

    /**
     * Tandai koreksi sebagai selesai.
     *
     * @return $this
     */
    public function tandaiSelesai()
    {
        $this->update([
            'status' => 'completed',
            'corrected_at' => now(),
        ]);
        return $this;
    }

    /**
     * Alias: markAsCompleted() untuk kompatibilitas.
     *
     * @return $this
     */
    public function markAsCompleted()
    {
        return $this->tandaiSelesai();
    }

    /**
     * Tandai koreksi sebagai sedang diproses.
     *
     * @return $this
     */
    public function tandaiSedangDiproses()
    {
        $this->update(['status' => 'in_progress']);
        return $this;
    }

    /**
     * Alias: markAsInProgress() untuk kompatibilitas.
     *
     * @return $this
     */
    public function markAsInProgress()
    {
        return $this->tandaiSedangDiproses();
    }

    /**
     * Mendapatkan label status.
     *
     * @return string Label status
     */
    public function ambilLabelStatus()
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
     * Alias: getStatusLabel() untuk kompatibilitas.
     *
     * @return string
     */
    public function getStatusLabel()
    {
        return $this->ambilLabelStatus();
    }

    /**
     * Mendapatkan warna badge status.
     *
     * @return string Warna badge
     */
    public function ambilWarnaBadgeStatus()
    {
        $warna = [
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'rejected' => 'danger',
        ];
        return $warna[$this->status] ?? 'secondary';
    }

    /**
     * Alias: getStatusBadgeColor() untuk kompatibilitas.
     *
     * @return string
     */
    public function getStatusBadgeColor()
    {
        return $this->ambilWarnaBadgeStatus();
    }

    /**
     * Mendapatkan jumlah hari tersisa sampai tenggat.
     *
     * @return int|null Jumlah hari tersisa
     */
    public function ambilHariTersisa()
    {
        if ($this->due_date) {
            return $this->due_date->diffInDays(now());
        }
        return null;
    }

    /**
     * Alias: getDaysRemaining() untuk kompatibilitas.
     *
     * @return int|null
     */
    public function getDaysRemaining()
    {
        return $this->ambilHariTersisa();
    }
}
