<?php
/**
 * Wearable ERP SaaS Database Migration Setup Utility
 * Security Architect & Full Stack Developer - Antigravity
 */

// 1. Simple Security Access check to prevent unauthorized execution
$setupSecret = 'WellgroSetup2026';
if (($secret = $_GET['secret'] ?? null) !== $setupSecret) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo "<div style='font-family:sans-serif; padding:50px; text-align:center;'>";
    echo "<h2 style='color:#ef4444;'>🚫 Access Denied</h2>";
    echo "<p>Invalid migration secret token. Please supply the correct secret parameter.</p>";
    echo "</div>";
    exit;
}

// 2. Load configurations and autoloader
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

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Database Migration Tool</title><style>
body { font-family: 'Segoe UI', sans-serif; background-color:#f1f5f9; padding:40px; color:#1e293b; }
.card { background:#fff; border-radius:12px; padding:30px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); max-width:800px; margin:0 auto; }
h3 { color:#4f46e5; border-bottom: 2px solid #e2e8f0; padding-bottom:10px; }
pre { background:#f8fafc; padding:15px; border-radius:6px; border:1px solid #e2e8f0; overflow-x:auto; font-size:13px; }
.success { color:#10b981; font-weight:bold; }
.error { color:#ef4444; font-weight:bold; }
</style></head><body>";

echo "<div class='card'>";
echo "<h3><i class='fa-solid fa-database'></i> Database Migration Logs</h3>";

try {
    echo "Connecting to MySQL server... ";
    $db = Database::getInstance();
    echo "<span class='success'>CONNECTED</span><br>";

    $schemaFiles = [
        __DIR__ . '/database/schema.sql',
        __DIR__ . '/database/schema_v2.sql'
    ];

    foreach ($schemaFiles as $schemaPath) {
        $basename = basename($schemaPath);
        if (!file_exists($schemaPath)) {
            throw new Exception("Schema script not found at path: " . $schemaPath);
        }

        echo "Reading schema SQL file ($basename)... ";
        $sqlContent = file_get_contents($schemaPath);
        echo "<span class='success'>READ SUCCESS</span><br>";

        // Parse SQL commands separated by semicolons
        $queries = [];
        $tempQuery = '';
        $lines = file($schemaPath);

        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Skip comment lines and empty lines
            if ($trimmed === '' || substr($trimmed, 0, 2) === '--' || substr($trimmed, 0, 1) === '#') {
                continue;
            }

            $tempQuery .= $line;

            // If statement ends with semicolon, add it to list
            if (substr($trimmed, -1) === ';') {
                $queries[] = $tempQuery;
                $tempQuery = '';
            }
        }

        echo "Executing migrations for $basename (" . count($queries) . " SQL statements)...<br>";

        foreach ($queries as $index => $query) {
            $db->exec($query);
            // Extract statement type for clean log
            $type = preg_match('/^\s*([a-zA-Z]+)/i', $query, $matches) ? strtoupper($matches[1]) : 'SQL';
            echo "   [$basename - Statement " . ($index + 1) . "] Executing $type... <span class='success'>OK</span><br>";
        }
        echo "<br>";
    }

    echo "<br><h4 class='success'>🎉 ALL DATABASE SCHEMAS SEEDED & INITIALIZED SUCCESSFULLY!</h4>";
    echo "<p>Please verify your portal. <strong>For safety, you should delete the <code>db_setup.php</code> file from your server root folder now.</strong></p>";

} catch (Exception $e) {
    $originalMessage = $e->getMessage();
    echo "<br><h4 class='error'>❌ Migration Failed!</h4>";
    echo "<pre>" . htmlspecialchars($originalMessage) . "</pre>";
    echo "<p>Please check your database configurations in <code>config/database.php</code>.</p>";
}

echo "</div></body></html>";
