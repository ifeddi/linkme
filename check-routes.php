<?php

require_once __DIR__ . '/backend/vendor/autoload.php';

use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Annotation\Route;

$controllerDir = __DIR__ . '/backend/src/Controller';
$finder = new Finder();

echo "=== ROUTES CHECK ===\n\n";

if (!is_dir($controllerDir)) {
    echo "❌ Controller directory not found: $controllerDir\n";
    exit(1);
}

$controllers = $finder->files()->in($controllerDir)->name('*.php');

$allRoutes = [];
$routeCount = 0;

foreach ($controllers as $file) {
    $content = file_get_contents($file->getPathname());
    $filename = $file->getFilename();
    
    // Extract all routes from the file
    if (preg_match_all('/#\[Route\([\'"]([^\'"]+)[\'"],\s*name:\s*[\'"]([^\'"]+)[\'"],\s*methods:\s*\[([^\]]+)\]/', $content, $routeMatches, PREG_SET_ORDER)) {
        foreach ($routeMatches as $route) {
            $path = $route[1];
            $name = $route[2];
            $methods = $route[3];
            
            $allRoutes[] = [
                'file' => $filename,
                'path' => $path,
                'name' => $name,
                'methods' => $methods
            ];
            $routeCount++;
        }
    }
}

// Sort routes by path
usort($allRoutes, function($a, $b) {
    return strcmp($a['path'], $b['path']);
});

echo "=== ROUTES LIST ===\n\n";

foreach ($allRoutes as $route) {
    echo "🔗 {$route['methods']} {$route['path']}\n";
    echo "   Name: {$route['name']}\n";
    echo "   File: {$route['file']}\n\n";
}

echo "=== SUMMARY ===\n";
echo "Total routes found: $routeCount\n";

// Check for duplicate route names
$routeNames = array_column($allRoutes, 'name');
$duplicateNames = array_diff_assoc($routeNames, array_unique($routeNames));

if (!empty($duplicateNames)) {
    echo "⚠️  Duplicate route names found:\n";
    foreach (array_unique($duplicateNames) as $duplicate) {
        echo "   - $duplicate\n";
    }
} else {
    echo "✅ No duplicate route names found\n";
}

// Check for duplicate paths
$routePaths = array_column($allRoutes, 'path');
$duplicatePaths = array_diff_assoc($routePaths, array_unique($routePaths));

if (!empty($duplicatePaths)) {
    echo "⚠️  Duplicate route paths found:\n";
    foreach (array_unique($duplicatePaths) as $duplicate) {
        echo "   - $duplicate\n";
    }
} else {
    echo "✅ No duplicate route paths found\n";
}

if ($routeCount === 0) {
    echo "❌ No routes found!\n";
    exit(1);
} else {
    echo "✅ Routes check completed successfully\n";
}
