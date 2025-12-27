<?php

namespace App\Models;

/**
 * Model SuratMasuk
 * 
 * Merepresentasikan surat masuk dalam sistem.
 * Menggunakan Single Table Inheritance (STI) dari model Dokumen.
 * Sesuai dengan UML Class Diagram.
 * 
 * @property string $pengirim Pengirim surat
 * @property \Carbon\Carbon $tanggal_diterima Tanggal surat diterima
 * @property string $no_surat_pengirim Nomor surat dari pengirim
 */
class SuratMasuk extends Dokumen
{
    /**
     * Tipe dokumen untuk model ini.
     *
     * @var string
     */
    protected static string $tipeDokumen = 'masuk';

    /**
     * Method boot untuk mengatur nilai default.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Otomatis set type ke 'masuk' saat membuat surat masuk
        static::creating(function ($model) {
            $model->type = 'masuk';
        });

        // Filter hanya surat masuk
        static::addGlobalScope('surat_masuk', function ($query) {
            $query->where('type', 'masuk');
        });
    }

    // ==========================================
    // METHODS SESUAI UML CLASS DIAGRAM
    // ==========================================

    /**
     * Membaca konten surat.
     * Sesuai UML: +bacaKonten(): void
     *
     * @return string
     */
    public function bacaKonten(): string
    {
        if ($this->is_encrypted && $this->description) {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($this->description);
            } catch (\Exception $e) {
                return $this->description;
            }
        }
        return $this->description ?? '';
    }

    /**
     * Mendistribusikan surat ke penerima.
     * Sesuai UML: +distribusikan(): void
     *
     * @param array $penerima Array ID pengguna penerima
     * @return void
     */
    public function distribusikan(array $penerima): void
    {
        // Implementasi distribusi surat ke penerima
        // Bisa mengirim notifikasi ke setiap penerima
        foreach ($penerima as $userId) {
            Notifikasi::create([
                'user_id' => $userId,
                'type' => Notifikasi::TYPE_DOCUMENT_STATUS_CHANGED,
                'title' => 'Surat Masuk Baru',
                'message' => "Anda menerima surat masuk: {$this->subject}",
                'related_model_type' => self::class,
                'related_model_id' => $this->id,
            ]);
        }
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope untuk mendapatkan surat yang belum dibaca.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBelumDibaca($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope untuk mendapatkan surat berdasarkan pengirim.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $pengirim
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDariPengirim($query, string $pengirim)
    {
        return $query->where('sender', 'like', "%{$pengirim}%");
    }

    /**
     * Scope untuk mendapatkan surat yang diterima pada tanggal tertentu.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon|string $tanggal
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDiterimaPada($query, $tanggal)
    {
        return $query->whereDate('date', $tanggal);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Mendapatkan pengirim surat.
     *
     * @return string
     */
    public function getPengirim(): string
    {
        return $this->sender ?? '';
    }

    /**
     * Mendapatkan tanggal diterima.
     *
     * @return \Carbon\Carbon|null
     */
    public function getTanggalDiterima()
    {
        return $this->date;
    }

    /**
     * Mendapatkan nomor surat pengirim.
     *
     * @return string
     */
    public function getNoSuratPengirim(): string
    {
        return $this->number ?? '';
    }

    /**
     * Cek apakah surat sudah didistribusikan.
     *
     * @return bool
     */
    public function sudahDidistribusikan(): bool
    {
        return $this->status !== 'draft';
    }
}
