<?php
require_once "config/config.php";
require_once "app/Core/Database.php";
require_once "app/Core/Auth.php";
require_once "app/Core/Model.php";

$db = \App\Core\Database::getInstance();
$stmt = $db->query("SELECT * FROM companies");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Companies:\n";
foreach ($companies as $c) {
    echo "- {$c['id']}: {$c['subdomain']} ({$c['name']})\n";
}

$stmt = $db->query("SELECT id, company_id, name, email, employee_code, deleted_at FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nUsers:\n";
foreach ($users as $u) {
    echo "- ID: {$u['id']} | Comp: {$u['company_id']} | Email: {$u['email']} | Code: {$u['employee_code']} | Deleted: {$u['deleted_at']}\n";
}
