<?php

namespace App\Services;

use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportExportService
{
    /**
     * Generate PDF content berdasarkan tipe laporan
     */
    public function generatePdfContent(Report $report): string
    {
        $method = 'generate' . ucfirst($report->type) . 'PdfContent';
        
        if (method_exists($this, $method)) {
            return $this->$method($report);
        }
        
        return $this->generateGenericPdfContent($report);
    }
    
    /**
     * Generate Excel berdasarkan tipe laporan
     */
    public function generateExcel(Report $report): string
    {
        $method = 'generate' . ucfirst($report->type) . 'Excel';
        
        if (method_exists($this, $method)) {
            return $this->$method($report);
        }
        
        return $this->generateGenericExcel($report);
    }
    
    /**
     * PDF untuk Laporan Dokumen
     */
    private function generateDocumentPdfContent(Report $report): string
    {
        $creatorName = $report->generatedBy?->name ?? 'Sistem';
        $periodStart = $report->period_start ? $report->period_start->format('d-m-Y') : '-';
        $periodEnd = $report->period_end ? $report->period_end->format('d-m-Y') : '-';
        $data = $report->data;
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 15mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.4; }
        .header { text-align: center; border-bottom: 3px solid #1a472a; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16pt; color: #1a472a; }
        .header h2 { margin: 5px 0; font-size: 14pt; color: #1a472a; }
        .header .subtitle { font-size: 9pt; color: #666; }
        .info-box { background: #f5f5f5; border-left: 4px solid #1a472a; padding: 10px; margin: 15px 0; }
        .info-box table { width: 100%; border-collapse: collapse; }
        .info-box td { padding: 3px 5px; }
        .info-box td:first-child { font-weight: bold; width: 120px; }
        .section-title { font-size: 11pt; font-weight: bold; color: #1a472a; margin: 20px 0 10px 0; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px; }
        table.data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.data-table th { background-color: #1a472a; color: white; font-weight: bold; }
        table.data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .highlight { background-color: #FFF9E6; font-weight: bold; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 2px solid #e0e0e0; font-size: 8pt; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>KESDAM III/SILIWANGI</h1>
        <div class="subtitle">Sistem Manajemen Penjadwalan & Surat</div>
        <h2>Laporan Dokumen - Dec 2025</h2>
    </div>
    
    <div class="info-box">
        <table>
            <tr><td>Periode</td><td>: {$periodStart} s/d {$periodEnd}</td></tr>
        </table>
    </div>
    
    <div class="section-title">RINGKASAN:</div>
    <table class="data-table">
        <tr><td style="width: 70%;">Total Dokumen</td><td class="highlight" style="text-align: right;">: {$data['total_documents']}</td></tr>
        <tr><td>Tingkat Persetujuan</td><td style="text-align: right;">: {$data['approval_rate']}%</td></tr>
        <tr><td>Menunggu Persetujuan</td><td style="text-align: right;">: {$data['pending_count']}</td></tr>
        <tr><td>Disetujui</td><td style="text-align: right;">: {$data['approved_count']}</td></tr>
        <tr><td>Ditolak</td><td style="text-align: right;">: {$data['rejected_count']}</td></tr>
    </table>
    
    <div class="section-title">DISTRIBUSI PER KLASIFIKASI:</div>
    <table class="data-table">
        <tr><td style="width: 70%;">Biasa</td><td style="text-align: right;">: {$data['klasifikasi']['biasa']['count']} ({$data['klasifikasi']['biasa']['percent']}%)</td></tr>
        <tr><td>Rahasia</td><td style="text-align: right;">: {$data['klasifikasi']['rahasia']['count']} ({$data['klasifikasi']['rahasia']['percent']}%)</td></tr>
        <tr><td>Telegram</td><td style="text-align: right;">: {$data['klasifikasi']['telegram']['count']} ({$data['klasifikasi']['telegram']['percent']}%)</td></tr>
    </table>
    
    <div class="section-title">DISTRIBUSI PER TIPE:</div>
    <table class="data-table">
        <tr><td style="width: 70%;">Surat Masuk</td><td style="text-align: right;">: {$data['tipe']['surat_masuk']['count']} ({$data['tipe']['surat_masuk']['percent']}%)</td></tr>
        <tr><td>Surat Keluar</td><td style="text-align: right;">: {$data['tipe']['surat_keluar']['count']} ({$data['tipe']['surat_keluar']['percent']}%)</td></tr>
    </table>
    
    <div class="section-title">KINERJA APPROVAL:</div>
    <table class="data-table">
        <tr><td style="width: 70%;">Rata-rata waktu</td><td style="text-align: right;">: {$data['kinerja_approval']['rata_rata_waktu']} hari</td></tr>
        <tr><td>Tercepat</td><td style="text-align: right;">: {$data['kinerja_approval']['tercepat']} hari</td></tr>
        <tr><td>Terlama</td><td style="text-align: right;">: {$data['kinerja_approval']['terlama']} hari</td></tr>
    </table>
    
    <div class="footer">
        <p>Dokumen ini dihasilkan secara otomatis oleh sistem KESDAM III/SILIWANGI</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
    
    /**
     * PDF untuk Laporan Jadwal
     */
    private function generateSchedulePdfContent(Report $report): string
    {
        $creatorName = $report->generatedBy?->name ?? 'Sistem';
        $periodStart = $report->period_start ? $report->period_start->format('d-m-Y') : '-';
        $periodEnd = $report->period_end ? $report->period_end->format('d-m-Y') : '-';
        $data = $report->data;
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 15mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.4; }
        .header { text-align: center; border-bottom: 3px solid #1a472a; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16pt; color: #1a472a; }
        .header h2 { margin: 5px 0; font-size: 14pt; color: #1a472a; }
        .header .subtitle { font-size: 9pt; color: #666; }
        .info-box { background: #f5f5f5; border-left: 4px solid #1a472a; padding: 10px; margin: 15px 0; }
        .info-box table { width: 100%; border-collapse: collapse; }
        .info-box td { padding: 3px 5px; }
        .info-box td:first-child { font-weight: bold; width: 120px; }
        .section-title { font-size: 11pt; font-weight: bold; color: #1a472a; margin: 20px 0 10px 0; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px; }
        table.data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.data-table th { background-color: #1a472a; color: white; font-weight: bold; }
        table.data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .highlight { background-color: #FFF9E6; font-weight: bold; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 2px solid #e0e0e0; font-size: 8pt; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>KESDAM III/SILIWANGI</h1>
        <div class="subtitle">Sistem Manajemen Penjadwalan & Surat</div>
        <h2>Laporan Jadwal - Dec 2025</h2>
    </div>
    
    <div class="info-box">
        <table>
            <tr><td>Periode</td><td>: {$periodStart} s/d {$periodEnd}</td></tr>
        </table>
    </div>
    
    <div class="section-title">RINGKASAN:</div>
    <table class="data-table">
        <tr><td style="width: 70%;">Total Jadwal</td><td class="highlight" style="text-align: right;">: {$data['total_schedules']}</td></tr>
        <tr><td>Rata-rata Durasi</td><td style="text-align: right;">: {$data['average_duration']} hari</td></tr>
    </table>
    
    <div class="footer">
        <p>Dokumen ini dihasilkan secara otomatis oleh sistem KESDAM III/SILIWANGI</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
    
    /**
     * PDF untuk Laporan Efektivitas Jadwal
     */
    private function generateEffectivenessPdfContent(Report $report): string
    {
        $creatorName = $report->generatedBy?->name ?? 'Sistem';
        $periodStart = $report->period_start ? $report->period_start->format('d-m-Y') : '-';
        $periodEnd = $report->period_end ? $report->period_end->format('d-m-Y') : '-';
        $data = $report->data;
        
        // Status badge
        $statusBadge = '🔴';
        if ($data['status_evaluasi']['status'] === 'excellent') {
            $statusBadge = '🟢';
        } elseif ($data['status_evaluasi']['status'] === 'warning') {
            $statusBadge = '🟡';
        }
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 15mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.4; }
        .header { text-align: center; border-bottom: 3px solid #1a472a; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16pt; color: #1a472a; }
        .header h2 { margin: 5px 0; font-size: 14pt; color: #1a472a; }
        .header .subtitle { font-size: 9pt; color: #666; }
        .info-box { background: #f5f5f5; border-left: 4px solid #1a472a; padding: 10px; margin: 15px 0; }
        .info-box table { width: 100%; border-collapse: collapse; }
        .info-box td { padding: 3px 5px; }
        .info-box td:first-child { font-weight: bold; width: 120px; }
        .section-title { font-size: 11pt; font-weight: bold; color: #1a472a; margin: 20px 0 10px 0; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px; }
        table.data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.data-table th { background-color: #1a472a; color: white; font-weight: bold; text-align: center; }
        table.data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .highlight { background-color: #FFF9E6; font-weight: bold; }
        .status-box { background: #ffebee; border-left: 4px solid #f44336; padding: 10px; margin: 15px 0; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 2px solid #e0e0e0; font-size: 8pt; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>KESDAM III/SILIWANGI</h1>
        <div class="subtitle">Sistem Manajemen Penjadwalan & Surat</div>
        <h2>Laporan Efektivitas Jadwal - Dec 2025</h2>
    </div>
    
    <div class="info-box">
        <table>
            <tr><td>Periode</td><td>: {$periodStart} s/d {$periodEnd}</td></tr>
        </table>
    </div>
    
    <div class="section-title">RINGKASAN:</div>
    <table class="data-table">
        <tr><td style="width: 70%;">Total Jadwal</td><td class="highlight" style="text-align: right;">: {$data['total_schedules']}</td></tr>
        <tr><td>Jadwal Aktif</td><td style="text-align: right;">: {$data['active_schedules']}</td></tr>
        <tr><td>Rata-rata Durasi</td><td style="text-align: right;">: {$data['average_duration']} hari</td></tr>
        <tr><td>Tingkat Efektivitas</td><td class="highlight" style="text-align: right;">: {$data['effectiveness_rate']}%</td></tr>
        <tr><td>Jadwal Dibatalkan</td><td style="text-align: right;">: {$data['cancelled_schedules']}</td></tr>
        <tr><td>Jadwal Selesai</td><td style="text-align: right;">: {$data['completed_schedules']}</td></tr>
    </table>
    
    <div class="section-title">DISTRIBUSI PER JENIS:</div>
    <table class="data-table">
        <tr><td style="width: 70%;">Jadwal Dukkes</td><td style="text-align: right;">: {$data['distribusi_jenis']['jadwal_dukkes']['count']} ({$data['distribusi_jenis']['jadwal_dukkes']['percent']}%)</td></tr>
        <tr><td>Jadwal Jaga</td><td style="text-align: right;">: {$data['distribusi_jenis']['jadwal_jaga']['count']} ({$data['distribusi_jenis']['jadwal_jaga']['percent']}%)</td></tr>
        <tr><td>Kegiatan Satuan</td><td style="text-align: right;">: {$data['distribusi_jenis']['kegiatan_satuan']['count']} ({$data['distribusi_jenis']['kegiatan_satuan']['percent']}%)</td></tr>
    </table>
    
    <div class="section-title">KINERJA:</div>
    <table class="data-table">
        <thead>
            <tr><th>Metrik</th><th>Target</th><th>Aktual</th></tr>
        </thead>
        <tbody>
            <tr><td>On-time</td><td style="text-align: center;">≥{$data['kinerja']['on_time']['target']}%</td><td style="text-align: center;">{$data['kinerja']['on_time']['actual']}%</td></tr>
            <tr><td>Cancelled</td><td style="text-align: center;">≤{$data['kinerja']['cancelled']['target']}%</td><td style="text-align: center;">{$data['kinerja']['cancelled']['actual']}%</td></tr>
            <tr><td>Completed</td><td style="text-align: center;">≥{$data['kinerja']['completed']['target']}%</td><td style="text-align: center;">{$data['kinerja']['completed']['actual']}%</td></tr>
        </tbody>
    </table>
    
    <div class="status-box">
        <strong>STATUS: {$statusBadge} {$data['status_evaluasi']['message']}</strong>
    </div>
    
    <div class="footer">
        <p>Dokumen ini dihasilkan secara otomatis oleh sistem KESDAM III/SILIWANGI</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
    
    /**
     * Generic PDF Content
     */
    private function generateGenericPdfContent(Report $report): string
    {
        return $this->generateDocumentPdfContent($report);
    }
    
    /**
     * Excel untuk Laporan Jadwal
     */
    private function generateScheduleExcel(Report $report): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Jadwal');
        
        $data = $report->data;
        $periodStart = $report->period_start ? $report->period_start->format('d-m-Y') : '-';
        $periodEnd = $report->period_end ? $report->period_end->format('d-m-Y') : '-';
        
        // Headers
        $sheet->setCellValue('A1', 'LAPORAN JADWAL');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $sheet->setCellValue('A3', 'Periode: ' . $periodStart . ' s/d ' . $periodEnd);
        $sheet->mergeCells('A3:B3');
        
        // RINGKASAN
        $row = 5;
        $sheet->setCellValue("A$row", 'RINGKASAN:');
        $sheet->getStyle("A$row")->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue("A$row", '• Total Jadwal');
        $sheet->setCellValue("B$row", ': ' . ($data['total_schedules'] ?? 0));
        $row++;
        
        $sheet->setCellValue("A$row", '• Rata-rata Durasi');
        $sheet->setCellValue("B$row", ': ' . ($data['average_duration'] ?? 0) . ' hari');
        $row += 2;
        
        // Auto-size columns
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);
        
        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'report_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return $tempFile;
    }
    
    /**
     * Excel untuk Laporan Dokumen
     */
    private function generateDocumentExcel(Report $report): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Dokumen');
        
        $data = $report->data;
        $periodStart = $report->period_start ? $report->period_start->format('d-m-Y') : '-';
        $periodEnd = $report->period_end ? $report->period_end->format('d-m-Y') : '-';
        
        // Headers
        $sheet->setCellValue('A1', 'LAPORAN DOKUMEN');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $sheet->setCellValue('A3', 'Periode: ' . $periodStart . ' s/d ' . $periodEnd);
        $sheet->mergeCells('A3:B3');
        
        // RINGKASAN
        $row = 5;
        $sheet->setCellValue("A$row", 'RINGKASAN:');
        $sheet->getStyle("A$row")->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue("A$row", '• Total Dokumen');
        $sheet->setCellValue("B$row", ': ' . $data['total_documents']);
        $row++;
        
        $sheet->setCellValue("A$row", '• Tingkat Persetujuan');
        $sheet->setCellValue("B$row", ': ' . $data['approval_rate'] . '%');
        $row++;
        
        $sheet->setCellValue("A$row", '• Menunggu Persetujuan');
        $sheet->setCellValue("B$row", ': ' . $data['pending_count']);
        $row++;
        
        $sheet->setCellValue("A$row", '• Disetujui');
        $sheet->setCellValue("B$row", ': ' . $data['approved_count']);
        $row++;
        
        $sheet->setCellValue("A$row", '• Ditolak');
        $sheet->setCellValue("B$row", ': ' . $data['rejected_count']);
        $row += 2;
        
        // DISTRIBUSI PER KLASIFIKASI
        $sheet->setCellValue("A$row", 'DISTRIBUSI PER KLASIFIKASI:');
        $sheet->getStyle("A$row")->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue("A$row", '• Biasa');
        $sheet->setCellValue("B$row", ': ' . $data['klasifikasi']['biasa']['count'] . ' (' . $data['klasifikasi']['biasa']['percent'] . '%)');
        $row++;
        
        $sheet->setCellValue("A$row", '• Rahasia');
        $sheet->setCellValue("B$row", ': ' . $data['klasifikasi']['rahasia']['count'] . ' (' . $data['klasifikasi']['rahasia']['percent'] . '%)');
        $row++;
        
        $sheet->setCellValue("A$row", '• Telegram');
        $sheet->setCellValue("B$row", ': ' . $data['klasifikasi']['telegram']['count'] . ' (' . $data['klasifikasi']['telegram']['percent'] . '%)');
        $row += 2;
        
        // Auto-size columns
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);
        
        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'report_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return $tempFile;
    }
    
    /**
     * Excel untuk Laporan Efektivitas
     */
    private function generateEffectivenessExcel(Report $report): string
    {
        return $this->generateDocumentExcel($report);
    }
    
    /**
     * Generic Excel
     */
    private function generateGenericExcel(Report $report): string
    {
        return $this->generateDocumentExcel($report);
    }
}



