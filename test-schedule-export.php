<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Report;
use App\Services\ReportExportService;

$report = Report::where('type', 'schedule')->first();

if ($report) {
    echo "Report ID: {$report->id}\n";
    echo "Type: {$report->type}\n";
    echo "Data keys: " . implode(', ', array_keys($report->data)) . "\n\n";
    
    $exportService = new ReportExportService();
    
    try {
        $html = $exportService->generatePdfContent($report);
        echo "✓ PDF generated successfully!\n";
        echo "HTML length: " . strlen($html) . " chars\n\n";
        
        $excelFile = $exportService->generateExcel($report);
        echo "✓ Excel generated successfully!\n";
        echo "Excel file: {$excelFile}\n";
        
        if (file_exists($excelFile)) {
            echo "File size: " . filesize($excelFile) . " bytes\n";
        }
        
    } catch (\Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "No schedule report found\n";
}
