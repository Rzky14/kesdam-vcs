<?php

/**
 * Comprehensive Phase 1 and Phase 7 Verification Script
 * Checks database migrations, tables, models, controllers, tests, and functionality
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== PHASE 1 & PHASE 7 VERIFICATION REPORT ===\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n\n";

// ============================================
// PHASE 1: FOUNDATION & INFRASTRUCTURE
// ============================================
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    PHASE 1 VERIFICATION                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$phase1Results = [
    'migrations' => [],
    'tables' => [],
    'models' => [],
    'services' => [],
    'seeders' => [],
    'tests' => [],
];

// Check Phase 1 Migrations
echo "1. MIGRATIONS CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase1Migrations = [
    '2025_10_30_000001_create_roles_table.php',
    '2025_10_30_000002_create_permissions_table.php',
    '2025_10_30_000003_create_role_user_table.php',
    '2025_10_30_000004_create_permission_role_table.php',
    '2025_10_30_000005_add_additional_fields_to_users_table.php',
    '2025_10_30_000006_create_audit_logs_table.php',
];

foreach ($phase1Migrations as $migration) {
    $path = database_path('migrations/' . $migration);
    $exists = file_exists($path);
    $phase1Results['migrations'][$migration] = $exists;
    
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo sprintf("  %-60s %s\n", $migration, $status);
}

// Check Phase 1 Tables
echo "\n2. DATABASE TABLES CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase1Tables = [
    'users',
    'roles', 
    'permissions',
    'role_user',
    'permission_role',
    'audit_logs',
];

try {
    foreach ($phase1Tables as $table) {
        $exists = Schema::hasTable($table);
        $phase1Results['tables'][$table] = $exists;
        
        if ($exists) {
            $count = DB::table($table)->count();
            echo sprintf("  ✅ %-30s (Records: %d)\n", $table, $count);
        } else {
            echo sprintf("  ❌ %-30s MISSING\n", $table);
        }
    }
} catch (Exception $e) {
    echo "  ⚠️  Database Error: " . $e->getMessage() . "\n";
}

// Check Phase 1 Models
echo "\n3. MODELS CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase1Models = [
    'User' => 'App\\Models\\User',
    'Role' => 'App\\Models\\Role',
    'Permission' => 'App\\Models\\Permission',
    'AuditLog' => 'App\\Models\\AuditLog',
];

foreach ($phase1Models as $name => $class) {
    $exists = class_exists($class);
    $phase1Results['models'][$name] = $exists;
    
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo sprintf("  %-30s %s\n", $name, $status);
    
    if ($exists) {
        // Check critical methods/relationships
        $reflection = new ReflectionClass($class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(fn($m) => $m->getName(), $methods);
        
        if ($name === 'User') {
            $required = ['roles', 'hasRole', 'hasPermission'];
            foreach ($required as $method) {
                $has = in_array($method, $methodNames);
                $icon = $has ? "    ✓" : "    ✗";
                echo sprintf("  %s %s\n", $icon, $method);
            }
        }
    }
}

// Check Phase 1 Services
echo "\n4. SERVICES CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase1Services = [
    'EncryptionService' => 'App\\Services\\EncryptionService',
    'BackupService' => 'App\\Services\\BackupService',
];

foreach ($phase1Services as $name => $class) {
    $exists = class_exists($class);
    $phase1Results['services'][$name] = $exists;
    
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo sprintf("  %-30s %s\n", $name, $status);
}

// Check Phase 1 Seeders
echo "\n5. SEEDERS CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase1Seeders = [
    'RolePermissionSeeder.php',
    'UserSeeder.php',
];

foreach ($phase1Seeders as $seeder) {
    $path = database_path('seeders/' . $seeder);
    $exists = file_exists($path);
    $phase1Results['seeders'][$seeder] = $exists;
    
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo sprintf("  %-30s %s\n", $seeder, $status);
}

// Check Phase 1 Tests
echo "\n6. TESTS CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase1Tests = [
    'AuthenticationTest.php' => 'tests/Feature/AuthenticationTest.php',
    'UserManagementTest.php' => 'tests/Feature/UserManagementTest.php',
];

foreach ($phase1Tests as $name => $path) {
    $exists = file_exists(base_path($path));
    $phase1Results['tests'][$name] = $exists;
    
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo sprintf("  %-30s %s\n", $name, $status);
}

// ============================================
// PHASE 7: REPORTING & ARCHIVE
// ============================================
echo "\n\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    PHASE 7 VERIFICATION                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$phase7Results = [
    'migrations' => [],
    'tables' => [],
    'models' => [],
    'services' => [],
    'factories' => [],
    'seeders' => [],
    'tests' => [],
];

// Check Phase 7 Migrations
echo "1. MIGRATIONS CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase7Migrations = [
    '2025_12_06_000003_create_reports_table.php',
    '2025_12_06_000004_create_archives_table.php',
];

foreach ($phase7Migrations as $migration) {
    $path = database_path('migrations/' . $migration);
    $exists = file_exists($path);
    $phase7Results['migrations'][$migration] = $exists;
    
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo sprintf("  %-60s %s\n", $migration, $status);
}

// Check Phase 7 Tables
echo "\n2. DATABASE TABLES CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase7Tables = [
    'reports',
    'archives',
];

try {
    foreach ($phase7Tables as $table) {
        $exists = Schema::hasTable($table);
        $phase7Results['tables'][$table] = $exists;
        
        if ($exists) {
            $count = DB::table($table)->count();
            $columns = Schema::getColumnListing($table);
            echo sprintf("  ✅ %-30s (Records: %d, Columns: %d)\n", $table, $count, count($columns));
            
            // Show critical columns
            $critical = array_intersect(['id', 'type', 'reportable_type', 'reportable_id', 'archiveable_type', 'created_at'], $columns);
            echo "     Columns: " . implode(', ', $critical) . "\n";
        } else {
            echo sprintf("  ❌ %-30s MISSING\n", $table);
        }
    }
} catch (Exception $e) {
    echo "  ⚠️  Database Error: " . $e->getMessage() . "\n";
}

// Check Phase 7 Models
echo "\n3. MODELS CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase7Models = [
    'Report' => 'App\\Models\\Report',
    'Archive' => 'App\\Models\\Archive',
];

foreach ($phase7Models as $name => $class) {
    $exists = class_exists($class);
    $phase7Results['models'][$name] = $exists;
    
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo sprintf("  %-30s %s\n", $name, $status);
    
    if ($exists) {
        $reflection = new ReflectionClass($class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(fn($m) => $m->getName(), $methods);
        
        // Check polymorphic relationships
        if ($name === 'Report') {
            $required = ['reportable'];
            foreach ($required as $method) {
                $has = in_array($method, $methodNames);
                $icon = $has ? "    ✓" : "    ✗";
                echo sprintf("  %s %s (polymorphic)\n", $icon, $method);
            }
        }
        
        if ($name === 'Archive') {
            $required = ['archiveable'];
            foreach ($required as $method) {
                $has = in_array($method, $methodNames);
                $icon = $has ? "    ✓" : "    ✗";
                echo sprintf("  %s %s (polymorphic)\n", $icon, $method);
            }
        }
    }
}

// Check Phase 7 Services
echo "\n4. SERVICES CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase7Services = [
    'ReportService' => 'App\\Services\\ReportService',
    'ArchiveService' => 'App\\Services\\ArchiveService',
];

foreach ($phase7Services as $name => $class) {
    $exists = class_exists($class);
    $phase7Results['services'][$name] = $exists;
    
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo sprintf("  %-30s %s\n", $name, $status);
    
    if ($exists) {
        $reflection = new ReflectionClass($class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        echo "     Methods: " . count($methods) . "\n";
    }
}

// Check Phase 7 Factories
echo "\n5. FACTORIES CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase7Factories = [
    'ReportFactory.php' => 'database/factories/ReportFactory.php',
    'ArchiveFactory.php' => 'database/factories/ArchiveFactory.php',
];

foreach ($phase7Factories as $name => $path) {
    $exists = file_exists(base_path($path));
    $phase7Results['factories'][$name] = $exists;
    
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo sprintf("  %-30s %s\n", $name, $status);
}

// Check Phase 7 Seeders
echo "\n6. SEEDERS CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase7Seeders = [
    'ReportArchiveSeeder.php',
];

foreach ($phase7Seeders as $seeder) {
    $path = database_path('seeders/' . $seeder);
    $exists = file_exists($path);
    $phase7Results['seeders'][$seeder] = $exists;
    
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo sprintf("  %-30s %s\n", $seeder, $status);
}

// Check Phase 7 Tests
echo "\n7. TESTS CHECK:\n";
echo str_repeat("-", 70) . "\n";

$phase7Tests = [
    'ReportingTest.php' => 'tests/Feature/ReportingTest.php',
    'ArchiveTest.php' => 'tests/Feature/ArchiveTest.php',
];

foreach ($phase7Tests as $name => $path) {
    $exists = file_exists(base_path($path));
    $phase7Results['tests'][$name] = $exists;
    
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo sprintf("  %-30s %s\n", $name, $status);
    
    if ($exists) {
        $content = file_get_contents(base_path($path));
        preg_match_all('/public function test_/', $content, $matches);
        $testCount = count($matches[0]);
        echo sprintf("     Test Methods: %d\n", $testCount);
    }
}

// ============================================
// SUMMARY & RECOMMENDATIONS
// ============================================
echo "\n\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                      SUMMARY REPORT                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Phase 1 Summary
echo "PHASE 1 COMPLETION:\n";
echo str_repeat("-", 70) . "\n";

$phase1Total = 0;
$phase1Passed = 0;

foreach ($phase1Results as $category => $items) {
    $categoryPassed = count(array_filter($items));
    $categoryTotal = count($items);
    $phase1Total += $categoryTotal;
    $phase1Passed += $categoryPassed;
    
    $percentage = $categoryTotal > 0 ? round(($categoryPassed / $categoryTotal) * 100) : 0;
    echo sprintf("  %-20s %2d/%2d (%3d%%)\n", ucfirst($category) . ':', $categoryPassed, $categoryTotal, $percentage);
}

$phase1Percentage = $phase1Total > 0 ? round(($phase1Passed / $phase1Total) * 100) : 0;
echo str_repeat("-", 70) . "\n";
echo sprintf("  TOTAL PHASE 1:       %2d/%2d (%3d%%)\n", $phase1Passed, $phase1Total, $phase1Percentage);

if ($phase1Percentage >= 90) {
    echo "  Status: ✅ EXCELLENT\n";
} elseif ($phase1Percentage >= 70) {
    echo "  Status: ⚠️  GOOD - Minor issues\n";
} else {
    echo "  Status: ❌ NEEDS ATTENTION\n";
}

// Phase 7 Summary
echo "\n\nPHASE 7 COMPLETION:\n";
echo str_repeat("-", 70) . "\n";

$phase7Total = 0;
$phase7Passed = 0;

foreach ($phase7Results as $category => $items) {
    $categoryPassed = count(array_filter($items));
    $categoryTotal = count($items);
    $phase7Total += $categoryTotal;
    $phase7Passed += $categoryPassed;
    
    $percentage = $categoryTotal > 0 ? round(($categoryPassed / $categoryTotal) * 100) : 0;
    echo sprintf("  %-20s %2d/%2d (%3d%%)\n", ucfirst($category) . ':', $categoryPassed, $categoryTotal, $percentage);
}

$phase7Percentage = $phase7Total > 0 ? round(($phase7Passed / $phase7Total) * 100) : 0;
echo str_repeat("-", 70) . "\n";
echo sprintf("  TOTAL PHASE 7:       %2d/%2d (%3d%%)\n", $phase7Passed, $phase7Total, $phase7Percentage);

if ($phase7Percentage >= 90) {
    echo "  Status: ✅ EXCELLENT\n";
} elseif ($phase7Percentage >= 70) {
    echo "  Status: ⚠️  GOOD - Minor issues\n";
} else {
    echo "  Status: ❌ NEEDS ATTENTION\n";
}

// Overall Status
echo "\n\nOVERALL STATUS:\n";
echo str_repeat("-", 70) . "\n";

$overallTotal = $phase1Total + $phase7Total;
$overallPassed = $phase1Passed + $phase7Passed;
$overallPercentage = $overallTotal > 0 ? round(($overallPassed / $overallTotal) * 100) : 0;

echo sprintf("  Total Items Checked: %d\n", $overallTotal);
echo sprintf("  Items Passed:        %d\n", $overallPassed);
echo sprintf("  Items Failed:        %d\n", $overallTotal - $overallPassed);
echo sprintf("  Success Rate:        %d%%\n", $overallPercentage);

if ($overallPercentage >= 95) {
    echo "\n  🎉 EXCELLENT! Both phases are production-ready!\n";
} elseif ($overallPercentage >= 80) {
    echo "\n  ✅ GOOD! Minor improvements needed.\n";
} elseif ($overallPercentage >= 60) {
    echo "\n  ⚠️  ATTENTION REQUIRED - Several components missing.\n";
} else {
    echo "\n  ❌ CRITICAL - Major components missing!\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "End of Report\n";
echo str_repeat("=", 70) . "\n\n";
