<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\ProductionOrder;
use App\Models\ProductionStageLog;
use App\Models\QualityInspection;
use App\Models\Style;
use App\Models\BuyerPo;
use App\Models\Machine;
use App\Models\User;
use App\Services\ProductionService;
use App\Models\AuditLog;

/**
 * Production and Quality Control Controller
 * Full Stack Developer - Antigravity
 */
class ProductionController extends Controller {
    /**
     * Production Orders Overview
     */
    public function orders(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch production orders joined with style & buyer PO details
        $stmt = $db->prepare("SELECT pro.*, s.style_no, s.name as style_name, po.po_no as buyer_po_no, po.quantity as target_qty
                             FROM production_orders pro
                             JOIN buyer_pos po ON pro.po_id = po.id
                             JOIN styles s ON po.style_id = s.id
                             WHERE pro.company_id = ? AND pro.deleted_at IS NULL
                             ORDER BY pro.id DESC");
        $stmt->execute([$companyId]);
        $orders = $stmt->fetchAll() ?: [];

        // Fetch active buyer POs joined with active buyer details
        $stmt = $db->prepare("
            SELECT po.id, po.po_no, po.style_id, s.style_no, c.name as buyer_name, c.code as buyer_code, c.brand_name
            FROM buyer_pos po
            JOIN contacts c ON po.buyer_id = c.id
            JOIN styles s ON po.style_id = s.id
            WHERE po.company_id = ? AND po.status IN ('approved', 'active') AND c.status = 'active' AND po.deleted_at IS NULL AND c.deleted_at IS NULL
            ORDER BY po.id DESC
        ");
        $stmt->execute([$companyId]);
        $buyerPOs = $stmt->fetchAll() ?: [];

        $this->renderView('company/production_orders', [
            'title' => 'Production Orders | ERP',
            'orders' => $orders,
            'buyer_pos' => $buyerPOs
        ]);
    }

    /**
     * Create Production Order
     */
    public function createOrder(Request $request, Response $response): void {
        $poId = (int)$request->get('po_id');
        $productionNo = trim($request->get('production_no'));
        $startDate = $request->get('start_date');

        if (empty($poId) || empty($productionNo) || empty($startDate)) {
            Session::setFlash('error', 'Linked Buyer PO, Production Number, and Start Date are required.');
            $this->redirect('company/production/orders');
        }

        $orderModel = new ProductionOrder();
        $orderId = $orderModel->insert([
            'po_id' => $poId,
            'production_no' => $productionNo,
            'start_date' => $startDate,
            'status' => 'pending',
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_production_order', 'ProductionOrder', $orderId, null, null, "Created production order: {$productionNo}");
        Session::setFlash('success', 'Production order created and is pending operations setup.');
        $this->redirect('company/production/orders');
    }

    /**
     * Production Stage Tracker & WIP logs
     */
    public function stage(Request $request, Response $response, string $id): void {
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        // Fetch production order details
        $stmt = $db->prepare("SELECT pro.*, s.style_no, s.name as style_name, po.po_no as buyer_po_no, po.quantity as target_qty
                             FROM production_orders pro
                             JOIN buyer_pos po ON pro.po_id = po.id
                             JOIN styles s ON po.style_id = s.id
                             WHERE pro.id = ? AND pro.company_id = ? AND pro.deleted_at IS NULL");
        $stmt->execute([$id, $companyId]);
        $order = $stmt->fetch();

        if (!$order) {
            Session::setFlash('error', 'Production order not found.');
            $this->redirect('company/production/orders');
        }

        $productionService = new ProductionService();
        $wipSummary = $productionService->getOrderWipSummary($companyId, (int)$id);

        // Fetch stage history logs
        $stmt = $db->prepare("SELECT psl.*, m.name as machine_name, u.name as employee_name
                             FROM production_stage_logs psl
                             LEFT JOIN machines m ON psl.machine_id = m.id
                             LEFT JOIN users u ON psl.employee_id = u.id
                             WHERE psl.production_order_id = ? AND psl.company_id = ?
                             ORDER BY psl.id DESC");
        $stmt->execute([$id, $companyId]);
        $history = $stmt->fetchAll() ?: [];

        // Fetch machines & employees
        $machineModel = new Machine();
        $machines = $machineModel->all();

        $userModel = new User();
        $employees = $userModel->all();

        $this->renderView('company/production_stage', [
            'title' => "Stage Logs: {$order['production_no']} | ERP",
            'order' => $order,
            'wip_summary' => $wipSummary,
            'history' => $history,
            'machines' => $machines,
            'employees' => $employees
        ]);
    }

    /**
     * Submit Production Stage Log
     */
    public function logStage(Request $request, Response $response, string $id): void {
        $stage = $request->get('stage');
        $qtyIn = (int)$request->get('qty_in');
        $qtyOut = (int)$request->get('qty_out');
        $wasteQty = (int)$request->get('waste_qty');
        $machineId = $request->get('machine_id') ? (int)$request->get('machine_id') : null;
        $employeeId = $request->get('employee_id') ? (int)$request->get('employee_id') : null;
        
        $startTimeDate = $request->get('start_date') . ' ' . $request->get('start_time');
        $endTimeDate = $request->get('end_date') . ' ' . $request->get('end_time');

        if (empty($stage) || $qtyIn <= 0 || empty($request->get('start_date')) || empty($request->get('end_date'))) {
            Session::setFlash('error', 'Stage, Quantity In, and Start/End timestamps are required.');
            $this->redirect("company/production/stage/{$id}");
        }

        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $productionService = new ProductionService();

        try {
            $productionService->logProductionStage(
                $companyId,
                (int)$id,
                $stage,
                $machineId,
                $employeeId,
                $qtyIn,
                $qtyOut,
                $wasteQty,
                $startTimeDate,
                $endTimeDate,
                $userId
            );

            // Update order status to running if not already
            $db = Database::getInstance();
            $db->prepare("UPDATE production_orders SET status = 'running' WHERE id = ?")->execute([$id]);

            AuditLog::log($companyId, $userId, 'log_production_stage', 'ProductionOrder', (int)$id, null, null, "Logged stage {$stage} activity for order {$id}");
            Session::setFlash('success', "Production stage {$stage} entry logged successfully.");
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to log stage activity: ' . $e->getMessage());
        }

        $this->redirect("company/production/stage/{$id}");
    }

    /**
     * Quality Control Inspections View
     */
    public function quality(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch inspections
        $stmt = $db->prepare("SELECT qi.*, u.name as inspector_name 
                             FROM quality_inspections qi
                             LEFT JOIN users u ON qi.inspector_id = u.id
                             WHERE qi.company_id = ? AND qi.deleted_at IS NULL
                             ORDER BY qi.id DESC");
        $stmt->execute([$companyId]);
        $inspections = $stmt->fetchAll() ?: [];

        // Fetch active production orders
        $stmt = $db->prepare("SELECT id, production_no FROM production_orders WHERE company_id = ? AND deleted_at IS NULL");
        $stmt->execute([$companyId]);
        $orders = $stmt->fetchAll() ?: [];

        $this->renderView('company/quality', [
            'title' => 'Quality Control Inspections | ERP',
            'inspections' => $inspections,
            'orders' => $orders
        ]);
    }

    /**
     * Create Quality Inspection AQL Log
     */
    public function createInspection(Request $request, Response $response): void {
        $refType = $request->get('reference_type');
        $refId = (int)$request->get('reference_id');
        $inspected = (int)$request->get('inspected_qty');
        $passed = (int)$request->get('passed_qty');
        $failed = (int)$request->get('failed_qty');
        $aqlStatus = $request->get('aql_status');
        
        $rework = (int)$request->get('rework_qty');
        $reject = (int)$request->get('reject_qty');
        
        $defectKey = $request->get('defect_key') ?: [];
        $defectVal = $request->get('defect_val') ?: [];

        if (empty($refType) || empty($refId) || $inspected <= 0 || $passed < 0 || $failed < 0) {
            Session::setFlash('error', 'Inspection reference, Inspected qty, and Pass/Fail counts are required.');
            $this->redirect('company/production/quality');
        }

        $defects = [];
        for ($i = 0; $i < count($defectKey); $i++) {
            if (!empty($defectKey[$i])) {
                $defects[trim($defectKey[$i])] = (int)($defectVal[$i] ?? 0);
            }
        }

        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        $qiModel = new QualityInspection();
        $qiId = $qiModel->insert([
            'reference_type' => $refType,
            'reference_id' => $refId,
            'inspector_id' => $userId,
            'inspected_qty' => $inspected,
            'passed_qty' => $passed,
            'failed_qty' => $failed,
            'aql_status' => $aqlStatus ?: 'pass',
            'defects_json' => json_encode($defects),
            'rework_qty' => $rework,
            'reject_qty' => $reject,
            'created_by' => $userId
        ]);

        AuditLog::log($companyId, $userId, 'create_quality_inspection', 'QualityInspection', $qiId, null, null, "Created quality AQL inspection check: Pass count={$passed}");
        Session::setFlash('success', 'Quality Control AQL inspection report saved successfully.');
        $this->redirect('company/production/quality');
    }

    public function deleteOrder(Request $request, Response $response, string $id): void {
        $model = new ProductionOrder();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Production Order deleted successfully.');
        $this->redirect('company/production/orders');
    }

    public function deleteInspection(Request $request, Response $response, string $id): void {
        $model = new QualityInspection();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Quality inspection record deleted successfully.');
        $this->redirect('company/production/quality');
    }
}
