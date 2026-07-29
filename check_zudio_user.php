<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance();
    $stmt = $db->query("SELECT u.*, c.name as company_name FROM users u JOIN companies c ON u.company_id = c.id WHERE c.subdomain = 'zudiotest' LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h1>Zudio Test Super Admin User Check</h1>";
    if ($user) {
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        echo "<p>Is password 'Password@123' valid: " . (password_verify('Password@123', $user['password_hash']) ? 'YES' : 'NO') . "</p>";
        echo "<p>Is password 'admin' valid: " . (password_verify('admin', $user['password_hash']) ? 'YES' : 'NO') . "</p>";
    } else {
        echo "<p>No user found for company zudiotest!</p>";
    }
} catch (Exception $e) {
    echo "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
}
