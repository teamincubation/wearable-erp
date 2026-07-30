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

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $subdomain = null;

        // 1. Resolve Subdomain from Host
        if ($host === DEV_PORTAL_HOST) {
            $subdomain = 'erp';
        } else {
            $overrideTenant = $request->get('tenant');
            if ($overrideTenant) {
                $subdomain = $overrideTenant;
            } else {
                $parts = explode('.', $host);
                if (count($parts) >= 3) {
                    $subdomain = $parts[0];
                }
            }
        }

        // 2. Developer Portal Context
        if ($subdomain === 'erp' || $host === DEV_PORTAL_HOST) {
            // Strict Isolation: Prevent authenticated tenant users from entering DEV_PORTAL_HOST
            if (\App\Core\Session::has('company_id') && !\App\Core\Session::get('is_developer_session')) {
                $tenant = \App\Core\Session::get('current_tenant');
                if ($tenant && !empty($tenant['subdomain']) && defined('ROOT_DOMAIN')) {
                    $tenantHost = $tenant['subdomain'] . '.' . ROOT_DOMAIN;
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                    header("Location: " . $protocol . $tenantHost . $request->getPath());
                    exit;
                }
            }
            Model::setActiveCompanyId(null);
            \App\Core\Session::set('current_tenant', null);
            return true;
        }

        // 3. Tenant Context Validation
        if ($subdomain) {
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT * FROM companies WHERE subdomain = ? AND deleted_at IS NULL LIMIT 1");
                $stmt->execute([$subdomain]);
                $requestedCompany = $stmt->fetch();

                if ($requestedCompany) {
                    if ($requestedCompany['status'] !== 'active') {
                        $response->setStatusCode(403);
                        echo "<h3>Access Denied: This company account is suspended or inactive. Please contact support.</h3>";
                        exit;
                    }

                    // Strict Cross-Tenant Leakage Prevention
                    if (\App\Core\Session::has('company_id') && \App\Core\Session::get('company_id') !== null) {
                        $sessionCompanyId = (int)\App\Core\Session::get('company_id');
                        
                        if ($sessionCompanyId !== (int)$requestedCompany['id']) {
                            // User is logged into a DIFFERENT tenant. 
                            // Destroy session to prevent data leakage and force re-authentication for THIS tenant.
                            \App\Core\Session::destroy();
                            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                            header("Location: " . $protocol . $host . "/login");
                            exit;
                        }
                    }

                    // Set active tenant scope securely
                    Model::setActiveCompanyId($requestedCompany['id']);
                    \App\Core\Session::set('current_tenant', $requestedCompany);
                    if (!empty($requestedCompany['timezone'])) {
                        date_default_timezone_set($requestedCompany['timezone']);
                    }
                    return true;
                }
            } catch (\Exception $e) {
                // DB not ready yet or query failed
            }
        }

        // 4. No valid tenant resolved (e.g. root domain)
        Model::setActiveCompanyId(null);
        \App\Core\Session::set('current_tenant', null);
        return true;
    }
}
