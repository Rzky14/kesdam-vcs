<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Interface SistemKepegawaian
 * 
 * Interface untuk integrasi dengan sistem kepegawaian eksternal.
 * Sesuai dengan UML Class Diagram - SistemKepegawaian.
 * 
 * Interface ini mendefinisikan kontrak untuk mengambil data pegawai
 * dari sistem kepegawaian yang ada.
 */
interface SistemKepegawaian
{
    /**
     * Mengambil data pengguna/pegawai dari sistem kepegawaian.
     * Sesuai UML: +getUserData(): void (typo fixed dari getUseData)
     *
     * @return Collection Koleksi data pegawai
     */
    public function getUserData(): Collection;

    /**
     * Mengambil data pegawai berdasarkan NRP.
     *
     * @param string $nrp Nomor Registrasi Pegawai
     * @return array|null Data pegawai atau null jika tidak ditemukan
     */
    public function getPegawaiByNrp(string $nrp): ?array;

    /**
     * Mengambil daftar pegawai berdasarkan unit/divisi.
     *
     * @param string $unit Nama unit/divisi
     * @return Collection Koleksi pegawai dalam unit tersebut
     */
    public function getPegawaiByUnit(string $unit): Collection;

    /**
     * Mengambil daftar pegawai berdasarkan jabatan.
     *
     * @param string $jabatan Nama jabatan
     * @return Collection Koleksi pegawai dengan jabatan tersebut
     */
    public function getPegawaiByJabatan(string $jabatan): Collection;

    /**
     * Sinkronisasi data pegawai ke sistem lokal.
     *
     * @return int Jumlah data yang disinkronisasi
     */
    public function sinkronisasiData(): int;

    /**
     * Memeriksa status aktif pegawai.
     *
     * @param string $nrp Nomor Registrasi Pegawai
     * @return bool True jika pegawai aktif
     */
    public function isPegawaiAktif(string $nrp): bool;

    /**
     * Mengambil struktur organisasi.
     *
     * @return array Struktur organisasi dalam format hierarki
     */
    public function getStrukturOrganisasi(): array;
}



