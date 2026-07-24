<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Session;

/**
 * Role-Based Access Control (RBAC) Permission Middleware
 * Lead Software Architect - Antigravity
 */
class PermissionMiddleware extends Middleware {
    public function handle(Request $request, Response $response, array $params, ?\App\Core\Route $route = null): bool {
        if (!$route) {
            return true;
        }

        $requiredPermission = $route->getPermission();
        if ($requiredPermission) {
            Session::set('current_page_permission', $requiredPermission);
        }

        if ($requiredPermission && !Auth::hasPermission($requiredPermission)) {
            if ($request->isAjax()) {
                $response->json(['error' => 'Forbidden: Insufficient privileges.'], 403);
                return false;
            }

            Session::setFlash('error', 'Access Denied: You do not have the required permissions to perform this action.');
            
            // Redirect to appropriate dashboard based on session context
            if (Session::get('is_developer_session') || Session::get('company_id') === null) {
                $response->redirect(base_url('developer/dashboard'));
            } else {
                $fallbackUrl = Auth::getFirstAccessibleCompanyUrl();
                // Prevent redirect loop if the fallback URL requires the permission we just failed on
                if ($fallbackUrl === 'logout' || ltrim($request->getUri(), '/') === $fallbackUrl) {
                    $response->redirect(base_url('logout'));
                } else {
                    $response->redirect(base_url($fallbackUrl));
                }
            }
            return false;
        }

        return true;
    }
}
