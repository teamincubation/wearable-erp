<?php
require_once __DIR__ . '/config/config.php';

// Register Autoloader for App namespace
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

try {
    $reflector = new ReflectionMethod('App\Core\Auth', 'attempt');
    echo "<h1>Loaded Auth::attempt method source:</h1>";
    echo "<pre>";
    $src = file($reflector->getFileName());
    for ($i = $reflector->getStartLine() - 1; $i < $reflector->getEndLine(); $i++) {
        echo ($i + 1) . ": " . htmlspecialchars($src[$i]);
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
}
