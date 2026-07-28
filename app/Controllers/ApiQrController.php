<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Session;
use App\Helpers\StageHelper;

/**
 * Separate Dedicated REST API Controller for QR Code Scanning Hub
 * Replicates 1-to-1 exact functionality of company/production/qr-tracking for Mobile App
 */
class ApiQrController extends Controller {

    /**
     * Resolve active tenant company ID
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

        try {
            $db = Database::getInstance();
            $lastComp = $db->query("SELECT company_id FROM production_orders WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 1")->fetchColumn();
            if ($lastComp) {
                return (int)$lastComp;
            }
        } catch (\Exception $e) {}

        return 1;
    }

    /**
     * GET /api/production/qr-tracking-setup
     * Fetch active started production batches for Step 1 dropdown
     */
    public function getSetup(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $companyId = $this->resolveCompanyId($request);
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT pro.id, pro.production_no, pro.po_number, pro.quantity, pro.status,
                   COALESCE(sm.style_no, s.style_no, 'N/A') as style_no,
                   COALESCE(sm.style_name, s.name, 'Garment Style') as style_name
            FROM production_orders pro
            LEFT JOIN style_master sm ON pro.style_id = sm.id
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            WHERE pro.deleted_at IS NULL
            ORDER BY pro.id DESC
        ");
        $stmt->execute();
        $batches = $stmt->fetchAll() ?: [];

        $response->json([
            'success' => true,
            'user' => [
                'name' => Session::get('user_name') ?: 'Operator',
                'email' => Session::get('user_email') ?: ''
            ],
            'batches' => $batches
        ]);
    }

    /**
     * GET /api/production/batch-stages/{id}
     * Auto-load style WIP stages when active batch is selected in Step 1
     */
    public function getBatchStages(Request $request, Response $response, string $id = ''): void {
        header('Content-Type: application/json');
        $companyId = $this->resolveCompanyId($request);
        $batchId = (int)($id ?: $request->get('batch_id'));

        if ($batchId <= 0) {
            $response->json(['success' => false, 'stages' => [], 'message' => 'Please select a batch first.'], 400);
            return;
        }

        $rawStages = ProductionController::getBatchStagesList($batchId, $companyId);
        $stagesList = [];

        foreach ($rawStages as $stg) {
            $key = is_array($stg) ? ($stg['key'] ?? $stg['name'] ?? '') : (string)$stg;
            $name = is_array($stg) ? ($stg['name'] ?? StageHelper::toStageName($key)) : StageHelper::toStageName($key);
            $order = is_array($stg) ? ($stg['order'] ?? null) : null;

            $stagesList[] = [
                'key' => StageHelper::toStageKey($key),
                'name' => ($order ? "#{$order} " : '') . strtoupper($name),
                'order' => $order
            ];
        }

        $response->json([
            'success' => true,
            'batch_id' => $batchId,
            'stages' => $stagesList
        ]);
    }

    /**
     * POST /api/production/verify-qr
     * AJAX Endpoint to verify scanned QR Code and retrieve product details
     * Replicates exact pre-scan sequence, duplicate, and quality gate checks
     */
    public function verifyQr(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $jsonBody = json_decode($rawInput, true) ?? [];

        $companyId = $this->resolveCompanyId($request);
        $db = Database::getInstance();

        // Enforce tenant timezone
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

        // 1. Duplicate check: Prevent validating if already processed in this exact WIP stage
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

        // Parse QR Code e.g. BATCH-TOCCO-001-S-0005
        $parts = explode('-', $qrCode);
        if (count($parts) < 3) {
            $response->json(['success' => false, 'message' => 'Invalid tag format. QR code must match: [BATCH_CODE]-[SIZE]-[SERIAL].'], 400);
            return;
        }
        $serial = (int)array_pop($parts);
        $size = array_pop($parts);
        $batchNo = implode('-', $parts);

        // Fetch production order details
        $stmtBatch = $db->prepare("
            SELECT pro.*, sm.style_no, sm.style_name, sm.category, sm.fabric
            FROM production_orders pro
            LEFT JOIN style_master sm ON pro.style_id = sm.id
            WHERE pro.production_no = ? AND pro.deleted_at IS NULL
        ");
        $stmtBatch->execute([$batchNo]);
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
        $batchStages = ProductionController::getBatchStagesList((int)$batch['id'], $companyId);
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

        // Return verified item details matching Web Popup
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
     * Submit Pass / Fail scan decision
     */
    public function logQr(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $jsonBody = json_decode($rawInput, true) ?? [];

        $companyId = $this->resolveCompanyId($request);
        $userId = Session::get('user_id');
        $db = Database::getInstance();

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

        $parts = explode('-', $qrCode);
        $serial = (int)array_pop($parts);
        $size = array_pop($parts);
        $batchNo = implode('-', $parts);

        $stmtBatch = $db->prepare("SELECT id, status FROM production_orders WHERE production_no = ? AND deleted_at IS NULL");
        $stmtBatch->execute([$batchNo]);
        $batch = $stmtBatch->fetch();

        if (!$batch) {
            $response->json(['success' => false, 'message' => "Batch '{$batchNo}' not found."], 404);
            return;
        }

        $validEmployeeId = null;
        if (!empty($userId) && (int)$userId > 0 && (int)$userId !== 999999) {
            $stmtCheckUser = $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
            $stmtCheckUser->execute([(int)$userId]);
            if ($stmtCheckUser->fetchColumn()) {
                $validEmployeeId = (int)$userId;
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
