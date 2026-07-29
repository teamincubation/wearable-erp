<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance();
    $identifier = 'admin';
    $contextCompanyId = 2;
    
    $stmt = $db->prepare("
        SELECT u.*, c.name as company_name, c.status as company_status 
        FROM users u
        INNER JOIN companies c ON u.company_id = c.id
        WHERE u.company_id = ? AND (u.email = ? OR u.employee_code = ? OR u.phone = ? OR (u.email LIKE ? AND SUBSTRING_INDEX(u.email, '@', 1) = ?)) AND u.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$contextCompanyId, $identifier, $identifier, $identifier, $identifier . '@%', $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h1>Tenant login query check:</h1>";
    if ($user) {
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        echo "<p>Is password 'admin' valid: " . (password_verify('admin', $user['password_hash']) ? 'YES' : 'NO') . "</p>";
    } else {
        echo "<p>No user found matching query!</p>";
    }
} catch (Exception $e) {
    echo "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
}
