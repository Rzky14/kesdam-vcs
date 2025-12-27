<?php

namespace App\Services;

use App\Models\Arsip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ArchiveService
{
    /**
     * Arsipkan model (Dokumen, Jadwal, Laporan)
     */
    public function archiveModel(
        $model,
        string $kategori,
        ?string $deskripsi = null,
        ?array $tags = null,
        ?Carbon $retensiSampai = null
    ): Arsip {
        // Set default periode retensi (5 tahun)
        if (!$retensiSampai) {
            $retensiSampai = now()->addYears(5);
        }

        return Arsip::create([
            'name' => $model->name ?? $model->subject ?? $model->title ?? 'Item Arsip',
            'description' => $deskripsi,
            'archiveable_type' => class_basename($model),
            'archiveable_id' => $model->id,
            'archived_by' => Auth::id() ?? 1,
            'archive_date' => now(),
            'retention_until' => $retensiSampai,
            'category' => $kategori,
            'tags' => $tags ?? [],
            'is_indexed' => false,
        ]);
    }

    /**
     * Cari arsip berdasarkan kata kunci
     */
    public function cari(
        string $kataKunci,
        ?string $kategori = null,
        ?int $batas = 50
    ): Collection {
        $query = Arsip::search($kataKunci);

        if ($kategori) {
            $query->byCategory($kategori);
        }

        return $query->limit($batas)->get();
    }

    /**
     * Ambil arsip berdasarkan kategori
     */
    public function ambilBerdasarkanKategori(string $kategori, ?int $batas = 50): Collection
    {
        return Arsip::byCategory($kategori)
            ->orderBy('archive_date', 'desc')
            ->limit($batas)
            ->get();
    }

    /**
     * Ambil semua arsip yang sudah diindeks
     */
    public function ambilArsipTerindeks(?int $batas = 50): Collection
    {
        return Arsip::indexed()
            ->orderBy('archive_date', 'desc')
            ->limit($batas)
            ->get();
    }

    /**
     * Indeks arsip (untuk full-text search)
     */
    public function indeksArsip(): int
    {
        $arsipList = Arsip::where('is_indexed', false)
            ->limit(100)
            ->get();

        $jumlah = 0;
        foreach ($arsipList as $arsip) {
            $arsip->markAsIndexed();
            $jumlah++;
        }

        return $jumlah;
    }

    /**
     * Ambil arsip yang retensinya akan kedaluwarsa
     */
    public function ambilArsipRetensiKedaluwarsa(int $hari = 30): Collection
    {
        return Arsip::retentionExpiring($hari)->get();
    }

    /**
     * Hapus permanen arsip yang sudah kedaluwarsa
     */
    public function hapusArsipKedaluwarsa(): int
    {
        return Arsip::where('retention_until', '<', now())
            ->forceDelete();
    }

    /**
     * Tambahkan tag ke arsip
     */
    public function addTags(Arsip $arsip, array $tagBaru): Arsip
    {
        $tagYangAda = $arsip->tags ?? [];
        $arsip->tags = array_unique(array_merge($tagYangAda, $tagBaru));
        $arsip->save();

        return $arsip;
    }

    /**
     * Ambil statistik arsip
     */
    public function ambilStatistik(): array
    {
        return [
            'total_arsip' => Arsip::count(),
            'berdasarkan_kategori' => Arsip::groupBy('category')->selectRaw('category, COUNT(*) as count')->get()->toArray(),
            'arsip_terindeks' => Arsip::indexed()->count(),
            'akan_kedaluwarsa' => Arsip::retentionExpiring(30)->count(),
        ];
    }

    // ==========================================
    // ALIAS METHODS (untuk kompatibilitas)
    // ==========================================

    /**
     * Alias: Cari arsip (English method name)
     */
    public function search(string $term, ?string $category = null, ?int $limit = 50): Collection
    {
        return $this->cari($term, $category, $limit);
    }

    /**
     * Alias: Ambil berdasarkan kategori (English method name)
     */
    public function getByCategory(string $category, ?int $limit = 50): Collection
    {
        return $this->ambilBerdasarkanKategori($category, $limit);
    }

    /**
     * Alias: Ambil arsip terindeks (English method name)
     */
    public function getIndexedArchives(?int $limit = 50): Collection
    {
        return $this->ambilArsipTerindeks($limit);
    }

    /**
     * Alias: Indeks arsip (English method name)
     */
    public function indexArchives(): int
    {
        return $this->indeksArsip();
    }

    /**
     * Alias: Ambil arsip retensi kedaluwarsa (English method name)
     */
    public function getRetentionExpiringArchives(int $days = 30): Collection
    {
        return $this->ambilArsipRetensiKedaluwarsa($days);
    }

    /**
     * Alias: Hapus arsip kedaluwarsa (English method name)
     */
    public function deleteExpiredArchives(): int
    {
        return $this->hapusArsipKedaluwarsa();
    }

    /**
     * Alias: Ambil statistik (English method name)
     */
    public function getStatistics(): array
    {
        return $this->ambilStatistik();
    }
}



