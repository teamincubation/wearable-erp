<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Session;
use App\Core\Model;
use App\Helpers\StageHelper;

/**
 * Dedicated Separate REST API Controller for QR Code Scanning Hub
 * Shared by both Web Interface & Mobile Flutter App.
 */
class ApiQrController extends Controller {

    /**
     * Resolve Tenant Company ID from session, headers, or query params
     */
    private function resolveCompanyId(Request $request): int {
        $companyId = Session::get('company_id');
        if (!empty($companyId) && (int)$companyId > 0) {
            return (int)$companyId;
        }

        $headerTenant = $_SERVER['HTTP_X_TENANT_ID'] ?? ($_SERVER['HTTP_TENANT'] ?? $request->get('tenant'));
        if (!empty($headerTenant)) {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT id FROM companies WHERE (subdomain = ? OR id = ?) AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$headerTenant, $headerTenant]);
            $foundId = $stmt->fetchColumn();
            if ($foundId) {
                return (int)$foundId;
            }
        }

        // Fallback to latest active production order company ID
        try {
            $db = Database::getInstance();
            $stmtLast = $db->query("SELECT company_id FROM production_orders WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 1");
            $lastComp = $stmtLast ? $stmtLast->fetchColumn() : null;
            if ($lastComp) {
                return (int)$lastComp;
            }
        } catch (\Exception $e) {}

        return 1;
    }

    /**
     * GET /api/production/batches
     * Returns list of active running production batches for dropdown selection
     */
    public function getBatches(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $companyId = $this->resolveCompanyId($request);
        $db = Database::getInstance();

        // Fetch active production orders directly via API query
        $stmt = $db->prepare("
            SELECT pro.id, pro.production_no, pro.po_number, pro.quantity, pro.status, pro.company_id,
                   COALESCE(sm.style_no, s.style_no, 'N/A') as style_no,
                   COALESCE(sm.style_name, s.name, 'Garment Style') as style_name
            FROM production_orders pro
            LEFT JOIN style_master sm ON pro.style_id = sm.id
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            WHERE pro.deleted_at IS NULL AND pro.status != 'completed'
            ORDER BY pro.id DESC
        ");
        $stmt->execute();
        $batches = $stmt->fetchAll() ?: [];

        $response->json([
            'success' => true,
            'batches' => $batches
        ]);
    }

    /**
     * GET /api/production/stages
     * Returns WIP stages list for selected batch or default company WIP workflow
     */
    public function getStages(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $companyId = $this->resolveCompanyId($request);
        $batchId = (int)$request->get('batch_id');

        $stagesList = [];
        if ($batchId > 0) {
            $rawStages = ProductionController::getBatchStagesList($batchId, $companyId);
            foreach ($rawStages as $stg) {
                $key = is_array($stg) ? ($stg['key'] ?? $stg['name'] ?? '') : (string)$stg;
                $name = is_array($stg) ? ($stg['name'] ?? StageHelper::toStageName($key)) : StageHelper::toStageName($key);
                $stagesList[] = [
                    'key' => StageHelper::toStageKey($key),
                    'name' => $name
                ];
            }
        }

        if (empty($stagesList)) {
            $companyWipStages = CompanyController::getCompanyWipStages($companyId);
            foreach ($companyWipStages as $key => $stg) {
                $stgKey = is_array($stg) ? ($stg['key'] ?? $key) : (is_string($key) ? $key : (string)$stg);
                $stgName = is_array($stg) ? ($stg['name'] ?? StageHelper::toStageName($stgKey)) : StageHelper::toStageName($stgKey);
                $stagesList[] = [
                    'key' => StageHelper::toStageKey($stgKey),
                    'name' => $stgName
                ];
            }
        }

        $response->json([
            'success' => true,
            'stages' => $stagesList
        ]);
    }

    /**
     * POST /api/production/verify-qr
     * Validates scanned QR Code for already-scanned checks, sequence order, and fetches product metadata
     */
    public function verifyQr(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $jsonBody = json_decode($rawInput, true) ?? [];

        $companyId = $this->resolveCompanyId($request);
        $db = Database::getInstance();

        // Enforce tenant company timezone
        $stmtTz = $db->prepare("SELECT timezone FROM companies WHERE id = ?");
        $stmtTz->execute([$companyId]);
        $companyTz = $stmtTz->fetchColumn() ?: 'Asia/Kolkata';
        date_default_timezone_set($companyTz);

        $qrCode = trim($request->get('qr_code') ?: ($jsonBody['qr_code'] ?? ''));
        $rawStage = trim((string)($request->get('stage') ?: ($jsonBody['stage'] ?? '')));
        $stageKey = !empty($rawStage) ? StageHelper::toStageKey($rawStage) : '';

        if (empty($qrCode)) {
            $response->json(['success' => false, 'message' => 'Scanned QR code is empty.'], 400);
            return;
        }

        // 1. Already Scanned Check: Prevent validating if already processed in this exact WIP stage
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
                    
                    $response->json([
                        'success' => false,
                        'already_validated' => true,
                        'message' => "This QR Code ({$qrCode}) has ALREADY been validated in stage '{$formattedStage}' by {$operatorName} on {$logTime}."
                    ], 200);
                    return;
                }
            }
        }

        // Parse QR Code format e.g. BATCH-TOCCO-001-S-0005
        $parts = explode('-', $qrCode);
        if (count($parts) < 3) {
            $response->json(['success' => false, 'message' => 'Invalid tag format. QR code must match: [BATCH_CODE]-[SIZE]-[SERIAL].'], 400);
            return;
        }
        $serial = (int)array_pop($parts);
        $size = array_pop($parts);
        $batchNo = implode('-', $parts);

        // Fetch production batch details
        $stmtBatch = $db->prepare("
            SELECT pro.*, sm.style_no, sm.style_name, sm.category, sm.fabric
            FROM production_orders pro
            LEFT JOIN style_master sm ON pro.style_id = sm.id
            WHERE pro.production_no = ? AND pro.company_id = ? AND pro.deleted_at IS NULL
        ");
        $stmtBatch->execute([$batchNo, $companyId]);
        $batch = $stmtBatch->fetch();

        if (!$batch) {
            $response->json(['success' => false, 'message' => "Production batch '{$batchNo}' not found."], 404);
            return;
        }

        // 2. Cross-Batch Uniqueness Check
        $stmtCheckCross = $db->prepare("
            SELECT psl.production_order_id, pro.production_no 
            FROM production_stage_logs psl
            JOIN production_orders pro ON psl.production_order_id = pro.id
            WHERE psl.company_id = ? AND (psl.qr_code = ? OR psl.scanned_qr_code = ?) AND psl.production_order_id != ? 
            LIMIT 1
        ");
        $stmtCheckCross->execute([$companyId, $qrCode, $qrCode, (int)$batch['id']]);
        $existingCross = $stmtCheckCross->fetch();
        if ($existingCross) {
            $response->json([
                'success' => false,
                'duplicate_qr' => true,
                'message' => "QR Code Duplicate Error: QR code '{$qrCode}' is already registered to another Production Batch '{$existingCross['production_no']}'."
            ], 200);
            return;
        }

        // 3. Preceding Stage Order Sequence Check
        $batchStages = ProductionController::getBatchStagesList((int)$batch['id']);
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
                    $response->json([
                        'success' => false,
                        'sequence_mismatch' => true,
                        'message' => "Order Sequence Error: Unit ({$qrCode}) cannot enter stage '{$targetName}' yet. Preceding stage '{$precedingName}' must be completed first!"
                    ], 200);
                    return;
                }

                // Check if unit was marked as FAILED in preceding stage
                $isFail = ((int)($precLog['qty_out'] ?? 0) === 0 || (int)($precLog['waste_qty'] ?? 0) > 0);
                if ($isFail) {
                    $targetName = is_array($batchStages[$targetIndex]) ? ($batchStages[$targetIndex]['name'] ?? StageHelper::toStageName($stageKey)) : StageHelper::toStageName($stageKey);
                    $response->json([
                        'success' => false,
                        'failed_unit' => true,
                        'message' => "Quality Gate Blocked: Unit ({$qrCode}) was marked as FAILED in preceding stage '{$precedingName}'."
                    ], 200);
                    return;
                }
            }
        }

        // Return validated product details
        $response->json([
            'success' => true,
            'qr_code' => $qrCode,
            'batch_no' => $batchNo,
            'size' => $size,
            'serial' => $serial,
            'style_no' => $batch['style_no'] ?? 'N/A',
            'style_name' => $batch['style_name'] ?? 'Garment Piece',
            'category' => $batch['category'] ?? 'Apparel',
            'fabric' => $batch['fabric'] ?? 'Cotton Blend',
            'batch_id' => (int)$batch['id'],
            'target_stage' => $stageKey
        ], 200);
    }

    /**
     * POST /api/production/log-qr
     * Submits Pass / Fail scan decision to production_stage_logs table
     */
    public function logQr(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $jsonBody = json_decode($rawInput, true) ?? [];

        $companyId = $this->resolveCompanyId($request);
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        // Enforce tenant company timezone
        $stmtTz = $db->prepare("SELECT timezone FROM companies WHERE id = ?");
        $stmtTz->execute([$companyId]);
        $companyTz = $stmtTz->fetchColumn() ?: 'Asia/Kolkata';
        date_default_timezone_set($companyTz);

        $qrCode = trim($request->get('qr_code') ?: ($jsonBody['qr_code'] ?? ''));
        $rawStage = trim((string)($request->get('stage') ?: ($jsonBody['stage'] ?? '')));
        $stage = StageHelper::toStageKey($rawStage);
        $status = strtolower(trim((string)($request->get('status') ?: ($jsonBody['status'] ?? 'pass'))));
        $durationSeconds = (int)($request->get('duration_seconds') ?: ($jsonBody['duration_seconds'] ?? 2));

        if (empty($qrCode) || empty($stage)) {
            $response->json(['success' => false, 'message' => 'Missing scanned QR code or stage.'], 400);
            return;
        }

        // Parse QR Code to locate batch
        $parts = explode('-', $qrCode);
        $serial = (int)array_pop($parts);
        $size = array_pop($parts);
        $batchNo = implode('-', $parts);

        $stmtBatch = $db->prepare("SELECT id, status FROM production_orders WHERE production_no = ? AND company_id = ? AND deleted_at IS NULL");
        $stmtBatch->execute([$batchNo, $companyId]);
        $batch = $stmtBatch->fetch();

        if (!$batch) {
            $response->json(['success' => false, 'message' => "Batch '{$batchNo}' not found."], 404);
            return;
        }

        $employeeId = $userId;
        $validEmployeeId = null;
        if (!empty($employeeId) && (int)$employeeId > 0 && (int)$employeeId !== 999999) {
            $stmtCheckUser = $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
            $stmtCheckUser->execute([(int)$employeeId]);
            if ($stmtCheckUser->fetchColumn()) {
                $validEmployeeId = (int)$employeeId;
            }
        }

        $qtyIn = 1;
        $qtyOut = ($status === 'pass') ? 1 : 0;
        $wasteQty = ($status === 'pass') ? 0 : 1;

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
                $validEmployeeId,
                $qrCode
            ]);

            if ($batch['status'] === 'pending') {
                $db->prepare("UPDATE production_orders SET status = 'running' WHERE id = ?")->execute([$batch['id']]);
            }

            $response->json([
                'success' => true,
                'message' => "Piece #{$serial} (Size {$size}) logged as " . strtoupper($status) . " under stage " . ucfirst(str_replace('_', ' ', $stage)) . ".",
                'details' => [
                    'batch_no' => $batchNo,
                    'size' => $size,
                    'serial' => $serial,
                    'status' => $status
                ]
            ], 200);
        } catch (\Exception $e) {
            $response->json(['success' => false, 'message' => 'Failed to save log to database: ' . $e->getMessage()], 500);
        }
    }
}
