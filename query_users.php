<?php
error_reporting(E_ALL);
ini_set("display_errors", "1");
require_once __DIR__ . "/config/config.php";
spl_autoload_register(function (string $class) {
    $prefix = "App\\";
    $baseDir = __DIR__ . "/app/";
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace("\\", "/", $relativeClass) . ".php";
    if (file_exists($file)) {
        require_once $file;
    }
});

try {
    $db = App\Core\Database::getInstance();
    $stmt = $db->query("SELECT id, email, role_id, company_id, status FROM users");
    $users = $stmt->fetchAll();
    echo "Found " . count($users) . " users:\n";
    foreach ($users as $u) {
        echo "ID: {$u[\x27id\x27]} | Email: {$u[\x27email\x27]} | Role: {$u[\x27role_id\x27]} | Company: " . ($u[\x27company_id\x27] ?? \x27NULL\x27) . " | Status: {$u[\x27status\x27]}\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

