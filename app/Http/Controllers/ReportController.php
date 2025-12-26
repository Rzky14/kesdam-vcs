<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ReportService;
use App\Http\Requests\StoreScheduleReportRequest;
use App\Http\Requests\StoreDocumentReportRequest;
use App\Http\Requests\StoreEffectivenessReportRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    /**
     * Tampilkan dasbor laporan beserta statistik.
     */
    public function index(Request $request): View
    {
        // Ambil laporan terbaru
        $latestReports = Report::latest()
            ->take(10)
            ->get();
        
        // Statistik
        $totalReports = Report::count();
        $monthlyReports = Report::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        $completedReports = Report::where('status', 'completed')->count();
        $inProgressReports = Report::where('status', 'in_progress')->count();
        
        // Laporan terbaru per tipe
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
     * Tampilkan detail laporan.
     */
    public function show(Report $report): View
    {
        return view('reports.show', ['report' => $report]);
    }

    /**
     * Tampilkan formulir pembuatan laporan jadwal.
     */
    public function createScheduleReport(): View
    {
        return view('reports.generate-schedule');
    }

    /**
     * Proses pembuatan laporan jadwal.
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
     * Tampilkan formulir pembuatan laporan dokumen.
     */
    public function createDocumentReport(): View
    {
        return view('reports.generate-document');
    }

    /**
     * Proses pembuatan laporan dokumen.
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
     * Tampilkan formulir pembuatan laporan efektivitas.
     */
    public function createEffectivenessReport(): View
    {
        return view('reports.generate-effectiveness');
    }

    /**
     * Proses pembuatan laporan efektivitas jadwal.
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
     * Ekspor laporan ke PDF.
     */
    public function exportPdf(Report $report): \Symfony\Component\HttpFoundation\Response
    {
        try {
            // Load relasi yang dibutuhkan
            $report->load('generatedBy');
            
            // Generate HTML content
            $html = $this->generatePdfContent($report);
            
            // Generate PDF using DomPDF
            $pdf = Pdf::loadHTML($html);
            
            // Konfigurasi PDF
            $pdf->setPaper('A4', 'portrait');
            
            // Return as download
            $filename = $this->sanitizeFileName($report->name) . '_' . date('YmdHis') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Error exporting PDF: ' . $e->getMessage(), [
                'report_id' => $report->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Gagal export PDF: ' . $e->getMessage());
        }
    }

    /**
     * Ekspor laporan ke Excel (CSV).
     */
    public function exportExcel(Report $report): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        try {
            // Load relasi yang dibutuhkan
            $report->load('generatedBy');
            
            // Buat konten CSV kompatibel Excel
            $csv = $this->generateExcelContent($report);
            
            // Kembalikan sebagai CSV (kompatibel Excel)
            $filename = $this->sanitizeFileName($report->name) . '_' . date('YmdHis') . '.csv';
            
            return response()->streamDownload(function () use ($csv) {
                echo "\xEF\xBB\xBF"; // UTF-8 BOM untuk Excel
                echo $csv;
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('Error exporting Excel: ' . $e->getMessage(), [
                'report_id' => $report->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Gagal export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Bangun konten PDF dari laporan.
     */
    private function generatePdfContent(Report $report): string
    {
        $creatorName = $report->generatedBy?->name ?? 'Sistem';
        $periodStart = $report->period_start ? $report->period_start->format('d-m-Y') : '-';
        $periodEnd = $report->period_end ? $report->period_end->format('d-m-Y') : '-';
        $createdAt = $report->created_at ? $report->created_at->format('d-m-Y H:i') : '-';
        
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
            <p><strong>Periode:</strong> {$periodStart} s/d {$periodEnd}</p>
            <p><strong>Dibuat:</strong> {$createdAt}</p>
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

        // Tambahkan baris data ringkas
        if (!empty($report->data) && is_array($report->data)) {
            foreach ($report->data as $key => $value) {
                if (!is_array($value) && !is_object($value)) {
                    $displayValue = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                    $displayKey = htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8');
                    $html .= "<tr><td>{$displayKey}</td><td>{$displayValue}</td></tr>";
                }
            }
        } else {
            $html .= "<tr><td colspan='2'>Tidak ada data tersedia</td></tr>";
        }

        $html .= <<<HTML
    </table>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Bangun konten Excel (CSV) dari laporan.
     */
    private function generateExcelContent(Report $report): string
    {
        $creatorName = $report->generatedBy?->name ?? 'Sistem';
        $periodStart = $report->period_start ? $report->period_start->format('d-m-Y') : '-';
        $periodEnd = $report->period_end ? $report->period_end->format('d-m-Y') : '-';
        $createdAt = $report->created_at ? $report->created_at->format('d-m-Y H:i') : '-';
        
        $csv = "LAPORAN,{$report->name}\n";
        $csv .= "Periode,{$periodStart} s/d {$periodEnd}\n";
        $csv .= "Tipe,{$report->type}\n";
        $csv .= "Dibuat,{$createdAt}\n";
        $csv .= "Dibuat oleh,{$creatorName}\n\n";
        
        $csv .= "Data:\n";
        $csv .= "Keterangan,Nilai\n";

        // Tambahkan data ringkas
        if (!empty($report->data) && is_array($report->data)) {
            foreach ($report->data as $key => $value) {
                if (!is_array($value) && !is_object($value)) {
                    $csv .= "\"{$key}\",\"{$value}\"\n";
                }
            }
        } else {
            $csv .= "\"Tidak ada data\",\"-\"\n";
        }

        return $csv;
    }

    /**
     * Sanitasi nama berkas untuk unduhan.
     */
    private function sanitizeFileName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9-_]/', '_', $name);
    }

    /**
     * Hapus laporan.
     */
    public function destroy(Report $report): RedirectResponse
    {
        $this->reportService->deleteReport($report->id);

        return redirect()->route('reports.index')
            ->with('success', 'Laporan berhasil dihapus!');
    }
}
