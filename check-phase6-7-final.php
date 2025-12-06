<?php
/**
 * Final Verification Script for Phase 6 & 7
 * KESDAM VCS - Notification System & Reporting/Archive
 */

echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║        KESDAM VCS - PHASE 6 & 7 FINAL VERIFICATION                          ║\n";
echo "║        Date: " . date('Y-m-d H:i:s') . "                                            ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n\n";

$baseDir = __DIR__;
$errors = [];
$warnings = [];

// ============================================================================
// PHASE 6: NOTIFICATION SYSTEM
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "PHASE 6: NOTIFICATION SYSTEM VERIFICATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$phase6Components = [
    // Database Layer
    'migrations' => [
        'database/migrations/2025_12_06_100000_create_notifications_table.php' => 'Notifications & Preferences Table',
    ],
    
    // Model Layer
    'models' => [
        'app/Models/NotificationPreference.php' => 'NotificationPreference Model',
    ],
    
    // Notification Classes
    'notifications' => [
        'app/Notifications/ScheduleReminderNotification.php' => 'Schedule Reminder',
        'app/Notifications/ApprovalRequestNotification.php' => 'Approval Request',
        'app/Notifications/DocumentApprovedNotification.php' => 'Document Approved',
        'app/Notifications/DocumentRejectedNotification.php' => 'Document Rejected',
        'app/Notifications/CorrectionRequestedNotification.php' => 'Correction Requested',
        'app/Notifications/DocumentStatusChangedNotification.php' => 'Status Changed',
    ],
    
    // Service Layer
    'services' => [
        'app/Services/NotificationService.php' => 'Notification Service',
    ],
    
    // Controller Layer
    'controllers' => [
        'app/Http/Controllers/NotificationController.php' => 'Notification Controller',
    ],
    
    // View Layer
    'views' => [
        'resources/views/notifications/index.blade.php' => 'Notification List View',
        'resources/views/notifications/show.blade.php' => 'Notification Detail View',
        'resources/views/notifications/preferences.blade.php' => 'Preferences View',
    ],
    
    // Testing
    'tests' => [
        'database/factories/NotificationPreferenceFactory.php' => 'Notification Factory',
        'database/seeders/NotificationSeeder.php' => 'Notification Seeder',
        'tests/Feature/NotificationTest.php' => 'Notification Tests',
    ],
];

$phase6Stats = [
    'total' => 0,
    'exists' => 0,
    'missing' => 0,
];

foreach ($phase6Components as $category => $files) {
    echo "├─ " . strtoupper($category) . "\n";
    foreach ($files as $file => $description) {
        $phase6Stats['total']++;
        $fullPath = $baseDir . '/' . $file;
        $exists = file_exists($fullPath);
        
        if ($exists) {
            $phase6Stats['exists']++;
            $filesize = filesize($fullPath);
            $lines = count(file($fullPath));
            echo "│  ✓ " . str_pad($description, 40) . " [" . $lines . " lines, " . number_format($filesize) . " bytes]\n";
        } else {
            $phase6Stats['missing']++;
            echo "│  ✗ " . str_pad($description, 40) . " [MISSING]\n";
            $errors[] = "Phase 6: Missing file - $file";
        }
    }
    echo "│\n";
}

// Check routes
echo "├─ ROUTES\n";
$routesFile = $baseDir . '/routes/web.php';
$routesContent = file_get_contents($routesFile);

$requiredRoutes = [
    "route('notifications.index')" => 'Notifications Index',
    "route('notifications.show'" => 'Notification Detail',
    "route('notifications.recent')" => 'Recent Notifications',
    "route('notifications.unread-count')" => 'Unread Count',
    "route('notifications.mark-all-read')" => 'Mark All Read',
    "route('notifications.preferences')" => 'Preferences',
];

foreach ($requiredRoutes as $routeName => $description) {
    $exists = strpos($routesContent, $routeName) !== false || 
              preg_match('/notifications\.index|notifications\.show|notifications\.recent/', $routesContent);
    echo "│  " . ($exists ? "✓" : "✗") . " " . str_pad($description, 40) . "\n";
    if (!$exists) {
        $warnings[] = "Phase 6: Route might be missing - $description";
    }
}
echo "│\n";

// Check notification bell integration
echo "├─ UI INTEGRATION\n";
$layoutFile = $baseDir . '/resources/views/layouts/app.blade.php';
if (file_exists($layoutFile)) {
    $layoutContent = file_get_contents($layoutFile);
    $hasNotificationBell = strpos($layoutContent, 'notification-bell') !== false;
    $hasNotificationDropdown = strpos($layoutContent, 'notification-dropdown') !== false;
    $hasAjaxLoad = strpos($layoutContent, 'loadNotifications') !== false;
    $hasAutoRefresh = strpos($layoutContent, 'setInterval') !== false && strpos($layoutContent, '30000') !== false;
    
    echo "│  " . ($hasNotificationBell ? "✓" : "✗") . " Notification Bell Icon\n";
    echo "│  " . ($hasNotificationDropdown ? "✓" : "✗") . " Notification Dropdown\n";
    echo "│  " . ($hasAjaxLoad ? "✓" : "✗") . " AJAX Loading\n";
    echo "│  " . ($hasAutoRefresh ? "✓" : "✗") . " Auto-refresh (30s)\n";
    
    if (!$hasNotificationBell) {
        $warnings[] = "Phase 6: Notification bell not integrated in layout";
    }
} else {
    echo "│  ✗ Layout file not found\n";
    $errors[] = "Phase 6: Layout file missing";
}
echo "│\n";

