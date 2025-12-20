<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ReportService;
use App\Http\Requests\StoreScheduleReportRequest;
use App\Http\Requests\StoreDocumentReportRequest;
use App\Http\Requests\StoreEffectivenessReportRequest;
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
    public function storeScheduleReport(StoreScheduleReportRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $report = $this->reportService->generateScheduleReport(
                Carbon::parse($validated['period_start']),
                Carbon::parse($validated['period_end']),
                $validated['schedule_types'] ?? null
            );

            return redirect()->route('reports.show', $report)
                ->with('success', '✓ Laporan jadwal berhasil dibuat!');
        } catch (\Exception $e) {
            return back()
                ->with('error', '✗ Gagal membuat laporan: ' . $e->getMessage())
                ->withInput();
        }
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
    public function storeDocumentReport(StoreDocumentReportRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $report = $this->reportService->generateDocumentReport(
                Carbon::parse($validated['period_start']),
                Carbon::parse($validated['period_end']),
                $validated['classifications'] ?? null,
                $validated['document_status'] ?? null
            );

            return redirect()->route('reports.show', $report)
                ->with('success', '✓ Laporan dokumen berhasil dibuat!');
        } catch (\Exception $e) {
            return back()
                ->with('error', '✗ Gagal membuat laporan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form to generate effectiveness report
     */
    public function createEffectivenessReport(): View
    {
        return view('reports.generate-effectiveness');
    }

    /**
     * Generate schedule effectiveness report
     */
    public function storeEffectivenessReport(StoreEffectivenessReportRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $report = $this->reportService->generateEffectivenessReport(
                Carbon::parse($validated['period_start']),
                Carbon::parse($validated['period_end']),
                $validated['schedule_type'] ?? null
            );

            return redirect()->route('reports.show', $report)
                ->with('success', '✓ Laporan efektivitas jadwal berhasil dibuat!');
        } catch (\Exception $e) {
            return back()
                ->with('error', '✗ Gagal membuat laporan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Export report to PDF
     */
    public function exportPdf(Report $report): \Symfony\Component\HttpFoundation\Response
    {
        try {
            // Prepare PDF content
            $content = $this->generatePdfContent($report);
            
            // Return as downloadable file
            return response($content, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $this->sanitizeFileName($report->name) . '.pdf"');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal export PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export report to Excel
     */
    public function exportExcel(Report $report): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        try {
            // Generate Excel content
            $csv = $this->generateExcelContent($report);
            
            // Return as CSV (Excel compatible)
            return response()->streamDownload(function () use ($csv) {
                echo $csv;
            }, $this->sanitizeFileName($report->name) . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $this->sanitizeFileName($report->name) . '.csv"',
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF content from report
     */
    private function generatePdfContent(Report $report): string
    {
        $creatorName = $report->generatedBy?->name ?? 'System';
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$report->name}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { margin-bottom: 30px; }
        .info { color: #666; font-size: 12px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{$report->name}</h1>
        <div class="info">
            <p><strong>Tipe:</strong> {$report->type}</p>
            <p><strong>Periode:</strong> {$report->period_start->format('d-m-Y')} s/d {$report->period_end->format('d-m-Y')}</p>
            <p><strong>Dibuat:</strong> {$report->created_at->format('d-m-Y H:i')}</p>
            <p><strong>Dibuat oleh:</strong> {$creatorName}</p>
        </div>
    </div>
    
    <h3>Ringkasan Data</h3>
    <table>
        <tr>
            <th>Keterangan</th>
            <th>Nilai</th>
        </tr>
HTML;

        // Add data rows
        foreach ($report->data as $key => $value) {
            if (!is_array($value)) {
                $html .= "<tr><td>{$key}</td><td>{$value}</td></tr>";
            }
        }

        $html .= <<<HTML
    </table>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Generate Excel (CSV) content from report
     */
    private function generateExcelContent(Report $report): string
    {
        $creatorName = $report->creator?->name ?? 'System';
        
        $csv = "LAPORAN,{$report->name}\n";
        $csv .= "Periode,{$report->period_start->format('d-m-Y')} s/d {$report->period_end->format('d-m-Y')}\n";
        $csv .= "Tipe,{$report->type}\n";
        $csv .= "Dibuat,{$report->created_at->format('d-m-Y H:i')}\n";
        $csv .= "Dibuat oleh,{$creatorName}\n\n";
        
        $csv .= "Data:\n";
        $csv .= "Keterangan,Nilai\n";

        // Add data
        foreach ($report->data as $key => $value) {
            if (!is_array($value)) {
                $csv .= "\"{$key}\",\"{$value}\"\n";
            }
        }

        return $csv;
    }

    /**
     * Sanitize filename for download
     */
    private function sanitizeFileName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9-_]/', '_', $name);
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
