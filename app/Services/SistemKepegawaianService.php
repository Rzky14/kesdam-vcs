<?php

namespace App\Services;

use App\Contracts\SistemKepegawaian;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LayananSistemKepegawaian (SistemKepegawaianService)
 * 
 * Implementasi dari interface SistemKepegawaian.
 * Menangani integrasi dengan sistem kepegawaian eksternal.
 * Sesuai dengan UML Class Diagram.
 */
class SistemKepegawaianService implements SistemKepegawaian
{
    /**
     * URL endpoint API sistem kepegawaian.
     *
     * @var string
     */
    protected string $apiUrl;

    /**
     * API key untuk autentikasi.
     *
     * @var string
     */
    protected string $apiKey;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->apiUrl = config('services.kepegawaian.url', '');
        $this->apiKey = config('services.kepegawaian.key', '');
    }

    /**
     * Mengambil data pengguna/pegawai dari sistem kepegawaian.
     * Sesuai UML: +getUserData(): void (typo fixed dari getUseData)
     *
     * @return Collection Koleksi data pegawai
     */
    public function getUserData(): Collection
    {
        try {
            // Jika tidak ada API endpoint, gunakan data lokal
            if (empty($this->apiUrl)) {
                return $this->getDataLokal();
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->apiUrl . '/pegawai');

            if ($response->successful()) {
                return collect($response->json('data'));
            }

            Log::warning('Gagal mengambil data dari sistem kepegawaian', [
                'status' => $response->status(),
                'message' => $response->body(),
            ]);

            return $this->getDataLokal();
        } catch (\Exception $e) {
            Log::error('Error saat mengambil data kepegawaian', [
                'error' => $e->getMessage(),
            ]);

            return $this->getDataLokal();
        }
    }

    /**
     * Mengambil data pegawai berdasarkan NRP.
     *
     * @param string $nrp Nomor Registrasi Pegawai
     * @return array|null Data pegawai atau null jika tidak ditemukan
     */
    public function getPegawaiByNrp(string $nrp): ?array
    {
        try {
            if (empty($this->apiUrl)) {
                $user = User::where('nrp', $nrp)->first();
                return $user ? $user->toArray() : null;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->apiUrl . '/pegawai/' . $nrp);

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error saat mengambil data pegawai', [
                'nrp' => $nrp,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Mengambil daftar pegawai berdasarkan unit/divisi.
     *
     * @param string $unit Nama unit/divisi
     * @return Collection Koleksi pegawai dalam unit tersebut
     */
    public function getPegawaiByUnit(string $unit): Collection
    {
        try {
            if (empty($this->apiUrl)) {
                return User::where('unit', $unit)->get();
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->apiUrl . '/pegawai', [
                'unit' => $unit,
            ]);

            if ($response->successful()) {
                return collect($response->json('data'));
            }

            return collect();
        } catch (\Exception $e) {
            Log::error('Error saat mengambil data pegawai by unit', [
                'unit' => $unit,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Mengambil daftar pegawai berdasarkan jabatan.
     *
     * @param string $jabatan Nama jabatan
     * @return Collection Koleksi pegawai dengan jabatan tersebut
     */
    public function getPegawaiByJabatan(string $jabatan): Collection
    {
        try {
            if (empty($this->apiUrl)) {
                return User::where('position', $jabatan)->get();
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->apiUrl . '/pegawai', [
                'jabatan' => $jabatan,
            ]);

            if ($response->successful()) {
                return collect($response->json('data'));
            }

            return collect();
        } catch (\Exception $e) {
            Log::error('Error saat mengambil data pegawai by jabatan', [
                'jabatan' => $jabatan,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Sinkronisasi data pegawai ke sistem lokal.
     *
     * @return int Jumlah data yang disinkronisasi
     */
    public function sinkronisasiData(): int
    {
        $data = $this->getUserData();
        $count = 0;

        foreach ($data as $pegawai) {
            try {
                User::updateOrCreate(
                    ['nrp' => $pegawai['nrp'] ?? $pegawai['id']],
                    [
                        'name' => $pegawai['nama'] ?? $pegawai['name'],
                        'email' => $pegawai['email'] ?? null,
                        'rank' => $pegawai['pangkat'] ?? $pegawai['rank'] ?? null,
                        'position' => $pegawai['jabatan'] ?? $pegawai['position'] ?? null,
                        'unit' => $pegawai['unit'] ?? $pegawai['divisi'] ?? null,
                        'is_active' => $pegawai['status_aktif'] ?? $pegawai['is_active'] ?? true,
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                Log::warning('Gagal sinkronisasi pegawai', [
                    'pegawai' => $pegawai,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Sinkronisasi data kepegawaian selesai', [
            'total_disinkronisasi' => $count,
        ]);

        return $count;
    }

    /**
     * Memeriksa status aktif pegawai.
     *
     * @param string $nrp Nomor Registrasi Pegawai
     * @return bool True jika pegawai aktif
     */
    public function isPegawaiAktif(string $nrp): bool
    {
        $pegawai = $this->getPegawaiByNrp($nrp);
        
        if (!$pegawai) {
            return false;
        }

        return $pegawai['status_aktif'] ?? $pegawai['is_active'] ?? false;
    }

    /**
     * Mengambil struktur organisasi.
     *
     * @return array Struktur organisasi dalam format hierarki
     */
    public function getStrukturOrganisasi(): array
    {
        try {
            if (empty($this->apiUrl)) {
                return $this->getStrukturLokal();
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->apiUrl . '/struktur-organisasi');

            if ($response->successful()) {
                return $response->json('data');
            }

            return $this->getStrukturLokal();
        } catch (\Exception $e) {
            Log::error('Error saat mengambil struktur organisasi', [
                'error' => $e->getMessage(),
            ]);

            return $this->getStrukturLokal();
        }
    }

    /**
     * Mengambil data lokal dari database.
     *
     * @return Collection
     */
    protected function getDataLokal(): Collection
    {
        return User::where('is_active', true)->get();
    }

    /**
     * Mengambil struktur organisasi lokal.
     *
     * @return array
     */
    protected function getStrukturLokal(): array
    {
        return [
            'pimpinan' => User::whereHas('roles', function ($q) {
                $q->where('name', 'pimpinan');
            })->get()->toArray(),
            'kasi' => User::whereHas('roles', function ($q) {
                $q->where('name', 'kasi');
            })->get()->toArray(),
            'kaur' => User::whereHas('roles', function ($q) {
                $q->where('name', 'kaur');
            })->get()->toArray(),
            'batih' => User::whereHas('roles', function ($q) {
                $q->where('name', 'batih');
            })->get()->toArray(),
            'staff' => User::whereHas('roles', function ($q) {
                $q->where('name', 'staff');
            })->get()->toArray(),
        ];
    }
}



