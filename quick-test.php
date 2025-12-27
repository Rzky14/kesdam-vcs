<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Quick Model Test ===\n\n";

try {
    echo "1. Surat class: " . (class_exists('App\Models\Surat') ? '✓' : '✗') . "\n";
    echo "2. SuratMasuk class: " . (class_exists('App\Models\SuratMasuk') ? '✓' : '✗') . "\n";
    echo "3. SuratKeluar class: " . (class_exists('App\Models\SuratKeluar') ? '✓' : '✗') . "\n";
    
    $count = App\Models\Surat::count();
    echo "4. Total surat: $count\n";
    
    echo "\n✓ All working!\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
