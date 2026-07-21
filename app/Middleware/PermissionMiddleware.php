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
            
            // Redirect to appropriate dashboard based on company context
            $companyId = Session::get('company_id');
            if ($companyId === null) {
                $response->redirect(base_url('developer/dashboard'));
            } else {
                $response->redirect(base_url('company/dashboard'));
            }
            return false;
        }

        return true;
    }
}
