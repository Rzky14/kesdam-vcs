<?php

namespace App\Services;

use App\Models\Dokumen;
use App\Models\Report;
use App\Models\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportService
{
    /**
     * Generate schedule report untuk periode tertentu
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
            'generated_by' => Auth::id() ?? 1,
            'data' => $data,
            'status' => 'completed',
        ]);
    }

    /**
     * Generate document report untuk periode tertentu
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

        // Calculate statistics
        $data = [
            'total_documents' => $documents->count(),
            'by_type' => $documents->groupBy('type')->map->count(),
            'by_classification' => $documents->groupBy('classification')->map->count(),
            'by_status' => $documents->groupBy('status')->map->count(),
            'approval_rate' => $this->calculateApprovalRate($documents),
            'pending_count' => $documents->where('status', 'pending_approval')->count(),
            'approved_count' => $documents->where('status', 'approved')->count(),
            'rejected_count' => $documents->where('status', 'rejected')->count(),
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
            'generated_by' => Auth::id() ?? 1,
            'data' => $data,
            'status' => 'completed',
        ]);
    }

    /**
     * Generate schedule effectiveness report
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

        // Calculate effectiveness metrics
        $totalSchedules = $schedules->count();
        $completedSchedules = $schedules->where('status', 'completed')->count();
        $activeSchedules = $schedules->where('status', 'active')->count();
        $cancelledSchedules = $schedules->where('status', 'cancelled')->count();

        $effectivenessRate = $totalSchedules > 0 
            ? round(($completedSchedules / $totalSchedules) * 100, 2)
            : 0;

        $data = [
            'total_schedules' => $totalSchedules,
            'completed_schedules' => $completedSchedules,
            'active_schedules' => $activeSchedules,
            'cancelled_schedules' => $cancelledSchedules,
            'effectiveness_rate' => $effectivenessRate,
            'by_type' => $schedules->groupBy('type')->map->count(),
            'average_duration' => $schedules->avg(function ($schedule) {
                return $schedule->end_date->diffInHours($schedule->start_date);
            }),
            'schedules' => $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->title,
                    'type' => $schedule->type,
                    'start_date' => $schedule->start_date->toDateTimeString(),
                    'end_date' => $schedule->end_date->toDateTimeString(),
                    'status' => $schedule->status,
                    'personnel_count' => is_array($schedule->personnel) ? count($schedule->personnel) : 0,
                ];
            })->toArray(),
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
