<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;

/**
 * Model RiwayatPerubahan
 * 
 * Alias dari AuditLog sesuai dengan UML Class Diagram.
 * Model ini digunakan untuk mencatat semua perubahan data dalam sistem.
 * 
 * Atribut UML:
 * - idRiwayat: int
 * - entitas: String
 * - idEntitas: int
 * - aksi: String
 * - nilaiSebelum: String
 * - nilaiSesudah: String
 * - dibuatPada: Date
 * 
 * Method UML:
 * - +catat(): void
 */
class RiwayatPerubahan extends AuditLog
{
    /**
     * Nama tabel database.
     * Menggunakan tabel yang sama dengan AuditLog.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * Mencatat perubahan data sesuai UML.
     * Implementasi dari method +catat(): void
     *
     * @param string $entitas Nama entitas yang berubah
     * @param int $idEntitas ID dari entitas yang berubah
     * @param string $aksi Jenis aksi (create, update, delete)
     * @param array|string $nilaiSebelum Nilai sebelum perubahan
     * @param array|string $nilaiSesudah Nilai setelah perubahan
     * @return static
     */
    public static function catatPerubahan(
        string $entitas,
        int $idEntitas,
        string $aksi,
        array|string $nilaiSebelum = [],
        array|string $nilaiSesudah = []
    ): static {
        // Konversi string ke array jika diperlukan
        $nilaiLama = is_string($nilaiSebelum) ? ['value' => $nilaiSebelum] : $nilaiSebelum;
        $nilaiBaru = is_string($nilaiSesudah) ? ['value' => $nilaiSesudah] : $nilaiSesudah;

        return static::create([
            'user_id' => Auth::id(),
            'event' => $aksi,
            'auditable_type' => $entitas,
            'auditable_id' => $idEntitas,
            'old_values' => $nilaiLama,
            'new_values' => $nilaiBaru,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => "Perubahan data {$entitas} #{$idEntitas}: {$aksi}",
        ]);
    }

    /**
     * Mendapatkan riwayat perubahan berdasarkan entitas.
     *
     * @param string $entitas Nama entitas
     * @param int|null $idEntitas ID entitas (opsional)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRiwayat(string $entitas, ?int $idEntitas = null)
    {
        $query = static::where('auditable_type', $entitas);

        if ($idEntitas !== null) {
            $query->where('auditable_id', $idEntitas);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Mendapatkan riwayat perubahan terbaru.
     *
     * @param int $jumlah Jumlah record yang diambil
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRiwayatTerbaru(int $jumlah = 10)
    {
        return static::with('pengguna')
            ->orderByDesc('created_at')
            ->limit($jumlah)
            ->get();
    }

    /**
     * Mendapatkan ringkasan perubahan hari ini.
     *
     * @return array
     */
    public static function ringkasanHariIni(): array
    {
        $data = static::whereDate('created_at', today())
            ->selectRaw('event as aksi, count(*) as total')
            ->groupBy('event')
            ->get();

        return $data->pluck('total', 'aksi')->toArray();
    }

    /**
     * Membandingkan nilai sebelum dan sesudah.
     *
     * @return array Daftar field yang berubah
     */
    public function getPerubahan(): array
    {
        $sebelum = $this->old_values ?? [];
        $sesudah = $this->new_values ?? [];
        $perubahan = [];

        // Cari field yang berubah
        foreach ($sesudah as $key => $nilai) {
            $nilaiLama = $sebelum[$key] ?? null;
            if ($nilaiLama !== $nilai) {
                $perubahan[$key] = [
                    'sebelum' => $nilaiLama,
                    'sesudah' => $nilai,
                ];
            }
        }

        // Cari field yang dihapus
        foreach ($sebelum as $key => $nilai) {
            if (!array_key_exists($key, $sesudah)) {
                $perubahan[$key] = [
                    'sebelum' => $nilai,
                    'sesudah' => null,
                ];
            }
        }

        return $perubahan;
    }

    /**
     * Accessor untuk atribut 'entitas' sesuai UML.
     *
     * @return string|null
     */
    public function getEntitasAttribute(): ?string
    {
        return $this->auditable_type;
    }

    /**
     * Accessor untuk atribut 'idEntitas' sesuai UML.
     *
     * @return int|null
     */
    public function getIdEntitasAttribute(): ?int
    {
        return $this->auditable_id;
    }

    /**
     * Accessor untuk atribut 'aksi' sesuai UML.
     *
     * @return string
     */
    public function getAksiAttribute(): string
    {
        return $this->event;
    }

    /**
     * Accessor untuk atribut 'nilaiSebelum' sesuai UML.
     *
     * @return array
     */
    public function getNilaiSebelumAttribute(): array
    {
        return $this->old_values ?? [];
    }

    /**
     * Accessor untuk atribut 'nilaiSesudah' sesuai UML.
     *
     * @return array
     */
    public function getNilaiSesudahAttribute(): array
    {
        return $this->new_values ?? [];
    }

    /**
     * Accessor untuk atribut 'dibuatPada' sesuai UML.
     *
     * @return \Illuminate\Support\Carbon|null
     */
    public function getDibuatPadaAttribute()
    {
        return $this->created_at;
    }
}
