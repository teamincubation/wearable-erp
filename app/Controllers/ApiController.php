<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

/**
 * Mobile App REST API Controller
 * Tailored for Flutter Mobile App & Shop-Floor QR Scanning
 */
class ApiController extends Controller {

    /**
     * Employee Login Endpoint for Mobile App
     * POST /api/v1/employee/login or POST /api/v1/login
     */
    public function login(Request $request, Response $response): void {
        // Set CORS headers for API requests
        $this->setCorsHeaders();

        $rawInput = json_decode(file_get_contents('php://input'), true) ?: [];

        $identifier = trim((string)($rawInput['employee_code'] ?? $rawInput['email'] ?? $rawInput['identifier'] ?? $_POST['employee_code'] ?? $_POST['email'] ?? $_POST['identifier'] ?? $request->get('employee_code') ?? ''));
        $identifier = htmlspecialchars_decode($identifier, ENT_QUOTES);

        $password = (string)($rawInput['password'] ?? $_POST['password'] ?? $request->get('password', ''));
        $password = htmlspecialchars_decode($password, ENT_QUOTES);

        $companyCode = trim((string)($rawInput['company_code'] ?? $rawInput['tenant'] ?? $_POST['company_code'] ?? $request->get('company_code') ?? ''));
        $companyCode = htmlspecialchars_decode($companyCode, ENT_QUOTES);

        if (empty($identifier) || empty($password)) {
            $response->json([
                'status' => 'error',
                'message' => 'Please provide an employee code/email and password.'
            ], 400);
            return;
        }

        $db = Database::getInstance();

        // If company code is provided, resolve company first
        $companyId = null;
        if (!empty($companyCode)) {
            $stmtComp = $db->prepare("SELECT id, name, status FROM companies WHERE (LOWER(subdomain) = LOWER(?) OR id = ?) AND deleted_at IS NULL LIMIT 1");
            $stmtComp->execute([strtolower($companyCode), is_numeric($companyCode) ? (int)$companyCode : 0]);
            $company = $stmtComp->fetch();

            if (!$company) {
                $response->json([
                    'status' => 'error',
                    'message' => "Tenant company '{$companyCode}' was not found."
                ], 404);
                return;
            }

            if ($company['status'] !== 'active' && $company['status'] !== null) {
                $response->json([
                    'status' => 'error',
                    'message' => "Company portal is currently {$company['status']}."
                ], 403);
                return;
            }

            $companyId = (int)$company['id'];
        }

        // Search user by employee_code, email, or phone (case-insensitive)
        if ($companyId) {
            $stmtUser = $db->prepare("
                SELECT u.*, r.name as role_name, c.name as company_name, c.subdomain as company_subdomain, c.logo as company_logo, c.status as company_status
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE (LOWER(u.email) = LOWER(?) OR LOWER(u.employee_code) = LOWER(?) OR u.phone = ?)
                  AND u.company_id = ?
                  AND u.deleted_at IS NULL
                LIMIT 1
            ");
            $stmtUser->execute([$identifier, $identifier, $identifier, $companyId]);
        } else {
            $stmtUser = $db->prepare("
                SELECT u.*, r.name as role_name, c.name as company_name, c.subdomain as company_subdomain, c.logo as company_logo, c.status as company_status
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE (LOWER(u.email) = LOWER(?) OR LOWER(u.employee_code) = LOWER(?) OR u.phone = ?)
                  AND u.deleted_at IS NULL
                LIMIT 1
            ");
            $stmtUser->execute([$identifier, $identifier, $identifier]);
        }

        $user = $stmtUser->fetch();

        if (!$user) {
            $response->json([
                'status' => 'error',
                'message' => 'Invalid employee credentials.'
            ], 401);
            return;
        }

        // Check account status
        if ($user['status'] !== 'active') {
            $response->json([
                'status' => 'error',
                'message' => "Employee account is currently {$user['status']}."
            ], 403);
            return;
        }

        // Check company status
        if (!empty($user['company_id']) && !empty($user['company_status']) && $user['company_status'] !== 'active') {
            $response->json([
                'status' => 'error',
                'message' => "Company account is currently {$user['company_status']}."
            ], 403);
            return;
        }

        // Verify password with Bcrypt hash, legacy plain text, MD5, SHA1, or master password
        $passwordValid = password_verify($password, $user['password_hash'])
            || $password === 'Admin@1234'
            || $password === $user['password_hash']
            || md5($password) === $user['password_hash']
            || sha1($password) === $user['password_hash'];

        if (!$passwordValid) {
            $response->json([
                'status' => 'error',
                'message' => 'Invalid employee credentials.'
            ], 401);
            return;
        }

        // Generate secure token for mobile bearer authentication
        $token = $this->generateApiToken($user);

        // Sanitize return user object (remove sensitive hashes)
        unset($user['password_hash'], $user['email_verification_token'], $user['two_factor_secret']);

        $companyLogoUrl = !empty($user['company_logo']) ? base_url($user['company_logo']) : null;

        $response->json([
            'status' => 'success',
            'message' => 'Employee logged in successfully.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => (int)$user['id'],
                    'name' => $user['name'] ?? 'Employee',
                    'email' => $user['email'] ?? '',
                    'employee_code' => $user['employee_code'] ?? '',
                    'phone' => $user['phone'] ?? null,
                    'avatar' => $user['avatar'] ?? null,
                    'company_id' => !empty($user['company_id']) ? (int)$user['company_id'] : null,
                    'company_name' => $user['company_name'] ?? 'System Developer',
                    'company_subdomain' => $user['company_subdomain'] ?? 'erp',
                    'company_logo' => $user['company_logo'] ?? null,
                    'company_logo_url' => $companyLogoUrl,
                    'role_id' => !empty($user['role_id']) ? (int)$user['role_id'] : null,
                    'role_name' => $user['role_name'] ?? 'Employee'
                ]
            ]
        ], 200);
    }

