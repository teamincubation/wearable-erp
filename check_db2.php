<?php
require 'app/Core/Database.php';
$db = \App\Core\Database::getInstance();
$companies = $db->query("SELECT id, name, subdomain FROM companies")->fetchAll(PDO::FETCH_ASSOC);
print_r($companies);

$users = $db->query("SELECT id, name, company_id, email FROM users")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
