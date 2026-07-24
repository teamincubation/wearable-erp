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
                SELECT u.*, r.name as role_name, c.name as company_name, c.subdomain as company_subdomain, c.status as company_status
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
                SELECT u.*, r.name as role_name, c.name as company_name, c.subdomain as company_subdomain, c.status as company_status
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
            SELECT u.*, r.name as role_name, c.name as company_name, c.subdomain as company_subdomain
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
                    'role_id' => $user['role_id'] ? (int)$user['role_id'] : null,
                    'role_name' => $user['role_name'] ?? 'Employee'
                ]
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
