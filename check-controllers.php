<?php

require_once __DIR__ . '/backend/vendor/autoload.php';

use Symfony\Component\Finder\Finder;

$controllerDir = __DIR__ . '/backend/src/Controller';
$finder = new Finder();

echo "=== CONTROLLERS CHECK ===\n\n";

if (!is_dir($controllerDir)) {
    echo "❌ Controller directory not found: $controllerDir\n";
    exit(1);
}

$controllers = $finder->files()->in($controllerDir)->name('*.php');

$controllerCount = 0;
$routeCount = 0;

foreach ($controllers as $file) {
    $controllerCount++;
    $content = file_get_contents($file->getPathname());
    $filename = $file->getFilename();
    
    echo "📁 $filename\n";
    
    // Extract class name
    if (preg_match('/class\s+(\w+)/', $content, $matches)) {
        $className = $matches[1];
        echo "   Class: $className\n";
    }
    
    // Count routes
    $routes = preg_match_all('/#\[Route\(/', $content);
    $routeCount += $routes;
    echo "   Routes: $routes\n";
    
    // List route details
    if (preg_match_all('/#\[Route\([\'"]([^\'"]+)[\'"],\s*name:\s*[\'"]([^\'"]+)[\'"],\s*methods:\s*\[([^\]]+)\]/', $content, $routeMatches, PREG_SET_ORDER)) {
        foreach ($routeMatches as $route) {
            $path = $route[1];
            $name = $route[2];
            $methods = $route[3];
            echo "     - $methods $path ($name)\n";
        }
    }
    
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "Controllers found: $controllerCount\n";
echo "Total routes: $routeCount\n";

if ($controllerCount === 0) {
    echo "❌ No controllers found!\n";
    exit(1);
} else {
    echo "✅ Controllers check completed successfully\n";
}
