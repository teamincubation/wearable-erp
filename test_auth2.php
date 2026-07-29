<?php
require_once "config/config.php";
require_once "app/Core/Database.php";
require_once "app/Core/Auth.php";
require_once "app/Core/Model.php";

$db = \App\Core\Database::getInstance();

// Simulate Developer Admin updating a tenant
$companyId = 1;
$adminEmail = 'testadmin@tenant.com';
$adminPassword = 'password123';

// Create a mock tenant and user directly in DB if not exist
$db->exec("INSERT IGNORE INTO companies (id, name, subdomain, email) VALUES (1, 'Test Tenant', 'tenant1', 'test@tenant.com')");
$db->exec("INSERT IGNORE INTO roles (id, company_id, name) VALUES (1, 1, 'Company Admin')");

// Update user
$sql = "UPDATE users SET email = ?, password_hash = ? WHERE company_id = ? LIMIT 1";
$db->prepare($sql)->execute([$adminEmail, password_hash($adminPassword, PASSWORD_BCRYPT), $companyId]);

// Try logging in exactly how Auth::attempt does
\App\Core\Model::setActiveCompanyId(1);
$user = \App\Core\Auth::attempt($adminEmail, $adminPassword);

if ($user) {
    echo "SUCCESS: Logged in as " . $user['email'];
} else {
    echo "FAILED: Auth::attempt returned null.";
}
