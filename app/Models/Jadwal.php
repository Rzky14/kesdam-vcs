<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Jadwal
 * 
 * Kelas dasar untuk semua tipe jadwal (Dukkes, Jaga, Kegiatan Satuan).
 * Sesuai dengan UML Class Diagram.
 */
class Jadwal extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'schedules';

    /**
     * Primary key untuk model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Menunjukkan apakah ID auto-increment.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Tipe dari ID auto-increment.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Aktifkan timestamp default Laravel.
     *
     * @var bool
     */
    public $timestamps = true;

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
     * Atribut yang harus di-cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'personnel' => 'array',
    ];

    /**
     * Method boot untuk setup model events.
     */
    protected static function boot()
    {
        parent::boot();
    }

    /**
     * Override route key name untuk route model binding.
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Dapatkan pengguna yang membuat jadwal ini.
     */
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Dapatkan pengguna yang terakhir mengupdate jadwal ini.
     */
    public function pengubah()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    /**
     * Scope query untuk filter berdasarkan jenis.
     */
    public function scopeBerdasarkanJenis($query, $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    /**
     * Scope query untuk filter berdasarkan status.
     */
    public function scopeBerdasarkanStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query untuk filter berdasarkan rentang tanggal.
     */
    public function scopeAntaraTanggal($query, $mulai, $selesai)
    {
        return $query->whereBetween('tanggalMulai', [$mulai, $selesai])
                    ->orWhereBetween('tanggalSelesai', [$mulai, $selesai]);
    }

    /**
     * Scope query untuk hanya menampilkan jadwal aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Cek apakah jadwal aktif.
     */
    public function isAktif(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Cek apakah jadwal selesai.
     */
    public function isSelesai(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Dapatkan rentang tanggal yang diformat.
     */
    public function getRentangTanggal(): string
    {
        return $this->tanggalMulai->format('d M Y') . ' - ' . 
               $this->tanggalSelesai->format('d M Y');
    }
}
