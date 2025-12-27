<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST PEMBUATAN LAPORAN DENGAN FORMAT BARU ===\n\n";

// Test 1: Buat Laporan Dokumen
echo "1. Membuat Laporan Dokumen...\n";
$reportService = app(\App\Services\ReportService::class);

$documentReport = $reportService->generateDocumentReport(
    \Carbon\Carbon::parse('2025-12-01'),
    \Carbon\Carbon::parse('2025-12-31')
);

echo "   ✓ Laporan Dokumen dibuat: {$documentReport->name}\n";
echo "   - Total Dokumen: {$documentReport->data['total_documents']}\n";
echo "   - Approval Rate: {$documentReport->data['approval_rate']}%\n";
echo "   - Klasifikasi Biasa: {$documentReport->data['klasifikasi']['biasa']['count']} ({$documentReport->data['klasifikasi']['biasa']['percent']}%)\n";
echo "\n";

// Test 2: Buat Laporan Efektivitas
echo "2. Membuat Laporan Efektivitas...\n";
$effectivenessReport = $reportService->generateEffectivenessReport(
    \Carbon\Carbon::parse('2025-12-01'),
    \Carbon\Carbon::parse('2025-12-31')
);

echo "   ✓ Laporan Efektivitas dibuat: {$effectivenessReport->name}\n";
echo "   - Total Jadwal: {$effectivenessReport->data['total_schedules']}\n";
echo "   - Tingkat Efektivitas: {$effectivenessReport->data['effectiveness_rate']}%\n";
echo "   - Status: {$effectivenessReport->data['status_evaluasi']['message']}\n";
echo "\n";

// Test 3: Export PDF
echo "3. Testing Export PDF...\n";
$exportService = app(\App\Services\ReportExportService::class);

$pdfContent = $exportService->generatePdfContent($documentReport);
echo "   ✓ PDF Content generated (length: " . strlen($pdfContent) . " bytes)\n";
echo "\n";

// Test 4: Export Excel
echo "4. Testing Export Excel...\n";
try {
    $excelFile = $exportService->generateExcel($documentReport);
    if (file_exists($excelFile)) {
        $fileSize = filesize($excelFile);
        echo "   ✓ Excel file generated: $excelFile (size: $fileSize bytes)\n";
        unlink($excelFile); // Cleanup
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== SEMUA TEST SELESAI ===\n";
