<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Dokumen;
use Illuminate\Support\Facades\Crypt;

echo "=== Testing Documents ===\n\n";

$documents = Dokumen::limit(5)->get();

foreach ($documents as $doc) {
    echo "ID: {$doc->id}\n";
    echo "Type: {$doc->type}\n";
    echo "Classification: {$doc->classification}\n";
    echo "Is Encrypted: " . ($doc->is_encrypted ? 'YES' : 'NO') . "\n";
    echo "Subject (raw): " . substr($doc->getRawOriginal('subject'), 0, 50) . "...\n";
    
    if ($doc->is_encrypted && $doc->classification === 'rahasia') {
        try {
            $decrypted = Crypt::decryptString($doc->getRawOriginal('subject'));
            echo "Subject (decrypted): {$decrypted}\n";
        } catch (\Exception $e) {
            echo "Failed to decrypt: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Subject: {$doc->subject}\n";
    }
    
    echo "---\n\n";
}
