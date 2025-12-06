<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Report;
use App\Models\Schedule;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ReportService
{
    /**
     * Generate schedule report untuk periode tertentu
     */
    public function generateScheduleReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $scheduleType = null,
        ?int $userId = null
    ): Report {
        $query = Schedule::whereBetween('start_date', [$startDate, $endDate]);

        if ($scheduleType) {
            $query->where('type', $scheduleType);
        }

        if ($userId) {
            $query->where('created_by', $userId);
        }

        $schedules = $query->get();

        // Calculate statistics
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
            'created_by' => auth()->id() ?? 1,
            'data' => $data,
            'status' => 'generated',
        ]);
    }

    /**
     * Generate document report untuk periode tertentu
     */
    public function generateDocumentReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $type = null,
        ?string $classification = null
    ): Report {
        $query = Document::whereBetween('created_at', [$startDate, $endDate]);

        if ($type) {
            $query->where('type', $type);
        }

        if ($classification) {
            $query->where('classification', $classification);
        }

        $documents = $query->get();

        // Calculate statistics
        $data = [
            'total_documents' => $documents->count(),
            'by_type' => $documents->groupBy('type')->map->count(),
            'by_classification' => $documents->groupBy('classification')->map->count(),
            'by_status' => $documents->groupBy('status')->map->count(),
            'approval_rate' => $this->calculateApprovalRate($documents),
            'documents' => $documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'subject' => $doc->subject,
                    'type' => $doc->type,
                    'classification' => $doc->classification,
                    'status' => $doc->status,
                    'created_at' => $doc->created_at->toDateString(),
                ];
            })->toArray(),
        ];

        return Report::create([
            'name' => "Laporan Dokumen - {$startDate->format('M Y')}",
            'type' => 'document',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'created_by' => auth()->id() ?? 1,
            'data' => $data,
            'status' => 'generated',
        ]);
    }

    /**
     * Get reports list
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
     * Get report by ID
     */
    public function getReport(int $id): ?Report
    {
        return Report::find($id);
    }

    /**
     * Delete report
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
     * Calculate approval rate
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
