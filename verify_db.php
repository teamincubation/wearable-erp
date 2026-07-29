<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance();
    $stmt = $db->query("SELECT id, email, password_hash, status FROM users WHERE id = 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h1>Database Connection: SUCCESS</h1>";
    if ($user) {
        echo "<p>User ID: " . $user['id'] . "</p>";
        echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
        echo "<p>Hash in DB: " . htmlspecialchars($user['password_hash']) . "</p>";
        echo "<p>Is valid for 'Admin@1234': " . (password_verify('Admin@1234', $user['password_hash']) ? 'YES' : 'NO') . "</p>";
    } else {
        echo "<p>User ID 1 not found!</p>";
    }
} catch (Exception $e) {
    echo "<h1>Database Connection: FAILED</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
