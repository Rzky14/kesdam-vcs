<?php
// Test if we can access reports route
echo "Testing reports route...\n\n";

// Load Laravel
require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Route;

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test route exists
echo "Checking routes...\n";
$routes = Route::getRoutes();
$reportRoutes = [];
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'reports')) {
        $reportRoutes[] = [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
        ];
    }
}

echo "Found " . count($reportRoutes) . " report routes:\n";
print_r($reportRoutes);

// Test controller exists
echo "\n\nChecking ReportController...\n";
if (class_exists('App\Http\Controllers\ReportController')) {
    echo "✓ ReportController exists\n";
    
    $reflection = new ReflectionClass('App\Http\Controllers\ReportController');
    echo "Methods:\n";
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->class === 'App\Http\Controllers\ReportController') {
            echo "  - " . $method->getName() . "\n";
        }
    }
} else {
    echo "✗ ReportController not found\n";
}

// Test Report model
echo "\n\nChecking Report model...\n";
if (class_exists('App\Models\Report')) {
    echo "✓ Report model exists\n";
    try {
        $count = App\Models\Report::count();
        echo "Reports in database: $count\n";
    } catch (Exception $e) {
        echo "Error querying database: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Report model not found\n";
}
