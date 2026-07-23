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

        // Pagination variables
        $page = max(1, (int)($request->get('page') ?: 1));
        $limit = 30;
        $offset = ($page - 1) * $limit;

        // Get total logs count for pagination controls
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM production_stage_logs WHERE production_order_id = ? AND company_id = ?");
        $stmtCount->execute([$id, $companyId]);
        $totalLogs = (int)$stmtCount->fetchColumn();
        $totalPages = max(1, (int)ceil($totalLogs / $limit));

        // Fetch stage history logs page-wise
        $stmt = $db->prepare("SELECT psl.*, m.name as machine_name, u.name as employee_name
                             FROM production_stage_logs psl
                             LEFT JOIN machines m ON psl.machine_id = m.id
                             LEFT JOIN users u ON psl.employee_id = u.id
                             WHERE psl.production_order_id = ? AND psl.company_id = ?
                             ORDER BY psl.id DESC
                             LIMIT {$limit} OFFSET {$offset}");
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
            'stagesList' => $stagesList,
            'currentPage' => $page,
            'totalPages' => $totalPages
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
                'title' => "QR Code Cards: {$batch['production_no']} | ERP",
                'batch' => $batch,
                'start' => $startSerial,
                'end' => $endSerial
            ]);
    }

    /**
     * Mobile RFID QR Code production tracking scanner page
     */
    public function qrTracking(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Self-healing permission injection
        $stmtPerm = $db->prepare("SELECT id FROM permissions WHERE name = ?");
        $stmtPerm->execute(['company.production.rfid_tracking']);
        $permId = $stmtPerm->fetchColumn();
        if (!$permId) {
            try {
                $db->exec("INSERT INTO permissions (id, name, description, module) VALUES (25, 'company.production.rfid_tracking', 'Access RFID Production Tracking mobile scanner page', 'tenant')");
                $db->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 25)");
            } catch (\Exception $e) {
                // Ignore if exists
            }
        }

        // Fetch active WIP stages configured for this company from system_settings
        $stmtWip = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = 'active_production_stages' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
        $stmtWip->execute([$companyId]);
        $activeWipRaw = $stmtWip->fetchColumn();
        
        $activeWipStages = $activeWipRaw ? json_decode($activeWipRaw, true) : [];
        if (!is_array($activeWipStages) || empty($activeWipStages)) {
            $activeWipStages = ['cutting', 'embellishment', 'sewing', 'washing', 'finishing', 'packing'];
        }

        $this->renderView('company/qr_tracking', [
            'title' => 'Mobile QR Code Scanner | ERP',
            'stages' => $activeWipStages
        ], 'mobile');
    }

    /**
     * AJAX Endpoint to log scanned QR Code activity
     */
    public function logQrActivity(Request $request, Response $response): void {
        header('Content-Type: application/json');
        
        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        // Dynamically enforce tenant company's assigned timezone for accurate timestamps
        $stmtTz = $db->prepare("SELECT timezone FROM companies WHERE id = ?");
        $stmtTz->execute([$companyId]);
        $companyTz = $stmtTz->fetchColumn() ?: 'Asia/Kolkata';
        date_default_timezone_set($companyTz);

        $qrCode = trim($request->get('qr_code'));
        $stage = trim($request->get('stage'));
        $status = trim($request->get('status')); // 'pass' or 'fail'
        $durationSeconds = (int)$request->get('duration_seconds');

        if (empty($qrCode) || empty($stage) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Missing scanned QR code, stage, or pass/fail status.']);
            exit;
        }

        // Duplicate check: Prevent logging the same QR code twice under the same WIP stage
        $stmtCheckAlready = $db->prepare("
            SELECT id FROM production_stage_logs 
            WHERE company_id = ? AND qr_code = ? AND stage = ? AND deleted_at IS NULL 
            LIMIT 1
        ");
        $stmtCheckAlready->execute([$companyId, $qrCode, $stage]);
        if ($stmtCheckAlready->fetchColumn()) {
            $formattedStage = strtoupper(str_replace('_', ' ', $stage));
            echo json_encode([
                'success' => false,
                'already_validated' => true,
                'message' => "This QR Code ({$qrCode}) has ALREADY been validated in stage '{$formattedStage}'."
            ]);
            exit;
        }

        // Parse QR Code e.g. BATCH-TOCCO-001-S-0005
        $parts = explode('-', $qrCode);
        if (count($parts) < 3) {
            echo json_encode(['success' => false, 'message' => 'Invalid tag format. QR code must match: [BATCH_CODE]-[SIZE]-[SERIAL].']);
            exit;
        }
        $serial = (int)array_pop($parts);
        $size = array_pop($parts);
        $batchNo = implode('-', $parts);

        // Fetch production order details
        $stmtBatch = $db->prepare("SELECT * FROM production_orders WHERE production_no = ? AND company_id = ? AND deleted_at IS NULL");
        $stmtBatch->execute([$batchNo, $companyId]);
        $batch = $stmtBatch->fetch();

        if (!$batch) {
            echo json_encode(['success' => false, 'message' => "Production batch '{$batchNo}' not found."]);
            exit;
        }

        // Logged-in user is the operator / employee
        $employeeId = $userId;

        // Map Pass/Fail logic
        $qtyIn = 1;
        $qtyOut = ($status === 'pass') ? 1 : 0;
        $wasteQty = ($status === 'pass') ? 0 : 1;

        // Calculate dates & duration in assigned company timezone
        $nowTs = time();
        $endTime = date('Y-m-d H:i:s', $nowTs);
        $startTime = date('Y-m-d H:i:s', $nowTs - max(1, $durationSeconds));
        $durationMinutes = (int)max(1, ceil($durationSeconds / 60));

        try {
            $stmtLog = $db->prepare("
                INSERT INTO production_stage_logs 
                (company_id, production_order_id, stage, employee_id, qty_in, qty_out, waste_qty, start_time, end_time, duration_minutes, created_by, qr_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtLog->execute([
                $companyId,
                $batch['id'],
                $stage,
                $employeeId,
                $qtyIn,
                $qtyOut,
                $wasteQty,
                $startTime,
                $endTime,
                $durationMinutes,
                $userId,
                $qrCode
            ]);

            // Update batch status to running if not already
            if ($batch['status'] === 'pending') {
                $db->prepare("UPDATE production_orders SET status = 'running' WHERE id = ?")->execute([$batch['id']]);
            }

            echo json_encode([
                'success' => true,
                'message' => "Piece #{$serial} (Size {$size}) logged successfully as " . strtoupper($status) . " under stage " . ucfirst(str_replace('_', ' ', $stage)) . ".",
                'details' => [
                    'batch_no' => $batchNo,
                    'size' => $size,
                    'serial' => $serial,
                    'status' => $status
                ]
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to save log to database: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX Endpoint to verify scanned QR Code and retrieve product details
     */
    public function verifyQrCode(Request $request, Response $response): void {
        header('Content-Type: application/json');
        
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Dynamically enforce tenant company's assigned timezone for accurate verification date checks
        $stmtTz = $db->prepare("SELECT timezone FROM companies WHERE id = ?");
        $stmtTz->execute([$companyId]);
        $companyTz = $stmtTz->fetchColumn() ?: 'Asia/Kolkata';
        date_default_timezone_set($companyTz);

        $qrCode = trim($request->get('qr_code'));
        $stage = trim($request->get('stage'));

        if (empty($qrCode)) {
            echo json_encode(['success' => false, 'message' => 'Scanned QR code is empty.']);
            exit;
        }

        // Duplicate check: Prevent validating QR code if it was already processed in this exact WIP stage
        if (!empty($stage)) {
            $stmtCheckAlready = $db->prepare("
                SELECT psl.*, u.name as operator_name 
                FROM production_stage_logs psl
                LEFT JOIN users u ON psl.employee_id = u.id
                WHERE psl.company_id = ? AND psl.qr_code = ? AND psl.stage = ? AND psl.deleted_at IS NULL
                ORDER BY psl.id DESC LIMIT 1
            ");
            $stmtCheckAlready->execute([$companyId, $qrCode, $stage]);
            $alreadyLogged = $stmtCheckAlready->fetch();

            if ($alreadyLogged) {
                $formattedStage = strtoupper(str_replace('_', ' ', $stage));
                $operatorName = $alreadyLogged['operator_name'] ?: 'Operator';
                $logTime = date('d-M-Y h:i A', strtotime($alreadyLogged['created_at'] ?? $alreadyLogged['end_time']));
                
                echo json_encode([
                    'success' => false,
                    'already_validated' => true,
                    'message' => "This QR Code ({$qrCode}) has ALREADY been validated in stage {$formattedStage} by {$operatorName} on {$logTime}."
                ]);
                exit;
            }
        }

        // Parse QR Code e.g. BATCH-TOCCO-001-S-0005
        $parts = explode('-', $qrCode);
        if (count($parts) < 3) {
            echo json_encode(['success' => false, 'message' => 'Invalid tag format. QR code must match: [BATCH_CODE]-[SIZE]-[SERIAL].']);
            exit;
        }
        $serial = (int)array_pop($parts);
        $size = array_pop($parts);
        $batchNo = implode('-', $parts);

        // Fetch production order with style details
        $stmt = $db->prepare("
            SELECT pro.*, 
                   s.style_no, s.name as style_name, s.category as style_category, s.composition as fabric_composition, s.brand as style_brand,
                   po.po_no as buyer_po_no, po.quantity as target_qty, po.sizes_json
            FROM production_orders pro
            JOIN buyer_pos po ON pro.po_id = po.id
            JOIN styles s ON po.style_id = s.id
            WHERE pro.production_no = ? AND pro.company_id = ? AND pro.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$batchNo, $companyId]);
        $batch = $stmt->fetch();

        if (!$batch) {
            echo json_encode(['success' => false, 'message' => "Production batch '{$batchNo}' is not registered or active in this ERP."]);
            exit;
        }

        // Validate serial range
        if ($serial < 1 || $serial > $batch['target_qty']) {
            echo json_encode(['success' => false, 'message' => "Serial number #{$serial} exceeds target quantity limit of " . number_format($batch['target_qty']) . " pieces."]);
            exit;
        }

        // Validate size breakdown exists
        $sizes = json_decode($batch['sizes_json'] ?? '[]', true) ?: [];
        if (!empty($sizes) && !isset($sizes[$size])) {
            echo json_encode(['success' => false, 'message' => "Size '{$size}' is not configured for production batch '{$batchNo}'."]);
            exit;
        }

        // QR Code is active and verified! Return details.
        echo json_encode([
            'success' => true,
            'message' => 'QR Code verified successfully.',
            'product' => [
                'batch_no' => $batchNo,
                'style_no' => $batch['style_no'],
                'style_name' => $batch['style_name'],
                'category' => $batch['style_category'],
                'brand' => $batch['style_brand'] ?: 'N/A',
                'composition' => $batch['fabric_composition'] ?: '100% Premium Cotton',
                'buyer_po' => $batch['buyer_po_no'],
                'size' => $size,
                'serial' => $serial,
                'target_qty' => $batch['target_qty']
            ]
        ]);
        exit;
    }

    /**
     * Export all stage history logs for a production order to CSV (Excel compatible)
     */
    public function exportStageLogs(Request $request, Response $response, string $id): void {
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        // Fetch production order
        $stmtOrder = $db->prepare("SELECT production_no FROM production_orders WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1");
        $stmtOrder->execute([$id, $companyId]);
        $orderNo = $stmtOrder->fetchColumn();
        if (!$orderNo) {
            Session::setFlash('error', 'Production order not found.');
            $this->redirect('company/production/orders');
            return;
        }

        // Fetch all logs in ASCENDING order (oldest first)
        $stmt = $db->prepare("
            SELECT psl.*, m.name as machine_name, u.name as employee_name
            FROM production_stage_logs psl
            LEFT JOIN machines m ON psl.machine_id = m.id
            LEFT JOIN users u ON psl.employee_id = u.id
            WHERE psl.production_order_id = ? AND psl.company_id = ?
            ORDER BY psl.id ASC
        ");
        $stmt->execute([$id, $companyId]);
        $logs = $stmt->fetchAll() ?: [];

        $filename = "production_logs_" . strtolower(str_replace(' ', '_', $orderNo)) . "_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for proper Excel encoding of special chars
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Column headers
        fputcsv($output, [
            'Log ID', 
            'Stage', 
            'Operator Name', 
            'Machine Code / Tag ID', 
            'Qty In', 
            'Qty Out', 
            'Waste Qty', 
            'Start Time', 
            'End Time', 
            'Duration (Mins)', 
            'Logged Date'
        ]);

        foreach ($logs as $l) {
            $machineCode = $l['machine_name'] ?: ($l['qr_code'] ?: 'Manual');
            fputcsv($output, [
                $l['id'],
                strtoupper(str_replace('_', ' ', $l['stage'])),
                $l['employee_name'] ?: 'System / Admin',
                $machineCode,
                $l['qty_in'],
                $l['qty_out'],
                $l['waste_qty'],
                $l['start_time'],
                $l['end_time'],
                $l['duration_minutes'],
                date('Y-m-d H:i:s', strtotime($l['created_at']))
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Live Operations Stage Dashboard Report
     */
    public function stageLiveReport(Request $request, Response $response, string $id): void {
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        // Fetch production order details joined with PO & Style
        $stmt = $db->prepare("
            SELECT pro.*, s.style_no, s.name as style_name, s.category as style_category, s.composition as fabric_composition,
                   po.po_no as buyer_po_no, po.quantity as target_qty, po.sizes_json
            FROM production_orders pro
            JOIN buyer_pos po ON pro.po_id = po.id
            JOIN styles s ON po.style_id = s.id
            WHERE pro.id = ? AND pro.company_id = ? AND pro.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id, $companyId]);
        $order = $stmt->fetch();

        if (!$order) {
            Session::setFlash('error', 'Production order not found.');
            $this->redirect('company/production/orders');
            return;
        }

        $productionService = new ProductionService();
        $wipSummary = $productionService->getOrderWipSummary($companyId, (int)$id);

        // Fetch active production stages settings
        $stmtStageSettings = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = 'active_production_stages' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
        $stmtStageSettings->execute([$companyId]);
        $activeStagesRaw = $stmtStageSettings->fetchColumn();
        
        $allStagesDefault = ['knitting', 'dyeing', 'compacting', 'relaxing', 'spreading', 'cutting', 'bundling', 'printing', 'embroidery', 'sewing', 'checking', 'thread_cutting', 'washing', 'ironing', 'packing', 'carton_packing', 'shipment'];
        $stagesList = $activeStagesRaw ? json_decode(html_entity_decode($activeStagesRaw), true) : $allStagesDefault;
        if (!is_array($stagesList) || empty($stagesList)) {
            $stagesList = $allStagesDefault;
        }

        // Fetch the 10 most recent activity logs for live ticker
        $stmtLogs = $db->prepare("
            SELECT psl.*, u.name as employee_name, m.name as machine_name
            FROM production_stage_logs psl
            LEFT JOIN users u ON psl.employee_id = u.id
            LEFT JOIN machines m ON psl.machine_id = m.id
            WHERE psl.production_order_id = ? AND psl.company_id = ?
            ORDER BY psl.id DESC
            LIMIT 10
        ");
        $stmtLogs->execute([$id, $companyId]);
        $recentLogs = $stmtLogs->fetchAll() ?: [];

        $this->renderView('company/production_stage_live', [
            'title' => "Live Monitor: {$order['production_no']} | ERP",
            'order' => $order,
            'wip_summary' => $wipSummary,
            'stagesList' => $stagesList,
            'recentLogs' => $recentLogs
        ], 'mobile');
    }
}
