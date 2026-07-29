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
        $usersCount = count($userModel->getActiveCompanyEmployees());

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

        // Dynamic ERP counts
        $productionCount = $db->query("SELECT COUNT(*) FROM production_orders WHERE company_id = {$companyId} AND deleted_at IS NULL")->fetchColumn();
        $contractsValue = $db->query("SELECT IFNULL(SUM(total_amount), 0.00) FROM buyer_pos WHERE company_id = {$companyId} AND deleted_at IS NULL")->fetchColumn();
        $uniqueStockCount = $db->query("SELECT COUNT(DISTINCT item_name) FROM inventory_transactions WHERE company_id = {$companyId}")->fetchColumn();
        
        $inspectStats = $db->query("SELECT SUM(inspected_qty) as total_inspected, SUM(failed_qty) as total_failed FROM quality_inspections WHERE company_id = {$companyId} AND deleted_at IS NULL")->fetch();
        $rejectRate = 0.0;
        if (!empty($inspectStats['total_inspected']) && $inspectStats['total_inspected'] > 0) {
            $rejectRate = ($inspectStats['total_failed'] / $inspectStats['total_inspected']) * 100;
        }

        // Fetch active running production batches for live dashboard shortcuts
        $stmtActive = $db->prepare("
            SELECT pro.id, pro.production_no, pro.started_at, pro.status,
                   s.style_no, s.name as style_name, po.po_no as buyer_po_no, po.quantity as target_qty,
                   c.name as buyer_name
            FROM production_orders pro
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN contacts c ON po.buyer_id = c.id
            WHERE pro.company_id = ? AND pro.status IN ('running', 'in_progress') AND pro.deleted_at IS NULL
            ORDER BY pro.id DESC
        ");
        $stmtActive->execute([$companyId]);
        $activeBatches = $stmtActive->fetchAll() ?: [];

        $this->renderView('company/dashboard', [
            'title' => 'Dashboard | ' . $company['name'],
            'company' => $company,
            'users_count' => $usersCount,
            'roles_count' => $rolesCount,
            'recent_logs' => $recentLogs,
            'features' => $features,
            'production_count' => $productionCount,
            'contracts_value' => $contractsValue,
            'unique_stock_count' => $uniqueStockCount,
            'reject_rate' => $rejectRate,
            'active_batches' => $activeBatches
        ]);
    }
}
