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
        echo "ID: " . $u["id"] . " | Email: " . $u["email"] . " | Role: " . $u["role_id"] . " | Company: " . ($u["company_id"] ?? "NULL") . " | Status: " . $u["status"] . "\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

