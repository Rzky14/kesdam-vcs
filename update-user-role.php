<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "========================================\n";
echo "🔧 UPDATE USER ROLE TO KAUR\n";
echo "========================================\n\n";

// Find user by name pattern (Safril)
$user = User::where('name', 'LIKE', '%Safril%Maulana%')->first();

if (!$user) {
    echo "❌ User 'Safril Yusup Maulana' not found!\n";
    echo "Available users:\n";
    User::all()->each(function($u) {
        echo "  - {$u->name} ({$u->email})\n";
    });
    exit;
}

// Find KAUR role
$kaurRole = Role::where('name', 'kaur')->first();

if (!$kaurRole) {
    echo "❌ Role 'kaur' not found!\n";
    exit;
}

echo "👤 Found User: {$user->name} ({$user->email})\n";
echo "🎯 Current Roles: " . $user->roles->pluck('display_name')->implode(', ') . "\n\n";

// Remove old roles and attach new role
$user->roles()->sync([$kaurRole->id]);

echo "✅ Updated to: {$kaurRole->display_name}\n\n";

// Verify
$user->load('roles');
echo "📋 New Roles: " . $user->roles->pluck('display_name')->implode(', ') . "\n";
echo "🔑 Permissions: " . $user->getAllPermissions()->pluck('name')->implode(', ') . "\n\n";

echo "========================================\n";
echo "✅ DONE! User can now approve at Level 1\n";
echo "========================================\n";
