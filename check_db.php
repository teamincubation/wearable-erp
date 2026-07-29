<?php
require_once __DIR__ . '/config/config.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

try {
    $db = Database::getInstance();
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Purge TOCCO Exports
    $stmtT = $db->query("SELECT id FROM companies WHERE LOWER(subdomain) = 'tocco' OR LOWER(name) LIKE '%tocco%'");
    $toccoIds = $stmtT->fetchAll(PDO::FETCH_COLUMN) ?: [];
    
    if (!empty($toccoIds)) {
        echo "Found TOCCO IDs: " . implode(', ', $toccoIds) . "\n";
        foreach ($toccoIds as $tId) {
            $stmtTables = $db->query("SELECT DISTINCT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'company_id'");
            $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN) ?: [];
            foreach ($tables as $tbl) {
                if ($tbl === 'companies') continue;
                try {
                    $db->prepare("DELETE FROM `{$tbl}` WHERE company_id = ?")->execute([(int)$tId]);
                    echo "Deleted from $tbl\n";
                } catch (\Exception $ex) {
                    echo "Error deleting from $tbl: " . $ex->getMessage() . "\n";
                }
            }
            try {
                $db->prepare("DELETE FROM role_permissions WHERE role_id IN (SELECT id FROM roles WHERE company_id = ?)")->execute([(int)$tId]);
                echo "Deleted from role_permissions\n";
            } catch (\Exception $ex) {}
            $db->prepare("DELETE FROM companies WHERE id = ?")->execute([(int)$tId]);
            echo "Deleted company ID: $tId\n";
        }
    } else {
        echo "No TOCCO found in companies table.\n";
    }
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
