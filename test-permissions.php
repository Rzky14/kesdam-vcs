<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== User & Permission Test ===\n\n";

// Get first user (should be Admin Sistem)
$user = App\Models\User::first();

if (!$user) {
    echo "✗ No user found in database\n";
    exit(1);
}

echo "User: {$user->name} (ID: {$user->id})\n";
echo "Role: {$user->role_name}\n\n";

echo "Permissions Check:\n";
$permissions = [
    'view_documents',
    'create_documents',
    'update_documents',
    'delete_documents',
    'approve_documents',
    'view_classified_documents'
];

foreach ($permissions as $perm) {
    $has = $user->hasPermission($perm);
    echo "  - {$perm}: " . ($has ? '✓' : '✗') . "\n";
}

echo "\nRole Methods:\n";
echo "  - isAdmin(): " . ($user->isAdmin() ? '✓' : '✗') . "\n";
echo "  - hasRole('Admin Sistem'): " . ($user->hasRole('Admin Sistem') ? '✓' : '✗') . "\n";

// Get a draft document
$document = App\Models\Surat::where('status', 'draft')->first();

if ($document) {
    echo "\n--- Testing Document Permissions ---\n";
    echo "Document: {$document->number} (Status: {$document->status})\n";
    echo "Created by: User ID {$document->created_by}\n\n";
    
    $policy = new App\Policies\SuratPolicy();
    
    echo "Policy Checks:\n";
    echo "  - view: " . ($policy->view($user, $document) ? '✓' : '✗') . "\n";
    echo "  - update: " . ($policy->update($user, $document) ? '✓' : '✗') . "\n";
    echo "  - delete: " . ($policy->delete($user, $document) ? '✓' : '✗') . "\n";
}

echo "\n✓ Test completed!\n";