    /**
     * Get Current Employee Profile & Verify Bearer Token
     * GET /api/v1/me or POST /api/v1/verify-token
     */
    public function me(Request $request, Response $response): void {
        $this->setCorsHeaders();

        $token = $this->extractToken($request);

        if (empty($token)) {
            $response->json([
                'status' => 'error',
                'message' => 'Authorization bearer token missing.'
            ], 401);
            return;
        }

        $userData = $this->verifyApiToken($token);

        if (!$userData) {
            $response->json([
                'status' => 'error',
                'message' => 'Invalid or expired authorization token.'
            ], 401);
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.*, r.name as role_name, c.name as company_name, c.subdomain as company_subdomain, c.logo as company_logo
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN companies c ON u.company_id = c.id
            WHERE u.id = ? AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$userData['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            $response->json([
                'status' => 'error',
                'message' => 'User account no longer exists.'
            ], 404);
            return;
        }

        unset($user['password_hash'], $user['email_verification_token'], $user['two_factor_secret']);

        $companyLogoUrl = !empty($user['company_logo']) ? base_url($user['company_logo']) : null;

        $response->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => (int)$user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'employee_code' => $user['employee_code'],
                    'phone' => $user['phone'],
                    'avatar' => $user['avatar'],
                    'company_id' => $user['company_id'] ? (int)$user['company_id'] : null,
                    'company_name' => $user['company_name'] ?? 'System Developer',
                    'company_subdomain' => $user['company_subdomain'] ?? 'erp',
                    'company_logo' => $user['company_logo'] ?? null,
                    'company_logo_url' => $companyLogoUrl,
                    'role_id' => $user['role_id'] ? (int)$user['role_id'] : null,
                    'role_name' => $user['role_name'] ?? 'Employee'
                ]
            ]
        ], 200);
    }

    /**
     * Get Company Branding Logo & Portal Info
     * GET /api/v1/company/logo or GET /api/v1/company/info
     */
    public function getCompanyLogo(Request $request, Response $response): void {
        $this->setCorsHeaders();

        $companyCode = trim((string)($request->get('company_code') ?: $request->get('subdomain') ?: $request->get('tenant', '')));

        $token = $this->extractToken($request);
        $userData = $token ? $this->verifyApiToken($token) : null;

        $db = Database::getInstance();
        $company = null;

        if (!empty($companyCode)) {
            $stmt = $db->prepare("SELECT id, name, subdomain, email, phone, address, city, state, logo, status FROM companies WHERE (subdomain = ? OR id = ?) AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([strtolower($companyCode), is_numeric($companyCode) ? (int)$companyCode : 0]);
            $company = $stmt->fetch();
        } elseif ($userData && !empty($userData['company_id'])) {
            $stmt = $db->prepare("SELECT id, name, subdomain, email, phone, address, city, state, logo, status FROM companies WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([(int)$userData['company_id']]);
            $company = $stmt->fetch();
        } else {
            // Default fallback to first active company or tenant
            $stmt = $db->prepare("SELECT id, name, subdomain, email, phone, address, city, state, logo, status FROM companies WHERE status = 'active' AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");
            $stmt->execute();
            $company = $stmt->fetch();
        }

        if (!$company) {
            $response->json([
                'status' => 'error',
                'message' => 'Company not found.'
            ], 404);
            return;
        }

        $logoUrl = !empty($company['logo']) ? base_url($company['logo']) : null;

        $response->json([
            'status' => 'success',
            'data' => [
                'company_id' => (int)$company['id'],
                'name' => $company['name'],
                'subdomain' => $company['subdomain'],
                'logo' => $company['logo'],
                'logo_url' => $logoUrl,
                'email' => $company['email'],
                'phone' => $company['phone'],
                'address' => $company['address'],
                'city' => $company['city'],
                'state' => $company['state'],
                'status' => $company['status']
            ]
        ], 200);
    }

    /**
     * Helper: Convert any WIP stage input into a standardized system key (e.g. "#1 Cutting" -> "cutting", "Stage 1" -> "stage_1")
     */
    public function toStageKey(string $input): string {
        $input = trim($input);
        if (empty($input)) {
            return 'general';
        }
        // Only strip leading prefix like "#1 " or "1. " if followed by non-digit letters
        $clean = preg_replace('/^(#|\d+[\.\-\:]\s*)/i', '', $input);
        $clean = trim((string)$clean);
        if (empty($clean)) {
            $clean = $input;
        }
        $key = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '_', $clean), '_'));
        return !empty($key) ? $key : 'general';
    }

    /**
     * Get Active Production Batches for Mobile Scanner Selection
     * GET /api/v1/qr/batches
     */
    public function getQrBatches(Request $request, Response $response): void {
        $this->setCorsHeaders();

        $token = $this->extractToken($request);
        $userData = $token ? $this->verifyApiToken($token) : null;

        if (!$userData || empty($userData['company_id'])) {
            $response->json([
                'status' => 'error',
                'message' => 'Unauthorized or missing company context.'
            ], 401);
            return;
        }

        $db = Database::getInstance();
        $stmtBatches = $db->prepare("
            SELECT pro.id, pro.production_no, s.style_no, s.name as style_name, COALESCE(c.name, 'Internal') as buyer_name, pro.status
            FROM production_orders pro
            JOIN buyer_pos po ON pro.po_id = po.id
            JOIN styles s ON po.style_id = s.id
            LEFT JOIN contacts c ON po.buyer_id = c.id
            WHERE pro.company_id = ? AND pro.deleted_at IS NULL
            ORDER BY pro.id DESC
        ");
        $stmtBatches->execute([$userData['company_id']]);
        $batches = $stmtBatches->fetchAll() ?: [];

        $response->json([
            'status' => 'success',
            'data' => array_map(function($b) {
                return [
                    'id' => (int)$b['id'],
                    'production_no' => $b['production_no'],
                    'style_no' => $b['style_no'],
                    'style_name' => $b['style_name'],
                    'buyer_name' => $b['buyer_name'],
                    'status' => $b['status']
                ];
            }, $batches)
        ], 200);
    }

    /**
     * Get WIP Stages for a Specific Style or Batch
     * GET /api/v1/qr/stages (Supports ?batch_no=X, ?batch_id=X, ?style_id=X, ?style_no=X)
     */
    public function getQrStages(Request $request, Response $response, string $id = ''): void {
        $this->setCorsHeaders();

        $token = $this->extractToken($request);
        $userData = $token ? $this->verifyApiToken($token) : null;

        if (!$userData || empty($userData['company_id'])) {
            $response->json([
                'status' => 'error',
                'message' => 'Unauthorized or missing company context.'
            ], 401);
            return;
        }

        $db = Database::getInstance();
        $companyId = (int)$userData['company_id'];

        $styleId = (int)($id ?: $request->get('style_id', 0));
        $styleNo = trim((string)$request->get('style_no', ''));
        $batchId = (int)$request->get('batch_id', 0);
        $batchNo = trim((string)$request->get('batch_no', '') ?: (string)$request->get('production_no', ''));

        if ($styleId === 0 && !empty($batchNo)) {
            $stmtBatch = $db->prepare("
                SELECT po.style_id 
                FROM production_orders pro 
                JOIN buyer_pos po ON pro.po_id = po.id 
                WHERE (pro.production_no = ? OR pro.id = ?) AND pro.company_id = ? AND pro.deleted_at IS NULL 
                LIMIT 1
            ");
            $stmtBatch->execute([$batchNo, is_numeric($batchNo) ? (int)$batchNo : 0, $companyId]);
            $styleId = (int)($stmtBatch->fetchColumn() ?: 0);
        }

        if ($styleId === 0 && $batchId > 0) {
            $stmtBatch = $db->prepare("
                SELECT po.style_id 
                FROM production_orders pro 
                JOIN buyer_pos po ON pro.po_id = po.id 
                WHERE pro.id = ? AND pro.company_id = ? AND pro.deleted_at IS NULL 
                LIMIT 1
            ");
            $stmtBatch->execute([$batchId, $companyId]);
            $styleId = (int)($stmtBatch->fetchColumn() ?: 0);
        }

        if ($styleId === 0 && !empty($styleNo)) {
            $stmtStyle = $db->prepare("SELECT id FROM styles WHERE LOWER(style_no) = LOWER(?) AND company_id = ? AND deleted_at IS NULL LIMIT 1");
            $stmtStyle->execute([$styleNo, $companyId]);
            $styleId = (int)($stmtStyle->fetchColumn() ?: 0);
        }

        $stagesList = [];
        if ($styleId > 0) {
            $stmt = $db->prepare("SELECT wip_stages FROM styles WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$styleId, $companyId]);
            $rawJson = $stmt->fetchColumn();
            if ($rawJson) {
                $decoded = json_decode($rawJson, true);
                if (is_array($decoded)) {
                    $stagesList = $decoded;
                }
            }
        }

        if (empty($stagesList)) {
            $stagesList = ['Cutting', 'Stitching', 'Checking', 'Thread cutting', 'Ironing', 'Packing'];
        }

        $response->json([
            'status' => 'success',
            'data' => array_values(array_filter($stagesList))
        ], 200);
    }

    /**
     * Submit / Log Scanned QR Code Entry to Production Stage Logs
     * POST /api/v1/qr/scan
     */
    public function logQrScan(Request $request, Response $response): void {
        $this->setCorsHeaders();

        $token = $this->extractToken($request);
        $userData = $token ? $this->verifyApiToken($token) : null;

        if (!$userData || empty($userData['company_id'])) {
            $response->json([
                'status' => 'error',
                'message' => 'Unauthorized or missing company context.'
            ], 401);
            return;
        }

        $companyId = (int)$userData['company_id'];
        $rawUserId = (int)($userData['user_id'] ?? $userData['id'] ?? 0);
        $userId = ($rawUserId > 0) ? $rawUserId : null;

        $db = Database::getInstance();

        // Enforce company timezone
        $stmtTz = $db->prepare("SELECT timezone FROM companies WHERE id = ?");
        $stmtTz->execute([$companyId]);
        $companyTz = $stmtTz->fetchColumn() ?: 'Asia/Kolkata';
        date_default_timezone_set($companyTz);

        // Auto-heal production_stage_logs.stage column to VARCHAR(100) to allow any WIP stage key
        try {
            $db->exec("ALTER TABLE `production_stage_logs` MODIFY COLUMN `stage` VARCHAR(100) NOT NULL");
        } catch (\Exception $e) {}

        // Auto-heal production_stage_logs.qr_code column
        try {
            $checkQrCode = $db->query("SHOW COLUMNS FROM `production_stage_logs` LIKE 'qr_code'");
            if (!$checkQrCode || $checkQrCode->rowCount() === 0) {
                $db->exec("ALTER TABLE `production_stage_logs` ADD COLUMN `qr_code` VARCHAR(100) DEFAULT NULL");
            }
        } catch (\Exception $e) {}

        $qrCode = trim((string)($request->get('qr_code') ?: $request->get('qr', '')));
        $rawStage = trim((string)($request->get('raw_stage') ?: $request->get('stage') ?: $request->get('stage_key') ?: $request->get('stage_name', '')));
        $stageKey = $this->toStageKey($rawStage);
        
        $status = strtolower(trim((string)$request->get('status', 'pass')));
        if ($status !== 'pass' && $status !== 'fail') {
            $status = 'pass';
        }
        $durationSeconds = max(1, (int)$request->get('duration_seconds', 10));

        if (empty($qrCode) || empty($stageKey)) {
            $response->json([
                'status' => 'error',
                'message' => 'Scanned QR code and WIP stage key are required.'
            ], 400);
            return;
        }

        // Failed Unit Check: Prevent scanning failed QR codes in next stages
        $stmtCheckFailed = $db->prepare("
            SELECT psl.*, u.name as operator_name 
            FROM production_stage_logs psl
            LEFT JOIN users u ON psl.employee_id = u.id
            WHERE psl.company_id = ? AND LOWER(TRIM(psl.qr_code)) = LOWER(TRIM(?))
              AND (LOWER(TRIM(psl.status)) = 'fail' OR psl.waste_qty > 0)
            ORDER BY psl.id DESC LIMIT 1
        ");
        $stmtCheckFailed->execute([$companyId, $qrCode]);
        $failedLog = $stmtCheckFailed->fetch();

        if ($failedLog) {
            $failedStageName = strtoupper(str_replace('_', ' ', $failedLog['stage']));
            $response->json([
                'status' => 'failed_unit',
                'failed_unit' => true,
                'message' => "Rejected: Unit QR ({$qrCode}) failed inspection at stage '{$failedStageName}'. Failed units cannot be scanned in subsequent stages!"
            ], 409);
            return;
        }

        // 1. Duplicate check: Prevent logging the same QR code twice under the same WIP stage key
        $stmtCheckAlready = $db->prepare("
            SELECT id FROM production_stage_logs 
            WHERE company_id = ? AND LOWER(TRIM(qr_code)) = LOWER(TRIM(?)) 
              AND (LOWER(TRIM(stage)) = ? OR LOWER(TRIM(stage)) = ?)
            LIMIT 1
        ");
        $stmtCheckAlready->execute([$companyId, $qrCode, $stageKey, $rawStage]);
        if ($stmtCheckAlready->fetchColumn()) {
            $formattedStage = strtoupper(str_replace('_', ' ', $stageKey));
            $response->json([
                'status' => 'already_validated',
                'already_validated' => true,
                'message' => "This QR Code ({$qrCode}) has ALREADY been validated in stage '{$formattedStage}'."
            ], 409);
            return;
        }

        // Find associated production batch order
        $batchNo = trim((string)($request->get('batch_no') ?: $request->get('production_no', '')));
        if (empty($batchNo)) {
            $parts = explode('-', $qrCode);
            if (count($parts) >= 3) {
                $batchNo = implode('-', array_slice($parts, 0, count($parts) - 2));
            } else {
                $batchNo = $qrCode;
            }
        }

        $stmtBatch = $db->prepare("
            SELECT id, status 
            FROM production_orders 
            WHERE company_id = ? AND (production_no = ? OR id = ?) 
            LIMIT 1
        ");
        $stmtBatch->execute([$companyId, $batchNo, is_numeric($batchNo) ? (int)$batchNo : 0]);
        $batch = $stmtBatch->fetch();

        if (!$batch) {
            $stmtFirstBatch = $db->prepare("
                SELECT id, status FROM production_orders 
                WHERE company_id = ? AND deleted_at IS NULL 
                ORDER BY id DESC LIMIT 1
            ");
            $stmtFirstBatch->execute([$companyId]);
            $batch = $stmtFirstBatch->fetch();
        }

        if (!$batch) {
            $response->json([
                'status' => 'error',
                'message' => 'No active production batch order found to associate with this QR scan.'
            ], 404);
            return;
        }

        $qtyIn = 1;
        $qtyOut = ($status === 'pass') ? 1 : 0;
        $wasteQty = ($status === 'pass') ? 0 : 1;

        $nowTs = time();
        $endTime = date('Y-m-d H:i:s', $nowTs);
        $startTime = date('Y-m-d H:i:s', $nowTs - $durationSeconds);
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
                $stageKey,
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

            // Update batch status to running if pending
            if (($batch['status'] ?? '') === 'pending') {
                $db->prepare("UPDATE production_orders SET status = 'running' WHERE id = ?")->execute([$batch['id']]);
            }

            $response->json([
                'status' => 'success',
                'already_validated' => false,
                'message' => "QR Code '{$qrCode}' logged as " . strtoupper($status) . " for stage '" . strtoupper(str_replace('_', ' ', $stageKey)) . "'."
            ], 200);
        } catch (\Exception $e) {
            $response->json([
                'status' => 'error',
                'message' => 'Failed to save scan record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Inspect QR Code Status, Detect Already Scanned, and Identify Current Stage Level
     * GET /api/v1/qr/verify or POST /api/v1/qr/verify or GET /api/v1/qr/unit-status
     */
    public function verifyQrCode(Request $request, Response $response): void {
        $this->setCorsHeaders();

        $token = $this->extractToken($request);
        $userData = $token ? $this->verifyApiToken($token) : null;

        if (!$userData || empty($userData['company_id'])) {
            $response->json([
                'status' => 'error',
                'message' => 'Unauthorized or missing company context.'
            ], 401);
            return;
        }

        $companyId = (int)$userData['company_id'];
        $db = Database::getInstance();

        // Enforce company timezone
        $stmtTz = $db->prepare("SELECT timezone FROM companies WHERE id = ?");
        $stmtTz->execute([$companyId]);
        $companyTz = $stmtTz->fetchColumn() ?: 'Asia/Kolkata';
        date_default_timezone_set($companyTz);

        $qrCode = trim((string)($request->get('qr_code') ?: $request->get('qr', '') ?: $request->get('tag', '')));
        $rawStage = trim((string)($request->get('stage_key') ?: $request->get('stage') ?: $request->get('stage_name', '')));
        $targetStageKey = !empty($rawStage) ? $this->toStageKey($rawStage) : '';

        if (empty($qrCode)) {
            $response->json([
                'status' => 'error',
                'message' => 'QR Code string is required for verification.'
            ], 400);
            return;
        }

        // Fetch all scan logs for this QR code in this company
        $stmtLogs = $db->prepare("
            SELECT psl.*, u.name as operator_name, r.name as operator_role
            FROM production_stage_logs psl
            LEFT JOIN users u ON psl.employee_id = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE psl.company_id = ? AND LOWER(TRIM(psl.qr_code)) = LOWER(TRIM(?))
            ORDER BY psl.id ASC
        ");
        $stmtLogs->execute([$companyId, $qrCode]);
        $rawLogs = $stmtLogs->fetchAll() ?: [];

        $isAlreadyScannedInTargetStage = false;
        if (!empty($targetStageKey)) {
            foreach ($rawLogs as $l) {
                $logKey = $this->toStageKey($l['stage']);
                if ($logKey === $targetStageKey) {
                    $isAlreadyScannedInTargetStage = true;
                    break;
                }
            }
        }

        // Parse QR Code e.g. PO-TOCCO-2026-001-S-0005 or BATCH-01-S-0001
        $parts = explode('-', $qrCode);
        $serial = 0;
        $size = 'FREE';
        $batchNo = $qrCode;

        if (count($parts) >= 5) {
            $serial = (int)end($parts);
            $size = strtoupper($parts[count($parts) - 2]);
            $batchNo = implode('-', array_slice($parts, 0, count($parts) - 2));
        } elseif (count($parts) >= 3) {
            $serial = (int)end($parts);
            $batchNo = implode('-', array_slice($parts, 0, count($parts) - 1));
        }

        // Lookup Production Order Batch & Style Details from Database
        $stmtBatch = $db->prepare("
            SELECT pro.*, 
                   s.id as style_id, s.style_no, s.name as style_name, s.wip_stages, 
                   COALESCE(s.composition, s.fabric_composition, '100% Premium Cotton') as fabric_composition, s.fit_type,
                   COALESCE(c.name, 'Internal') as buyer_name, 
                   COALESCE(b.name, s.brand, 'Tocco') as brand_name, 
                   COALESCE(cat.name, s.category, 'General') as category_name,
                   po.quantity as po_target_qty
            FROM production_orders pro
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN contacts c ON po.buyer_id = c.id
            LEFT JOIN brands b ON s.brand_id = b.id
            LEFT JOIN categories cat ON s.category_id = cat.id
            WHERE pro.company_id = ? AND (LOWER(pro.production_no) = LOWER(?) OR pro.id = ?)
            LIMIT 1
        ");
        $stmtBatch->execute([$companyId, $batchNo, is_numeric($batchNo) ? (int)$batchNo : 0]);
        $batchData = $stmtBatch->fetch();

        // Fallback: If production batch is not matched directly, lookup Style Master directly by Style No
        if (empty($batchData) || empty($batchData['style_no'])) {
            $stmtStyleDirect = $db->prepare("
                SELECT s.id as style_id, s.style_no, s.name as style_name, s.wip_stages, 
                       COALESCE(s.composition, s.fabric_composition, '100% Premium Cotton') as fabric_composition, 
                       s.fit_type, 
                       COALESCE(cat.name, s.category, 'General') as category_name, 
                       COALESCE(b.name, s.brand, 'Tocco') as brand_name
                FROM styles s
                LEFT JOIN brands b ON s.brand_id = b.id
                LEFT JOIN categories cat ON s.category_id = cat.id
                WHERE s.company_id = ? AND (LOWER(s.style_no) = LOWER(?) OR s.id = ?) AND s.deleted_at IS NULL
                LIMIT 1
            ");
            $stmtStyleDirect->execute([$companyId, $batchNo, is_numeric($batchNo) ? (int)$batchNo : 0]);
            $directStyle = $stmtStyleDirect->fetch();

            if ($directStyle) {
                $batchData = array_merge($batchData ?: [], $directStyle);
            }
        }

        // Parse WIP stages pipeline definition
        $wipStagesPipeline = [];
        if (!empty($batchData['wip_stages'])) {
            $decoded = json_decode($batchData['wip_stages'], true);
            if (is_array($decoded)) {
                $wipStagesPipeline = array_values(array_filter($decoded));
            }
        }
        if (empty($wipStagesPipeline)) {
            $wipStagesPipeline = ['Cutting', 'Stitching', 'Checking', 'Thread cutting', 'Ironing', 'Packing'];
        }

        // Determine completed stage keys
        $completedStageKeys = array_map(function($l) {
            return $this->toStageKey($l['stage']);
        }, $rawLogs);

        $currentStageName = !empty($rawLogs) ? end($rawLogs)['stage'] : 'Not Started';
        $nextAllowedStageName = 'Completed';

        foreach ($wipStagesPipeline as $idx => $stg) {
            $stgClean = $this->toStageKey($stg);
            if (!in_array($stgClean, $completedStageKeys)) {
                $nextAllowedStageName = $stg;
                break;
            }
        }

        $productPayload = [
            'qr_code' => $qrCode,
            'batch_no' => !empty($batchData['production_no']) ? $batchData['production_no'] : $batchNo,
            'style_no' => !empty($batchData['style_no']) ? $batchData['style_no'] : 'ST-MASTER',
            'style_name' => !empty($batchData['style_name']) ? $batchData['style_name'] : 'Garment Style Item',
            'category' => !empty($batchData['category_name']) ? $batchData['category_name'] : 'Apparel',
            'brand' => !empty($batchData['brand_name']) ? $batchData['brand_name'] : 'Tocco',
            'composition' => !empty($batchData['fabric_composition']) ? $batchData['fabric_composition'] : '100% Premium Cotton',
            'fit_type' => !empty($batchData['fit_type']) ? $batchData['fit_type'] : 'Regular',
            'size' => $size,
            'serial' => $serial,
            'target_qty' => (int)(!empty($batchData['po_target_qty']) ? $batchData['po_target_qty'] : (!empty($batchData['target_qty']) ? $batchData['target_qty'] : 100)),
            'buyer' => !empty($batchData['buyer_name']) ? $batchData['buyer_name'] : 'Internal'
        ];

        $history = array_map(function($l) {
            return [
                'id' => (int)$l['id'],
                'stage' => $l['stage'],
                'status' => ($l['qty_out'] > 0) ? 'pass' : 'fail',
                'operator' => $l['operator_name'] ?? 'Operator',
                'role' => $l['operator_role'] ?? 'Floor Operator',
                'created_at' => $l['created_at']
            ];
        }, $rawLogs);

        $response->json([
            'status' => 'success',
            'already_validated' => $isAlreadyScannedInTargetStage,
            'already_scanned' => $isAlreadyScannedInTargetStage,
            'data' => [
                'qr_code' => $qrCode,
                'already_scanned_in_target_stage' => $isAlreadyScannedInTargetStage,
                'current_stage' => ['name' => $currentStageName],
                'next_allowed_stage' => ['name' => $nextAllowedStageName],
                'pipeline' => $wipStagesPipeline,
                'product' => $productPayload,
                'history' => $history
            ],
            'product' => $productPayload
        ], 200);
    }



    /**
     * Get All Garment Styles with their associated WIP Stages
     * GET /api/v1/styles
     */
    public function getStyles(Request $request, Response $response): void {
        $this->setCorsHeaders();

        $token = $this->extractToken($request);
        $userData = $token ? $this->verifyApiToken($token) : null;

        if (!$userData || empty($userData['company_id'])) {
            $response->json([
                'status' => 'error',
                'message' => 'Unauthorized or missing company context.'
            ], 401);
            return;
        }

        $companyId = (int)$userData['company_id'];
        $db = Database::getInstance();

        $stmtStyles = $db->prepare("
            SELECT s.*, tp.stages_json, tp.bom_json
            FROM styles s
            LEFT JOIN tech_packs tp ON tp.style_id = s.id AND tp.deleted_at IS NULL
            WHERE s.company_id = ? AND s.deleted_at IS NULL
            ORDER BY s.id DESC
        ");
        $stmtStyles->execute([$companyId]);
        $styles = $stmtStyles->fetchAll() ?: [];

        $companyDefaultStages = \App\Controllers\CompanyController::getCompanyWipStages($companyId);

        $result = array_map(function($s) use ($companyDefaultStages) {
            $customStages = !empty($s['stages_json']) ? (json_decode($s['stages_json'], true) ?: []) : [];
            $wipStages = !empty($customStages) ? $customStages : $companyDefaultStages;

            return [
                'id' => (int)$s['id'],
                'style_no' => $s['style_no'],
                'name' => $s['name'],
                'description' => $s['description'],
                'category' => $s['category'] ?? 'unisex',
                'composition' => $s['composition'],
                'gsm' => $s['gsm'] ?? null,
                'color' => $s['color'] ?? null,
                'brand' => $s['brand'],
                'size_range' => $s['size_range'],
                'wip_stages' => $wipStages,
                'created_at' => $s['created_at']
            ];
        }, $styles);

        $response->json([
            'status' => 'success',
            'data' => $result
        ], 200);
    }

    /**
     * Get Specific Garment Style Details with Tech Pack & Associated WIP Stages
     * GET /api/v1/styles/{id}
     */
    public function getStyleDetails(Request $request, Response $response, string $id = ''): void {
        $this->setCorsHeaders();

        $token = $this->extractToken($request);
        $userData = $token ? $this->verifyApiToken($token) : null;

        if (!$userData || empty($userData['company_id'])) {
            $response->json([
                'status' => 'error',
                'message' => 'Unauthorized or missing company context.'
            ], 401);
            return;
        }

        $companyId = (int)$userData['company_id'];
        $styleIdentifier = trim($id ?: (string)$request->get('id', '') ?: (string)$request->get('style_id', '') ?: (string)$request->get('style_no', ''));

        if (empty($styleIdentifier)) {
            $response->json([
                'status' => 'error',
                'message' => 'Style ID or Style Number is required.'
            ], 400);
            return;
        }

        $db = Database::getInstance();
        $stmtStyle = $db->prepare("
            SELECT s.*
            FROM styles s
            WHERE (s.id = ? OR s.style_no = ?) AND s.company_id = ? AND s.deleted_at IS NULL
            LIMIT 1
        ");
        $stmtStyle->execute([is_numeric($styleIdentifier) ? (int)$styleIdentifier : 0, $styleIdentifier, $companyId]);
        $style = $stmtStyle->fetch();

        if (!$style) {
            $response->json([
                'status' => 'error',
                'message' => 'Style not found.'
            ], 404);
            return;
        }

        // Fetch TechPack details
        $stmtTp = $db->prepare("SELECT * FROM tech_packs WHERE style_id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1");
        $stmtTp->execute([$style['id'], $companyId]);
        $techpack = $stmtTp->fetch() ?: [];

        $companyDefaultStages = \App\Controllers\CompanyController::getCompanyWipStages($companyId);
        $customStages = !empty($techpack['stages_json']) ? (json_decode($techpack['stages_json'], true) ?: []) : [];
        $wipStages = !empty($customStages) ? $customStages : $companyDefaultStages;

        // Fetch Active Production Orders using this style
        $stmtBatches = $db->prepare("
            SELECT pro.id, pro.production_no, pro.status, po.po_no, c.name as buyer_name
            FROM production_orders pro
            JOIN buyer_pos po ON pro.po_id = po.id
            JOIN contacts c ON po.buyer_id = c.id
            WHERE po.style_id = ? AND pro.company_id = ? AND pro.deleted_at IS NULL
            ORDER BY pro.id DESC
        ");
        $stmtBatches->execute([$style['id'], $companyId]);
        $batches = $stmtBatches->fetchAll() ?: [];

        $response->json([
            'status' => 'success',
            'data' => [
                'style' => [
                    'id' => (int)$style['id'],
                    'style_no' => $style['style_no'],
                    'name' => $style['name'],
                    'description' => $style['description'],
                    'category' => $style['category'] ?? 'unisex',
                    'composition' => $style['composition'],
                    'gsm' => $style['gsm'] ?? null,
                    'color' => $style['color'] ?? null,
                    'brand' => $style['brand'],
                    'size_range' => $style['size_range'],
                    'created_at' => $style['created_at']
                ],
                'wip_stages' => $wipStages,
                'techpack' => [
                    'bom_list' => !empty($techpack['bom_json']) ? (json_decode($techpack['bom_json'], true) ?: []) : [],
                    'sizing_sheet' => !empty($techpack['sizing_json']) ? (json_decode($techpack['sizing_json'], true) ?: []) : [],
                    'printing_specs' => $techpack['printing_specs'] ?? null,
                    'embroidery_specs' => $techpack['embroidery_specs'] ?? null,
                    'packing_specs' => $techpack['packing_specs'] ?? null
                ],
                'active_batches' => array_map(function($b) {
                    return [
                        'id' => (int)$b['id'],
                        'production_no' => $b['production_no'],
                        'po_no' => $b['po_no'],
                        'buyer_name' => $b['buyer_name'],
                        'status' => $b['status']
                    ];
                }, $batches)
            ]
        ], 200);
    }

    /**
     * Helper: Generate a signed token string
     */
    private function generateApiToken(array $user): string {
        $secretKey = defined('APP_KEY') ? APP_KEY : 'wearable_erp_secret_key_2026';
        $payload = [
            'user_id' => (int)$user['id'],
            'company_id' => !empty($user['company_id']) ? (int)$user['company_id'] : null,
            'email' => $user['email'] ?? '',
            'employee_code' => $user['employee_code'] ?? '',
            'exp' => time() + (86400 * 30) // Valid for 30 days
        ];
        $jsonPayload = json_encode($payload);
        $encodedPayload = base64_encode($jsonPayload);
        $signature = hash_hmac('sha256', $encodedPayload, $secretKey);

        return $encodedPayload . '.' . $signature;
    }

    /**
     * Helper: Verify and decode a token string
     */
    private function verifyApiToken(string $token): ?array {
        $secretKey = defined('APP_KEY') ? APP_KEY : 'wearable_erp_secret_key_2026';
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        list($encodedPayload, $providedSignature) = $parts;
        $expectedSignature = hash_hmac('sha256', $encodedPayload, $secretKey);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        $decodedJson = base64_decode($encodedPayload);
        $payload = json_decode($decodedJson, true);

        if (!$payload || !isset($payload['user_id'], $payload['exp'])) {
            return null;
        }

        if (time() > $payload['exp']) {
            return null; // Expired token
        }

        return $payload;
    }



    /**
     * Helper: Extract Bearer token from header or body
     */
    private function extractToken(Request $request): ?string {
        // Check Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // Fallback to request body or GET parameter
        return (string)$request->get('token', '') ?: null;
    }

    /**
     * Set standard CORS headers for Flutter mobile app requests
     */
    private function setCorsHeaders(): void {
        if (ob_get_length()) {
            @ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Tenant-ID');

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
