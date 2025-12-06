<?php

/**
 * Script untuk testing Reporting & Archive - Phase 7
 * Menguji fungsionalitas reporting dan archive secara komprehensif
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Report;
use App\Models\Archive;
use App\Services\ReportService;
use App\Services\ArchiveService;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "========================================\n";
echo " KESDAM VCS - PHASE 7 TESTING\n";
echo " Reporting & Archive Verification\n";
echo "========================================\n\n";

$errors = [];
$passed = 0;
$total = 0;

// Test 1: Check Database Tables
echo "Test 1: Checking Database Tables...\n";
$total++;
$tables = ['reports', 'archives'];

$allTablesExist = true;
foreach ($tables as $table) {
    $exists = DB::select("SHOW TABLES LIKE '$table'");
    if (empty($exists)) {
        $errors[] = "Table '$table' does not exist";
        $allTablesExist = false;
    } else {
        echo "  ✓ Table '$table' exists\n";
    }
}

if ($allTablesExist) {
    $passed++;
    echo "  ✅ All reporting & archive tables exist\n\n";
} else {
    echo "  ❌ Some tables are missing\n\n";
}

// Test 2: Check Models
echo "Test 2: Checking Models...\n";
$total++;

try {
    $reportModel = new Report();
    echo "  ✓ Report model exists\n";
    
    $archiveModel = new Archive();
    echo "  ✓ Archive model exists\n";
    
    // Check fillable
    $reportFillable = $reportModel->getFillable();
    if (count($reportFillable) > 0) {
        echo "  ✓ Report model has fillable fields: " . count($reportFillable) . "\n";
    }
    
    $archiveFillable = $archiveModel->getFillable();
    if (count($archiveFillable) > 0) {
        echo "  ✓ Archive model has fillable fields: " . count($archiveFillable) . "\n";
    }
    
    $passed++;
    echo "  ✅ Models functional\n\n";
} catch (\Exception $e) {
    $errors[] = "Model error: " . $e->getMessage();
    echo "  ❌ Model check failed: {$e->getMessage()}\n\n";
}

// Test 3: Check Services
echo "Test 3: Checking Services...\n";
$total++;

try {
    $reportService = app(ReportService::class);
    echo "  ✓ ReportService injectable\n";
    
    $reflection = new \ReflectionClass($reportService);
    $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
    $methodNames = array_map(fn($m) => $m->getName(), $methods);
    
    $requiredMethods = ['generateScheduleReport', 'generateDocumentReport', 'getReports'];
    $methodsExist = true;
    foreach ($requiredMethods as $method) {
        if (in_array($method, $methodNames)) {
            echo "  ✓ ReportService::{$method}() exists\n";
        } else {
            $errors[] = "ReportService::{$method}() not found";
            $methodsExist = false;
        }
    }
    
    if (class_exists('App\Services\ArchiveService')) {
        $archiveService = app(ArchiveService::class);
        echo "  ✓ ArchiveService injectable\n";
    }
    
    if ($methodsExist) {
        $passed++;
        echo "  ✅ Services functional\n\n";
    } else {
        echo "  ❌ Some methods missing\n\n";
    }
} catch (\Exception $e) {
    $errors[] = "Service error: " . $e->getMessage();
    echo "  ❌ Service check failed: {$e->getMessage()}\n\n";
}

// Test 4: Check Controllers
echo "Test 4: Checking Controllers...\n";
$total++;

$controllersOk = true;
if (class_exists('App\Http\Controllers\ReportController')) {
    echo "  ✓ ReportController exists\n";
    
    $reflection = new \ReflectionClass('App\Http\Controllers\ReportController');
    $methods = array_map(fn($m) => $m->getName(), $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));
    
    $requiredMethods = ['index', 'show', 'createScheduleReport', 'storeScheduleReport', 'createDocumentReport', 'storeDocumentReport'];
    foreach ($requiredMethods as $method) {
        if (in_array($method, $methods)) {
            echo "  ✓ ReportController::{$method}() exists\n";
        } else {
            $errors[] = "ReportController::{$method}() not found";
            $controllersOk = false;
        }
    }
} else {
    $errors[] = "ReportController not found";
    $controllersOk = false;
}

if (class_exists('App\Http\Controllers\ArchiveController')) {
    echo "  ✓ ArchiveController exists\n";
} else {
    $errors[] = "ArchiveController not found";
    $controllersOk = false;
}

if ($controllersOk) {
    $passed++;
    echo "  ✅ Controllers complete\n\n";
} else {
    echo "  ❌ Some controllers or methods missing\n\n";
}

// Test 5: Check Routes
echo "Test 5: Checking Routes...\n";
$total++;

$routes = collect(\Illuminate\Support\Facades\Route::getRoutes());
$reportRoutes = $routes->filter(fn($r) => str_contains($r->getName() ?? '', 'reports.'));
$archiveRoutes = $routes->filter(fn($r) => str_contains($r->getName() ?? '', 'archives.'));

echo "  Report routes: {$reportRoutes->count()}\n";
echo "  Archive routes: {$archiveRoutes->count()}\n";

if ($reportRoutes->count() >= 5 && $archiveRoutes->count() >= 4) {
    $passed++;
    echo "  ✅ Routes registered\n\n";
} else {
    $errors[] = "Insufficient routes (reports: {$reportRoutes->count()}, archives: {$archiveRoutes->count()})";
    echo "  ❌ Insufficient routes\n\n";
}

// Test 6: Check Views
echo "Test 6: Checking Views...\n";
$total++;

$views = [
    'reports.index',
    'reports.show',
    'reports.generate-schedule',
    'reports.generate-document',
    'archives.index',
    'archives.show',
    'archives.search',
    'archives.statistics',
];

$allViewsExist = true;
foreach ($views as $view) {
    if (view()->exists($view)) {
        echo "  ✓ View '$view' exists\n";
    } else {
        $errors[] = "View '$view' not found";
        $allViewsExist = false;
    }
}

if ($allViewsExist) {
    $passed++;
    echo "  ✅ All views exist\n\n";
} else {
    echo "  ❌ Some views missing\n\n";
}

// Test 7: Check Factory Classes  
echo "Test 7: Checking Factory Classes...\n";
$total++;

try {
    if (class_exists('Database\Factories\ReportFactory')) {
        $testReport = \App\Models\Report::factory()->make();
        echo "  ✓ ReportFactory works\n";
        $factoryOk = true;
    } else {
        echo "  ⚠️  ReportFactory not found (optional)\n";
        $factoryOk = false;
    }
    
    if (class_exists('Database\Factories\ArchiveFactory')) {
        $testArchive = \App\Models\Archive::factory()->make();
        echo "  ✓ ArchiveFactory works\n";
    } else {
        echo "  ⚠️  ArchiveFactory not found (optional)\n";
        $factoryOk = false;
    }
    
    if ($factoryOk) {
        $passed++;
        echo "  ✅ Factories functional\n\n";
    } else {
        echo "  ⚠️  Factories missing (will create)\n\n";
    }
} catch (\Exception $e) {
    echo "  ⚠️  Factory error: {$e->getMessage()} (will create)\n\n";
}

// Test 8: Check Tests
echo "Test 8: Checking Test Files...\n";
$total++;

$testFiles = [
    'tests/Feature/ReportingTest.php',
    'tests/Feature/ArchiveTest.php',
];

$testsExist = false;
foreach ($testFiles as $testFile) {
    if (file_exists($testFile)) {
        echo "  ✓ Test file '$testFile' exists\n";
        $testsExist = true;
    } else {
        echo "  ⚠️  Test file '$testFile' not found (will create)\n";
    }
}

if ($testsExist) {
    $passed++;
    echo "  ✅ Some tests exist\n\n";
} else {
    echo "  ⚠️  No tests found (will create)\n\n";
}

// Final Report
echo "========================================\n";
echo " TEST SUMMARY\n";
echo "========================================\n";
echo "Total Tests: $total\n";
echo "Passed: $passed\n";
echo "Failed: " . ($total - $passed) . "\n";
$percentage = round(($passed / $total) * 100, 2);
echo "Success Rate: {$percentage}%\n";

if ($percentage === 100.0) {
    echo "\n🎉 ALL TESTS PASSED! Phase 7 is complete!\n";
} elseif ($percentage >= 75) {
    echo "\n✅ MOSTLY PASSED! Phase 7 is functional, needs minor additions.\n";
} else {
    echo "\n⚠️  NEEDS WORK! Phase 7 requires more implementation.\n";
}

if (!empty($errors)) {
    echo "\n❌ ERRORS FOUND:\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". $error\n";
    }
}

echo "\n========================================\n";
echo " Phase 7: Reporting & Archive - ";
if ($percentage >= 80) {
    echo "✅ GOOD\n";
} elseif ($percentage >= 60) {
    echo "⚠️ NEEDS WORK\n";
} else {
    echo "❌ INCOMPLETE\n";
}
echo "========================================\n\n";

echo "MISSING ITEMS TO COMPLETE 100%:\n";
echo "  1. Factory classes (ReportFactory, ArchiveFactory)\n";
echo "  2. Feature tests (ReportingTest, ArchiveTest)\n";
echo "  3. PDF/Excel export implementation\n";
echo "  4. Seeder for sample reports/archives\n\n";
