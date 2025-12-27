<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Dokumen;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

echo "=== Fixing Corrupt Encrypted Documents ===\n\n";

// Find documents marked as encrypted but data is not properly encrypted
$documents = Dokumen::where('is_encrypted', true)
    ->where('classification', 'rahasia')
    ->get();

$fixed = 0;
$failed = 0;

foreach ($documents as $doc) {
    echo "Checking Document ID: {$doc->id}\n";
    
    // Try to decrypt
    try {
        $decrypted = Crypt::decryptString($doc->getRawOriginal('subject'));
        echo "  ✓ Subject properly encrypted\n";
    } catch (\Exception $e) {
        echo "  ✗ Subject NOT properly encrypted - fixing...\n";
        
        try {
            // Re-encrypt the subject properly
            $plainSubject = $doc->getRawOriginal('subject');
            $encryptedSubject = Crypt::encryptString($plainSubject);
            
            // Update directly to bypass model mutators
            DB::table('documents')
                ->where('id', $doc->id)
                ->update([
                    'subject' => $encryptedSubject,
                    'updated_at' => now()
                ]);
            
            echo "  ✓ Fixed subject encryption\n";
            $fixed++;
        } catch (\Exception $e2) {
            echo "  ✗ Failed to fix: " . $e2->getMessage() . "\n";
            $failed++;
        }
    }
    
    // Check description if exists
    if ($doc->description) {
        try {
            $decrypted = Crypt::decryptString($doc->getRawOriginal('description'));
            echo "  ✓ Description properly encrypted\n";
        } catch (\Exception $e) {
            echo "  ✗ Description NOT properly encrypted - fixing...\n";
            
            try {
                $plainDesc = $doc->getRawOriginal('description');
                $encryptedDesc = Crypt::encryptString($plainDesc);
                
                DB::table('documents')
                    ->where('id', $doc->id)
                    ->update([
                        'description' => $encryptedDesc,
                        'updated_at' => now()
                    ]);
                
                echo "  ✓ Fixed description encryption\n";
                $fixed++;
            } catch (\Exception $e2) {
                echo "  ✗ Failed to fix description: " . $e2->getMessage() . "\n";
                $failed++;
            }
        }
    }
    
    echo "\n";
}

echo "\n=== Summary ===\n";
echo "Fixed: {$fixed}\n";
echo "Failed: {$failed}\n";
echo "\nDone!\n";
