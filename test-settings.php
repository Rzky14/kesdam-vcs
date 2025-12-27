<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "Testing Settings System...\n\n";

// Test 1: Check if settings table exists
echo "1. Checking settings table...\n";
try {
    $count = DB::table('settings')->count();
    echo "   ✓ Settings table exists with {$count} records\n\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Check if default settings are loaded
echo "2. Checking default settings...\n";
try {
    $systemSettings = DB::table('settings')->where('type', 'system')->get();
    foreach ($systemSettings as $setting) {
        echo "   - {$setting->key}: " . json_encode(json_decode($setting->value)) . "\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Test SettingService
echo "3. Testing SettingService...\n";
try {
    $service = new App\Services\SettingService();
    $settings = $service->getAllSettings();
    echo "   ✓ SettingService works\n";
    echo "   - System settings: " . count($settings['system']) . "\n";
    echo "   - Notification settings: " . count($settings['notification']) . "\n\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Test User isAdmin method
echo "4. Testing User isAdmin method...\n";
try {
    $user = App\Models\User::first();
    if ($user) {
        echo "   User: {$user->name}\n";
        echo "   Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . "\n";
        echo "   ✓ isAdmin method works\n\n";
    } else {
        echo "   - No users found\n\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 5: Test backup directory
echo "5. Checking backup directory...\n";
$backupPath = storage_path('app/backups');
if (!file_exists($backupPath)) {
    mkdir($backupPath, 0755, true);
    echo "   ✓ Backup directory created\n\n";
} else {
    echo "   ✓ Backup directory exists\n\n";
}

// Test 6: Test Setting model methods
echo "6. Testing Setting model methods...\n";
try {
    // Test get method
    $value = App\Models\Setting::get('system.auto_backup', false);
    echo "   - system.auto_backup: " . json_encode($value) . "\n";
    
    // Test isEnabled method
    $enabled = App\Models\Setting::isEnabled('system.auto_backup');
    echo "   - Is enabled: " . ($enabled ? 'Yes' : 'No') . "\n";
    
    echo "   ✓ Setting model methods work\n\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

echo "✓ All tests completed!\n";
