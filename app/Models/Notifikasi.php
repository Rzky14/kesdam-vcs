<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Notifikasi
 * 
 * Merepresentasikan notifikasi dalam sistem.
 * Menggunakan tabel 'notifications' yang ada di database.
 */
class Notifikasi extends Model
{
    use HasFactory;

    /**
     * Tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'action_url',
        'related_model_type',
        'related_model_id',
        'read_at',
    ];

    /**
     * Atribut yang harus di-cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Konstanta tipe notifikasi.
     */
    public const TYPE_SCHEDULE_REMINDER = 'schedule_reminder';
    public const TYPE_APPROVAL_REQUEST = 'approval_request';
    public const TYPE_DOCUMENT_APPROVED = 'document_approved';
    public const TYPE_DOCUMENT_REJECTED = 'document_rejected';
    public const TYPE_CORRECTION_REQUESTED = 'correction_requested';
    public const TYPE_DOCUMENT_STATUS_CHANGED = 'document_status_changed';

    // ==========================================
    // RELASI
    // ==========================================

    /**
     * Dapatkan pengguna yang menerima notifikasi ini.
     */
    public function penerima()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias: user() untuk kompatibilitas.
     */
    public function user()
    {
        return $this->penerima();
    }

    /**
     * Relasi polimorfik ke model terkait.
     */
    public function relatedModel()
    {
        return $this->morphTo('related_model', 'related_model_type', 'related_model_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope query untuk hanya menampilkan notifikasi belum dibaca.
     */
    public function scopeBelumDibaca($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Alias: scopeUnread() untuk kompatibilitas.
     */
    public function scopeUnread($query)
    {
        return $this->scopeBelumDibaca($query);
    }

    /**
     * Scope query untuk hanya menampilkan notifikasi sudah dibaca.
     */
    public function scopeSudahDibaca($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Alias: scopeRead() untuk kompatibilitas.
     */
    public function scopeRead($query)
    {
        return $this->scopeSudahDibaca($query);
    }

    /**
     * Scope query untuk filter berdasarkan jenis.
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
     * Scope query untuk filter berdasarkan penerima.
     */
    public function scopeBerdasarkanPenerima($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Alias: scopeForUser() untuk kompatibilitas.
     */
    public function scopeForUser($query, $userId)
    {
        return $this->scopeBerdasarkanPenerima($query, $userId);
    }

    // ==========================================
    // ACCESSORS (untuk kompatibilitas)
    // ==========================================

    /**
     * Aksesor untuk kompatibilitas: idPenerima.
     */
    public function getIdPenerimaAttribute()
    {
        return $this->user_id;
    }

    /**
     * Aksesor untuk kompatibilitas: jenis.
     */
    public function getJenisAttribute()
    {
        return $this->type;
    }

    /**
     * Aksesor untuk kompatibilitas: pesan.
     */
    public function getPesanAttribute()
    {
        return $this->message;
    }

    /**
     * Aksesor untuk kompatibilitas: tanggalWaktu.
     */
    public function getTanggalWaktuAttribute()
    {
        return $this->created_at;
    }

    /**
     * Aksesor untuk kompatibilitas: statusBaca.
     */
    public function getStatusBacaAttribute()
    {
        return $this->read_at !== null;
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Tandai notifikasi sebagai sudah dibaca.
     */
    public function tandaiBaca()
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Alias: markAsRead() untuk kompatibilitas.
     */
    public function markAsRead()
    {
        return $this->tandaiBaca();
    }

    /**
     * Tandai notifikasi sebagai belum dibaca.
     */
    public function tandaiBelumBaca()
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Alias: markAsUnread() untuk kompatibilitas.
     */
    public function markAsUnread()
    {
        return $this->tandaiBelumBaca();
    }

    /**
     * Cek apakah notifikasi sudah dibaca.
     */
    public function isBaca(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Alias: isRead() untuk kompatibilitas.
     */
    public function isRead(): bool
    {
        return $this->isBaca();
    }
}
