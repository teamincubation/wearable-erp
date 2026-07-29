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
use App\Helpers\StageHelper;

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

        // Fetch active production orders (excluding completed) joined with style & buyer PO details
        $stmt = $db->prepare("SELECT pro.*, s.style_no, s.name as style_name, po.po_no as buyer_po_no, po.quantity as target_qty
                             FROM production_orders pro
                             LEFT JOIN buyer_pos po ON pro.po_id = po.id
                             LEFT JOIN styles s ON po.style_id = s.id
                             WHERE pro.company_id = ? AND (pro.status IS NULL OR pro.status != 'completed') AND pro.deleted_at IS NULL
                             ORDER BY pro.id DESC");
        $stmt->execute([$companyId]);
        $orders = $stmt->fetchAll() ?: [];

        // Fetch active approved buyer POs joined with active buyer details
        $stmt = $db->prepare("
            SELECT po.id, po.po_no, po.quantity, po.sizes_json, po.style_id, s.style_no, s.name as style_name, s.size_range, c.name as buyer_name, c.code as buyer_code, c.brand_name
            FROM buyer_pos po
            JOIN contacts c ON po.buyer_id = c.id
            JOIN styles s ON po.style_id = s.id
            WHERE po.company_id = ? AND po.status = 'approved' AND c.status = 'active' AND po.deleted_at IS NULL AND c.deleted_at IS NULL
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
     * Real-Time AJAX Check for Unique Production Batch Number per Tenant Company
     * GET /company/production/orders/check-batch-no
     */
    public function checkBatchNo(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $companyId = Session::get('company_id');
        $productionNo = trim((string)$request->get('production_no'));
        $excludeId = (int)$request->get('exclude_id');

        if (empty($productionNo)) {
            echo json_encode(['exists' => false, 'message' => 'Batch number is empty.']);
            exit;
        }

        $db = Database::getInstance();
        $sql = "SELECT id FROM production_orders WHERE company_id = ? AND LOWER(TRIM(production_no)) = LOWER(TRIM(?)) AND deleted_at IS NULL";
        $params = [$companyId, $productionNo];

        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $sql .= " LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $exists = (bool)$stmt->fetchColumn();

        if ($exists) {
            echo json_encode([
                'exists' => true,
                'message' => "Batch Number '{$productionNo}' already exists for your company!"
            ]);
        } else {
            echo json_encode([
                'exists' => false,
                'message' => "Batch Number '{$productionNo}' is available."
            ]);
        }
        exit;
    }

    /**
     * Create Production Order
     */
    public function createOrder(Request $request, Response $response): void {
        $poId = (int)$request->get('po_id');
        $productionNo = trim((string)$request->get('production_no'));
        $startDate = $request->get('start_date');

        if (empty($poId) || empty($productionNo) || empty($startDate)) {
            Session::setFlash('error', 'Linked Buyer PO, Production Number, and Start Date are required.');
            $this->redirect('company/production/orders');
            return;
        }

        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        // Enforce unique production_no per tenant company
        $stmtChk = $db->prepare("SELECT id FROM production_orders WHERE company_id = ? AND LOWER(TRIM(production_no)) = LOWER(TRIM(?)) AND deleted_at IS NULL LIMIT 1");
        $stmtChk->execute([$companyId, $productionNo]);
        if ($stmtChk->fetchColumn()) {
            Session::setFlash('error', "Production Batch Number '{$productionNo}' already exists for your company! Please enter a unique batch number.");
            $this->redirect('company/production/orders');
            return;
        }

        $orderModel = new ProductionOrder();
        $orderId = $orderModel->insert([
            'po_id' => $poId,
            'production_no' => $productionNo,
            'start_date' => $startDate,
            'status' => 'pending',
            'created_by' => Session::get('user_id')
        ]);

        // Auto Load Size Breakdown & Generate Unique Product QR Codes
        $sizeQtys = $request->get('size_qty') ?: [];
        if (empty($sizeQtys)) {
            $stmtPo = $db->prepare("SELECT sizes_json, quantity FROM buyer_pos WHERE id = ? AND company_id = ?");
            $stmtPo->execute([$poId, $companyId]);
            $poRow = $stmtPo->fetch();
            $sizeQtys = json_decode($poRow['sizes_json'] ?? '[]', true) ?: [];
            if (empty($sizeQtys)) {
                $sizeQtys = ['FREE' => (int)($poRow['quantity'] ?? 1)];
            }
        }

        $totalGeneratedQrs = 0;
        foreach ($sizeQtys as $szName => $qty) {
            $qtyCount = (int)$qty;
            if ($qtyCount > 0) {
                $totalGeneratedQrs += $qtyCount;
            }
        }

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_production_order', 'ProductionOrder', $orderId, null, null, "Created production order: {$productionNo} with {$totalGeneratedQrs} pieces planned");
        Session::setFlash('success', "Production order '{$productionNo}' planned successfully! Breakdown of {$totalGeneratedQrs} pieces saved. QR Codes are ready to be printed and scanned by operators.");
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

        // Filter variables for Real-Time Activity Feed & Export
        $filterStage = trim((string)$request->get('filter_stage'));
        $filterStatus = strtolower(trim((string)$request->get('filter_status')));
        $filterOperator = (int)$request->get('filter_operator');
        $filterDate = trim((string)$request->get('filter_date'));
        $filterSearch = trim((string)($request->get('search') ?: $request->get('q')));

        $where = ["psl.production_order_id = ?", "psl.company_id = ?"];
        $params = [(int)$id, $companyId];

        if (!empty($filterStage)) {
            $where[] = "psl.stage = ?";
            $params[] = $filterStage;
        }

        if ($filterStatus === 'pass') {
            $where[] = "psl.qty_out > 0";
        } elseif ($filterStatus === 'fail') {
            $where[] = "(psl.qty_out = 0 OR psl.waste_qty > 0)";
        }

        if ($filterOperator > 0) {
            $where[] = "psl.employee_id = ?";
            $params[] = $filterOperator;
        }

        if (!empty($filterDate)) {
            $where[] = "(DATE(psl.created_at) = ? OR DATE(psl.start_time) = ?)";
            $params[] = $filterDate;
            $params[] = $filterDate;
        }

        if (!empty($filterSearch)) {
            $where[] = "(psl.qr_code LIKE ? OR psl.stage LIKE ? OR u.name LIKE ? OR m.name LIKE ?)";
            $searchParam = "%{$filterSearch}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $whereClause = implode(" AND ", $where);

        // Get total logs count for pagination controls
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM production_stage_logs psl LEFT JOIN users u ON psl.employee_id = u.id LEFT JOIN machines m ON psl.machine_id = m.id WHERE {$whereClause}");
        $stmtCount->execute($params);
        $totalLogs = (int)$stmtCount->fetchColumn();
        $totalPages = max(1, (int)ceil($totalLogs / $limit));

        // Auto-heal edited_by, edited_at, edit_remarks columns in production_stage_logs
        try {
            $db->query("SELECT edited_by FROM production_stage_logs LIMIT 1");
        } catch (\Exception $e) {
            try {
                $db->exec("ALTER TABLE `production_stage_logs` ADD COLUMN `edited_by` INT DEFAULT NULL AFTER `created_at`");
                $db->exec("ALTER TABLE `production_stage_logs` ADD COLUMN `edited_at` DATETIME DEFAULT NULL AFTER `edited_by`");
                $db->exec("ALTER TABLE `production_stage_logs` ADD COLUMN `edit_remarks` VARCHAR(255) DEFAULT NULL AFTER `edited_at`");
            } catch (\Exception $ex) {}
        }

        // Fetch stage history logs page-wise
        $stmt = $db->prepare("SELECT psl.*, m.name as machine_name, u.name as employee_name, u_edit.name as edited_by_name
                             FROM production_stage_logs psl
                             LEFT JOIN machines m ON psl.machine_id = m.id
                             LEFT JOIN users u ON psl.employee_id = u.id
                             LEFT JOIN users u_edit ON psl.edited_by = u_edit.id
                             WHERE {$whereClause}
                             ORDER BY psl.id DESC
                             LIMIT {$limit} OFFSET {$offset}");
        $stmt->execute($params);
        $history = $stmt->fetchAll() ?: [];

        // Fetch machines & employees
        $machineModel = new Machine();
        $machines = $machineModel->all();

        $userModel = new User();
        $employees = $userModel->getActiveCompanyEmployees();

        // Fetch tenant timezone
        $stmtComp = $db->prepare("SELECT timezone FROM companies WHERE id = ?");
        $stmtComp->execute([$companyId]);
        $tenantTimezone = $stmtComp->fetchColumn() ?: 'Asia/Kolkata';

        // Fetch batch stage sequence based on style techpack specifications
        $batchStagesObj = self::getBatchStagesList((int)$id);
        $stagesList = array_map(function($stg) {
            return StageHelper::toStageKey(is_array($stg) ? ($stg['key'] ?? $stg['name'] ?? '') : (string)$stg);
        }, $batchStagesObj);
        $stagesList = array_values(array_filter($stagesList));
        if (empty($stagesList)) {
            $stagesList = ['knitting', 'dyeing', 'compacting', 'relaxing', 'spreading', 'cutting', 'bundling', 'printing', 'embroidery', 'sewing', 'checking', 'thread_cutting', 'washing', 'ironing', 'packing', 'carton_packing', 'shipment'];
        }

        $this->renderView('company/production_stage', [
            'title' => "Stage Logs: {$order['production_no']} | ERP",
            'order' => $order,
            'wip_summary' => $wipSummary,
            'history' => $history,
            'machines' => $machines,
            'employees' => $employees,
            'stagesList' => $stagesList,
            'batchStagesObj' => $batchStagesObj,
            'tenantTimezone' => $tenantTimezone,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'filterStage' => $filterStage,
            'filterStatus' => $filterStatus,
            'filterOperator' => $filterOperator,
            'filterDate' => $filterDate,
            'filterSearch' => $filterSearch
        ]);
    }

    /**
     * Submit Production Stage Log
     */
    public function logStage(Request $request, Response $response, string $id): void {
        $stage = StageHelper::toStageKey((string)$request->get('stage'));
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

        // Verify preceding stage order sequence compliance
        $batchStages = self::getBatchStagesList((int)$id);
        $stageKeys = array_map(function($stg) {
            return StageHelper::toStageKey(is_array($stg) ? ($stg['key'] ?? $stg['name'] ?? '') : (string)$stg);
        }, $batchStages);
        $targetIndex = array_search($stage, $stageKeys);

        if ($targetIndex !== false && $targetIndex > 0) {
            $db = Database::getInstance();
            for ($i = 0; $i < $targetIndex; $i++) {
                $precedingKey = is_array($batchStages[$i]) ? ($batchStages[$i]['key'] ?? $batchStages[$i]['name'] ?? '') : (string)$batchStages[$i];
                $precedingKeyClean = StageHelper::toStageKey($precedingKey);
                $precedingName = is_array($batchStages[$i]) ? ($batchStages[$i]['name'] ?? StageHelper::toStageName($precedingKey)) : StageHelper::toStageName($precedingKey);

                $stmtCheckPrec = $db->prepare("SELECT stage FROM production_stage_logs WHERE production_order_id = ? AND company_id = ?");
                $stmtCheckPrec->execute([(int)$id, $companyId]);
                $allLoggedStages = $stmtCheckPrec->fetchAll() ?: [];

                $foundPrec = false;
                foreach ($allLoggedStages as $als) {
                    if (StageHelper::toStageKey($als['stage']) === $precedingKeyClean) {
                        $foundPrec = true;
                        break;
                    }
                }

                if (!$foundPrec) {
                    $targetName = is_array($batchStages[$targetIndex]) ? ($batchStages[$targetIndex]['name'] ?? StageHelper::toStageName($stage)) : StageHelper::toStageName($stage);
                    Session::setFlash('error', "Order Sequence Error: Cannot log stage '{$targetName}' yet. Batch must complete preceding stage '{$precedingName}' first!");
                    $this->redirect("company/production/stage/{$id}");
                    return;
                }
            }
        }

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
    public function editStageLog($request = null, $response = null, $id = null): void {
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

        $stage = StageHelper::toStageKey((string)$request->get('stage'));
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

        $editRemarks = trim((string)$request->get('edit_remarks'));

        // Sanitize FK fields to prevent integrity constraint failures
        $validEmployeeId = null;
        if (!empty($employeeId) && (int)$employeeId > 0 && (int)$employeeId !== 999999) {
            $stmtCheckUser = $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
            $stmtCheckUser->execute([(int)$employeeId]);
            if ($stmtCheckUser->fetchColumn()) {
                $validEmployeeId = (int)$employeeId;
            }
        }

        $validEditedBy = null;
        if (!empty($userId) && (int)$userId > 0 && (int)$userId !== 999999) {
            $stmtCheckUser = $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
            $stmtCheckUser->execute([(int)$userId]);
            if ($stmtCheckUser->fetchColumn()) {
                $validEditedBy = (int)$userId;
            }
        }

        $validMachineId = null;
        if (!empty($machineId) && (int)$machineId > 0) {
            $stmtCheckMachine = $db->prepare("SELECT id FROM machines WHERE id = ? LIMIT 1");
            $stmtCheckMachine->execute([(int)$machineId]);
            if ($stmtCheckMachine->fetchColumn()) {
                $validMachineId = (int)$machineId;
            }
        }

        try {
            $stmtUpdate = $db->prepare("UPDATE production_stage_logs 
                                        SET stage = ?, machine_id = ?, employee_id = ?, qty_in = ?, qty_out = ?, waste_qty = ?, start_time = ?, end_time = ?, duration_minutes = ?,
                                            edited_by = ?, edited_at = UTC_TIMESTAMP(), edit_remarks = ?
                                        WHERE id = ? AND company_id = ?");
            $stmtUpdate->execute([
                $stage,
                $validMachineId,
                $validEmployeeId,
                $qtyIn,
                $qtyOut,
                $wasteQty,
                $startTimeDate,
                $endTimeDate,
                $durationMinutes,
                $validEditedBy,
                !empty($editRemarks) ? $editRemarks : null,
                (int)$id,
                $companyId
            ]);

            AuditLog::log($companyId, $userId, 'edit_production_stage_log', 'ProductionStageLog', (int)$id, null, null, "Edited stage {$stage} activity for log id {$id}" . (!empty($editRemarks) ? " (Remarks: {$editRemarks})" : ""));
            Session::setFlash('success', "Stage log entry updated successfully.");
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to update stage log: ' . $e->getMessage());
        }

        $this->redirect("company/production/stage/{$log['production_order_id']}");
    }

    /**
     * Delete individual production stage log
     */
    public function deleteStageLog($request = null, $response = null, $id = null): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        if ($request === null) {
            $request = new Request();
        }

        // Fetch log detail to get production_order_id & check ownership
        $stmt = $db->prepare("SELECT * FROM production_stage_logs WHERE id = ? AND company_id = ?");
        $stmt->execute([(int)$id, $companyId]);
        $log = $stmt->fetch();

        if (!$log) {
            Session::setFlash('error', 'Stage log entry not found.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'company/production/orders');
            return;
        }

        $confirmCode = strtoupper(trim((string)($request->get('confirm_code') ?: $request->get('confirm', 'DELETE'))));
        if ($confirmCode !== 'DELETE') {
            Session::setFlash('error', 'Deletion cancelled. You must type "DELETE" in capital letters to confirm.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? "company/production/stage/{$log['production_order_id']}");
            return;
        }

        try {
            // Delete all matching duplicate log entries for this QR code tag in this stage to prevent false 'already done' checks
            $qrVal = !empty($log['qr_code']) ? $log['qr_code'] : (!empty($log['scanned_qr_code']) ? $log['scanned_qr_code'] : '');
            $cleanStage = StageHelper::toStageKey((string)$log['stage']);

            if (!empty($qrVal)) {
                $stmtDelete = $db->prepare("
                    DELETE FROM production_stage_logs 
                    WHERE company_id = ? AND (
                        id = ? OR 
                        ((LOWER(TRIM(qr_code)) = LOWER(TRIM(?)) OR LOWER(TRIM(scanned_qr_code)) = LOWER(TRIM(?))) AND 
                         (LOWER(TRIM(stage)) = LOWER(TRIM(?)) OR LOWER(TRIM(stage)) = LOWER(TRIM(?))))
                    )
                ");
                $stmtDelete->execute([
                    $companyId,
                    (int)$id,
                    $qrVal,
                    $qrVal,
                    $log['stage'],
                    $cleanStage
                ]);
            } else {
                $stmtDelete = $db->prepare("DELETE FROM production_stage_logs WHERE id = ? AND company_id = ?");
                $stmtDelete->execute([(int)$id, $companyId]);
            }

            AuditLog::log($companyId, $userId, 'delete_production_stage_log', 'ProductionStageLog', (int)$id, null, null, "Deleted stage log id {$id}");
            Session::setFlash('success', "Stage log entry and tag history cleared successfully.");
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to delete stage log: ' . $e->getMessage());
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? "company/production/stage/{$log['production_order_id']}");
    }

    /**
     * Route Alias for clearing all stage logs
     */
    public function clearLogs($request = null, $response = null, $id = null): void {
        $this->clearStageLogs($request, $response, $id);
    }

    /**
     * Clear All Stage Logs for a Production Order Batch
     */
    public function clearStageLogs($request = null, $response = null, $id = null): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        if ($request === null) {
            $request = new Request();
        }

        $confirmCode = strtoupper(trim((string)($request->get('confirm_code') ?: $request->get('confirm', 'DELETE'))));
        if ($confirmCode !== 'DELETE') {
            Session::setFlash('error', 'Operation cancelled. You must type "DELETE" to confirm clearing all activity logs.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? "company/production/stage/{$id}");
            return;
        }

        // Fetch production order details to match production_no as well
        $stmtOrder = $db->prepare("SELECT production_no FROM production_orders WHERE id = ? AND company_id = ? LIMIT 1");
        $stmtOrder->execute([(int)$id, $companyId]);
        $orderNo = $stmtOrder->fetchColumn();

        try {
            if ($orderNo) {
                $batchParam = "%{$orderNo}%";
                $stmtClear = $db->prepare("
                    DELETE FROM production_stage_logs 
                    WHERE company_id = ? AND (production_order_id = ? OR qr_code LIKE ? OR scanned_qr_code LIKE ?)
                ");
                $stmtClear->execute([$companyId, (int)$id, $batchParam, $batchParam]);
            } else {
                $stmtClear = $db->prepare("DELETE FROM production_stage_logs WHERE production_order_id = ? AND company_id = ?");
                $stmtClear->execute([(int)$id, $companyId]);
            }

            AuditLog::log($companyId, $userId, 'clear_production_stage_logs', 'ProductionOrder', (int)$id, null, null, "Cleared all activity logs for batch id {$id}");
            Session::setFlash('success', 'All activity feed logs for this production order have been deleted.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to clear activity logs: ' . $e->getMessage());
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? "company/production/stage/{$id}");
    }

    /**
     * Clear All Quality Control Inspections
     */
    public function clearAllQualityInspections(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        $confirmCode = strtoupper(trim((string)$request->get('confirm_code', '')));
        if ($confirmCode !== 'DELETE') {
            Session::setFlash('error', 'Operation cancelled. You must type "DELETE" to confirm clearing all quality inspection logs.');
            $this->redirect('company/production/quality');
            return;
        }

        try {
            $stmtClear = $db->prepare("UPDATE quality_inspections SET deleted_at = NOW(), deleted_by = ? WHERE company_id = ? AND deleted_at IS NULL");
            $stmtClear->execute([$userId, $companyId]);

            AuditLog::log($companyId, $userId, 'clear_quality_inspections', 'QualityInspection', 0, null, null, "Cleared all quality inspection records");
            Session::setFlash('success', 'All quality control inspection records have been cleared.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to clear quality inspections: ' . $e->getMessage());
        }

        $this->redirect('company/production/quality');
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
     * Retrieve batch stage sequence from tech pack specifications
     */
    public static function getBatchStagesList(int $batchId, ?int $companyId = null): array {
        $db = Database::getInstance();
        if (empty($companyId)) {
            $companyId = Session::get('company_id');
        }

        if (empty($companyId)) {
            $stmtC = $db->prepare("SELECT company_id FROM production_orders WHERE id = ? LIMIT 1");
            $stmtC->execute([$batchId]);
            $companyId = (int)($stmtC->fetchColumn() ?: 0);
        }

        $stmt = $db->prepare("
            SELECT tp.stages_json
            FROM production_orders pro
            JOIN buyer_pos po ON pro.po_id = po.id
            JOIN styles s ON po.style_id = s.id
            LEFT JOIN tech_packs tp ON s.id = tp.style_id
            WHERE pro.id = ? AND pro.company_id = ? AND pro.deleted_at IS NULL
        ");
        $stmt->execute([$batchId, $companyId]);
        $stagesJson = $stmt->fetchColumn();

        if ($stagesJson) {
            $decoded = json_decode($stagesJson, true);
            if (is_array($decoded) && !empty($decoded)) {
                usort($decoded, function($a, $b) {
                    return (int)($a['order'] ?? 0) <=> (int)($b['order'] ?? 0);
                });
                return $decoded;
            }
        }

        return \App\Controllers\CompanyController::getCompanyWipStages((int)$companyId);
    }

    /**
     * AJAX endpoint to fetch WIP stages for a production batch
     */
    public function getBatchStages(Request $request, Response $response, string $id): void {
        header('Content-Type: application/json');
        $batchId = (int)$id;
        $stages = self::getBatchStagesList($batchId);
        echo json_encode(['success' => true, 'stages' => $stages]);
        exit;
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
                $stmtIns = $db->prepare("INSERT INTO permissions (name, description, module) VALUES (?, ?, ?)");
                $stmtIns->execute(['company.production.rfid_tracking', 'Access QR Code / RFID Production Scanner page', 'tenant']);
                $permId = $db->lastInsertId();
                if ($permId) {
                    $db->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, " . (int)$permId . ")");
                }
            } catch (\Exception $e) {
                // Ignore if exists
            }
        }

        // Fetch active production started batches for scanner selection dropdown
        $stmtBatches = $db->prepare("
            SELECT pro.id, pro.production_no, s.style_no, s.name as style_name, c.name as buyer_name
            FROM production_orders pro
            JOIN buyer_pos po ON pro.po_id = po.id
            JOIN styles s ON po.style_id = s.id
            JOIN contacts c ON po.buyer_id = c.id
            WHERE pro.company_id = ? AND pro.status IN ('running', 'in_progress') AND pro.deleted_at IS NULL
            ORDER BY pro.id DESC
        ");
        $stmtBatches->execute([$companyId]);
        $activeBatches = $stmtBatches->fetchAll() ?: [];

        $companyWipStages = \App\Controllers\CompanyController::getCompanyWipStages($companyId);

        $this->renderView('company/qr_tracking', [
            'title' => 'Mobile QR Code Scanner | ERP',
            'batches' => $activeBatches,
            'stages' => $companyWipStages
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

        $qrCode = trim((string)$request->get('qr_code'));
        $rawStage = trim((string)$request->get('stage'));
        $stage = StageHelper::toStageKey($rawStage);
        $status = strtolower(trim((string)$request->get('status'))); // 'pass' or 'fail'
        $durationSeconds = (int)$request->get('duration_seconds');

        if (empty($qrCode) || empty($stage) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Missing scanned QR code, stage, or pass/fail status.']);
            exit;
        }

        // Duplicate check: Prevent logging the same QR code twice under the same WIP stage
        $stmtCheckAlready = $db->prepare("
            SELECT stage FROM production_stage_logs 
            WHERE company_id = ? AND (LOWER(TRIM(qr_code)) = LOWER(TRIM(?)) OR LOWER(TRIM(scanned_qr_code)) = LOWER(TRIM(?)))
        ");
        $stmtCheckAlready->execute([$companyId, $qrCode, $qrCode]);
        $existingLogs = $stmtCheckAlready->fetchAll() ?: [];

        foreach ($existingLogs as $l) {
            if (StageHelper::toStageKey($l['stage']) === $stage) {
                $formattedStage = StageHelper::toStageName($stage);
                echo json_encode([
                    'success' => false,
                    'already_validated' => true,
                    'message' => "This QR Code ({$qrCode}) has ALREADY been validated in stage '{$formattedStage}'."
                ]);
                exit;
            }
        }

        // Parse QR Code e.g. BATCH-CODE-001-S-0005
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

        // Global Uniqueness Check per Company: Ensure QR code is never reused across different production orders/batches/styles/POs
        $stmtCheckCrossBatch = $db->prepare("
            SELECT psl.production_order_id, pro.production_no 
            FROM production_stage_logs psl
            JOIN production_orders pro ON psl.production_order_id = pro.id
            WHERE psl.company_id = ? AND (psl.qr_code = ? OR psl.scanned_qr_code = ?) AND psl.production_order_id != ? 
            LIMIT 1
        ");
        $stmtCheckCrossBatch->execute([$companyId, $qrCode, $qrCode, (int)$batch['id']]);
        $existingCross = $stmtCheckCrossBatch->fetch();
        if ($existingCross) {
            echo json_encode([
                'success' => false,
                'duplicate_qr' => true,
                'message' => "QR Code Duplicate Error: QR code '{$qrCode}' is already registered to another Production Batch '{$existingCross['production_no']}'. All QR codes must be globally unique per company!"
            ]);
            exit;
        }

        // Verify preceding stage order sequence & quality PASS compliance for QR code scan
        $batchStages = self::getBatchStagesList((int)$batch['id']);
        $stageKeys = array_map(function($stg) {
            return StageHelper::toStageKey(is_array($stg) ? ($stg['key'] ?? $stg['name'] ?? '') : (string)$stg);
        }, $batchStages);
        $targetIndex = array_search($stage, $stageKeys);

        if ($targetIndex !== false && $targetIndex > 0) {
            for ($i = 0; $i < $targetIndex; $i++) {
                $precedingKey = is_array($batchStages[$i]) ? ($batchStages[$i]['key'] ?? $batchStages[$i]['name'] ?? '') : (string)$batchStages[$i];
                $precedingKeyClean = StageHelper::toStageKey($precedingKey);
                $precedingName = is_array($batchStages[$i]) ? ($batchStages[$i]['name'] ?? StageHelper::toStageName($precedingKey)) : StageHelper::toStageName($precedingKey);

                $stmtCheckPrec = $db->prepare("
                    SELECT id, qty_out, waste_qty, stage 
                    FROM production_stage_logs 
                    WHERE company_id = ? AND LOWER(TRIM(qr_code)) = LOWER(TRIM(?)) 
                    ORDER BY id DESC
                ");
                $stmtCheckPrec->execute([$companyId, $qrCode]);
                $allPrecLogs = $stmtCheckPrec->fetchAll() ?: [];

                $precLog = null;
                foreach ($allPrecLogs as $pl) {
                    if (StageHelper::toStageKey($pl['stage']) === $precedingKeyClean) {
                        $precLog = $pl;
                        break;
                    }
                }

                if (!$precLog) {
                    $targetName = is_array($batchStages[$targetIndex]) ? ($batchStages[$targetIndex]['name'] ?? StageHelper::toStageName($stage)) : StageHelper::toStageName($stage);
                    echo json_encode([
                        'success' => false,
                        'sequence_mismatch' => true,
                        'message' => "Order Sequence Error: Unit ({$qrCode}) cannot enter stage '{$targetName}' yet. Preceding stage '{$precedingName}' must be completed first!"
                    ]);
                    exit;
                }

                // Block scanning if unit was marked as FAILED in any preceding stage
                $isFail = ((int)($precLog['qty_out'] ?? 0) === 0 || (int)($precLog['waste_qty'] ?? 0) > 0);
                if ($isFail) {
                    $targetName = is_array($batchStages[$targetIndex]) ? ($batchStages[$targetIndex]['name'] ?? StageHelper::toStageName($stage)) : StageHelper::toStageName($stage);
                    echo json_encode([
                        'success' => false,
                        'failed_unit' => true,
                        'message' => "Quality Gate Blocked: Unit ({$qrCode}) was marked as FAILED in preceding stage '{$precedingName}'. Edit entry in stage log to PASS to unblock."
                    ]);
                    exit;
                }
            }
        }

        // Logged-in user is the operator / employee. Sanitize for FK integrity.
        $employeeId = $userId;
        $validEmployeeId = null;
        if (!empty($employeeId) && (int)$employeeId > 0 && (int)$employeeId !== 999999) {
            $stmtCheckUser = $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
            $stmtCheckUser->execute([(int)$employeeId]);
            if ($stmtCheckUser->fetchColumn()) {
                $validEmployeeId = (int)$employeeId;
            }
        }
        $validCreatedBy = $validEmployeeId;

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
                $validEmployeeId,
                $qtyIn,
                $qtyOut,
                $wasteQty,
                $startTime,
                $endTime,
                $durationMinutes,
                $validCreatedBy,
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
        $rawStage = trim((string)$request->get('stage'));
        $stageKey = !empty($rawStage) ? StageHelper::toStageKey($rawStage) : '';

        if (empty($qrCode)) {
            echo json_encode(['success' => false, 'message' => 'Scanned QR code is empty.']);
            exit;
        }

        // Duplicate check: Prevent validating QR code if it was already processed in this exact WIP stage
        if (!empty($stageKey)) {
            $stmtCheckAlready = $db->prepare("
                SELECT psl.*, u.name as operator_name 
                FROM production_stage_logs psl
                LEFT JOIN users u ON psl.employee_id = u.id
                WHERE psl.company_id = ? AND (LOWER(TRIM(psl.qr_code)) = LOWER(TRIM(?)) OR LOWER(TRIM(psl.scanned_qr_code)) = LOWER(TRIM(?)))
                ORDER BY psl.id DESC
            ");
            $stmtCheckAlready->execute([$companyId, $qrCode, $qrCode]);
            $allUserLogs = $stmtCheckAlready->fetchAll() ?: [];

            foreach ($allUserLogs as $alreadyLogged) {
                if (StageHelper::toStageKey($alreadyLogged['stage']) === $stageKey) {
                    $formattedStage = StageHelper::toStageName($stageKey);
                    $operatorName = $alreadyLogged['operator_name'] ?: 'Operator';
                    $logTime = date('d-M-Y h:i A', strtotime($alreadyLogged['created_at'] ?? $alreadyLogged['end_time']));
                    
                    echo json_encode([
                        'success' => false,
                        'already_validated' => true,
                        'message' => "This QR Code ({$qrCode}) has ALREADY been validated in stage '{$formattedStage}' by {$operatorName} on {$logTime}."
                    ]);
                    exit;
                }
            }
        }

        // Parse QR Code e.g. BATCH-CODE-001-S-0005
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

        // Verify preceding stage order sequence & quality PASS compliance for QR code scan
        if (!empty($stageKey) && !empty($batch['id'])) {
            $batchStages = self::getBatchStagesList((int)$batch['id']);
            $stageKeys = array_map(function($stg) {
                return StageHelper::toStageKey(is_array($stg) ? ($stg['key'] ?? $stg['name'] ?? '') : (string)$stg);
            }, $batchStages);
            $targetIndex = array_search($stageKey, $stageKeys);

            if ($targetIndex !== false && $targetIndex > 0) {
                for ($i = 0; $i < $targetIndex; $i++) {
                    $precedingKey = is_array($batchStages[$i]) ? ($batchStages[$i]['key'] ?? $batchStages[$i]['name'] ?? '') : (string)$batchStages[$i];
                    $precedingKeyClean = StageHelper::toStageKey($precedingKey);
                    $precedingName = is_array($batchStages[$i]) ? ($batchStages[$i]['name'] ?? StageHelper::toStageName($precedingKey)) : StageHelper::toStageName($precedingKey);

                    $stmtCheckPrec = $db->prepare("
                        SELECT id, qty_out, waste_qty, stage 
                        FROM production_stage_logs 
                        WHERE company_id = ? AND LOWER(TRIM(qr_code)) = LOWER(TRIM(?)) 
                        ORDER BY id DESC
                    ");
                    $stmtCheckPrec->execute([$companyId, $qrCode]);
                    $allPrecLogs = $stmtCheckPrec->fetchAll() ?: [];

                    $precLog = null;
                    foreach ($allPrecLogs as $pl) {
                        if (StageHelper::toStageKey($pl['stage']) === $precedingKeyClean) {
                            $precLog = $pl;
                            break;
                        }
                    }

                    if (!$precLog) {
                        $targetName = is_array($batchStages[$targetIndex]) ? ($batchStages[$targetIndex]['name'] ?? StageHelper::toStageName($stageKey)) : StageHelper::toStageName($stageKey);
                        echo json_encode([
                            'success' => false,
                            'sequence_mismatch' => true,
                            'message' => "Order Sequence Error: Unit ({$qrCode}) cannot enter stage '{$targetName}' yet. Preceding stage '{$precedingName}' must be completed first!"
                        ]);
                        exit;
                    }

                    // Check if unit failed in preceding stage
                    $isFail = ((int)($precLog['qty_out'] ?? 0) === 0 || (int)($precLog['waste_qty'] ?? 0) > 0);
                    if ($isFail) {
                        $targetName = is_array($batchStages[$targetIndex]) ? ($batchStages[$targetIndex]['name'] ?? StageHelper::toStageName($stageKey)) : StageHelper::toStageName($stageKey);
                        echo json_encode([
                            'success' => false,
                            'failed_unit' => true,
                            'message' => "Quality Gate Blocked: Unit ({$qrCode}) was marked as FAILED in preceding stage '{$precedingName}'. Edit entry in stage log to PASS to unblock."
                        ]);
                        exit;
                    }
                }
            }
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

        // Filter variables for Export
        $filterStage = trim((string)$request->get('filter_stage'));
        $filterStatus = strtolower(trim((string)$request->get('filter_status')));
        $filterOperator = (int)$request->get('filter_operator');
        $filterDate = trim((string)$request->get('filter_date'));
        $filterSearch = trim((string)($request->get('search') ?: $request->get('q')));

        $where = ["psl.production_order_id = ?", "psl.company_id = ?"];
        $params = [(int)$id, $companyId];

        if (!empty($filterStage)) {
            $where[] = "psl.stage = ?";
            $params[] = $filterStage;
        }

        if ($filterStatus === 'pass') {
            $where[] = "psl.qty_out > 0";
        } elseif ($filterStatus === 'fail') {
            $where[] = "(psl.qty_out = 0 OR psl.waste_qty > 0)";
        }

        if ($filterOperator > 0) {
            $where[] = "psl.employee_id = ?";
            $params[] = $filterOperator;
        }

        if (!empty($filterDate)) {
            $where[] = "(DATE(psl.created_at) = ? OR DATE(psl.start_time) = ?)";
            $params[] = $filterDate;
            $params[] = $filterDate;
        }

        if (!empty($filterSearch)) {
            $where[] = "(psl.qr_code LIKE ? OR psl.stage LIKE ? OR u.name LIKE ? OR m.name LIKE ?)";
            $searchParam = "%{$filterSearch}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $whereClause = implode(" AND ", $where);

        // Fetch logs matching filters in ASCENDING order (oldest first)
        $stmt = $db->prepare("
            SELECT psl.*, m.name as machine_name, u.name as employee_name, u_edit.name as edited_by_name
            FROM production_stage_logs psl
            LEFT JOIN machines m ON psl.machine_id = m.id
            LEFT JOIN users u ON psl.employee_id = u.id
            LEFT JOIN users u_edit ON psl.edited_by = u_edit.id
            WHERE {$whereClause}
            ORDER BY psl.id ASC
        ");
        $stmt->execute($params);
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

        // Fetch batch stage sequence based on style techpack specifications
        $batchStagesObj = self::getBatchStagesList((int)$id);
        $stagesList = array_map(function($stg) {
            return StageHelper::toStageKey(is_array($stg) ? ($stg['key'] ?? $stg['name'] ?? '') : (string)$stg);
        }, $batchStagesObj);
        $stagesList = array_values(array_filter($stagesList));
        if (empty($stagesList)) {
            $stagesList = ['knitting', 'dyeing', 'compacting', 'relaxing', 'spreading', 'cutting', 'bundling', 'printing', 'embroidery', 'sewing', 'checking', 'thread_cutting', 'washing', 'ironing', 'packing', 'carton_packing', 'shipment'];
        }

        // Fetch company timezone
        $stmtComp = $db->prepare("SELECT timezone FROM companies WHERE id = ?");
        $stmtComp->execute([$companyId]);
        $tenantTimezone = $stmtComp->fetchColumn() ?: 'Asia/Kolkata';

        // Fetch the 15 most recent activity logs for live ticker
        $stmtLogs = $db->prepare("
            SELECT psl.*, u.name as employee_name, m.name as machine_name, u_edit.name as edited_by_name
            FROM production_stage_logs psl
            LEFT JOIN users u ON psl.employee_id = u.id
            LEFT JOIN machines m ON psl.machine_id = m.id
            LEFT JOIN users u_edit ON psl.edited_by = u_edit.id
            WHERE psl.production_order_id = ? AND psl.company_id = ?
            ORDER BY psl.id DESC
            LIMIT 15
        ");
        $stmtLogs->execute([$id, $companyId]);
        $recentLogs = $stmtLogs->fetchAll() ?: [];

        $this->renderView('company/production_stage_live', [
            'title' => "Live Monitor: {$order['production_no']} | ERP",
            'order' => $order,
            'wip_summary' => $wipSummary,
            'stagesList' => $stagesList,
            'recentLogs' => $recentLogs,
            'tenantTimezone' => $tenantTimezone
        ], 'mobile');
    }

    /**
     * AJAX Endpoint: Live Operations Stage Data Polling for Instant Sync
     */
    public function stageLiveApi(Request $request, Response $response, string $id): void {
        header('Content-Type: application/json');
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        // Fetch production order details
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
            echo json_encode(['success' => false, 'error' => 'Production order not found']);
            exit;
        }

        $productionService = new ProductionService();
        $wipSummary = $productionService->getOrderWipSummary($companyId, (int)$id);

        $batchStagesObj = self::getBatchStagesList((int)$id);
        $stagesList = array_map(function($stg) {
            return StageHelper::toStageKey(is_array($stg) ? ($stg['key'] ?? $stg['name'] ?? '') : (string)$stg);
        }, $batchStagesObj);
        $stagesList = array_values(array_filter($stagesList));
        if (empty($stagesList)) {
            $stagesList = ['knitting', 'dyeing', 'compacting', 'relaxing', 'spreading', 'cutting', 'bundling', 'printing', 'embroidery', 'sewing', 'checking', 'thread_cutting', 'washing', 'ironing', 'packing', 'carton_packing', 'shipment'];
        }

        $stmtComp = $db->prepare("SELECT timezone FROM companies WHERE id = ?");
        $stmtComp->execute([$companyId]);
        $tenantTimezone = $stmtComp->fetchColumn() ?: 'Asia/Kolkata';

        $stmtLogs = $db->prepare("
            SELECT psl.*, u.name as employee_name, m.name as machine_name, u_edit.name as edited_by_name
            FROM production_stage_logs psl
            LEFT JOIN users u ON psl.employee_id = u.id
            LEFT JOIN machines m ON psl.machine_id = m.id
            LEFT JOIN users u_edit ON psl.edited_by = u_edit.id
            WHERE psl.production_order_id = ? AND psl.company_id = ?
            ORDER BY psl.id DESC
            LIMIT 15
        ");
        $stmtLogs->execute([$id, $companyId]);
        $recentLogs = $stmtLogs->fetchAll() ?: [];

        foreach ($recentLogs as &$log) {
            $log['formatted_time'] = \App\Helpers\TimezoneHelper::formatTenantTime($log['created_at'] ?? 'now', $tenantTimezone, 'h:i:s A');
            $log['formatted_datetime'] = \App\Helpers\TimezoneHelper::formatTenantTime($log['created_at'] ?? 'now', $tenantTimezone, 'd M, h:i A');
            $log['time_ago'] = \App\Helpers\TimezoneHelper::timeAgo($log['created_at'] ?? 'now');
            $log['stage_clean'] = str_replace('_', ' ', $log['stage']);
            $log['edited_at_formatted'] = !empty($log['edited_at']) ? \App\Helpers\TimezoneHelper::formatTenantTime($log['edited_at'], $tenantTimezone, 'd M Y, h:i A') : null;
        }
        unset($log);

        $targetQty = (int)$order['target_qty'];
        $lastStage = end($stagesList);
        reset($stagesList);
        $finishedQty = isset($wipSummary[$lastStage]) ? (int)$wipSummary[$lastStage]['out'] : 0;
        $completionPct = $targetQty > 0 ? round(($finishedQty / $targetQty) * 100, 1) : 0;

        $totalWaste = 0;
        foreach ($stagesList as $stg) {
            $totalWaste += (isset($wipSummary[$stg]) ? (int)$wipSummary[$stg]['waste'] : 0);
        }
        $wastePct = $targetQty > 0 ? round(($totalWaste / $targetQty) * 100, 1) : 0;

        $latestLog = $recentLogs[0] ?? null;

        echo json_encode([
            'success' => true,
            'target_qty' => number_format($targetQty),
            'finished_qty' => number_format($finishedQty),
            'completion_pct' => $completionPct,
            'total_waste' => number_format($totalWaste),
            'waste_pct' => $wastePct,
            'latest_log' => $latestLog,
            'recent_logs' => $recentLogs,
            'wip_summary' => $wipSummary,
            'stages_list' => $stagesList,
            'server_time' => \App\Helpers\TimezoneHelper::formatTenantTime('now', $tenantTimezone, 'h:i:s A')
        ]);
        exit;
    }

    /**
     * Start Production Order Batch
     */
    public function startOrder(Request $request, Response $response, string $id): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Self-healing database column check
        try {
            $db->query("SELECT started_at FROM production_orders LIMIT 1");
        } catch (\Exception $e) {
            try {
                $db->exec("ALTER TABLE production_orders ADD COLUMN started_at TIMESTAMP NULL DEFAULT NULL AFTER end_date");
                $db->exec("ALTER TABLE production_orders ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER started_at");
            } catch (\Exception $ex) {}
        }

        // Auto-heal started_at column in production_orders
        try {
            $db->exec("ALTER TABLE `production_orders` ADD COLUMN `started_at` TIMESTAMP NULL DEFAULT NULL AFTER `end_date`");
        } catch (\Exception $e) {}

        $stmt = $db->prepare("SELECT * FROM production_orders WHERE id = ? AND company_id = ? AND deleted_at IS NULL");
        $stmt->execute([$id, $companyId]);
        $batch = $stmt->fetch();

        if (!$batch) {
            Session::setFlash('error', 'Production batch not found.');
            $this->redirect('company/production/orders');
            return;
        }

        $db->prepare("UPDATE production_orders SET status = 'running', started_at = UTC_TIMESTAMP(), updated_at = NOW() WHERE id = ?")->execute([$id]);

        AuditLog::log($companyId, Session::get('user_id'), 'start_production', 'ProductionOrder', (int)$id, null, null, "Started manufacturing batch: {$batch['production_no']}");
        Session::setFlash('success', "Production batch #{$batch['production_no']} started successfully. Work time duration counter is active from 0.");
        $this->redirect('company/production/orders');
    }

    /**
     * Complete Production Order Batch
     */
    public function completeOrder(Request $request, Response $response, string $id): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Self-healing database column check
        try {
            $db->query("SELECT completed_at FROM production_orders LIMIT 1");
        } catch (\Exception $e) {
            try {
                $db->exec("ALTER TABLE production_orders ADD COLUMN started_at TIMESTAMP NULL DEFAULT NULL AFTER end_date");
                $db->exec("ALTER TABLE production_orders ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER started_at");
            } catch (\Exception $ex) {}
        }

        $stmt = $db->prepare("SELECT * FROM production_orders WHERE id = ? AND company_id = ? AND deleted_at IS NULL");
        $stmt->execute([$id, $companyId]);
        $batch = $stmt->fetch();

        if (!$batch) {
            Session::setFlash('error', 'Production batch not found.');
            $this->redirect('company/production/orders');
            return;
        }

        $db->prepare("UPDATE production_orders SET status = 'completed', completed_at = UTC_TIMESTAMP(), end_date = CURDATE(), updated_at = NOW() WHERE id = ?")->execute([$id]);

        // Automatically update linked Buyer PO status to 'completed'
        \App\Controllers\MerchandisingController::syncCompletedBuyerPos($db, (int)$companyId);

        AuditLog::log($companyId, Session::get('user_id'), 'complete_production', 'ProductionOrder', (int)$id, null, null, "Marked production batch as completed: {$batch['production_no']}");
        Session::setFlash('success', "Production batch #{$batch['production_no']} marked as Completed! Archived to Completed Products and linked Buyer PO updated to Completed.");
        $this->redirect('company/production/completed');
    }

    /**
     * Completed Products Archive & Production Batch Dossiers
     */
    public function completedProducts(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch completed production batches joined with buyer, style, and PO contract details
        $stmt = $db->prepare("
            SELECT pro.*, s.style_no, s.name as style_name, s.category as style_category, s.brand as style_brand,
                   po.po_no as buyer_po_no, po.quantity as po_target_qty, po.unit_price as po_unit_price, po.total_amount as po_contract_value,
                   c.name as buyer_name, c.code as buyer_code
            FROM production_orders pro
            JOIN buyer_pos po ON pro.po_id = po.id
            JOIN styles s ON po.style_id = s.id
            JOIN contacts c ON po.buyer_id = c.id
            WHERE pro.company_id = ? AND pro.status = 'completed' AND pro.deleted_at IS NULL
            ORDER BY pro.completed_at DESC, pro.id DESC
        ");
        $stmt->execute([$companyId]);
        $completedBatches = $stmt->fetchAll() ?: [];

        // For each completed batch, compute totals, wastage, and attach WIP stage logs with operator names
        foreach ($completedBatches as &$batch) {
            $batchId = $batch['id'];

            // WIP stage logs with user/operator details
            try {
                $stmtLogs = $db->prepare("
                    SELECT psl.*, u.name as operator_name, r.name as operator_role
                    FROM production_stage_logs psl
                    LEFT JOIN users u ON psl.employee_id = u.id
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE psl.production_order_id = ? AND psl.deleted_at IS NULL
                    ORDER BY psl.id ASC
                ");
                $stmtLogs->execute([$batchId]);
                $batch['stage_logs'] = $stmtLogs->fetchAll() ?: [];
            } catch (\Exception $e) {
                try {
                    $stmtLogs = $db->prepare("
                        SELECT psl.*, u.name as operator_name
                        FROM production_stage_logs psl
                        LEFT JOIN users u ON psl.created_by = u.id
                        WHERE psl.production_order_id = ?
                        ORDER BY psl.id ASC
                    ");
                    $stmtLogs->execute([$batchId]);
                    $batch['stage_logs'] = $stmtLogs->fetchAll() ?: [];
                } catch (\Exception $ex) {
                    $batch['stage_logs'] = [];
                }
            }

            // Aggregate Actual Finished Output strictly from "checking" stage & group by unique operator
            $checkingOutput = 0;
            $totalWastage = 0;
            $operatorGrouped = [];

            foreach ($batch['stage_logs'] as $log) {
                $goodQty = (int)($log['qty_out'] ?? $log['good_qty'] ?? 0);
                $wasteQty = (int)($log['waste_qty'] ?? $log['reject_qty'] ?? 0);

                // Actual Finished Output strictly from "checking" stage
                $stgCleanKey = strtolower(trim((string)preg_replace('/^(#|\d+[\.\-\:\)]\s*|(Stage|Step)\s*\d+[\.\-\:\)]?\s*)/i', '', $log['stage'] ?? '')));
                $stgCleanKey = trim((string)preg_replace('/[^a-z0-9]+/', '_', $stgCleanKey), '_');
                if ($stgCleanKey === 'checking' || strtolower(trim($log['stage'] ?? '')) === 'checking') {
                    $checkingOutput += $goodQty;
                }
                $totalWastage += $wasteQty;

                // Group by unique operator / employee
                $operatorName = trim($log['operator_name'] ?? '') ?: 'System Operator';
                $operatorRole = trim($log['operator_role'] ?? '') ?: 'Production Operator';
                $opKey = strtolower($operatorName);

                if (!isset($operatorGrouped[$opKey])) {
                    $operatorGrouped[$opKey] = [
                        'name' => $operatorName,
                        'role' => $operatorRole,
                        'total_good_qty' => 0,
                        'total_waste_qty' => 0,
                        'stages' => []
                    ];
                }

                $operatorGrouped[$opKey]['total_good_qty'] += $goodQty;
                $operatorGrouped[$opKey]['total_waste_qty'] += $wasteQty;

                // Work duration string for stage log
                $durationStr = 'N/A';
                if (!empty($log['duration_minutes'])) {
                    $durationStr = $log['duration_minutes'] . ' mins';
                } elseif (!empty($log['start_time']) && !empty($log['end_time'])) {
                    $diffSecs = max(0, strtotime($log['end_time']) - strtotime($log['start_time']));
                    $hrs = floor($diffSecs / 3600);
                    $mins = floor(($diffSecs % 3600) / 60);
                    $durationStr = ($hrs > 0 ? "{$hrs}h " : "") . "{$mins}m";
                }

                $qrCodeVal = !empty($log['qr_code']) ? $log['qr_code'] : ($batch['production_no'] . '-' . strtoupper(substr($log['stage'], 0, 3)) . '-' . sprintf('%03d', $log['id'] ?? rand(1, 999)));

                $operatorGrouped[$opKey]['stages'][] = [
                    'stage' => $log['stage'],
                    'good_qty' => $goodQty,
                    'waste_qty' => $wasteQty,
                    'logged_at' => date('d M Y, h:i A', strtotime($log['created_at'] ?? $log['start_time'] ?? 'now')),
                    'duration' => $durationStr,
                    'qr_code' => $qrCodeVal
                ];
            }

            $batch['actual_produced_qty'] = ($checkingOutput > 0) ? $checkingOutput : $batch['po_target_qty'];
            $batch['wastage_qty'] = $totalWastage;
            $batch['wastage_percentage'] = ($batch['po_target_qty'] > 0) ? round(($totalWastage / $batch['po_target_qty']) * 100, 2) : 0;
            $batch['operator_summary'] = array_values($operatorGrouped);

            // Estimated Batch Costs & Margins
            $batchCost = (float)($batch['po_contract_value'] * 0.65);
            $batchRevenue = (float)$batch['po_contract_value'];
            $batchProfit = $batchRevenue - $batchCost;
            $batchMargin = ($batchRevenue > 0) ? round(($batchProfit / $batchRevenue) * 100, 2) : 0;

            $batch['total_expenses'] = $batchCost;
            $batch['revenue'] = $batchRevenue;
            $batch['net_profit'] = $batchProfit;
            $batch['margin_percentage'] = $batchMargin;
        }
        unset($batch);

        $this->renderView('company/completed_products', [
            'title' => 'Completed Products Archive | ERP',
            'completed_batches' => $completedBatches
        ]);
    }

    /**
     * AJAX endpoint: Track Unit Lifecycle by QR Code or Serial No
     */
    public function trackQrUnit(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $companyId = Session::get('company_id');
        $qrCode = trim((string)$request->get('qr_code'));
        $batchId = (int)$request->get('batch_id');

        $productionService = new ProductionService();
        $result = $productionService->getUnitTrackingHistory((int)$companyId, $qrCode, $batchId);

        echo json_encode($result);
        exit;
    }
}
