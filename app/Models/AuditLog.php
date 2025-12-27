<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

/**
 * Model LogAudit (AuditLog)
 * 
 * Melacak semua perubahan dan peristiwa penting dalam sistem untuk keperluan jejak audit.
 */
class AuditLog extends Model
{
    /**
     * Atribut yang dapat diisi massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
    ];

    /**
     * Cast atribut ke tipe yang sesuai.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELASI
    // ==========================================

    /**
     * Mendapatkan pengguna yang melakukan aksi.
     *
     * @return BelongsTo
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias: user() untuk kompatibilitas.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->pengguna();
    }

    /**
     * Mendapatkan model yang diaudit (relasi polimorfik).
     *
     * @return MorphTo
     */
    public function yangDiaudit(): MorphTo
    {
        return $this->morphTo('auditable');
    }

    /**
     * Alias: auditable() untuk kompatibilitas.
     *
     * @return MorphTo
     */
    public function auditable(): MorphTo
    {
        return $this->yangDiaudit();
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Membuat entri log audit baru.
     *
     * @param string $peristiwa Nama peristiwa yang dicatat
     * @param Model|null $model Model yang diaudit
     * @param array $nilaiLama Nilai sebelum perubahan
     * @param array $nilaiBaru Nilai setelah perubahan
     * @param string|null $deskripsi Deskripsi tambahan
     * @return static
     */
    public static function catat(
        string $peristiwa,
        ?Model $model = null,
        array $nilaiLama = [],
        array $nilaiBaru = [],
        ?string $deskripsi = null
    ): static {
        return static::create([
            'user_id' => Auth::id(),
            'event' => $peristiwa,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->id,
            'old_values' => $nilaiLama,
            'new_values' => $nilaiBaru,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $deskripsi,
        ]);
    }

    /**
     * Alias: log() untuk kompatibilitas.
     *
     * @param string $event
     * @param Model|null $model
     * @param array $oldValues
     * @param array $newValues
     * @param string|null $description
     * @return static
     */
    public static function log(
        string $event,
        ?Model $model = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null
    ): static {
        return static::catat($event, $model, $oldValues, $newValues, $description);
    }
}



