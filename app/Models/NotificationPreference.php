<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PreferensiNotifikasi (NotificationPreference)
 * 
 * Menyimpan preferensi notifikasi pengguna untuk berbagai tipe notifikasi.
 */
class NotificationPreference extends Model
{
    /**
     * Nama tabel yang digunakan oleh model.
     *
     * @var string
     */
    protected $table = 'user_notification_preferences';

    /**
     * Atribut yang dapat diisi massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'notification_type',
        'in_app_enabled',
        'email_enabled',
    ];

    /**
     * Cast atribut ke tipe yang sesuai.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'in_app_enabled' => 'boolean',
        'email_enabled' => 'boolean',
    ];

    /**
     * Tipe-tipe notifikasi yang tersedia dalam sistem.
     */
    public const TIPE = [
        'schedule_reminder' => 'Pengingat Jadwal',
        'approval_request' => 'Permintaan Persetujuan',
        'document_approved' => 'Dokumen Disetujui',
        'document_rejected' => 'Dokumen Ditolak',
        'correction_requested' => 'Koreksi Diminta',
        'document_status_changed' => 'Status Dokumen Berubah',
    ];

    /**
     * Alias: Konstanta TYPES untuk kompatibilitas.
     */
    public const TYPES = self::TIPE;

    // ==========================================
    // RELASI
    // ==========================================

    /**
     * Mendapatkan pengguna pemilik preferensi ini.
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

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Memeriksa apakah notifikasi dalam aplikasi diaktifkan.
     *
     * @return bool True jika diaktifkan
     */
    public function apakahNotifikasiAplikasiAktif(): bool
    {
        return $this->in_app_enabled;
    }

    /**
     * Alias: isInAppEnabled() untuk kompatibilitas.
     *
     * @return bool
     */
    public function isInAppEnabled(): bool
    {
        return $this->apakahNotifikasiAplikasiAktif();
    }

    /**
     * Memeriksa apakah notifikasi email diaktifkan.
     *
     * @return bool True jika diaktifkan
     */
    public function apakahNotifikasiEmailAktif(): bool
    {
        return $this->email_enabled;
    }

    /**
     * Alias: isEmailEnabled() untuk kompatibilitas.
     *
     * @return bool
     */
    public function isEmailEnabled(): bool
    {
        return $this->apakahNotifikasiEmailAktif();
    }

    /**
     * Mendapatkan nama tipe notifikasi yang mudah dibaca.
     *
     * @return string Nama tipe notifikasi
     */
    public function getNamaTipeAttribute(): string
    {
        return self::TIPE[$this->notification_type] ?? $this->notification_type;
    }

    /**
     * Alias: getTypeNameAttribute() untuk kompatibilitas.
     *
     * @return string
     */
    public function getTypeNameAttribute(): string
    {
        return $this->getNamaTipeAttribute();
    }
}
