<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Models\Company;

/**
 * Main Portal and Tenant Dashboard Controller
 * Full Stack PHP Engineer - Antigravity
 */
class DashboardController extends Controller {
    /**
     * Show SaaS landing/demonstration portal (with pilot shortcuts)
     */
    public function landing(Request $request, Response $response): void {
        $companyModel = new Company();
        $companies = $companyModel->all(); // List available tenants

        $this->renderView('landing', [
            'title' => 'Wearable ERP | Apparel SaaS Platform',
            'companies' => $companies
        ], 'auth');
    }

    /**
     * Show company dashboard
     */
    public function index(Request $request, Response $response): void {
        $companyId = Session::get('company_id');
        if ($companyId === null) {
            // No company context - redirect to Developer Portal dashboard
            $this->redirect('developer/dashboard');
        }

        $companyModel = new Company();
        $company = $companyModel->find($companyId);

        $userModel = new User();
        $usersCount = count($userModel->all());

        $roleModel = new Role();
        $rolesCount = count($roleModel->all());

        // Get recent audit logs for this company
        $auditModel = new AuditLog();
        $recentLogs = $auditModel->findBy([], 'id DESC LIMIT 5');

        // Fetch company feature flags
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT feature_key, status FROM feature_flags WHERE company_id = ?");
        $stmt->execute([$companyId]);
        $features = $stmt->fetchAll() ?: [];

        $this->renderView('company/dashboard', [
            'title' => 'Dashboard | ' . $company['name'],
            'company' => $company,
            'users_count' => $usersCount,
            'roles_count' => $rolesCount,
            'recent_logs' => $recentLogs,
            'features' => $features
        ]);
    }
}