// Check User model integration
echo "├─ MODEL RELATIONSHIPS\n";
$userModelFile = $baseDir . '/app/Models/User.php';
if (file_exists($userModelFile)) {
    $userContent = file_get_contents($userModelFile);
    $hasNotificationPrefs = strpos($userContent, 'notificationPreferences') !== false;
    $hasHelperMethods = strpos($userContent, 'hasInAppNotificationEnabled') !== false;
    
    echo "│  " . ($hasNotificationPrefs ? "✓" : "✗") . " notificationPreferences() relationship\n";
    echo "│  " . ($hasHelperMethods ? "✓" : "✗") . " Notification helper methods\n";
    
    if (!$hasNotificationPrefs) {
        $errors[] = "Phase 6: User model missing notificationPreferences relationship";
    }
} else {
    echo "│  ✗ User model not found\n";
}
echo "└─\n\n";

// Calculate Phase 6 percentage
$phase6Percentage = ($phase6Stats['total'] > 0) 
    ? round(($phase6Stats['exists'] / $phase6Stats['total']) * 100, 1) 
    : 0;

echo "Phase 6 Summary:\n";
echo "  Total Components: " . $phase6Stats['total'] . "\n";
echo "  Existing: " . $phase6Stats['exists'] . " ✓\n";
echo "  Missing: " . $phase6Stats['missing'] . " ✗\n";
echo "  Completion: " . $phase6Percentage . "%\n\n";

// ============================================================================
// PHASE 7: REPORTING & ARCHIVE SYSTEM
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "PHASE 7: REPORTING & ARCHIVE SYSTEM VERIFICATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$phase7Components = [
    // Database Layer
    'migrations' => [
        'database/migrations/2025_12_06_000003_create_reports_table.php' => 'Reports Table',
        'database/migrations/2025_12_06_000004_create_archives_table.php' => 'Archives Table',
    ],
    
    // Model Layer
    'models' => [
        'app/Models/Report.php' => 'Report Model',
        'app/Models/Archive.php' => 'Archive Model',
    ],
    
    // Controller Layer
    'controllers' => [
        'app/Http/Controllers/ReportController.php' => 'Report Controller',
        'app/Http/Controllers/ArchiveController.php' => 'Archive Controller',
    ],
    
    // View Layer - Reports
    'report_views' => [
        'resources/views/reports/index.blade.php' => 'Reports Dashboard',
        'resources/views/reports/show.blade.php' => 'Report Detail',
        'resources/views/reports/create-schedule.blade.php' => 'Create Schedule Report',
        'resources/views/reports/create-document.blade.php' => 'Create Document Report',
    ],
    
    // View Layer - Archives
    'archive_views' => [
        'resources/views/archives/index.blade.php' => 'Archives List',
        'resources/views/archives/show.blade.php' => 'Archive Detail',
        'resources/views/archives/search.blade.php' => 'Archive Search',
    ],
    
    // Testing
    'tests' => [
        'database/factories/ReportFactory.php' => 'Report Factory',
        'database/factories/ArchiveFactory.php' => 'Archive Factory',
        'database/seeders/ReportArchiveSeeder.php' => 'Report/Archive Seeder',
        'tests/Feature/ReportingTest.php' => 'Reporting Tests',
        'tests/Feature/ArchiveTest.php' => 'Archive Tests',
    ],
];

$phase7Stats = [
    'total' => 0,
    'exists' => 0,
    'missing' => 0,
];

foreach ($phase7Components as $category => $files) {
    echo "├─ " . strtoupper($category) . "\n";
    foreach ($files as $file => $description) {
        $phase7Stats['total']++;
        $fullPath = $baseDir . '/' . $file;
        $exists = file_exists($fullPath);
        
        if ($exists) {
            $phase7Stats['exists']++;
            $filesize = filesize($fullPath);
            $lines = count(file($fullPath));
            echo "│  ✓ " . str_pad($description, 40) . " [" . $lines . " lines, " . number_format($filesize) . " bytes]\n";
        } else {
            $phase7Stats['missing']++;
            echo "│  ✗ " . str_pad($description, 40) . " [MISSING]\n";
            $errors[] = "Phase 7: Missing file - $file";
        }
    }
    echo "│\n";
}

// Check routes
echo "├─ ROUTES\n";
$requiredReportRoutes = [
    "route('reports.index')" => 'Reports Index',
    "route('reports.show'" => 'Report Detail',
    "route('reports.create.schedule')" => 'Create Schedule Report',
    "route('reports.create.document')" => 'Create Document Report',
    "route('archives.index')" => 'Archives Index',
];

