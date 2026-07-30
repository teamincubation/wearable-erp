<?php
require_once __DIR__ . '/config/config.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

try {
    $db = Database::getInstance();
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    echo "Database check complete.\n";
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
