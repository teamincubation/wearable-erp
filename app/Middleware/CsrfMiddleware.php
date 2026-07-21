<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Cross-Site Request Forgery (CSRF) Protection Middleware
 * DevOps & Security Engineer - Antigravity
 */
class CsrfMiddleware extends Middleware {
    public function handle(Request $request, Response $response, array $params, ?\App\Core\Route $route = null): bool {
        // Only validate CSRF on POST or other state-changing methods
        if ($request->isPost()) {
            $token = $request->get('csrf_token') ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

            if (!Session::validateCsrf($token)) {
                if ($request->isAjax()) {
                    $response->json(['error' => 'Security Error: CSRF token mismatch.'], 403);
                    return false;
                }

                $response->setStatusCode(403);
                echo "<h3>Security Warning: CSRF token validation failed.</h3><p>Please go back, refresh the page and try again.</p>";
                exit;
            }
        }
        return true;
    }
}
