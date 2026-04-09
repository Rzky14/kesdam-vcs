<?php

namespace App\Models;

use Database\Factories\ArchiveFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Arsip
 * 
 * Merepresentasikan arsip yang menyimpan berbagai entitas (dokumen, jadwal, laporan) secara polimorfik.
 * Mendukung penandaan, retensi, dan indeksasi.
 */
class Arsip extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create the model factory instance.
     */
    protected static function newFactory()
    {
        return ArchiveFactory::new();
    }

    /**
     * Nama tabel yang digunakan oleh model.
     *
     * @var string
     */
    protected $table = 'archives';

    /**
     * Atribut yang dapat diisi massal.
     *
     * @var array<int, string>
     */
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

    /**
     * Cast atribut ke tipe yang sesuai.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'archive_date' => 'datetime',
        'retention_until' => 'datetime',
        'tags' => 'array',
        'is_indexed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==========================================
    // RELASI
    // ==========================================

    /**
     * Mendapatkan pengguna yang melakukan pengarsipan.
     */
    public function pengarsip(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Alias: archivist() untuk kompatibilitas.
     */
    public function archivist(): BelongsTo
    {
        return $this->pengarsip();
    }

    /**
     * Relasi polimorfik ke entitas yang diarsipkan.
     */
    public function arsipkan(): MorphTo
    {
        return $this->morphTo('archiveable', 'archiveable_type', 'archiveable_id');
    }

    /**
     * Alias: archiveable() untuk kompatibilitas.
     */
    public function archiveable(): MorphTo
    {
        return $this->arsipkan();
    }

    // ==========================================
    // SCOPE
    // ==========================================

    /**
     * Scope: filter berdasarkan kategori arsip.
     */
    public function scopeBerdasarkanKategori($query, string $kategori)
    {
        return $query->where('category', $kategori);
    }

    /**
     * Alias: scopeByCategory() untuk kompatibilitas.
     */
    public function scopeByCategory($query, string $category)
    {
        return $this->scopeBerdasarkanKategori($query, $category);
    }

    /**
     * Scope: hanya arsip yang terindeks.
     */
    public function scopeTerindeks($query)
    {
        return $query->where('is_indexed', true);
    }

    /**
     * Alias: scopeIndexed() untuk kompatibilitas.
     */
    public function scopeIndexed($query)
    {
        return $this->scopeTerindeks($query);
    }

    /**
     * Scope: arsip dengan retensi mendekati kadaluarsa.
     */
    public function scopeRetensiHampirKadaluarsa($query, int $hari = 30)
    {
        return $query->whereBetween('retention_until', [
            now(),
            now()->addDays($hari)
        ]);
    }

    /**
     * Alias: scopeRetentionExpiring() untuk kompatibilitas.
     */
    public function scopeRetentionExpiring($query, int $days = 30)
    {
        return $this->scopeRetensiHampirKadaluarsa($query, $days);
    }

    /**
     * Scope: pencarian arsip berdasarkan nama, deskripsi, atau tag.
     */
    public function scopeCari($query, string $kataKunci)
    {
        return $query->where('name', 'like', "%{$kataKunci}%")
            ->orWhere('description', 'like', "%{$kataKunci}%")
            ->orWhereJsonContains('tags', $kataKunci);
    }

    /**
     * Alias: scopeSearch() untuk kompatibilitas.
     */
    public function scopeSearch($query, string $term)
    {
        return $this->scopeCari($query, $term);
    }

    // ==========================================
    // HELPER
    // ==========================================

    /**
     * Tandai arsip sebagai sudah terindeks.
     */
    public function tandaiSebagaiTerindeks(): void
    {
        $this->update(['is_indexed' => true]);
    }

    /**
     * Alias: markAsIndexed() untuk kompatibilitas.
     */
    public function markAsIndexed(): void
    {
        $this->tandaiSebagaiTerindeks();
    }

    /**
     * Mendapatkan label kategori dalam Bahasa Indonesia.
     */
    public function ambilLabelKategori(): string
    {
        return match($this->category) {
            'schedule' => 'Jadwal',
            'document' => 'Dokumen',
            'report' => 'Laporan',
            default => $this->category,
        };
    }

    /**
     * Alias: getCategoryLabel() untuk kompatibilitas.
     */
    public function getCategoryLabel(): string
    {
        return $this->ambilLabelKategori();
    }
}