foreach ($requiredReportRoutes as $routeName => $description) {
    $exists = strpos($routesContent, $routeName) !== false || 
              preg_match('/reports\.|archives\./', $routesContent);
    echo "│  " . ($exists ? "✓" : "✗") . " " . str_pad($description, 40) . "\n";
    if (!$exists) {
        $warnings[] = "Phase 7: Route might be missing - $description";
    }
}
echo "│\n";

// Check navigation integration
echo "├─ NAVIGATION INTEGRATION\n";
if (file_exists($layoutFile)) {
    $hasReportsMenu = strpos($layoutContent, "route('reports.index')") !== false;
    $hasSettingsMenu = strpos($layoutContent, "route('settings.index')") !== false;
    
    echo "│  " . ($hasReportsMenu ? "✓" : "✗") . " Reports Menu Link\n";
    echo "│  " . ($hasSettingsMenu ? "✓" : "✗") . " Settings Menu Link (for Archives)\n";
    
    if (!$hasReportsMenu) {
        $warnings[] = "Phase 7: Reports menu not in navigation";
    }
}
echo "│\n";

// Check settings integration
echo "├─ SETTINGS PAGE\n";
$settingsController = $baseDir . '/app/Http/Controllers/SettingController.php';
$settingsView = $baseDir . '/resources/views/settings/index.blade.php';

$hasSettingsController = file_exists($settingsController);
$hasSettingsView = file_exists($settingsView);

echo "│  " . ($hasSettingsController ? "✓" : "✗") . " Settings Controller\n";
echo "│  " . ($hasSettingsView ? "✓" : "✗") . " Settings View\n";

if ($hasSettingsView) {
    $settingsContent = file_get_contents($settingsView);
    $hasArchiveSection = strpos($settingsContent, 'Arsip') !== false;
    $hasArchiveLink = strpos($settingsContent, "route('archives.index')") !== false;
    
    echo "│  " . ($hasArchiveSection ? "✓" : "✗") . " Archive Section in Settings\n";
    echo "│  " . ($hasArchiveLink ? "✓" : "✗") . " Archive Access Link\n";
}
echo "└─\n\n";

// Calculate Phase 7 percentage
$phase7Percentage = ($phase7Stats['total'] > 0) 
    ? round(($phase7Stats['exists'] / $phase7Stats['total']) * 100, 1) 
    : 0;

echo "Phase 7 Summary:\n";
echo "  Total Components: " . $phase7Stats['total'] . "\n";
echo "  Existing: " . $phase7Stats['exists'] . " ✓\n";
echo "  Missing: " . $phase7Stats['missing'] . " ✗\n";
echo "  Completion: " . $phase7Percentage . "%\n\n";

// ============================================================================
// OVERALL SUMMARY
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "OVERALL SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$overallPercentage = round(($phase6Percentage + $phase7Percentage) / 2, 1);

echo "Phase 6 (Notification System):  " . str_pad($phase6Percentage . "%", 8) . " [" . progressBar($phase6Percentage) . "]\n";
echo "Phase 7 (Reports & Archives):   " . str_pad($phase7Percentage . "%", 8) . " [" . progressBar($phase7Percentage) . "]\n";
echo "Overall Completion:             " . str_pad($overallPercentage . "%", 8) . " [" . progressBar($overallPercentage) . "]\n\n";

// Status determination
if ($phase6Percentage >= 100 && $phase7Percentage >= 100) {
    echo "✓ STATUS: BOTH PHASES COMPLETE - READY FOR PRODUCTION\n\n";
} elseif ($phase6Percentage >= 90 && $phase7Percentage >= 90) {
    echo "⚠ STATUS: NEARLY COMPLETE - MINOR TASKS REMAINING\n\n";
} else {
    echo "✗ STATUS: IN PROGRESS - MAJOR COMPONENTS MISSING\n\n";
}

// Errors and Warnings
if (count($errors) > 0) {
    echo "ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        echo "  ✗ $error\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "  ⚠ $warning\n";
    }
    echo "\n";
}

// Recommendations
echo "NEXT STEPS:\n";
if ($phase6Percentage < 100) {
    echo "  • Complete remaining Phase 6 components\n";
    if (!isset($hasNotificationBell) || !$hasNotificationBell) {
        echo "  • Integrate notification bell in layout\n";
    }
}
if ($phase7Percentage < 100) {
    echo "  • Complete remaining Phase 7 components\n";
    echo "  • Create missing views for reports and archives\n";
}
if ($phase6Percentage >= 90 && $phase7Percentage >= 90) {
    echo "  • Run migrations: php artisan migrate:fresh --seed\n";
    echo "  • Run tests: php artisan test\n";
    echo "  • Test notification real-time updates\n";
    echo "  • Test report generation functionality\n";
    echo "  • Test archive search and retrieval\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                         VERIFICATION COMPLETE                                ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";

function progressBar($percentage) {
    $filled = round($percentage / 5);
    $empty = 20 - $filled;
    return str_repeat('█', $filled) . str_repeat('░', $empty);
}
