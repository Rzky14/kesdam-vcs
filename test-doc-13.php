<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Dokumen;

$dokumen = Dokumen::find(13);

if (!$dokumen) {
    echo "Dokumen ID 13 tidak ditemukan\n";
    exit;
}

echo "=== Dokumen ID 13 ===\n";
echo "ID: " . $dokumen->id . "\n";
echo "Type: " . $dokumen->type . "\n";
echo "Number: " . $dokumen->number . "\n";
echo "Status: " . $dokumen->status . "\n";
echo "Created by: " . $dokumen->created_by . "\n";
echo "Created at: " . ($dokumen->created_at ? $dokumen->created_at : 'NULL') . "\n";
echo "\n--- Cek relasi creator ---\n";

try {
    $creator = $dokumen->creator;
    if ($creator) {
        echo "Creator ID: " . $creator->id . "\n";
        echo "Creator name: " . $creator->name . "\n";
    } else {
        echo "Creator NULL (user tidak ditemukan untuk created_by: " . $dokumen->created_by . ")\n";
    }
} catch (\Exception $e) {
    echo "Error loading creator: " . $e->getMessage() . "\n";
}

echo "\n--- Cek relasi updater ---\n";
echo "Updated by: " . ($dokumen->updated_by ?? 'NULL') . "\n";

if ($dokumen->updated_by) {
    try {
        $updater = $dokumen->updater;
        if ($updater) {
            echo "Updater name: " . $updater->name . "\n";
        } else {
            echo "Updater NULL\n";
        }
    } catch (\Exception $e) {
        echo "Error loading updater: " . $e->getMessage() . "\n";
    }
}
