<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Session;

/**
 * Authentication Gatekeeper Middleware
 * Multi-Tenant Security & Security Architect - Antigravity
 */
class AuthMiddleware extends Middleware {
    public function handle(Request $request, Response $response, array $params, ?\App\Core\Route $route = null): bool {
        if (!Auth::check()) {
            Session::setFlash('error', 'Please log in to access this page.');

            $tenantCode = Session::get('tenant_code');
            if (!empty($tenantCode)) {
                $response->redirect(base_url("{$tenantCode}/login"));
            } else {
                $response->redirect(base_url('developer/login'));
            }
            return false;
        }

        // Active Session Role Validation (checks if role has been deleted mid-session)
        $userId = Session::get('user_id');
        $roleId = Session::get('role_id');
        $isDev = Session::get('is_developer_session');

        if ($userId && $roleId && !$isDev) {
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("SELECT id FROM roles WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$roleId]);
            if (!$stmt->fetch()) {
                $tenantCode = Session::get('tenant_code');
                Auth::logout();
                Session::setFlash('error', 'Your assigned role has been deleted. Please contact your Company Admin.');
                
                if (!empty($tenantCode)) {
                    $response->redirect(base_url("{$tenantCode}/login"));
                } else {
                    $response->redirect(base_url('developer/login'));
                }
                return false;
            }
        }

        return true;
    }
}
