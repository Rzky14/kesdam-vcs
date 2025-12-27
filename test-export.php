<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Report;
use App\Http\Controllers\ReportController;
use App\Services\ReportService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;

echo "=== TEST EXPORT GENERATION ===\n\n";

// Ambil report terakhir
$report = Report::latest()->first();

if (!$report) {
    echo "No reports found. Please generate a report first.\n";
    exit(1);
}

echo "Testing with Report ID: {$report->id}\n";
echo "Report Name: {$report->name}\n";
echo "Report Type: {$report->type}\n\n";

// Create controller instance
$reportService = new ReportService();
$exportService = new ReportExportService();
$controller = new ReportController($reportService, $exportService);

// Test PDF Export
echo "1. Testing PDF Export...\n";
try {
    $response = $controller->exportPdf($report);
    
    if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
        echo "   ✓ PDF generated successfully\n";
        echo "   ✓ Content Type: " . $response->headers->get('Content-Type') . "\n";
        echo "   ✓ Content Length: " . strlen($response->getContent()) . " bytes\n";
        
        // Save to file for manual inspection
        $pdfPath = __DIR__ . '/storage/test_report.pdf';
        file_put_contents($pdfPath, $response->getContent());
        echo "   ✓ Saved to: $pdfPath\n";
    }
    
    echo "\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

// Test Excel Export
echo "2. Testing Excel Export...\n";
try {
    $response = $controller->exportExcel($report);
    
    if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
        echo "   ✓ Excel generated successfully\n";
        echo "   ✓ File: " . $response->getFile()->getPathname() . "\n";
        echo "   ✓ Size: " . $response->getFile()->getSize() . " bytes\n";
        
        // Copy to storage for manual inspection
        $excelPath = __DIR__ . '/storage/test_report.xlsx';
        copy($response->getFile()->getPathname(), $excelPath);
        echo "   ✓ Saved to: $excelPath\n";
    }
    
    echo "\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

echo "=== TEST COMPLETED ===\n";
echo "\nPlease check the generated files:\n";
echo "- storage/test_report.pdf\n";
echo "- storage/test_report.xlsx\n";
