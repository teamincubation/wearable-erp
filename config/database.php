<?php
/**
 * Wearable ERP SaaS Database Configuration
 * Database Architect - Antigravity
 */

return [
    'host'      => '127.0.0.1',
    'port'      => '3306',
    'database'  => 'wearable_erp',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]
];
