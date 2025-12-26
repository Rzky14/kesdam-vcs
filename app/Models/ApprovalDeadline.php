<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model BatasWaktuPersetujuan (ApprovalDeadline)
 * 
 * Merepresentasikan batas waktu persetujuan untuk dokumen.
 * Menyimpan informasi deadline dan status pemenuhannya.
 */
class ApprovalDeadline extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'approval_level',
        'document_type',
        'classification',
        'days_allowed',
        'deadline_at',
        'status',
    ];

    /**
     * Cast atribut ke tipe yang sesuai.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'deadline_at' => 'datetime',
        'days_allowed' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELASI
    // ==========================================

    /**
     * Mendapatkan dokumen yang terkait dengan batas waktu persetujuan ini.
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

    // ==========================================
    // SCOPE QUERIES
    // ==========================================

    /**
     * Scope untuk mendapatkan deadline yang aktif.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope untuk deadline yang sudah terpenuhi.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMet($query)
    {
        return $query->where('status', 'met');
    }

    /**
     * Scope untuk deadline yang terlewat.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMissed($query)
    {
        return $query->where('status', 'missed');
    }

    /**
     * Scope untuk deadline yang dihapuskan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWaived($query)
    {
        return $query->where('status', 'waived');
    }

    /**
     * Scope untuk deadline yang sudah lewat.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline_at', '<', now())
                     ->where('status', 'active');
    }

    /**
     * Scope untuk deadline yang akan datang.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hari Jumlah hari ke depan
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpcoming($query, $hari = 3)
    {
        return $query->whereBetween('deadline_at', [now(), now()->addDays($hari)])
                     ->where('status', 'active');
    }

    /**
     * Scope filter berdasarkan tipe dokumen.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tipeDokumen Tipe dokumen
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDocumentType($query, $tipeDokumen)
    {
        return $query->where('document_type', $tipeDokumen);
    }

    /**
     * Scope filter berdasarkan klasifikasi.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $klasifikasi Klasifikasi dokumen
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByClassification($query, $klasifikasi)
    {
        return $query->where('classification', $klasifikasi);
    }

    /**
     * Scope filter berdasarkan level persetujuan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $level Level persetujuan
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByApprovalLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Memeriksa apakah deadline sudah terlewat.
     *
     * @return bool True jika terlewat
     */
    public function apakahTerlewat()
    {
        return $this->status === 'active' && $this->deadline_at->isPast();
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
     * Memeriksa apakah deadline akan segera berakhir.
     *
     * @param int $hari Jumlah hari toleransi
     * @return bool True jika akan segera berakhir
     */
    public function apakahAkanSegera($hari = 2)
    {
        if ($this->status === 'active') {
            $hariTersisa = $this->deadline_at->diffInDays(now());
            return $hariTersisa <= $hari && !$this->apakahTerlewat();
        }
        return false;
    }

    /**
     * Alias: isComingSoon() untuk kompatibilitas.
     *
     * @param int $days Jumlah hari
     * @return bool
     */
    public function isComingSoon($days = 2)
    {
        return $this->apakahAkanSegera($days);
    }

    /**
     * Tandai deadline sebagai terpenuhi.
     *
     * @return $this
     */
    public function tandaiSebagaiTerpenuhi()
    {
        $this->update(['status' => 'met']);
        return $this;
    }

    /**
     * Alias: markAsMet() untuk kompatibilitas.
     *
     * @return $this
     */
    public function markAsMet()
    {
        return $this->tandaiSebagaiTerpenuhi();
    }

    /**
     * Tandai deadline sebagai terlewat.
     *
     * @return $this
     */
    public function tandaiSebagaiTerlewat()
    {
        $this->update(['status' => 'missed']);
        return $this;
    }

    /**
     * Alias: markAsMissed() untuk kompatibilitas.
     *
     * @return $this
     */
    public function markAsMissed()
    {
        return $this->tandaiSebagaiTerlewat();
    }

    /**
     * Hapuskan deadline (waive).
     *
     * @return $this
     */
    public function hapuskan()
    {
        $this->update(['status' => 'waived']);
        return $this;
    }

    /**
     * Alias: waive() untuk kompatibilitas.
     *
     * @return $this
     */
    public function waive()
    {
        return $this->hapuskan();
    }

    /**
     * Mendapatkan jumlah hari tersisa sampai deadline.
     *
     * @return int|null Jumlah hari tersisa
     */
    public function ambilHariTersisa()
    {
        if ($this->status === 'active') {
            return $this->deadline_at->diffInDays(now());
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

    /**
     * Mendapatkan jumlah jam tersisa sampai deadline.
     *
     * @return int|null Jumlah jam tersisa
     */
    public function ambilJamTersisa()
    {
        if ($this->status === 'active') {
            return $this->deadline_at->diffInHours(now());
        }
        return null;
    }

    /**
     * Alias: getHoursRemaining() untuk kompatibilitas.
     *
     * @return int|null
     */
    public function getHoursRemaining()
    {
        return $this->ambilJamTersisa();
    }

    /**
     * Mendapatkan label status.
     *
     * @return string Label status
     */
    public function ambilLabelStatus()
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
            'active' => 'primary',
            'met' => 'success',
            'missed' => 'danger',
            'waived' => 'secondary',
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
}
