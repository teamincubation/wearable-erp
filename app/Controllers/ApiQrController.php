<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Session;
use App\Helpers\StageHelper;

/**
 * Dedicated REST API Controller for QR Code Scanning Hub
 * Replicates exact web qr-tracking fetch logic 1-to-1 for Mobile App
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

    private function resolveUserId(Request $request): ?int {
        $userId = Session::get('user_id');
        if (!empty($userId)) return (int)$userId;
        
        $headerUser = $_SERVER['HTTP_X_USER_ID'] ?? $request->get('user_id');
        if (!empty($headerUser)) return (int)$headerUser;

        return null;
    }

    /**
     * GET /api/production/qr-tracking-setup
     * GET /api/production/batches
     * Returns all active manufacturing production batches for mobile dropdown selection
     */
    public function getSetup(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $db = Database::getInstance();
        $companyId = $this->resolveCompanyId($request);

        $sql = "
            SELECT pro.id, pro.production_no, po.po_no as po_number, po.quantity, pro.status, pro.company_id,
                   COALESCE(s.style_no, 'N/A') as style_no,
                   COALESCE(s.name, 'Garment Style') as style_name,
                   c.name as buyer_name
            FROM production_orders pro
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN contacts c ON po.buyer_id = c.id
            WHERE pro.deleted_at IS NULL AND pro.company_id = ? AND pro.status != 'completed'
            ORDER BY pro.id DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$companyId]);
        $batches = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $formattedBatches = array_map(function($b) {
            return [
                'id' => (int)$b['id'],
                'production_no' => (string)($b['production_no'] ?? ''),
                'po_number' => (string)($b['po_number'] ?? ''),
                'quantity' => (int)($b['quantity'] ?? 0),
                'status' => (string)($b['status'] ?? 'running'),
                'company_id' => (int)($b['company_id'] ?? 1),
                'style_no' => (string)($b['style_no'] ?? 'N/A'),
                'style_name' => (string)($b['style_name'] ?? 'Garment Style'),
                'buyer_name' => (string)($b['buyer_name'] ?? '')
            ];
        }, $batches);

        $response->json([
            'success' => true,
            'user' => [
                'name' => Session::get('user_name') ?: 'Operator',
                'email' => Session::get('user_email') ?: ''
            ],
            'batches' => $formattedBatches
        ]);
    }

    /**
     * GET /api/production/batch-stages/{id}
     * GET /api/production/stages?batch_id={id}
     * Auto-loads style WIP stages when active batch is selected in Step 1
     */
    public function getBatchStages(Request $request, Response $response, string $id = ''): void {
        header('Content-Type: application/json');
        $batchId = (int)($id ?: $request->get('batch_id'));

        if ($batchId <= 0) {
            $response->json(['success' => false, 'stages' => [], 'message' => 'Please select a batch first.'], 400);
            return;
        }

        $companyId = $this->resolveCompanyId($request);
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
     */
    public function verifyQr(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $jsonBody = json_decode($rawInput, true) ?? [];

        $companyId = $this->resolveCompanyId($request);
        $db = Database::getInstance();

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

        if (!empty($stageKey)) {
            $stmtCheckAlready = $db->prepare("
                SELECT psl.*, u.name as operator_name 
                FROM production_stage_logs psl
                LEFT JOIN users u ON psl.employee_id = u.id
                WHERE (LOWER(TRIM(psl.qr_code)) = LOWER(TRIM(?)) OR LOWER(TRIM(psl.scanned_qr_code)) = LOWER(TRIM(?)))
                ORDER BY psl.id DESC
            ");
            $stmtCheckAlready->execute([$qrCode, $qrCode]);
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

        $parts = explode('-', $qrCode);
        if (count($parts) < 3) {
            $response->json(['success' => false, 'message' => 'Invalid tag format. QR code must match: [BATCH_CODE]-[SIZE]-[SERIAL].'], 400);
            return;
        }
        $serial = (int)array_pop($parts);
        $size = array_pop($parts);
        $batchNo = implode('-', $parts);

        $stmtBatch = $db->prepare("
            SELECT pro.id, pro.status, pro.company_id, po.quantity as target_qty, COALESCE(pro.sizes_json, po.sizes_json) as sizes_json,
                   COALESCE(s.style_no, 'N/A') as style_no, COALESCE(s.name, 'Garment Piece') as style_name
            FROM production_orders pro
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            WHERE pro.production_no = ? AND pro.deleted_at IS NULL
        ");
        $stmtBatch->execute([$batchNo]);
        $batch = $stmtBatch->fetch();

        if (!$batch) {
            $response->json(['success' => false, 'message' => "Production batch '{$batchNo}' not found."], 404);
            return;
        }

        $targetQty = (int)($batch['target_qty'] ?? 0);
        $sizesJson = json_decode($batch['sizes_json'] ?? '{}', true) ?: [];
        
        if ($targetQty > 0) {
            $sizeTarget = 0;
            
            // Try to find exact size match or case-insensitive match
            foreach ($sizesJson as $szKey => $szQty) {
                if (strtolower(trim($szKey)) === strtolower(trim($size))) {
                    $sizeTarget = (int)$szQty;
                    break;
                }
            }
            
            // If the size isn't found in breakdown, fallback to total target just in case
            if ($sizeTarget === 0 && !empty($size) && strtoupper(trim($size)) !== 'FREE' && strtoupper(trim($size)) !== 'FREE SIZE') {
                $response->json(['success' => false, 'message' => "Invalid QR Code. Size '{$size}' is not part of this batch's breakdown."], 200);
                return;
            }



            if (!empty($stageKey)) {
                $stmtCount = $db->prepare("SELECT SUM(qty_out) FROM production_stage_logs WHERE production_order_id = ? AND LOWER(TRIM(stage)) = LOWER(TRIM(?))");
                $stmtCount->execute([$batch['id'], $stageKey]);
                $completedCount = (int)$stmtCount->fetchColumn();

                if ($completedCount >= $targetQty) {
                    $formattedStage = StageHelper::toStageName($stageKey);
                    $response->json(['success' => false, 'message' => "Target reached! Batch target is {$targetQty} pcs, and {$completedCount} pcs are already completed in '{$formattedStage}'."], 200);
                    return;
                }
            }
        }

        // --- Sequential Stage Validation ---
        $seqValidation = $this->validateSequentialStage($db, $qrCode, $stageKey, (int)$batch['id'], $companyId);
        if (!$seqValidation['success']) {
            $response->json([
                'success' => false,
                'message' => $seqValidation['message'],
                'sequence_mismatch' => $seqValidation['sequence_mismatch'] ?? false,
                'failed_unit' => $seqValidation['failed_unit'] ?? false
            ], 200);
            return;
        }

        $response->json([
            'success' => true,
            'qr_code' => $qrCode,
            'batch_no' => $batchNo,
            'size' => $size,
            'serial' => $serial,
            'style_no' => $batch['style_no'] ?? 'N/A',
            'style_name' => $batch['style_name'] ?? 'Garment Piece',
            'category' => 'Apparel',
            'fabric' => 'Cotton Blend',
            'batch_id' => (int)$batch['id'],
            'target_stage' => $stageKey
        ], 200);
    }

    private function validateSequentialStage($db, string $qrCode, string $stageKey, int $batchId, int $companyId): array {
        if (empty($stageKey)) return ['success' => true];

        $batchStages = ProductionController::getBatchStagesList($batchId, $companyId);
        
        $targetIndex = -1;
        $batchStagesKeys = [];
        
        foreach ($batchStages as $index => $stg) {
            $key = is_array($stg) ? ($stg['key'] ?? $stg['name'] ?? '') : (string)$stg;
            $k = StageHelper::toStageKey($key);
            $batchStagesKeys[] = $k;
            if ($k === $stageKey) {
                $targetIndex = $index;
            }
        }

        if ($targetIndex > 0) {
            $prevStageKey = $batchStagesKeys[$targetIndex - 1];
            
            $stmtCheck = $db->prepare("
                SELECT qty_out, waste_qty 
                FROM production_stage_logs 
                WHERE (LOWER(TRIM(qr_code)) = LOWER(TRIM(?)) OR LOWER(TRIM(scanned_qr_code)) = LOWER(TRIM(?)))
                  AND LOWER(TRIM(stage)) = LOWER(TRIM(?))
                ORDER BY id DESC LIMIT 1
            ");
            $stmtCheck->execute([$qrCode, $qrCode, $prevStageKey]);
            $prevLog = $stmtCheck->fetch();

            $formattedPrevStage = StageHelper::toStageName($prevStageKey);

            if (!$prevLog) {
                return ['success' => false, 'sequence_mismatch' => true, 'message' => "Order matters. Please complete '{$formattedPrevStage}' first."];
            }

            if ((int)$prevLog['qty_out'] === 0 || (int)$prevLog['waste_qty'] > 0) {
                return ['success' => false, 'failed_unit' => true, 'message' => "This item failed in previous step ('{$formattedPrevStage}') and cannot proceed."];
            }
        }
        
        return ['success' => true];
    }

    /**
     * POST /api/production/log-qr
     */
    public function logQr(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $jsonBody = json_decode($rawInput, true) ?? [];

        $companyId = $this->resolveCompanyId($request);
        $userId = $this->resolveUserId($request);
        $db = Database::getInstance();

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

        try {
            $db->beginTransaction();

            // Lock the batch row to prevent race conditions from concurrent scans
            $stmtBatch = $db->prepare("
                SELECT pro.id, pro.status, pro.company_id, po.quantity as target_qty, COALESCE(pro.sizes_json, po.sizes_json) as sizes_json
                FROM production_orders pro
                LEFT JOIN buyer_pos po ON pro.po_id = po.id
                WHERE pro.production_no = ? AND pro.deleted_at IS NULL LIMIT 1 FOR UPDATE
            ");
            $stmtBatch->execute([$batchNo]);
            $batch = $stmtBatch->fetch();

            if (!$batch) {
                $db->rollBack();
                $response->json(['success' => false, 'message' => "Batch '{$batchNo}' not found."], 404);
                return;
            }

            // Check if already validated in this stage (Strict duplicate check inside lock)
            $stmtCheckAlready = $db->prepare("
                SELECT id 
                FROM production_stage_logs 
                WHERE (LOWER(TRIM(qr_code)) = LOWER(TRIM(?)) OR LOWER(TRIM(scanned_qr_code)) = LOWER(TRIM(?)))
                  AND LOWER(TRIM(stage)) = LOWER(TRIM(?))
                LIMIT 1
            ");
            $stmtCheckAlready->execute([$qrCode, $qrCode, $stage]);
            if ($stmtCheckAlready->fetchColumn()) {
                $db->rollBack();
                $formattedStage = StageHelper::toStageName($stage);
                $response->json(['success' => false, 'message' => "This QR Code ({$qrCode}) has ALREADY been logged in stage '{$formattedStage}'."], 200);
                return;
            }

            $targetQty = (int)($batch['target_qty'] ?? 0);
            $sizesJson = json_decode($batch['sizes_json'] ?? '{}', true) ?: [];

            if ($targetQty > 0) {
                $sizeTarget = 0;
                foreach ($sizesJson as $szKey => $szQty) {
                    if (strtolower(trim($szKey)) === strtolower(trim($size))) {
                        $sizeTarget = (int)$szQty;
                        break;
                    }
                }

                $stmtCount = $db->prepare("SELECT SUM(qty_out) FROM production_stage_logs WHERE production_order_id = ? AND LOWER(TRIM(stage)) = LOWER(TRIM(?))");
                $stmtCount->execute([$batch['id'], $stage]);
                $completedCount = (int)$stmtCount->fetchColumn();

                if ($completedCount >= $targetQty) {
                    $db->rollBack();
                    $response->json(['success' => false, 'message' => "Target reached! Batch target is {$targetQty} pcs."], 200);
                    return;
                }
            }

            // --- Sequential Stage Validation ---
            $seqValidation = $this->validateSequentialStage($db, $qrCode, $stage, (int)$batch['id'], $companyId);
            if (!$seqValidation['success']) {
                $db->rollBack();
                $response->json([
                    'success' => false, 
                    'message' => $seqValidation['message'],
                    'sequence_mismatch' => $seqValidation['sequence_mismatch'] ?? false,
                    'failed_unit' => $seqValidation['failed_unit'] ?? false
                ], 200);
                return;
            }

            $batchCompanyId = (int)($batch['company_id'] ?: $companyId);
            $qtyIn = 1;
            $qtyOut = ($status === 'pass') ? 1 : 0;
            $wasteQty = ($status === 'pass') ? 0 : 1;

            $nowTs = time();
            $endTime = date('Y-m-d H:i:s', $nowTs);
            $startTime = date('Y-m-d H:i:s', $nowTs - max(1, $durationSeconds));
            $durationMinutes = (int)max(1, ceil($durationSeconds / 60));

            $stmtLog = $db->prepare("
                INSERT INTO production_stage_logs 
                (company_id, production_order_id, stage, employee_id, qty_in, qty_out, waste_qty, start_time, end_time, duration_minutes, created_by, qr_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtLog->execute([
                $batchCompanyId,
                $batch['id'],
                $stage,
                $userId,
                $qtyIn,
                $qtyOut,
                $wasteQty,
                $startTime,
                $endTime,
                $durationMinutes,
                $userId,
                $qrCode
            ]);

            if ($batch['status'] === 'pending') {
                $db->prepare("UPDATE production_orders SET status = 'running' WHERE id = ?")->execute([$batch['id']]);
            }

            $db->commit();

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
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $response->json(['success' => false, 'message' => 'Failed to save log to database: ' . $e->getMessage()], 500);
        }
    }
}
