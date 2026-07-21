<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Session;

/**
 * Authentication Gatekeeper Middleware
 * DevOps & Security Engineer - Antigravity
 */
class AuthMiddleware extends Middleware {
    public function handle(Request $request, Response $response, array $params, ?\App\Core\Route $route = null): bool {
        if (!Auth::check()) {
            Session::setFlash('error', 'Please log in to access this page.');

            // Preserve active tenant query parameters if present
            $tenant = Session::get('active_tenant_subdomain');
            $loginUrl = 'login';
            if ($tenant) {
                $loginUrl .= '?tenant=' . urlencode($tenant);
            }

            $response->redirect(base_url($loginUrl));
            return false;
        }
        return true;
    }
}
