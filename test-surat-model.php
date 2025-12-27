<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test Model Surat ===\n\n";

// Test 1: Check class existence
echo "1. Class Existence:\n";
echo "   - Surat: " . (class_exists('App\Models\Surat') ? '✓' : '✗') . "\n";
echo "   - SuratMasuk: " . (class_exists('App\Models\SuratMasuk') ? '✓' : '✗') . "\n";
echo "   - SuratKeluar: " . (class_exists('App\Models\SuratKeluar') ? '✓' : '✗') . "\n";
echo "   - Document (alias): " . (class_exists('App\Models\Document') ? '✓' : '✗') . "\n\n";

// Test 2: Check inheritance
echo "2. Inheritance:\n";
$surat = new App\Models\Surat();
echo "   - Surat class: " . get_class($surat) . "\n";

$suratMasuk = new App\Models\SuratMasuk();
echo "   - SuratMasuk class: " . get_class($suratMasuk) . "\n";
echo "   - SuratMasuk parent: " . get_parent_class($suratMasuk) . "\n";

$suratKeluar = new App\Models\SuratKeluar();
echo "   - SuratKeluar class: " . get_class($suratKeluar) . "\n";
echo "   - SuratKeluar parent: " . get_parent_class($suratKeluar) . "\n\n";

// Test 3: Database connection
echo "3. Database:\n";
try {
    $count = App\Models\Surat::count();
    echo "   - Total surat in database: $count\n";
    
    $masuk = App\Models\SuratMasuk::count();
    echo "   - Surat Masuk: $masuk\n";
    
    $keluar = App\Models\SuratKeluar::count();
    echo "   - Surat Keluar: $keluar\n";
} catch (\Exception $e) {
    echo "   - Error: " . $e->getMessage() . "\n";
}

echo "\n✓ All tests completed!\n";
