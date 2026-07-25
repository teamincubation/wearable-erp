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
                // Refresh CSRF token for next request
                Session::remove('csrf_token');
                Session::csrfToken();

                if ($request->isAjax()) {
                    $response->json(['error' => 'Security Error: CSRF token mismatch.'], 403);
                    return false;
                }

                Session::setFlash('error', 'Security Notice: Session expired or CSRF token mismatch. Please try again.');
                $response->redirect(base_url('login'));
                return false;
            }
        }
        return true;
    }
}
