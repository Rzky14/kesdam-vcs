<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Dokumen;

echo "=== Testing Route Generation ===\n\n";

$dokumen = Dokumen::find(8);

if (!$dokumen) {
    echo "Dokumen ID 8 tidak ditemukan\n";
    exit;
}

echo "Dokumen ID: {$dokumen->id}\n";
echo "Subject: {$dokumen->subject}\n\n";

echo "Testing route generation:\n";
echo "- documents.show: " . route('documents.show', $dokumen) . "\n";
echo "- documents.edit: " . route('documents.edit', $dokumen) . "\n";
echo "- documents.approve: " . route('documents.approve', $dokumen) . "\n";
echo "- documents.reject: " . route('documents.reject', $dokumen) . "\n";
echo "- documents.submit: " . route('documents.submit', $dokumen) . "\n";
echo "- documents.archive: " . route('documents.archive', $dokumen) . "\n";
echo "- documents.download: " . route('documents.download', [$dokumen, 0]) . "\n";

echo "\n✓ All routes generated successfully!\n";
