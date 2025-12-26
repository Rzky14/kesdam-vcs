<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Report (Laporan Sistem)
 * 
 * Merepresentasikan laporan yang dihasilkan oleh sistem.
 * Dapat berupa laporan jadwal atau dokumen.
 */
class Report extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atribut yang dapat diisi massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type', // 'schedule', 'document'
        'reportable_type',
        'reportable_id',
        'period_start',
        'period_end',
        'generated_by',
        'data',
        'status', // 'draft', 'in_progress', 'completed', 'failed'
    ];

    /**
     * Cast atribut ke tipe yang sesuai.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==========================================
    // RELASI
    // ==========================================

    /**
     * Mendapatkan pengguna yang membuat laporan.
     *
     * @return BelongsTo
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Alias: generatedBy() untuk kompatibilitas.
     *
     * @return BelongsTo
     */
    public function generatedBy(): BelongsTo
    {
        return $this->dibuatOleh();
    }

    /**
     * Relasi polimorfik ke model yang dilaporkan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function yangDilaporkan()
    {
        return $this->morphTo('reportable');
    }

    /**
     * Alias: reportable() untuk kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function reportable()
    {
        return $this->yangDilaporkan();
    }

    /**
     * Mendapatkan pembuat laporan (legacy).
     *
     * @return BelongsTo
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias: creator() untuk kompatibilitas.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->pembuat();
    }

    // ==========================================
    // SCOPE QUERIES
    // ==========================================

    /**
     * Scope untuk mendapatkan laporan berdasarkan tipe.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tipe Tipe laporan
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $tipe)
    {
        return $query->where('type', $tipe);
    }

    /**
     * Scope untuk mendapatkan laporan dalam rentang tanggal.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $mulai Tanggal mulai
     * @param mixed $selesai Tanggal selesai
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $mulai, $selesai)
    {
        return $query->whereBetween('period_start', [$mulai, $selesai]);
    }

    /**
     * Scope untuk mendapatkan laporan terbaru.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hari Jumlah hari kebelakang
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $hari = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($hari))
            ->orderBy('created_at', 'desc');
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Mendapatkan label tipe laporan.
     *
     * @return string Label tipe
     */
    public function ambilLabelTipe(): string
    {
        return match($this->type) {
            'schedule' => 'Laporan Jadwal',
            'document' => 'Laporan Dokumen',
            default => $this->type,
        };
    }

    /**
     * Alias: getTypeLabel() untuk kompatibilitas.
     *
     * @return string
     */
    public function getTypeLabel(): string
    {
        return $this->ambilLabelTipe();
    }
}
