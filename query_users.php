<?php
require_once "app/Core/Database.php";
try {
    $db = App\Core\Database::getInstance();
    $stmt = $db->query("SELECT id, email, role_id, company_id, status FROM users");
    $users = $stmt->fetchAll();
    echo "Found " . count($users) . " users:\n";
    foreach ($users as $u) {
        echo "ID: {$u[\x27id\x27]} | Email: {$u[\x27email\x27]} | Role: {$u[\x27role_id\x27]} | Company: " . ($u[\x27company_id\x27] ?? \x27NULL\x27) . " | Status: {$u[\x27status\x27]}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

