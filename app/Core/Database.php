<?php
namespace App\Core;

use PDO;
use Exception;

/**
 * Singleton Database Connection Wrapper using PDO
 * Database Architect - Antigravity
 */
class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Get the PDO Database Instance
     * @return PDO
     * @throws Exception
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $configPath = dirname(__DIR__, 2) . '/config/database.php';
            if (!file_exists($configPath)) {
                throw new Exception("Database configuration file not found at " . $configPath);
            }

            $config = require $configPath;

            try {
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset']
                );

                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (\PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
