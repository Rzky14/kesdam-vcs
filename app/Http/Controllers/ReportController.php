<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ReportService;
use App\Services\ReportExportService;
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
    public function __construct(
        private ReportService $reportService,
        private ReportExportService $exportService
    ) {
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
            
            // Generate HTML content menggunakan ReportExportService
            $html = $this->exportService->generatePdfContent($report);
            
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
     * Ekspor laporan ke Excel (XLSX).
     */
    public function exportExcel(Report $report): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        try {
            // Load relasi yang dibutuhkan
            $report->load('generatedBy');
            
            // Generate Excel menggunakan ReportExportService
            $tempFile = $this->exportService->generateExcel($report);
            
            // Return as download
            $filename = $this->sanitizeFileName($report->name) . '_' . date('YmdHis') . '.xlsx';
            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
            
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
        @page { margin: 15mm; }
        body { 
            font-family: 'Arial', sans-serif; 
            margin: 0;
            padding: 20px;
            font-size: 11pt;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #1a472a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 { 
            color: #1a472a; 
            margin: 0 0 5px 0;
            font-size: 18pt;
            font-weight: bold;
        }
        .header .logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 10px;
        }
        .info-box {
            background: #f5f5f5;
            border-left: 4px solid #1a472a;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .info-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-box td {
            padding: 4px 8px;
            font-size: 10pt;
        }
        .info-box td:first-child {
            font-weight: bold;
            width: 140px;
            color: #555;
        }
        h2 { 
            color: #1a472a; 
            font-size: 14pt;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 8px;
            margin: 25px 0 15px 0;
        }
        table.data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px;
            font-size: 10pt;
        }
        table.data-table th, 
        table.data-table td { 
            border: 1px solid #ddd; 
            padding: 10px 12px; 
            text-align: left; 
        }
        table.data-table th { 
            background-color: #1a472a; 
            color: white;
            font-weight: bold;
            text-align: center;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table.data-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
            font-size: 9pt;
            color: #777;
            text-align: center;
        }
        .stat-highlight {
            background: #fff9e6;
            font-weight: bold;
            color: #d4af37;
        }
        .empty-message {
            text-align: center;
            padding: 30px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>KESDAM III/SILIWANGI</h1>
        <div style="font-size: 10pt; color: #666;">Sistem Manajemen Penjadwalan & Surat</div>
    </div>
    
    <h1 style="text-align: center; color: #1a472a; margin: 20px 0;">{$report->name}</h1>
    
    <div class="info-box">
        <table>
            <tr>
                <td>Tipe Laporan</td>
                <td>: {$this->getTypeLabel($report->type)}</td>
            </tr>
            <tr>
                <td>Periode</td>
                <td>: {$periodStart} s/d {$periodEnd}</td>
            </tr>
            <tr>
                <td>Dibuat</td>
                <td>: {$createdAt}</td>
            </tr>
            <tr>
                <td>Dibuat oleh</td>
                <td>: {$creatorName}</td>
            </tr>
        </table>
    </div>
    
    <h2>Ringkasan Data</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 70%;">Keterangan</th>
                <th style="width: 30%;">Nilai</th>
            </tr>
        </thead>
        <tbody>
HTML;

        // Tambahkan data yang lebih terstruktur
        if (!empty($report->data) && is_array($report->data)) {
            $hasData = false;
            foreach ($report->data as $key => $value) {
                if (!is_array($value) && !is_object($value)) {
                    $hasData = true;
                    $displayValue = $this->formatValue($value);
                    $displayKey = $this->formatKey($key);
                    $highlightClass = strpos($key, 'total') !== false || strpos($key, 'rate') !== false ? ' class="stat-highlight"' : '';
                    $html .= "<tr><td>{$displayKey}</td><td{$highlightClass}>{$displayValue}</td></tr>";
                }
            }
            
            if (!$hasData) {
                $html .= "<tr><td colspan='2' class='empty-message'>Tidak ada data tersedia</td></tr>";
            }
        } else {
            $html .= "<tr><td colspan='2' class='empty-message'>Tidak ada data tersedia</td></tr>";
        }

        $html .= <<<HTML
        </tbody>
    </table>
    
    <div class="footer">
        <p>Dokumen ini dihasilkan secara otomatis oleh sistem KESDAM III/SILIWANGI</p>
        <p>Dicetak pada: {$createdAt}</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
    
    /**
     * Format key menjadi label yang lebih readable
     */
    private function formatKey(string $key): string
    {
        $labels = [
            'total_schedules' => 'Total Jadwal',
            'total_documents' => 'Total Dokumen',
            'approval_rate' => 'Tingkat Persetujuan',
            'pending_count' => 'Menunggu Persetujuan',
            'approved_count' => 'Disetujui',
            'rejected_count' => 'Ditolak',
            'average_duration' => 'Rata-rata Durasi',
            'effectiveness_rate' => 'Tingkat Efektivitas',
            'completed_schedules' => 'Jadwal Selesai',
            'active_schedules' => 'Jadwal Aktif',
            'cancelled_schedules' => 'Jadwal Dibatalkan',
        ];
        
        return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
    }
    
    /**
     * Format value untuk display
     */
    private function formatValue($value): string
    {
        if (is_numeric($value) && strpos((string)$value, '.') !== false) {
            // Jika angka desimal, format sebagai persentase atau angka desimal
            if ($value <= 1 && $value >= 0) {
                return round($value * 100, 2) . '%';
            }
            return number_format($value, 2);
        }
        
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get type label
     */
    private function getTypeLabel(string $type): string
    {
        $labels = [
            'schedule' => 'Laporan Jadwal',
            'document' => 'Laporan Dokumen',
            'effectiveness' => 'Laporan Efektivitas',
        ];
        
        return $labels[$type] ?? ucfirst($type);
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



