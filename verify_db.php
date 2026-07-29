<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance();
    $stmt = $db->query("SELECT id, email, status FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Database Connection: SUCCESS</h1>";
    echo "<h3>Users list:</h3><pre>";
    print_r($users);
    echo "</pre>";
    
    $stmt = $db->query("SELECT id, name, subdomain FROM companies");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Companies list:</h3><pre>";
    print_r($companies);
    echo "</pre>";
} catch (Exception $e) {
    echo "<h1>Database Connection: FAILED</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
