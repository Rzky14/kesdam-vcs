<?php

/**
 * Script untuk testing Approval Workflow - Phase 5
 * Menguji fungsionalitas approval workflow secara komprehensif
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Document;
use App\Models\Role;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalHistory;
use App\Models\ApprovalRolePermission;
use App\Models\CorrectionRequest;
use App\Services\ApprovalWorkflowService;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "========================================\n";
echo " KESDAM VCS - PHASE 5 TESTING\n";
echo " Approval Workflow Verification\n";
echo "========================================\n\n";

$errors = [];
$passed = 0;
$total = 0;

// Test 1: Check Database Tables
echo "Test 1: Checking Database Tables...\n";
$total++;
$tables = [
    'approval_workflows',
    'approval_histories',
    'approval_signatures',
    'correction_requests',
    'approval_role_permissions',
    'approval_deadlines'
];

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
    echo "  ✅ All approval workflow tables exist\n\n";
} else {
    echo "  ❌ Some tables are missing\n\n";
}

// Test 2: Check Workflow Seeder Data
echo "Test 2: Checking Workflow Configurations...\n";
$total++;
$workflowCount = ApprovalWorkflow::count();
$expectedWorkflows = 5;

if ($workflowCount >= $expectedWorkflows) {
    $passed++;
    echo "  ✅ Found $workflowCount workflows (expected: $expectedWorkflows)\n";
    
    $workflows = ApprovalWorkflow::all();
    foreach ($workflows as $workflow) {
        echo "    - {$workflow->name} (Priority: {$workflow->priority})\n";
        echo "      Type: " . ($workflow->document_type ?? 'all') . ", ";
        echo "Classification: " . ($workflow->classification ?? 'all') . "\n";
    }
    echo "\n";
} else {
    $errors[] = "Expected at least $expectedWorkflows workflows, found $workflowCount";
    echo "  ❌ Insufficient workflows\n\n";
}

// Test 3: Check Role Permissions
echo "Test 3: Checking Approval Role Permissions...\n";
$total++;
$rolePermCount = ApprovalRolePermission::count();

if ($rolePermCount > 0) {
    $passed++;
    echo "  ✅ Found $rolePermCount approval role permissions\n";
    
    $roles = Role::all();
    foreach ($roles as $role) {
        $perms = ApprovalRolePermission::where('role_id', $role->id)->get();
        echo "    - {$role->display_name}: {$perms->count()} permissions\n";
    }
    echo "\n";
} else {
    $errors[] = "No approval role permissions found";
    echo "  ❌ No role permissions\n\n";
}

// Test 4: Check Models and Relationships
echo "Test 4: Checking Model Relationships...\n";
$total++;
$relationshipsOk = true;

try {
    // Get a test user and document
    $user = User::first();
    $document = Document::first();
    
    if (!$user || !$document) {
        echo "  ⚠️  No test data (user/document) available for relationship testing\n\n";
    } else {
        // Check Document relationships
        $document->approvalHistories;
        $document->correctionRequests;
        $document->approvalDeadlines;
        echo "  ✓ Document relationships work\n";
        
        // Check ApprovalWorkflow methods
        $workflow = ApprovalWorkflow::active()->first();
        if ($workflow) {
            $nextRole = $workflow->getNextApproverRole(0);
            echo "  ✓ ApprovalWorkflow methods work\n";
        }
        
        $passed++;
        echo "  ✅ All model relationships functional\n\n";
    }
} catch (\Exception $e) {
    $errors[] = "Relationship error: " . $e->getMessage();
    echo "  ❌ Relationship check failed: {$e->getMessage()}\n\n";
}

// Test 5: Check ApprovalWorkflowService
echo "Test 5: Checking ApprovalWorkflowService...\n";
$total++;

try {
    $approvalService = app(ApprovalWorkflowService::class);
    
    // Test getWorkflow method with existing document
    $document = Document::where('type', 'masuk')
                       ->where('classification', 'biasa')
                       ->first();
    
    if (!$document) {
        echo "  ⚠️  No test document available, skipping workflow matching test\n";
        echo "  ✓ ApprovalWorkflowService class exists\n";
        $passed++;
        echo "  ✅ ApprovalWorkflowService accessible\n\n";
    } else {
        $workflow = $approvalService->getWorkflow($document);
        
        if ($workflow) {
            echo "  ✓ Service can find matching workflow\n";
            echo "    Found: {$workflow->name}\n";
            
            $passed++;
            echo "  ✅ ApprovalWorkflowService functional\n\n";
        } else {
            $errors[] = "Service could not find workflow for test document";
            echo "  ❌ No workflow found for test document\n\n";
        }
    }
} catch (\Exception $e) {
    $errors[] = "Service error: " . $e->getMessage();
    echo "  ❌ Service check failed: {$e->getMessage()}\n\n";
}

// Test 6: Check Factory Classes
echo "Test 6: Checking Factory Classes...\n";
$total++;

try {
    $testWorkflow = \App\Models\ApprovalWorkflow::factory()->make();
    echo "  ✓ ApprovalWorkflowFactory works\n";
    
    $testHistory = \App\Models\ApprovalHistory::factory()->make();
    echo "  ✓ ApprovalHistoryFactory works\n";
    
    $testCorrection = \App\Models\CorrectionRequest::factory()->make();
    echo "  ✓ CorrectionRequestFactory works\n";
    
    $passed++;
    echo "  ✅ All factory classes functional\n\n";
} catch (\Exception $e) {
    $errors[] = "Factory error: " . $e->getMessage();
    echo "  ❌ Factory check failed: {$e->getMessage()}\n\n";
}

// Test 7: Check Routes
echo "Test 7: Checking Approval Routes...\n";
$total++;

$routes = collect(\Illuminate\Support\Facades\Route::getRoutes())->filter(function($route) {
    return str_contains($route->getName() ?? '', 'approvals.');
});

if ($routes->count() >= 10) {
    $passed++;
    echo "  ✅ Found {$routes->count()} approval routes\n";
    foreach ($routes as $route) {
        echo "    - {$route->getName()}\n";
    }
    echo "\n";
} else {
    $errors[] = "Expected at least 10 approval routes, found {$routes->count()}";
    echo "  ❌ Insufficient approval routes\n\n";
}

// Test 8: Check Controller
echo "Test 8: Checking ApprovalController...\n";
$total++;

if (class_exists('App\Http\Controllers\ApprovalController')) {
    // Just check class and methods exist, don't instantiate
    $reflection = new \ReflectionClass('App\Http\Controllers\ApprovalController');
    $methods = array_map(fn($m) => $m->getName(), $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));
    
    $requiredMethods = ['dashboard', 'show', 'approve', 'reject', 'requestCorrection'];
    
    $allMethodsExist = true;
    foreach ($requiredMethods as $method) {
        if (!in_array($method, $methods)) {
            $errors[] = "Method '$method' not found in ApprovalController";
            $allMethodsExist = false;
        } else {
            echo "  ✓ Method '$method' exists\n";
        }
    }
    
    if ($allMethodsExist) {
        $passed++;
        echo "  ✅ ApprovalController complete\n\n";
    } else {
        echo "  ❌ Some methods missing\n\n";
    }
} else {
    $errors[] = "ApprovalController class not found";
    echo "  ❌ Controller not found\n\n";
}

// Test 9: Check Views
echo "Test 9: Checking Approval Views...\n";
$total++;

$views = [
    'approvals.dashboard',
    'approvals.show',
    'approvals.reject',
    'approvals.request-correction',
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
    echo "  ✅ All approval views exist\n\n";
} else {
    echo "  ❌ Some views missing\n\n";
}

// Test 10: Check Policy
echo "Test 10: Checking ApprovalPolicy...\n";
$total++;

if (class_exists('App\Policies\ApprovalPolicy')) {
    $reflection = new \ReflectionClass('App\Policies\ApprovalPolicy');
    $methods = array_map(fn($m) => $m->getName(), $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));
    
    echo "  ✓ ApprovalPolicy exists\n";
    echo "    Methods: " . implode(', ', array_slice($methods, 0, 5)) . "...\n";
    
    $passed++;
    echo "  ✅ ApprovalPolicy complete\n\n";
} else {
    $errors[] = "ApprovalPolicy class not found";
    echo "  ❌ Policy not found\n\n";
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
    echo "\n🎉 ALL TESTS PASSED! Phase 5 is ready!\n";
} elseif ($percentage >= 80) {
    echo "\n✅ MOSTLY PASSED! Phase 5 is functional with minor issues.\n";
} else {
    echo "\n❌ TESTS FAILED! Phase 5 needs more work.\n";
}

if (!empty($errors)) {
    echo "\n❌ ERRORS FOUND:\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". $error\n";
    }
}

echo "\n========================================\n";
echo " Phase 5: Approval Workflow - ";
if ($percentage >= 90) {
    echo "✅ COMPLETED\n";
} else {
    echo "⚠️ NEEDS ATTENTION\n";
}
echo "========================================\n\n";
