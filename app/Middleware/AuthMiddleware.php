<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Session;

/**
 * Authentication Gatekeeper & Portal Isolation Middleware
 * Multi-Tenant Security Architect - Antigravity
 */
class AuthMiddleware extends Middleware {
    public function handle(Request $request, Response $response, array $params, ?\App\Core\Route $route = null): bool {
        if (!Auth::check()) {
            Session::setFlash('error', 'Please log in to access this page.');
            $response->redirect(base_url('login'));
            return false;
        }

        $path = $request->getPath();
        $isDev = Session::get('is_developer_session');
        $companyId = Session::get('company_id');

        // Prevent tenant users from accessing Developer Portal routes
        if (str_starts_with($path, '/developer/') && !$isDev && $companyId !== null) {
            Session::setFlash('error', 'Access Denied: You do not have Developer Portal permissions.');
            $response->redirect(base_url(Auth::getFirstAccessibleCompanyUrl()));
            return false;
        }

        // Active Session Role Validation
        $userId = Session::get('user_id');
        $roleId = Session::get('role_id');

        if ($userId && $roleId && !$isDev) {
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("SELECT id FROM roles WHERE id = ? AND (company_id = ? OR company_id IS NULL) AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$roleId, $companyId]);
            if (!$stmt->fetch()) {
                Auth::logout();
                Session::setFlash('error', 'Your assigned role has been deleted. Please contact your Company Admin.');
                $response->redirect(base_url('login'));
                return false;
            }
        }

        return true;
    }
}
