<?php
/**
 * PHPUnit Bootstrap
 */

// 1. Load general configurations (will define APP_DEBUG, timezone, etc)
require_once dirname(__DIR__) . '/config/config.php';

// 2. Register Autoloader for App namespace (PSR-4 compliant)
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Class doesn't use the namespace prefix
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
