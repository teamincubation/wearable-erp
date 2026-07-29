<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance();
    $identifier = 'admin';
    
    $stmtDev = $db->prepare("
        SELECT u.*, c.status as company_status 
        FROM users u
        LEFT JOIN companies c ON u.company_id = c.id
        WHERE (u.email = ? OR u.employee_code = ? OR u.phone = ? OR (u.email LIKE ? AND SUBSTRING_INDEX(u.email, '@', 1) = ?)) AND u.deleted_at IS NULL
        ORDER BY (CASE WHEN u.company_id IS NULL THEN 1 ELSE 2 END) ASC
        LIMIT 1
    ");
    $stmtDev->execute([$identifier, $identifier, $identifier, $identifier . '@%', $identifier]);
    $user = $stmtDev->fetch(PDO::FETCH_ASSOC);
    
    echo "<h1>Database Query Test for 'admin'</h1>";
    if ($user) {
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        echo "<p>Is password valid: " . (password_verify('Admin@1234', $user['password_hash']) ? 'YES' : 'NO') . "</p>";
    } else {
        echo "<p>No user matched query!</p>";
    }
} catch (Exception $e) {
    echo "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
}
