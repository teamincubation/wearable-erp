<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Model;
use App\Core\Database;

/**
 * Tenant Subdomain Resolution Middleware
 * ERP Solution Architect - Antigravity
 */
class TenantMiddleware extends Middleware {
    public function handle(Request $request, Response $response, array $params, ?\App\Core\Route $route = null): bool {
        // Start secure session if not started
        if (session_status() === PHP_SESSION_NONE) {
            \App\Core\Session::start();
        }

        // 1. If logged in with a tenant company context, prioritize it
        if (\App\Core\Session::has('company_id') && \App\Core\Session::get('company_id') !== null) {
            $companyId = (int)\App\Core\Session::get('company_id');
            Model::setActiveCompanyId($companyId);
            
            if (!\App\Core\Session::has('current_tenant') || \App\Core\Session::get('current_tenant') === null) {
                try {
                    $db = Database::getInstance();
                    $stmt = $db->prepare("SELECT * FROM companies WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                    $stmt->execute([$companyId]);
                    $company = $stmt->fetch();
                    if ($company) {
                        \App\Core\Session::set('current_tenant', $company);
                    }
                } catch (\Exception $e) {
                }
            }
            return true;
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $subdomain = null;

        // 2. Developer query override or session override (For local dev ease)
        $overrideTenant = $request->get('tenant');
        if ($overrideTenant) {
            $subdomain = $overrideTenant;
            \App\Core\Session::set('active_tenant_subdomain', $subdomain);
        } elseif (\App\Core\Session::has('active_tenant_subdomain')) {
            $subdomain = \App\Core\Session::get('active_tenant_subdomain');
        } else {
            // 3. Resolve via Host Subdomain (e.g. tocco.mywellgro.online)
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                // If it looks like subdomain.domain.com, take the first part
                $subdomain = $parts[0];
            }
        }

        // If subdomain is 'erp' or matches DEV_PORTAL_HOST prefix, it's the Developer Portal
        if ($subdomain === 'erp' || $host === DEV_PORTAL_HOST) {
            Model::setActiveCompanyId(null);
            \App\Core\Session::set('current_tenant', null);
            return true;
        }

        // Resolve Tenant
        if ($subdomain) {
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT * FROM companies WHERE subdomain = ? AND deleted_at IS NULL LIMIT 1");
                $stmt->execute([$subdomain]);
                $company = $stmt->fetch();

                if ($company) {
                    if ($company['status'] !== 'active') {
                        $response->setStatusCode(403);
                        echo "<h3>Access Denied: This company account is suspended or inactive. Please contact support.</h3>";
                        exit;
                    }

                    // Set active tenant scope on base Model
                    Model::setActiveCompanyId($company['id']);
                    
                    // Share current tenant data
                    \App\Core\Session::set('current_tenant', $company);
                    return true;
                }
            } catch (\Exception $e) {
                // DB not ready yet or query failed
            }
        }

        // If we are on root domain or localhost and no tenant resolved, load SaaS landing page or Developer Portal
        Model::setActiveCompanyId(null);
        \App\Core\Session::set('current_tenant', null);
        return true;
    }
}
