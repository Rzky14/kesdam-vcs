<?php

namespace App\Services;

use App\Models\Dokumen;
use App\Models\Report;
use App\Models\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * LayananLaporan (ReportService)
 * 
 * Menangani pembuatan dan pengelolaan laporan untuk jadwal, dokumen, dan efektivitas.
 */
class ReportService
{
    /**
     * Buat laporan jadwal untuk periode tertentu.
     *
     * @param Carbon $startDate Tanggal mulai periode
     * @param Carbon $endDate Tanggal akhir periode
     * @param mixed $scheduleTypes Tipe jadwal (opsional)
     * @param int|null $userId ID pengguna pembuat (opsional)
     * @return Report Laporan yang dibuat
     */
    public function generateScheduleReport(
        Carbon $startDate,
        Carbon $endDate,
        $scheduleTypes = null,
        ?int $userId = null
    ): Report {
        $query = Schedule::whereBetween('start_date', [$startDate, $endDate]);

        if ($scheduleTypes) {
            if (is_array($scheduleTypes)) {
                $query->whereIn('type', $scheduleTypes);
            } else {
                $query->where('type', $scheduleTypes);
            }
        }

        if ($userId) {
            $query->where('created_by', $userId);
        }

        $schedules = $query->get();

        // Hitung statistik
        $data = [
            'total_schedules' => $schedules->count(),
            'by_type' => $schedules->groupBy('type')->map->count(),
            'by_status' => $schedules->groupBy('status')->map->count(),
            'average_duration' => $schedules->avg(function ($schedule) {
                return $schedule->end_date->diffInHours($schedule->start_date);
            }),
            'schedules' => $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'type' => $schedule->type,
                    'start_date' => $schedule->start_date->toDateTimeString(),
                    'end_date' => $schedule->end_date->toDateTimeString(),
                    'status' => $schedule->status,
                ];
            })->toArray(),
        ];

        return Report::create([
            'name' => "Laporan Jadwal - {$startDate->format('M Y')}",
            'type' => 'schedule',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'generated_by' => Auth::id() ?? 1,
            'data' => $data,
            'status' => 'completed',
        ]);
    }

    /**
     * Buat laporan dokumen untuk periode tertentu.
     *
     * @param Carbon $startDate Tanggal mulai periode
     * @param Carbon $endDate Tanggal akhir periode
     * @param mixed $classifications Klasifikasi dokumen (opsional)
     * @param string|null $documentStatus Status dokumen (opsional)
     * @return Report Laporan yang dibuat
     */
    public function generateDocumentReport(
        Carbon $startDate,
        Carbon $endDate,
        $classifications = null,
        ?string $documentStatus = null
    ): Report {
        $query = Dokumen::whereBetween('created_at', [$startDate, $endDate]);

        if ($classifications) {
            if (is_array($classifications)) {
                $query->whereIn('classification', $classifications);
            } else {
                $query->where('classification', $classifications);
            }
        }

        if ($documentStatus) {
            $query->where('status', $documentStatus);
        }

        $documents = $query->get();
        $total = $documents->count();

        // RINGKASAN
        $approvedCount = $documents->where('status', 'approved')->count();
        $pendingCount = $documents->where('status', 'pending_approval')->count();
        $rejectedCount = $documents->where('status', 'rejected')->count();
        $draftCount = $documents->where('status', 'draft')->count();
        $approvalRate = $total > 0 ? round(($approvedCount / $total) * 100, 1) : 0;

        // DISTRIBUSI PER KLASIFIKASI
        $byClassification = $documents->groupBy('classification');
        $biasaCount = $byClassification->get('biasa', collect())->count();
        $rahasiaCount = $byClassification->get('rahasia', collect())->count();
        $telegramCount = $byClassification->get('telegram', collect())->count();
        
        $biasaPercent = $total > 0 ? round(($biasaCount / $total) * 100) : 0;
        $rahasiaPercent = $total > 0 ? round(($rahasiaCount / $total) * 100) : 0;
        $telegramPercent = $total > 0 ? round(($telegramCount / $total) * 100) : 0;

        // DISTRIBUSI PER TIPE
        $byType = $documents->groupBy('type');
        $incomingCount = $byType->get('surat_masuk', collect())->count();
        $outgoingCount = $byType->get('surat_keluar', collect())->count();
        
        $incomingPercent = $total > 0 ? round(($incomingCount / $total) * 100) : 0;
        $outgoingPercent = $total > 0 ? round(($outgoingCount / $total) * 100) : 0;

        // KINERJA APPROVAL - Hitung waktu approval
        $approvedDocs = $documents->filter(function ($doc) {
            return $doc->status === 'approved' && $doc->created_at && $doc->updated_at;
        });
        
        $approvalTimes = $approvedDocs->map(function ($doc) {
            return $doc->created_at->diffInDays($doc->updated_at);
        });
        
        $avgApprovalDays = $approvalTimes->count() > 0 ? round($approvalTimes->avg(), 1) : 0;
        $fastestApproval = $approvalTimes->count() > 0 ? round($approvalTimes->min(), 1) : 0;
        $slowestApproval = $approvalTimes->count() > 0 ? round($approvalTimes->max(), 1) : 0;

        $data = [
            // RINGKASAN
            'total_documents' => $total,
            'approval_rate' => $approvalRate,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
            'draft_count' => $draftCount,
            
            // DISTRIBUSI PER KLASIFIKASI
            'klasifikasi' => [
                'biasa' => ['count' => $biasaCount, 'percent' => $biasaPercent],
                'rahasia' => ['count' => $rahasiaCount, 'percent' => $rahasiaPercent],
                'telegram' => ['count' => $telegramCount, 'percent' => $telegramPercent],
            ],
            
            // DISTRIBUSI PER TIPE
            'tipe' => [
                'surat_masuk' => ['count' => $incomingCount, 'percent' => $incomingPercent],
                'surat_keluar' => ['count' => $outgoingCount, 'percent' => $outgoingPercent],
            ],
            
            // KINERJA APPROVAL
            'kinerja_approval' => [
                'rata_rata_waktu' => $avgApprovalDays,
                'tercepat' => $fastestApproval,
                'terlama' => $slowestApproval,
            ],
        ];

        return Report::create([
            'name' => "Laporan Dokumen - {$startDate->format('M Y')}",
            'type' => 'document',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'generated_by' => Auth::id() ?? 1,
            'data' => $data,
            'status' => 'completed',
        ]);
    }

    /**
     * Buat laporan efektivitas jadwal.
     *
     * @param Carbon $startDate Tanggal mulai periode
     * @param Carbon $endDate Tanggal akhir periode
     * @param string|null $scheduleType Tipe jadwal (opsional)
     * @return Report Laporan yang dibuat
     */
    public function generateEffectivenessReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $scheduleType = null
    ): Report {
        $query = Schedule::whereBetween('start_date', [$startDate, $endDate]);

        if ($scheduleType) {
            $query->where('type', $scheduleType);
        }

        $schedules = $query->get();
        $totalSchedules = $schedules->count();

        // RINGKASAN
        $completedSchedules = $schedules->where('status', 'completed')->count();
        $activeSchedules = $schedules->where('status', 'active')->count();
        $cancelledSchedules = $schedules->where('status', 'cancelled')->count();

        // Hitung rata-rata durasi dalam hari
        $avgDurationDays = $schedules->avg(function ($schedule) {
            return $schedule->end_date->diffInDays($schedule->start_date, false);
        });
        $avgDurationDays = $avgDurationDays ? round($avgDurationDays, 0) : 0;

        // Tingkat efektivitas = (completed / total) * 100
        $effectivenessRate = $totalSchedules > 0 
            ? round(($completedSchedules / $totalSchedules) * 100, 0)
            : 0;

        // DISTRIBUSI PER JENIS
        $byType = $schedules->groupBy('type');
        $dukkesCount = $byType->get('dukkes', collect())->count();
        $jagaCount = $byType->get('jaga', collect())->count();
        $kegiatanCount = $byType->get('kegiatan_satuan', collect())->count();
        
        $dukkesPercent = $totalSchedules > 0 ? round(($dukkesCount / $totalSchedules) * 100) : 0;
        $jagaPercent = $totalSchedules > 0 ? round(($jagaCount / $totalSchedules) * 100) : 0;
        $kegiatanPercent = $totalSchedules > 0 ? round(($kegiatanCount / $totalSchedules) * 100) : 0;

        // KINERJA vs TARGET
        $onTimeRate = $effectivenessRate; // Simplifikasi: anggap completed = on-time
        $cancelledRate = $totalSchedules > 0 ? round(($cancelledSchedules / $totalSchedules) * 100, 0) : 0;
        $completedRate = $effectivenessRate;

        // Status: Tentukan berdasarkan tingkat efektivitas
        $status = 'normal';
        $statusMessage = 'Berjalan Baik';
        if ($effectivenessRate < 50) {
            $status = 'perlu_perhatian';
            $statusMessage = 'Perlu Perhatian - Tingkat penyelesaian rendah';
        } elseif ($effectivenessRate < 80) {
            $status = 'warning';
            $statusMessage = 'Perlu Perbaikan';
        } else {
            $status = 'excellent';
            $statusMessage = 'Sangat Baik';
        }

        $data = [
            // RINGKASAN
            'total_schedules' => $totalSchedules,
            'active_schedules' => $activeSchedules,
            'completed_schedules' => $completedSchedules,
            'cancelled_schedules' => $cancelledSchedules,
            'average_duration' => $avgDurationDays,
            'effectiveness_rate' => $effectivenessRate,
            
            // DISTRIBUSI PER JENIS
            'distribusi_jenis' => [
                'jadwal_dukkes' => ['count' => $dukkesCount, 'percent' => $dukkesPercent],
                'jadwal_jaga' => ['count' => $jagaCount, 'percent' => $jagaPercent],
                'kegiatan_satuan' => ['count' => $kegiatanCount, 'percent' => $kegiatanPercent],
            ],
            
            // KINERJA vs TARGET
            'kinerja' => [
                'on_time' => ['target' => 90, 'actual' => $onTimeRate],
                'cancelled' => ['target' => 5, 'actual' => $cancelledRate],
                'completed' => ['target' => 95, 'actual' => $completedRate],
            ],
            
            // STATUS
            'status_evaluasi' => [
                'status' => $status,
                'message' => $statusMessage,
            ],
        ];

        return Report::create([
            'name' => "Laporan Efektivitas Jadwal - {$startDate->format('M Y')}",
            'type' => 'effectiveness',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'generated_by' => Auth::id() ?? 1,
            'data' => $data,
            'status' => 'completed',
        ]);
    }

    /**
     * Ambil daftar laporan.
     *
     * @param string|null $type Tipe laporan (opsional)
     * @param int|null $limit Batas jumlah laporan
     * @param int|null $offset Offset untuk paginasi
     * @return Collection Daftar laporan
     */
    public function getReports(
        ?string $type = null,
        ?int $limit = 50,
        ?int $offset = 0
    ): Collection {
        $query = Report::query();

        if ($type) {
            $query->byType($type);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    /**
     * Ambil laporan berdasarkan ID.
     *
     * @param int $id ID laporan
     * @return Report|null Laporan atau null jika tidak ditemukan
     */
    public function getReport(int $id): ?Report
    {
        return Report::find($id);
    }

    /**
     * Hapus laporan.
     *
     * @param int $id ID laporan
     * @return bool Status berhasil atau gagal
     */
    public function deleteReport(int $id): bool
    {
        $report = Report::find($id);
        if (!$report) {
            return false;
        }

        return (bool) $report->delete();
    }

    /**
     * Hitung tingkat persetujuan dokumen.
     *
     * @param Collection $documents Koleksi dokumen
     * @return float Persentase tingkat persetujuan
     */
    private function calculateApprovalRate(Collection $documents): float
    {
        if ($documents->isEmpty()) {
            return 0;
        }

        $approved = $documents->filter(fn($doc) => $doc->status === 'approved')->count();
        return round(($approved / $documents->count()) * 100, 2);
    }
}
