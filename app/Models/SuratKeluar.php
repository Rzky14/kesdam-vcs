<?php

namespace App\Models;

/**
 * Model SuratKeluar
 * 
 * Merepresentasikan surat keluar dalam sistem.
 * Menggunakan Single Table Inheritance (STI) dari model Dokumen.
 * Sesuai dengan UML Class Diagram.
 * 
 * @property string $tujuan Tujuan pengiriman surat
 * @property string $tembusan Tembusan surat
 * @property \Carbon\Carbon $tanggal_dikirim Tanggal surat dikirim
 * @property string $no_surat_internal Nomor surat internal
 */
class SuratKeluar extends Dokumen
{
    /**
     * Tipe dokumen untuk model ini.
     *
     * @var string
     */
    protected static string $tipeDokumen = 'keluar';

    /**
     * Method boot untuk mengatur nilai default.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Otomatis set type ke 'keluar' saat membuat surat keluar
        static::creating(function ($model) {
            $model->type = 'keluar';
        });

        // Filter hanya surat keluar
        static::addGlobalScope('surat_keluar', function ($query) {
            $query->where('type', 'keluar');
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
     * Mendistribusikan/mengirim surat keluar.
     * Sesuai UML: +distribusikan(): void
     *
     * @param array $penerima Array ID pengguna yang perlu diberitahu (opsional)
     * @return void
     */
    public function distribusikan(array $penerima = []): void
    {
        // Update status menjadi dikirim
        $this->status = 'approved';
        $this->save();

        // Kirim notifikasi ke pembuat surat
        if ($this->created_by) {
            Notifikasi::create([
                'user_id' => $this->created_by,
                'type' => Notifikasi::TYPE_DOCUMENT_STATUS_CHANGED,
                'title' => 'Surat Keluar Dikirim',
                'message' => "Surat keluar '{$this->subject}' telah dikirim ke {$this->recipient}",
                'related_model_type' => self::class,
                'related_model_id' => $this->id,
            ]);
        }

        // Kirim notifikasi ke penerima tambahan jika ada
        foreach ($penerima as $userId) {
            Notifikasi::create([
                'user_id' => $userId,
                'type' => Notifikasi::TYPE_DOCUMENT_STATUS_CHANGED,
                'title' => 'Surat Keluar Baru',
                'message' => "Surat keluar '{$this->subject}' telah dikirim",
                'related_model_type' => self::class,
                'related_model_id' => $this->id,
            ]);
        }
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope untuk mendapatkan surat yang belum dikirim.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBelumDikirim($query)
    {
        return $query->whereIn('status', ['draft', 'pending_approval']);
    }

    /**
     * Scope untuk mendapatkan surat yang sudah dikirim.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSudahDikirim($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope untuk mendapatkan surat berdasarkan tujuan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tujuan
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeKeTujuan($query, string $tujuan)
    {
        return $query->where('recipient', 'like', "%{$tujuan}%");
    }

    /**
     * Scope untuk mendapatkan surat yang dikirim pada tanggal tertentu.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon|string $tanggal
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDikirimPada($query, $tanggal)
    {
        return $query->whereDate('date', $tanggal);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Mendapatkan tujuan surat.
     *
     * @return string
     */
    public function getTujuan(): string
    {
        return $this->recipient ?? '';
    }

    /**
     * Mendapatkan tembusan surat.
     * Tembusan disimpan dalam field description dengan format khusus.
     *
     * @return array
     */
    public function getTembusan(): array
    {
        // Parsing tembusan dari description jika ada
        // Format: "Tembusan: xxx, yyy, zzz"
        if (preg_match('/Tembusan:\s*(.+)/i', $this->description ?? '', $matches)) {
            return array_map('trim', explode(',', $matches[1]));
        }
        return [];
    }

    /**
     * Mendapatkan tanggal dikirim.
     *
     * @return \Carbon\Carbon|null
     */
    public function getTanggalDikirim()
    {
        return $this->date;
    }

    /**
     * Mendapatkan nomor surat internal.
     *
     * @return string
     */
    public function getNoSuratInternal(): string
    {
        return $this->number ?? '';
    }

    /**
     * Cek apakah surat sudah dikirim.
     *
     * @return bool
     */
    public function sudahDikirim(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Cek apakah surat membutuhkan persetujuan.
     *
     * @return bool
     */
    public function membutuhkanPersetujuan(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval']);
    }

    /**
     * Set tembusan surat.
     *
     * @param array $tembusan
     * @return void
     */
    public function setTembusan(array $tembusan): void
    {
        $tembusanStr = implode(', ', $tembusan);
        
        // Tambahkan tembusan ke description
        if (!str_contains($this->description ?? '', 'Tembusan:')) {
            $this->description = ($this->description ?? '') . "\n\nTembusan: " . $tembusanStr;
        } else {
            $this->description = preg_replace(
                '/Tembusan:\s*.+/i',
                'Tembusan: ' . $tembusanStr,
                $this->description
            );
        }
        
        $this->save();
    }
}
