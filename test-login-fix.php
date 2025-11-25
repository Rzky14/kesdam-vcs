<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Login Error Fix ===\n\n";

// Test 1: Check if auditable_type is nullable
echo "1. Checking audit_logs table structure:\n";
$columns = DB::select("SHOW COLUMNS FROM audit_logs");
foreach ($columns as $column) {
    if ($column->Field === 'auditable_type') {
        echo "   Column: {$column->Field}\n";
        echo "   Type: {$column->Type}\n";
        echo "   Null: {$column->Null}\n";
        if ($column->Null === 'YES') {
            echo "   ✅ auditable_type is now NULLABLE\n";
        } else {
            echo "   ❌ auditable_type is NOT NULL\n";
        }
        break;
    }
}

// Test 2: Try to create a failed login audit log
echo "\n2. Testing failed login audit log creation:\n";
try {
    App\Models\AuditLog::create([
        'user_id' => null,
        'event' => 'login_failed',
        'auditable_type' => null,
        'auditable_id' => null,
        'old_values' => null,
        'new_values' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
        'description' => 'Test failed login attempt',
    ]);
    echo "   ✅ Failed login audit log created successfully!\n";
} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 3: Check admin user
echo "\n3. Checking admin user:\n";
$admin = App\Models\User::where('email', 'admin@kesdam.mil.id')->first();
if ($admin) {
    echo "   ✅ Admin user exists\n";
    echo "   Name: {$admin->name}\n";
    echo "   Position: {$admin->position}\n";
    echo "   Email: {$admin->email}\n";
} else {
    echo "   ❌ Admin user not found!\n";
}

// Test 4: Count audit logs
echo "\n4. Audit logs count: " . App\Models\AuditLog::count() . "\n";

echo "\n=== Test Complete ===\n";
echo "\n📌 CARA LOGIN:\n";
echo "1. Buka: http://127.0.0.1:8000\n";
echo "2. Email: admin@kesdam.mil.id\n";
echo "3. Password: password123\n";
echo "4. Login seharusnya berhasil sekarang!\n";
