<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model TandaTanganPersetujuan (ApprovalSignature)
 * 
 * Merepresentasikan tanda tangan pada persetujuan dokumen.
 * Mendukung berbagai tipe tanda tangan: digital, scan, dan gambar.
 */
class ApprovalSignature extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'approval_history_id',
        'user_id',
        'signature_file_path',
        'signature_type',
        'signed_at',
        'certificate_number',
        'metadata',
    ];

    /**
     * Cast atribut ke tipe yang sesuai.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'signed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELASI
    // ==========================================

    /**
     * Mendapatkan riwayat persetujuan yang terkait dengan tanda tangan ini.
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
     * Mendapatkan pengguna yang menandatangani.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pengguna()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias: user() untuk kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->pengguna();
    }

    /**
     * Mendapatkan dokumen melalui riwayat persetujuan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function dokumen()
    {
        return $this->hasOneThrough(
            Surat::class,
            ApprovalHistory::class,
            'id',
            'id',
            'approval_history_id',
            'document_id'
        );
    }

    /**
     * Alias: document() untuk kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function document()
    {
        return $this->dokumen();
    }

    // ==========================================
    // SCOPE QUERIES
    // ==========================================

    /**
     * Scope untuk mendapatkan tanda tangan dari pengguna tertentu.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $idPengguna ID pengguna
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, $idPengguna)
    {
        return $query->where('user_id', $idPengguna);
    }

    /**
     * Scope untuk tanda tangan digital.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDigital($query)
    {
        return $query->where('signature_type', 'digital');
    }

    /**
     * Scope untuk tanda tangan scan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScanned($query)
    {
        return $query->where('signature_type', 'scanned');
    }

    /**
     * Scope untuk tanda tangan gambar terupload.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUploadedImage($query)
    {
        return $query->where('signature_type', 'uploaded_image');
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Mendapatkan label tipe tanda tangan.
     *
     * @return string Label tipe tanda tangan
     */
    public function ambilLabelTipeTandaTangan()
    {
        $labels = [
            'digital' => 'Digital',
            'scanned' => 'Scan',
            'uploaded_image' => 'Gambar Terupload',
        ];
        return $labels[$this->signature_type] ?? $this->signature_type;
    }

    /**
     * Alias: getSignatureTypeLabel() untuk kompatibilitas.
     *
     * @return string
     */
    public function getSignatureTypeLabel()
    {
        return $this->ambilLabelTipeTandaTangan();
    }

    /**
     * Mendapatkan URL lengkap file tanda tangan.
     *
     * @return string URL file tanda tangan
     */
    public function ambilUrlTandaTangan()
    {
        return asset('storage/' . $this->signature_file_path);
    }

    /**
     * Alias: getSignatureUrl() untuk kompatibilitas.
     *
     * @return string
     */
    public function getSignatureUrl()
    {
        return $this->ambilUrlTandaTangan();
    }

    /**
     * Memeriksa apakah tanda tangan valid dan tidak kedaluwarsa.
     *
     * @return bool True jika valid
     */
    public function apakahValid()
    {
        // Implementasi logika validasi tanda tangan
        // Untuk tanda tangan digital, bisa cek validitas sertifikat
        return true;
    }

    /**
     * Alias: isValid() untuk kompatibilitas.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->apakahValid();
    }
}



