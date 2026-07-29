<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;

/**
 * Dedicated REST API Authentication Controller
 * Provides clean JSON responses without HTTP 302 redirects.
 */
class ApiAuthController extends Controller {

    /**
     * POST /api/login
     * Authenticate mobile app users and return a JSON payload with status & token.
     */
    public function login(Request $request, Response $response): void {
        // Read raw JSON body if available
        $rawInput = file_get_contents('php://input');
        $jsonBody = json_decode($rawInput, true);
        if (!is_array($jsonBody)) {
            $jsonBody = [];
        }

        $identifier = trim(
            $request->get('email') ?:
            ($request->get('username') ?:
            ($jsonBody['email'] ?? ($jsonBody['username'] ?? '')))
        );
        $password = $request->get('password') ?: ($jsonBody['password'] ?? '');

        if (empty($identifier) || empty($password)) {
            $response->json([
                'success' => false,
                'error' => 'Please enter a valid email, username, and password.',
                'message' => 'Please enter a valid email, username, and password.'
            ], 400);
            return;
        }

        // Authenticate credentials via core Auth engine
        $user = Auth::attempt($identifier, $password);

        if (!$user) {
            $response->json([
                'success' => false,
                'error' => 'Invalid email/username or password.',
                'message' => 'Invalid email/username or password.'
            ], 401);
            return;
        }

        // Generate authorization token for mobile API session
        $token = bin2hex(random_bytes(32));

        $companyName = '';
        $companyLogo = '';
        if (!empty($user['company_id'])) {
            try {
                $db = \App\Core\Database::getInstance();
                $stmt = $db->prepare("SELECT name, logo, subdomain FROM companies WHERE id = ? LIMIT 1");
                $stmt->execute([(int)$user['company_id']]);
                $companyData = $stmt->fetch();
                if ($companyData) {
                    $companyName = $companyData['name'] ?? '';
                    if (!empty($companyData['logo'])) {
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                        $host = !empty($companyData['subdomain']) && defined('ROOT_DOMAIN') 
                            ? $companyData['subdomain'] . '.' . ROOT_DOMAIN 
                            : ($_SERVER['HTTP_HOST'] ?? 'localhost:8000');
                        $companyLogo = preg_match('/^https?:\/\//', $companyData['logo']) 
                            ? $companyData['logo'] 
                            : $protocol . $host . '/' . ltrim($companyData['logo'], '/');
                    }
                }
            } catch (\Exception $e) {
                // Ignore DB errors for missing table/columns
            }
        }

        $response->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'access_token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'name' => $user['name'] ?? '',
                'email' => $user['email'] ?? '',
                'role' => $user['role_id'] ?? 'user',
                'company_id' => $user['company_id'] ?? null,
                'subdomain' => $user['tenant_subdomain'] ?? null,
                'company_name' => $companyName,
                'company_logo' => $companyLogo,
            ]
        ], 200);
    }

    /**
     * GET /api/company
     * Fetch company details using X-Tenant-ID
     */
    public function getCompany(Request $request, Response $response): void {
        $companyId = $request->server('HTTP_X_TENANT_ID') ?? $request->get('company_id');
        
        if (empty($companyId)) {
            $response->json(['success' => false, 'error' => 'No company ID provided'], 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("SELECT name, logo, subdomain FROM companies WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$companyId]);
            $companyData = $stmt->fetch();
            
            if ($companyData) {
                $logoUrl = '';
                if (!empty($companyData['logo'])) {
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                    $host = !empty($companyData['subdomain']) && defined('ROOT_DOMAIN') 
                        ? $companyData['subdomain'] . '.' . ROOT_DOMAIN 
                        : ($_SERVER['HTTP_HOST'] ?? 'localhost:8000');
                    // Prepend base url if it's a relative path
                    $logoUrl = preg_match('/^https?:\/\//', $companyData['logo']) 
                        ? $companyData['logo'] 
                        : $protocol . $host . '/' . ltrim($companyData['logo'], '/');
                }

                $response->json([
                    'success' => true,
                    'company_name' => $companyData['name'] ?? '',
                    'company_logo' => $logoUrl
                ], 200);
            } else {
                $response->json(['success' => false, 'error' => 'Company not found'], 404);
            }
        } catch (\Exception $e) {
            $response->json(['success' => false, 'error' => 'Database error'], 500);
        }
    }
}
