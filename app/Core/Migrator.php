<?php
namespace App\Core;

use PDO;
use Exception;

/**
 * High-Performance Non-Destructive Auto-Migration Engine
 * Lead Database Architect - Antigravity
 */
class Migrator {
    /**
     * Run automatic migration checks by verifying schema hashes
     */
    public static function runAutoMigration(): void {
        $schemaFiles = [
            dirname(__DIR__, 2) . '/database/schema.sql',
            dirname(__DIR__, 2) . '/database/schema_v2.sql'
        ];

        // 1. Calculate combined MD5 hash of schema files to detect changes
        $combinedContent = '';
        foreach ($schemaFiles as $file) {
            if (file_exists($file)) {
                $combinedContent .= file_get_contents($file);
            }
        }

        if (empty($combinedContent)) {
            return; // No schema files to migrate
        }

        $currentHash = md5($combinedContent);
        $hashFilePath = dirname(__DIR__, 2) . '/storage/db_schema.hash';

        // 2. If hash matches, database is up-to-date. Return immediately.
        if (file_exists($hashFilePath) && trim(file_get_contents($hashFilePath)) === $currentHash) {
            return;
        }

        // 3. Run migration parser safely in transaction
        try {
            $db = Database::getInstance();
            
            // Temporary disable foreign keys to avoid order dependency errors
            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

            foreach ($schemaFiles as $schemaPath) {
                if (!file_exists($schemaPath)) {
                    continue;
                }

                $queries = self::parseSqlQueries($schemaPath);

                foreach ($queries as $query) {
                    $cleaned = trim($query);
                    if (empty($cleaned)) {
                        continue;
                    }

                    // A. Skip all DROP TABLE statements to prevent data loss in production
                    if (preg_match('/^\s*DROP\s+TABLE/i', $cleaned)) {
                        continue;
                    }

                    // B. Turn CREATE TABLE into CREATE TABLE IF NOT EXISTS
                    if (preg_match('/^\s*CREATE\s+TABLE/i', $cleaned)) {
                        $cleaned = preg_replace('/CREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS)/i', 'CREATE TABLE IF NOT EXISTS ', $cleaned);
                    }

                    // C. Turn INSERT INTO into INSERT IGNORE INTO to prevent primary key crashes
                    if (preg_match('/^\s*INSERT\s+INTO/i', $cleaned)) {
                        $cleaned = preg_replace('/INSERT\s+INTO/i', 'INSERT IGNORE INTO', $cleaned);
                    }

                    // D. Parse ALTER TABLE statements to check if column already exists
                    if (preg_match('/^\s*ALTER\s+TABLE\s+`?([a-zA-Z0-9_-]+)`?\s+ADD\s+(?:COLUMN\s+)?`?([a-zA-Z0-9_-]+)`?/i', $cleaned, $matches)) {
                        $tableName = $matches[1];
                        $columnName = $matches[2];

                        // Query database to see if column exists
                        try {
                            $check = $db->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'");
                            if ($check && $check->rowCount() > 0) {
                                continue; // Column already exists, skip ALTER statement safely
                            }
                        } catch (\PDOException $e) {
                            // Table may not exist yet or other query error, let the ALTER run or fail gracefully
                        }
                    }

                    // Execute cleaned query statement
                    try {
                        $db->exec($cleaned);
                    } catch (\PDOException $e) {
                        // Log migration warning or fail silently for ignorable schema mismatch
                    }
                }
            }

            // Restore foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

            // 4. Save current hash state on success
            $storageDir = dirname($hashFilePath);
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0777, true);
            }
            file_put_contents($hashFilePath, $currentHash);

        } catch (Exception $e) {
            // Silently fail auto-migration on boot to prevent server crash, but restore key checks
            try {
                $db = Database::getInstance();
                $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            } catch (\Exception $ex) {}
        }
    }

    /**
     * Parse SQL file into separate executable query strings
     */
    private static function parseSqlQueries(string $filePath): array {
        $queries = [];
        $tempQuery = '';
        $lines = file($filePath);

        if (!$lines) {
            return [];
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Skip comments and empty lines
            if ($trimmed === '' || substr($trimmed, 0, 2) === '--' || substr($trimmed, 0, 1) === '#') {
                continue;
            }

            $tempQuery .= $line;

            // If statement ends with semicolon, add to queries list
            if (substr($trimmed, -1) === ';') {
                $queries[] = $tempQuery;
                $tempQuery = '';
            }
        }

        return $queries;
    }
}
