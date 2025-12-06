<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Document;
use App\Models\User;

echo "========================================\n";
echo "🔍 DEBUG APPROVAL WORKFLOW\n";
echo "========================================\n\n";

// Get KAUR user
$kaur = User::where('email', 'kaur@kesdam.mil.id')->first();
if (!$kaur) {
    echo "❌ KAUR user not found!\n";
    exit;
}

echo "👤 User: {$kaur->name}\n";
echo "📧 Email: {$kaur->email}\n";
echo "🎭 Roles: " . $kaur->roles->pluck('name')->implode(', ') . "\n";
echo "🔑 Has 'approve_documents' permission: " . ($kaur->hasPermission('approve_documents') ? 'YES' : 'NO') . "\n";
echo "🎯 Has 'kaur' role: " . ($kaur->hasRole('kaur') ? 'YES' : 'NO') . "\n";
echo "\n";

// Get pending approval documents
$documents = Document::where('status', 'pending_approval')->get();

echo "📋 Pending Approval Documents: {$documents->count()}\n\n";

foreach ($documents as $doc) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📄 Document: {$doc->number}\n";
    echo "   Subject: {$doc->subject}\n";
    echo "   Status: {$doc->status}\n";
    echo "   Current Approval Level: " . $doc->getCurrentApprovalLevel() . "\n";
    echo "   Next Approver Role: " . ($doc->getNextApproverRole() ?? 'NONE') . "\n";
    echo "   Can KAUR Approve: " . ($doc->canUserApprove($kaur) ? 'YES ✅' : 'NO ❌') . "\n";
    
    // Check approval histories
    $histories = $doc->approvalHistories()->orderBy('approval_level')->get();
    if ($histories->count() > 0) {
        echo "   Approval History:\n";
        foreach ($histories as $history) {
            echo "     - Level {$history->approval_level}: {$history->action} by {$history->user->name}\n";
        }
    } else {
        echo "   Approval History: NONE (fresh submission)\n";
    }
    echo "\n";
}

echo "========================================\n";
