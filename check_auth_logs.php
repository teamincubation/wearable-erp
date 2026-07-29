<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance();
    $stmt = $db->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 20");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Security/Audit Logs Check</h1>";
    echo "<pre>";
    print_r($logs);
    echo "</pre>";
} catch (Exception $e) {
    echo "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
}
