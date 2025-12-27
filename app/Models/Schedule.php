<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Schedule
 *
 * Representasi jadwal untuk:
 * - Jadwal Dukkes (Jadwal Dukungan Kesehatan)
 * - Jadwal Jaga (Jadwal Tugas Jaga)
 * - Jadwal Kegiatan Satuan (Jadwal Kegiatan Unit)
 */
class Schedule extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'title',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'personnel',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * Atribut yang harus di-cast ke tipe tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'personnel' => 'array',
    ];

    /**
     * Jenis-jenis jadwal
     */
    const TYPE_DUKKES = 'dukkes';
    const TYPE_JAGA = 'jaga';
    const TYPE_KEGIATAN_SATUAN = 'kegiatan_satuan';

    /**
     * Status-status jadwal
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Mendapatkan pengguna yang membuat jadwal ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mendapatkan pengguna yang terakhir memperbarui jadwal ini.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Mendapatkan personel yang ditugaskan ke jadwal ini.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPersonnelUsers()
    {
        if (empty($this->personnel)) {
            return collect();
        }

        return User::whereIn('id', $this->personnel)->get();
    }

    /**
     * Cek apakah jadwal aktif.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Cek apakah jadwal masih draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Cek apakah jadwal sudah selesai.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Cek apakah jadwal dibatalkan.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Scope untuk filter berdasarkan tipe.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope untuk filter berdasarkan status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk mendapatkan jadwal yang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope untuk filter jadwal dalam rentang tanggal.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Scope untuk pencarian jadwal.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        });
    }

    /**
     * Mendapatkan label tipe yang terformat.
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_DUKKES => 'Jadwal Dukkes',
            self::TYPE_JAGA => 'Jadwal Jaga',
            self::TYPE_KEGIATAN_SATUAN => 'Jadwal Kegiatan Satuan',
            default => $this->type,
        };
    }

    /**
     * Mendapatkan label status yang terformat.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => $this->status,
        };
    }

    /**
     * Mendapatkan warna badge status.
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_COMPLETED => 'info',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }
}



