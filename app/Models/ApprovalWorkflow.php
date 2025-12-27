<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model AlurPersetujuan (ApprovalWorkflow)
 * 
 * Merepresentasikan alur kerja persetujuan untuk dokumen.
 * Menyimpan rantai persetujuan dan konfigurasi alur kerja.
 */
class ApprovalWorkflow extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atribut yang dapat diisi massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'document_type',
        'classification',
        'approval_chain',
        'is_active',
        'priority',
    ];

    /**
     * Cast atribut ke tipe yang sesuai.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approval_chain' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    // ==========================================
    // SCOPE QUERIES
    // ==========================================

    /**
     * Scope untuk mendapatkan alur kerja yang aktif.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope filter berdasarkan tipe dokumen.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tipeDokumen Tipe dokumen yang dicari
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDocumentType($query, $tipeDokumen)
    {
        return $query->where('document_type', $tipeDokumen)
                     ->orWhereNull('document_type');
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
        return $query->where('classification', $klasifikasi)
                     ->orWhereNull('classification');
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Mendapatkan detail rantai persetujuan dengan informasi peran.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function ambilDetailRantaiPersetujuan()
    {
        return Role::whereIn('id', $this->approval_chain)->get();
    }

    /**
     * Alias: getApprovalChainDetails() untuk kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getApprovalChainDetails()
    {
        return $this->ambilDetailRantaiPersetujuan();
    }

    /**
     * Mendapatkan peran penyetuju selanjutnya berdasarkan level saat ini.
     *
     * @param int $levelSaatIni Level persetujuan saat ini
     * @return int|null ID peran penyetuju selanjutnya
     */
    public function ambilPeranPenyetujuSelanjutnya($levelSaatIni = 0)
    {
        if (isset($this->approval_chain[$levelSaatIni])) {
            return $this->approval_chain[$levelSaatIni];
        }
        return null;
    }

    /**
     * Alias: getNextApproverRole() untuk kompatibilitas.
     *
     * @param int $currentLevel Level persetujuan saat ini
     * @return int|null
     */
    public function getNextApproverRole($currentLevel = 0)
    {
        return $this->ambilPeranPenyetujuSelanjutnya($currentLevel);
    }

    /**
     * Memeriksa apakah semua persetujuan sudah selesai.
     *
     * @param int $levelSaatIni Level persetujuan saat ini
     * @return bool True jika sudah selesai
     */
    public function apakahPersetujuanSelesai($levelSaatIni)
    {
        return $levelSaatIni >= count($this->approval_chain);
    }

    /**
     * Alias: isApprovalComplete() untuk kompatibilitas.
     *
     * @param int $currentLevel Level persetujuan saat ini
     * @return bool
     */
    public function isApprovalComplete($currentLevel)
    {
        return $this->apakahPersetujuanSelesai($currentLevel);
    }

    /**
     * Mendapatkan total level persetujuan yang diperlukan.
     *
     * @return int Jumlah level persetujuan
     */
    public function ambilTotalLevelPersetujuan()
    {
        return count($this->approval_chain);
    }

    /**
     * Alias: getTotalApprovalLevels() untuk kompatibilitas.
     *
     * @return int
     */
    public function getTotalApprovalLevels()
    {
        return $this->ambilTotalLevelPersetujuan();
    }
}



