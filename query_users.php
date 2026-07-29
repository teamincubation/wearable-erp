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
    
    // Direct database update to clean up any wrong company_id associations
    $db->exec("UPDATE users SET company_id = NULL WHERE role_id = 1");
    echo "Developer admin user\x27s company_id has been successfully cleared to NULL.\n\n";

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

