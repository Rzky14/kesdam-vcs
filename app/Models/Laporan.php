<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Laporan
 * 
 * Merepresentasikan laporan dalam sistem.
 * Menggunakan tabel 'reports' yang ada di database.
 */
class Laporan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'reports';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'period_start',
        'period_end',
        'generated_by',
        'data',
        'status',
    ];

    /**
     * Atribut yang harus di-cast.
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

    /**
     * Konstanta tipe laporan.
     */
    public const TYPE_SCHEDULE = 'schedule';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_EFFECTIVENESS = 'effectiveness';

    /**
     * Konstanta status laporan.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    // ==========================================
    // RELASI
    // ==========================================

    /**
     * Dapatkan pengguna yang membuat laporan ini.
     */
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Alias: creator() untuk kompatibilitas.
     */
    public function creator()
    {
        return $this->pembuat();
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope query untuk filter berdasarkan jenis laporan.
     */
    public function scopeBerdasarkanJenis($query, $jenis)
    {
        return $query->where('type', $jenis);
    }

    /**
     * Alias: scopeByType() untuk kompatibilitas.
     */
    public function scopeByType($query, $type)
    {
        return $this->scopeBerdasarkanJenis($query, $type);
    }

    /**
     * Scope query untuk filter berdasarkan rentang tanggal.
     */
    public function scopeAntaraTanggal($query, $mulai, $selesai)
    {
        return $query->where('period_start', '>=', $mulai)
                    ->where('period_end', '<=', $selesai);
    }

    /**
     * Alias: scopeBetweenDates() untuk kompatibilitas.
     */
    public function scopeBetweenDates($query, $start, $end)
    {
        return $this->scopeAntaraTanggal($query, $start, $end);
    }

    /**
     * Scope query untuk filter berdasarkan status.
     */
    public function scopeBerdasarkanStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: laporan yang sudah selesai.
     */
    public function scopeSelesai($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // ==========================================
    // ACCESSORS (untuk kompatibilitas)
    // ==========================================

    /**
     * Dapatkan rentang tanggal yang diformat.
     */
    public function getRentangTanggal(): string
    {
        if (!$this->period_start || !$this->period_end) {
            return '-';
        }
        return $this->period_start->format('d M Y') . ' - ' . 
               $this->period_end->format('d M Y');
    }

    /**
     * Aksesor untuk kompatibilitas: jenisLaporan.
     */
    public function getJenisLaporanAttribute()
    {
        return $this->type;
    }

    /**
     * Aksesor untuk kompatibilitas: tanggalMulai.
     */
    public function getTanggalMulaiAttribute()
    {
        return $this->period_start;
    }

    /**
     * Aksesor untuk kompatibilitas: tanggalSelesai.
     */
    public function getTanggalSelesaiAttribute()
    {
        return $this->period_end;
    }

    /**
     * Aksesor untuk kompatibilitas: tanggalDibuat.
     */
    public function getTanggalDibuatAttribute()
    {
        return $this->created_at;
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Cek apakah laporan sudah selesai.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Tandai laporan sebagai selesai.
     */
    public function markAsCompleted(): bool
    {
        return $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Tandai laporan sebagai gagal.
     */
    public function markAsFailed(): bool
    {
        return $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Ekspor laporan ke format tertentu.
     */
    public function eksporKe(string $format): string
    {
        // Akan diimplementasikan di service class
        // Mengikuti Single Responsibility Principle
        return '';
    }
}



