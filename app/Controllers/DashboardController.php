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

    /**
     * API: Get real-time graphical data for Dashboard Chart
     */
    public function getProductionChartData(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $companyId = Session::get('company_id');
        if (!$companyId) {
            $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $filter = $request->get('filter') ?: 'weekly'; // weekly, monthly, yearly
        
        $requestedStages = $request->get('stages');
        $stages = [];
        if (!empty($requestedStages)) {
            if (is_array($requestedStages)) {
                $stages = array_map('trim', $requestedStages);
            } else {
                $stages = array_map('trim', explode(',', (string)$requestedStages));
            }
        }
        if (empty($stages)) {
            $stages = ['knitting', 'sewing', 'packing']; // Default fallback
        }

        $db = \App\Core\Database::getInstance();
        $labels = [];
        $datasets = [];
        
        $colorPalette = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316'];
        $stageColors = [];
        foreach ($stages as $idx => $stage) {
            $datasets[$stage] = [];
            $stageColors[$stage] = $colorPalette[$idx % count($colorPalette)];
        }
        
        $placeholders = str_repeat('?,', count($stages) - 1) . '?';
        $params = array_merge([$companyId], $stages);

        if ($filter === 'weekly') {
            for ($i = 6; $i >= 0; $i--) {
                $labels[] = date('D', strtotime("-$i days"));
            }
            
            $stmt = $db->prepare("
                SELECT DATE(created_at) as log_date, stage, SUM(qty_out) as total
                FROM production_stage_logs 
                WHERE company_id = ? AND stage IN ($placeholders)
                  AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(created_at), stage
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll() ?: [];

            $map = [];
            for ($i = 6; $i >= 0; $i--) {
                $dateKey = date('Y-m-d', strtotime("-$i days"));
                $map[$dateKey] = array_fill_keys($stages, 0);
            }
            foreach ($results as $row) {
                if (isset($map[$row['log_date']]) && isset($map[$row['log_date']][$row['stage']])) {
                    $map[$row['log_date']][$row['stage']] += (int)$row['total'];
                }
            }
            foreach ($map as $dateKey => $stageTotals) {
                foreach ($stages as $stage) {
                    $datasets[$stage][] = $stageTotals[$stage];
                }
            }

        } elseif ($filter === 'monthly') {
            $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            $map = [
                1 => array_fill_keys($stages, 0),
                2 => array_fill_keys($stages, 0),
                3 => array_fill_keys($stages, 0),
                4 => array_fill_keys($stages, 0),
            ];

            $stmt = $db->prepare("
                SELECT DATEDIFF(CURDATE(), DATE(created_at)) as days_ago, stage, SUM(qty_out) as total
                FROM production_stage_logs 
                WHERE company_id = ? AND stage IN ($placeholders)
                  AND created_at >= DATE_SUB(CURDATE(), INTERVAL 27 DAY)
                GROUP BY DATE(created_at), stage
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll() ?: [];

            foreach ($results as $row) {
                $daysAgo = (int)$row['days_ago'];
                if ($daysAgo <= 6) $weekIdx = 4;
                elseif ($daysAgo <= 13) $weekIdx = 3;
                elseif ($daysAgo <= 20) $weekIdx = 2;
                else $weekIdx = 1;
                
                if (isset($map[$weekIdx][$row['stage']])) {
                    $map[$weekIdx][$row['stage']] += (int)$row['total'];
                }
            }

            for ($i = 1; $i <= 4; $i++) {
                foreach ($stages as $stage) {
                    $datasets[$stage][] = $map[$i][$stage];
                }
            }

        } elseif ($filter === 'yearly') {
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $labels = $months;

            $map = [];
            for ($i = 1; $i <= 12; $i++) {
                $map[$i] = array_fill_keys($stages, 0);
            }

            $currentYear = date('Y');
            $params = array_merge([$companyId, $currentYear], $stages);
            $stmt = $db->prepare("
                SELECT MONTH(created_at) as log_month, stage, SUM(qty_out) as total
                FROM production_stage_logs 
                WHERE company_id = ? AND YEAR(created_at) = ? AND stage IN ($placeholders)
                GROUP BY MONTH(created_at), stage
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll() ?: [];

            foreach ($results as $row) {
                $m = (int)$row['log_month'];
                if (isset($map[$m]) && isset($map[$m][$row['stage']])) {
                    $map[$m][$row['stage']] += (int)$row['total'];
                }
            }

            for ($i = 1; $i <= 12; $i++) {
                foreach ($stages as $stage) {
                    $datasets[$stage][] = $map[$i][$stage];
                }
            }
        }

        $formattedDatasets = [];
        foreach ($stages as $stage) {
            $formattedDatasets[] = [
                'label' => ucwords(str_replace('_', ' ', $stage)),
                'data' => $datasets[$stage],
                'backgroundColor' => $stageColors[$stage],
                'borderRadius' => 4,
                'barPercentage' => 0.6
            ];
        }

        $chartData = [
            'labels' => $labels,
            'datasets' => $formattedDatasets
        ];

        $response->json([
            'success' => true,
            'data' => $chartData
        ]);
    }
}
