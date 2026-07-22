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
            WHERE po.company_id = ? AND po.status IN ('approved', 'draft', 'pending_approval') AND c.status = 'active' AND po.deleted_at IS NULL AND c.deleted_at IS NULL
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
        $employees = $userModel->getActiveCompanyEmployees();

        // Fetch active production stages settings
        $stmtStageSettings = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = 'active_production_stages' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
        $stmtStageSettings->execute([$companyId]);
        $activeStagesRaw = $stmtStageSettings->fetchColumn();
        
        $allStagesDefault = ['knitting', 'dyeing', 'compacting', 'relaxing', 'spreading', 'cutting', 'bundling', 'printing', 'embroidery', 'sewing', 'checking', 'thread_cutting', 'washing', 'ironing', 'packing', 'carton_packing', 'shipment'];
        $stagesList = $activeStagesRaw ? json_decode(html_entity_decode($activeStagesRaw), true) : $allStagesDefault;
        if (!is_array($stagesList) || empty($stagesList)) {
            $stagesList = $allStagesDefault;
        }

        $this->renderView('company/production_stage', [
            'title' => "Stage Logs: {$order['production_no']} | ERP",
            'order' => $order,
            'wip_summary' => $wipSummary,
            'history' => $history,
            'machines' => $machines,
            'employees' => $employees,
            'stagesList' => $stagesList
        ]);
    }

    /**
     * Submit Production Stage Log
     */
    public function logStage(Request $request, Response $response, string $id): void {
        $stage = $request->get('stage');
        $qtyIn = $request->get('qty_in') !== null && $request->get('qty_in') !== '' ? (int)$request->get('qty_in') : 0;
        $qtyOut = $request->get('qty_out') !== null && $request->get('qty_out') !== '' ? (int)$request->get('qty_out') : 0;
        $wasteQty = (int)$request->get('waste_qty');
        $machineId = $request->get('machine_id') ? (int)$request->get('machine_id') : null;
        $employeeId = $request->get('employee_id') ? (int)$request->get('employee_id') : null;
        
        $startTimeDate = $request->get('start_date') . ' ' . $request->get('start_time');
        $endTimeDate = $request->get('end_date') . ' ' . $request->get('end_time');

        if (empty($stage) || empty($request->get('start_date')) || empty($request->get('end_date'))) {
            Session::setFlash('error', 'Stage and Start/End timestamps are required.');
            $this->redirect("company/production/stage/{$id}");
            return;
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
     * Edit individual production stage log
     */
    public function editStageLog(Request $request, Response $response, string $id): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        // Fetch log detail to get production_order_id & check ownership
        $stmt = $db->prepare("SELECT * FROM production_stage_logs WHERE id = ? AND company_id = ?");
        $stmt->execute([(int)$id, $companyId]);
        $log = $stmt->fetch();

        if (!$log) {
            Session::setFlash('error', 'Stage log entry not found.');
            $this->redirect('company/production/orders');
            return;
        }

        $stage = $request->get('stage');
        $qtyIn = $request->get('qty_in') !== null && $request->get('qty_in') !== '' ? (int)$request->get('qty_in') : 0;
        $qtyOut = $request->get('qty_out') !== null && $request->get('qty_out') !== '' ? (int)$request->get('qty_out') : 0;
        $wasteQty = (int)$request->get('waste_qty');
        $machineId = $request->get('machine_id') ? (int)$request->get('machine_id') : null;
        $employeeId = $request->get('employee_id') ? (int)$request->get('employee_id') : null;

        $startTimeDate = $request->get('start_date') . ' ' . $request->get('start_time');
        $endTimeDate = $request->get('end_date') . ' ' . $request->get('end_time');

        if (empty($stage) || empty($request->get('start_date')) || empty($request->get('end_date'))) {
            Session::setFlash('error', 'Stage and Start/End timestamps are required.');
            $this->redirect("company/production/stage/{$log['production_order_id']}");
            return;
        }

        // Calculate duration in minutes
        $start = strtotime($startTimeDate);
        $end = strtotime($endTimeDate);
        $durationMinutes = $end > $start ? (int)(($end - $start) / 60) : 0;

        try {
            $stmtUpdate = $db->prepare("UPDATE production_stage_logs 
                                        SET stage = ?, machine_id = ?, employee_id = ?, qty_in = ?, qty_out = ?, waste_qty = ?, start_time = ?, end_time = ?, duration_minutes = ?
                                        WHERE id = ? AND company_id = ?");
            $stmtUpdate->execute([
                $stage,
                $machineId,
                $employeeId,
                $qtyIn,
                $qtyOut,
                $wasteQty,
                $startTimeDate,
                $endTimeDate,
                $durationMinutes,
                (int)$id,
                $companyId
            ]);

            AuditLog::log($companyId, $userId, 'edit_production_stage_log', 'ProductionStageLog', (int)$id, null, null, "Edited stage {$stage} activity for log id {$id}");
            Session::setFlash('success', "Stage log entry updated successfully.");
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to update stage log: ' . $e->getMessage());
        }

        $this->redirect("company/production/stage/{$log['production_order_id']}");
    }

    /**
     * Delete individual production stage log
     */
    public function deleteStageLog(Request $request, Response $response, string $id): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        // Fetch log detail to get production_order_id & check ownership
        $stmt = $db->prepare("SELECT * FROM production_stage_logs WHERE id = ? AND company_id = ?");
        $stmt->execute([(int)$id, $companyId]);
        $log = $stmt->fetch();

        if (!$log) {
            Session::setFlash('error', 'Stage log entry not found.');
            $this->redirect('company/production/orders');
            return;
        }

        try {
            $stmtDelete = $db->prepare("DELETE FROM production_stage_logs WHERE id = ? AND company_id = ?");
            $stmtDelete->execute([(int)$id, $companyId]);

            AuditLog::log($companyId, $userId, 'delete_production_stage_log', 'ProductionStageLog', (int)$id, null, null, "Deleted stage log id {$id}");
            Session::setFlash('success', "Stage log entry deleted successfully.");
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to delete stage log: ' . $e->getMessage());
        }

        $this->redirect("company/production/stage/{$log['production_order_id']}");
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

        $inspectorId = $userId;
        if ($userId === 999999) {
            $db = Database::getInstance();
            $stmtFirstUser = $db->prepare("SELECT id FROM users WHERE company_id = ? AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");
            $stmtFirstUser->execute([$companyId]);
            $realUser = $stmtFirstUser->fetchColumn();
            if ($realUser) {
                $inspectorId = (int)$realUser;
            } else {
                $inspectorId = null;
            }
        }

        $qiModel = new QualityInspection();
        $qiId = $qiModel->insert([
            'reference_type' => $refType,
            'reference_id' => $refId,
            'inspector_id' => $inspectorId,
            'inspected_qty' => $inspected,
            'passed_qty' => $passed,
            'failed_qty' => $failed,
            'aql_status' => $aqlStatus ?: 'pass',
            'defects_json' => json_encode($defects),
            'rework_qty' => $rework,
            'reject_qty' => $reject,
            'created_by' => $inspectorId
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

    /**
     * Generate Manufacturing Batch Barcodes / RFID Cards
     */
    public function generateBatchBarcodes(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $id = (int)$request->get('id');

        // Ensure database table has sizes_json column
        try {
            $db->query("SELECT sizes_json FROM buyer_pos LIMIT 1");
        } catch (\Exception $e) {
            $db->exec("ALTER TABLE buyer_pos ADD COLUMN sizes_json JSON DEFAULT NULL AFTER total_amount");
        }

        $stmt = $db->prepare("
            SELECT pro.*, 
                   s.style_no, s.name as style_name, s.category as style_category, s.composition as fabric_composition, s.brand as style_brand,
                   po.po_no as buyer_po_no, po.quantity as target_qty, po.sizes_json
            FROM production_orders pro
            JOIN buyer_pos po ON pro.po_id = po.id
            JOIN styles s ON po.style_id = s.id
            WHERE pro.id = ? AND pro.company_id = ? AND pro.deleted_at IS NULL
        ");
        $stmt->execute([$id, $companyId]);
        $batch = $stmt->fetch();

        if (!$batch) {
            Session::setFlash('error', 'Manufacturing batch not found.');
            $this->redirect('company/production/orders');
            return;
        }

        // Get limits from request
        $startSerial = $request->get('start') !== null ? (int)$request->get('start') : 1;
        $endSerial = $request->get('end') !== null ? (int)$request->get('end') : min(100, $batch['target_qty']);

        $this->renderView('company/batch_barcodes', [
            'title' => "RFID Barcodes: {$batch['production_no']} | ERP",
            'batch' => $batch,
            'start' => $startSerial,
            'end' => $endSerial
        ]);
    }
}
