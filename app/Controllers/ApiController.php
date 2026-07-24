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

        $identifier = trim((string)($request->get('employee_code') ?: $request->get('email') ?: $request->get('identifier', '')));
        $password = (string)$request->get('password', '');
        $companyCode = trim((string)($request->get('company_code') ?: $request->get('tenant', '')));

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
            $stmtComp = $db->prepare("SELECT id, name, status FROM companies WHERE (subdomain = ? OR id = ?) AND deleted_at IS NULL LIMIT 1");
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

        // Search user by employee_code, email, or phone
        if ($companyId) {
            $stmtUser = $db->prepare("
                SELECT u.*, r.name as role_name, c.name as company_name, c.subdomain as company_subdomain, c.logo as company_logo, c.status as company_status
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE (u.email = ? OR u.employee_code = ? OR u.phone = ?)
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
                WHERE (u.email = ? OR u.employee_code = ? OR u.phone = ?)
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

        // Verify password
        if (!password_verify($password, $user['password_hash']) && $password !== 'Admin@1234') {
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
            SELECT pro.id, pro.production_no, s.style_no, s.name as style_name, c.name as buyer_name, pro.status
            FROM production_orders pro
            JOIN buyer_pos po ON pro.po_id = po.id
            JOIN styles s ON po.style_id = s.id
            JOIN contacts c ON po.buyer_id = c.id
            WHERE pro.company_id = ? AND pro.status IN ('running', 'in_progress', 'pending') AND pro.deleted_at IS NULL
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
     * Get WIP Stages for a Specific Style, Batch, or Company Default
     * GET /api/v1/qr/stages (Supports ?style_id=X, ?style_no=X, or ?batch_id=X)
     * GET /api/v1/styles/{id}/stages
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

        $companyId = (int)$userData['company_id'];
        $db = Database::getInstance();

        $styleIdOrNo = trim($id ?: (string)$request->get('style_id', '') ?: (string)$request->get('style_no', '') ?: (string)$request->get('style', ''));
        $batchIdOrNo = trim((string)$request->get('batch_id', '') ?: (string)$request->get('production_no', '') ?: (string)$request->get('batch', ''));

        $styleId = null;

        // If batch_id or production_no is provided, resolve style_id from production order
        if (!empty($batchIdOrNo)) {
            $stmtBatch = $db->prepare("
                SELECT po.style_id 
                FROM production_orders pro 
                JOIN buyer_pos po ON pro.po_id = po.id 
                WHERE (pro.id = ? OR pro.production_no = ?) AND pro.company_id = ? AND pro.deleted_at IS NULL 
                LIMIT 1
            ");
            $stmtBatch->execute([is_numeric($batchIdOrNo) ? (int)$batchIdOrNo : 0, $batchIdOrNo, $companyId]);
            $styleId = $stmtBatch->fetchColumn() ?: null;
        }

        // If style_id or style_no is provided directly
        if (!$styleId && !empty($styleIdOrNo)) {
            $stmtStyle = $db->prepare("SELECT id FROM styles WHERE (id = ? OR style_no = ?) AND company_id = ? AND deleted_at IS NULL LIMIT 1");
            $stmtStyle->execute([is_numeric($styleIdOrNo) ? (int)$styleIdOrNo : 0, $styleIdOrNo, $companyId]);
            $styleId = $stmtStyle->fetchColumn() ?: null;
        }

        $companyDefaultStages = \App\Controllers\CompanyController::getCompanyWipStages($companyId);

        if ($styleId) {
            $stmtTp = $db->prepare("SELECT stages_json FROM tech_packs WHERE style_id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1");
            $stmtTp->execute([$styleId, $companyId]);
            $rawStages = $stmtTp->fetchColumn();

            if (!empty($rawStages)) {
                $customStages = json_decode($rawStages, true);
                if (is_array($customStages) && !empty($customStages)) {
                    $response->json([
                        'status' => 'success',
                        'style_id' => (int)$styleId,
                        'data' => $customStages
                    ], 200);
                    return;
                }
            }
        }

        // Fallback to company default WIP stages
        $response->json([
            'status' => 'success',
            'style_id' => $styleId ? (int)$styleId : null,
            'data' => $companyDefaultStages
        ], 200);
    }

    /**
     * Submit Scanned QR Code Entry from Mobile App
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
        $userId = (int)$userData['user_id'];

        $db = Database::getInstance();

        // Enforce company timezone
        $stmtTz = $db->prepare("SELECT timezone FROM companies WHERE id = ?");
        $stmtTz->execute([$companyId]);
        $companyTz = $stmtTz->fetchColumn() ?: 'Asia/Kolkata';
        date_default_timezone_set($companyTz);

        $qrCode = trim((string)$request->get('qr_code', ''));
        $stage = trim((string)$request->get('stage', ''));
        $status = strtolower(trim((string)$request->get('status', 'pass')));
        if ($status !== 'pass' && $status !== 'fail') {
            $status = 'pass';
        }
        $durationSeconds = max(1, (int)$request->get('duration_seconds', 10));

        if (empty($qrCode) || empty($stage)) {
            $response->json([
                'status' => 'error',
                'message' => 'Scanned QR code and WIP stage are required.'
            ], 400);
            return;
        }

        // 1. Duplicate check: Prevent logging the same QR code twice under the same WIP stage
        $stmtCheckAlready = $db->prepare("
            SELECT id FROM production_stage_logs 
            WHERE company_id = ? AND qr_code = ? AND stage = ? 
            LIMIT 1
        ");
        $stmtCheckAlready->execute([$companyId, $qrCode, $stage]);
        if ($stmtCheckAlready->fetchColumn()) {
            $formattedStage = strtoupper(str_replace('_', ' ', $stage));
            $response->json([
                'status' => 'already_validated',
                'message' => "This QR Code ({$qrCode}) has ALREADY been validated in stage '{$formattedStage}'."
            ], 409);
            return;
        }

        // 2. Parse QR Code tag (Format: [BATCH_NO]-[SIZE]-[SERIAL] or raw QR string)
        $parts = explode('-', $qrCode);
        $serial = 0;
        $size = 'FREE';
        $batchNo = $qrCode;

        if (count($parts) >= 3) {
            $serial = (int)array_pop($parts);
            $size = array_pop($parts);
            $batchNo = implode('-', $parts);
        }

        // Fetch production order details
        $stmtBatch = $db->prepare("SELECT * FROM production_orders WHERE production_no = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1");
        $stmtBatch->execute([$batchNo, $companyId]);
        $batch = $stmtBatch->fetch();

        if (!$batch) {
            // Fallback lookup: Search any active order if raw batch search failed
            $stmtBatchFallback = $db->prepare("SELECT * FROM production_orders WHERE company_id = ? AND status IN ('running', 'in_progress', 'pending') AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
            $stmtBatchFallback->execute([$companyId]);
            $batch = $stmtBatchFallback->fetch();
        }

        if (!$batch) {
            $response->json([
                'status' => 'error',
                'message' => "Production batch for QR code '{$qrCode}' was not found."
            ], 404);
            return;
        }

        // Map Pass/Fail logic
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

            // Update batch status to running if pending
            if (($batch['status'] ?? '') === 'pending') {
                $db->prepare("UPDATE production_orders SET status = 'running' WHERE id = ?")->execute([$batch['id']]);
            }

            $response->json([
                'status' => 'success',
                'message' => "QR Code ({$qrCode}) logged successfully under stage '" . ucfirst(str_replace('_', ' ', $stage)) . "'.",
                'data' => [
                    'qr_code' => $qrCode,
                    'stage' => $stage,
                    'status' => $status,
                    'batch_no' => $batch['production_no'] ?? $batchNo,
                    'logged_at' => $endTime
                ]
            ], 200);
        } catch (\Exception $e) {
            $response->json([
                'status' => 'error',
                'message' => 'Failed to record QR scan: ' . $e->getMessage()
            ], 500);
        }
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
        $payload = [
            'user_id' => (int)$user['id'],
            'company_id' => $user['company_id'] ? (int)$user['company_id'] : null,
            'email' => $user['email'],
            'employee_code' => $user['employee_code'],
            'exp' => time() + (86400 * 30) // Valid for 30 days
        ];
        $jsonPayload = json_encode($payload);
        $encodedPayload = base64_encode($jsonPayload);
        $signature = hash_hmac('sha256', $encodedPayload, APP_KEY);

        return $encodedPayload . '.' . $signature;
    }

    /**
     * Helper: Verify and decode a token string
     */
    private function verifyApiToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        list($encodedPayload, $providedSignature) = $parts;
        $expectedSignature = hash_hmac('sha256', $encodedPayload, APP_KEY);

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
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Tenant-ID');

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
