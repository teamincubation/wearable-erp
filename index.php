<?php
/**
 * Wearable ERP SaaS Front Controller
 * Lead Software Architect - Antigravity
 */

// 1. Load general configurations
require_once __DIR__ . '/config/config.php';

// 2. Register Autoloader for App namespace (PSR-4 compliant)
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Class doesn't use the namespace prefix
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 3. Set secure error reporting based on App Environment
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\TenantMiddleware;

try {
    // Run automatic database migrations if any schema updates were pushed
    \App\Core\Migrator::runAutoMigration();

    // 4. Initialize secure session management
    Session::start();

    // 5. Build core Request and Response wrappers
    $request = new Request();
    $response = new Response();

    // 6. Enforce global tenant detection before any routing
    $tenantMiddleware = new TenantMiddleware();
    $tenantMiddleware->handle($request, $response, []);

    // 7. Instantiate router and load routing definitions
    $router = new Router($request, $response);
    
    // Load route configurations
    require_once __DIR__ . '/routes/web.php';

    // 8. Resolve request
    $router->resolve();

} catch (\Exception $e) {
    // Global Exception Handling
    if (APP_DEBUG) {
        echo "<h2>Application Exception:</h2>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Trace:</strong> <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>";
    } else {
        http_response_code(500);
        include_once __DIR__ . '/app/Views/errors/500.php';
    }
    exit;
}
