<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ReportService;
use Carbon\Carbon;

// Buat instance service
$reportService = app(ReportService::class);

echo "=== TEST REPORT GENERATION ===\n\n";

// Test 1: Laporan Jadwal
echo "1. Testing Schedule Report Generation...\n";
try {
    $startDate = Carbon::now()->startOfMonth();
    $endDate = Carbon::now()->endOfMonth();
    
    $scheduleReport = $reportService->generateScheduleReport($startDate, $endDate);
    
    echo "   ✓ Schedule Report ID: {$scheduleReport->id}\n";
    echo "   ✓ Name: {$scheduleReport->name}\n";
    echo "   ✓ Type: {$scheduleReport->type}\n";
    echo "   ✓ Status: {$scheduleReport->status}\n";
    echo "   ✓ Data Keys: " . implode(', ', array_keys($scheduleReport->data)) . "\n";
    
    if (!empty($scheduleReport->data)) {
        echo "\n   Data Preview:\n";
        foreach ($scheduleReport->data as $key => $value) {
            if (!is_array($value) && !is_object($value)) {
                echo "   - $key: $value\n";
            }
        }
    }
    
    echo "\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Laporan Dokumen
echo "2. Testing Document Report Generation...\n";
try {
    $startDate = Carbon::now()->startOfMonth();
    $endDate = Carbon::now()->endOfMonth();
    
    $documentReport = $reportService->generateDocumentReport($startDate, $endDate);
    
    echo "   ✓ Document Report ID: {$documentReport->id}\n";
    echo "   ✓ Name: {$documentReport->name}\n";
    echo "   ✓ Type: {$documentReport->type}\n";
    echo "   ✓ Status: {$documentReport->status}\n";
    echo "   ✓ Data Keys: " . implode(', ', array_keys($documentReport->data)) . "\n";
    
    if (!empty($documentReport->data)) {
        echo "\n   Data Preview:\n";
        foreach ($documentReport->data as $key => $value) {
            if (!is_array($value) && !is_object($value)) {
                echo "   - $key: $value\n";
            }
        }
    }
    
    echo "\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Laporan Efektivitas
echo "3. Testing Effectiveness Report Generation...\n";
try {
    $startDate = Carbon::now()->startOfMonth();
    $endDate = Carbon::now()->endOfMonth();
    
    $effectivenessReport = $reportService->generateEffectivenessReport($startDate, $endDate);
    
    echo "   ✓ Effectiveness Report ID: {$effectivenessReport->id}\n";
    echo "   ✓ Name: {$effectivenessReport->name}\n";
    echo "   ✓ Type: {$effectivenessReport->type}\n";
    echo "   ✓ Status: {$effectivenessReport->status}\n";
    echo "   ✓ Data Keys: " . implode(', ', array_keys($effectivenessReport->data)) . "\n";
    
    if (!empty($effectivenessReport->data)) {
        echo "\n   Data Preview:\n";
        foreach ($effectivenessReport->data as $key => $value) {
            if (!is_array($value) && !is_object($value)) {
                echo "   - $key: $value\n";
            }
        }
    }
    
    echo "\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

echo "\n=== TEST COMPLETED ===\n";
echo "\nTotal Reports: " . App\Models\Report::count() . "\n";
