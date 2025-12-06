<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    /**
     * Display reports dashboard with statistics
     */
    public function index(Request $request): View
    {
        // Get latest reports
        $latestReports = Report::latest()
            ->take(10)
            ->get();
        
        // Statistics
        $totalReports = Report::count();
        $monthlyReports = Report::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        $completedReports = Report::where('status', 'completed')->count();
        $inProgressReports = Report::where('status', 'in_progress')->count();
        
        // Recent reports by type
        $scheduleReports = Report::where('type', 'schedule')
            ->latest()
            ->take(5)
            ->get();
            
        $documentReports = Report::where('type', 'document')
            ->latest()
            ->take(5)
            ->get();
        
        return view('reports.index', compact(
            'latestReports',
            'totalReports',
            'monthlyReports',
            'completedReports',
            'inProgressReports',
            'scheduleReports',
            'documentReports'
        ));
    }

    /**
     * Show report details
     */
    public function show(Report $report): View
    {
        return view('reports.show', ['report' => $report]);
    }

    /**
     * Show form to generate schedule report
     */
    public function createScheduleReport(): View
    {
        return view('reports.generate-schedule');
    }

    /**
     * Generate schedule report
     */
    public function storeScheduleReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'schedule_type' => 'nullable|in:dukkes,jaga,kegiatan_satuan',
        ]);

        $report = $this->reportService->generateScheduleReport(
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end']),
            $validated['schedule_type'] ?? null
        );

        return redirect()->route('reports.show', $report)
            ->with('success', 'Laporan jadwal berhasil dibuat!');
    }

    /**
     * Show form to generate document report
     */
    public function createDocumentReport(): View
    {
        return view('reports.generate-document');
    }

    /**
     * Generate document report
     */
    public function storeDocumentReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'document_type' => 'nullable|in:masuk,keluar',
            'classification' => 'nullable|in:biasa,rahasia,telegram',
        ]);

        $report = $this->reportService->generateDocumentReport(
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end']),
            $validated['document_type'] ?? null,
            $validated['classification'] ?? null
        );

        return redirect()->route('reports.show', $report)
            ->with('success', 'Laporan dokumen berhasil dibuat!');
    }

    /**
     * Export report to PDF
     */
    public function exportPdf(Report $report)
    {
        // TODO: Implement PDF export using DomPDF or similar
        return response()->download("reports/{$report->id}.pdf");
    }

    /**
     * Export report to Excel
     */
    public function exportExcel(Report $report)
    {
        // TODO: Implement Excel export using Maatwebsite Excel
        return response()->download("reports/{$report->id}.xlsx");
    }

    /**
     * Delete report
     */
    public function destroy(Report $report): RedirectResponse
    {
        $this->reportService->deleteReport($report->id);

        return redirect()->route('reports.index')
            ->with('success', 'Laporan berhasil dihapus!');
    }
}
