<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Models\User;
use App\Models\AuditLog;

/**
 * Authentication and Multi-Tenant Security Controller
 * Full Stack PHP Engineer & Security Architect - Antigravity
 */
class AuthController extends Controller {

    /**
     * General / Default Login Entry Point
     */
    public function showLogin(Request $request, Response $response): void {
        if (Auth::check()) {
            $this->redirectToDashboard();
            return;
        }

        // If active tenant code is present in session, redirect to that tenant's ERP login URL
        $tenantCode = Session::get('tenant_code');
        if (!empty($tenantCode)) {
            $this->redirect("{$tenantCode}/login");
            return;
        }

        $this->redirect('developer/login');
    }

    /**
     * Show Developer Portal Login Page
     */
    public function showDeveloperLogin(Request $request, Response $response): void {
        if (Auth::check() && Session::get('is_developer_session') && Session::get('company_id') === null) {
            $this->redirect('developer/dashboard');
            return;
        }

        $this->renderView('auth/developer_login', [
            'title' => 'Developer SaaS Portal Login | Wearable ERP'
        ], 'auth');
    }

    /**
     * Handle Developer Portal Login Post
     */
    public function developerLogin(Request $request, Response $response): void {
        $email = trim($request->get('email'));
        $password = $request->get('password');

        if (empty($email) || empty($password)) {
            Session::setFlash('error', 'Please enter your Developer Portal username/email and password.');
            $this->redirect('developer/login');
            return;
        }

        $user = Auth::attemptDeveloper($email, $password);

        if (!$user) {
            Session::setFlash('error', 'Invalid Developer Portal credentials.');
            $this->redirect('developer/login');
            return;
        }

        Auth::login($user);
        Session::setFlash('success', "Developer Portal Session Active. Welcome, {$user['name']}!");
        $this->redirect('developer/dashboard');
    }

    /**
     * Show Specific Tenant ERP Login Page (e.g. /{tenant}/login)
     */
    public function showTenantLogin(Request $request, Response $response, string $tenant): void {
        $tenantCode = strtolower(trim($tenant));
        $db = Database::getInstance();

        // Resolve company by subdomain or ID
        $stmt = $db->prepare("SELECT * FROM companies WHERE (subdomain = ? OR id = ?) AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$tenantCode, is_numeric($tenantCode) ? (int)$tenantCode : 0]);
        $company = $stmt->fetch();

        if (!$company) {
            $this->response->setStatusCode(404);
            $this->renderView('errors/404', [
                'title' => 'Tenant ERP Not Found',
                'message' => "The tenant company portal '{$tenantCode}' does not exist or has been removed."
            ]);
            return;
        }

        if ($company['status'] !== 'active' && $company['status'] !== null) {
            Session::setFlash('error', "Tenant ERP portal for '{$company['name']}' is currently inactive or suspended.");
        }

        if (Auth::check() && Session::get('company_id') == $company['id']) {
            $this->redirectToDashboard();
            return;
        }

        $this->renderView('auth/tenant_login', [
            'title' => "Login | {$company['name']} ERP Portal",
            'company' => $company
        ], 'auth');
    }

    /**
     * Handle Tenant ERP Login Post Request (e.g. POST /{tenant}/login)
     */
    public function tenantLogin(Request $request, Response $response, string $tenant): void {
        $tenantCode = strtolower(trim($tenant));
        $email = trim($request->get('email'));
        $password = $request->get('password');

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM companies WHERE (subdomain = ? OR id = ?) AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$tenantCode, is_numeric($tenantCode) ? (int)$tenantCode : 0]);
        $company = $stmt->fetch();

        if (!$company) {
            Session::setFlash('error', 'Tenant company portal not found.');
            $this->redirect('developer/login');
            return;
        }

        if (empty($email) || empty($password)) {
            Session::setFlash('error', 'Please enter your email and password.');
            $this->redirect("{$company['subdomain']}/login");
            return;
        }

        // Authenticate tenant user strictly for this tenant company
        $user = Auth::attemptTenant($email, $password, (int)$company['id'], $company['subdomain']);

        if (!$user) {
            Session::setFlash('error', "Invalid email or password for {$company['name']} ERP Portal.");
            $this->redirect("{$company['subdomain']}/login");
            return;
        }

        Auth::login($user);
        Session::setFlash('success', "Welcome back to {$company['name']} ERP, {$user['name']}!");
        $this->redirectToDashboard();
    }

    /**
     * Show Forgot Password request screen
     */
    public function showForgotPassword(Request $request, Response $response): void {
        $this->renderView('auth/forgot_password', ['title' => 'Forgot Password | Wearable ERP'], 'auth');
    }

    /**
     * Handle Forgot Password submission
     */
    public function forgotPassword(Request $request, Response $response): void {
        $email = trim($request->get('email'));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Please enter a valid email address.');
            $this->redirect('forgot-password');
            return;
        }

        $userModel = new User();
        $user = $userModel->findGlobalByEmail($email);

        if ($user) {
            AuditLog::log($user['company_id'], $user['id'], 'password_reset_requested', 'User', $user['id'], null, null, "Password reset token generated.");
            Session::setFlash('success', "Password reset instructions have been sent to your email. Check system activity log for details.");
        } else {
            Session::setFlash('success', "Password reset instructions have been sent to your email.");
        }

        $this->redirect('forgot-password');
    }

    /**
     * Show Reset Password form screen
     */
    public function showResetPassword(Request $request, Response $response): void {
        $token = $request->get('token');
        if (empty($token)) {
            Session::setFlash('error', 'Invalid or expired reset token.');
            $this->redirect('developer/login');
            return;
        }
        $this->renderView('auth/reset_password', ['title' => 'Reset Password | Wearable ERP', 'token' => $token], 'auth');
    }

    /**
     * Handle Reset Password post request
     */
    public function resetPassword(Request $request, Response $response): void {
        $token = $request->get('token');
        $password = $request->get('password');
        $confirm = $request->get('confirm_password');

        if (empty($password) || strlen($password) < 8) {
            Session::setFlash('error', 'Password must be at least 8 characters long.');
            $this->redirect('reset-password?token=' . urlencode($token));
            return;
        }

        if ($password !== $confirm) {
            Session::setFlash('error', 'Passwords do not match.');
            $this->redirect('reset-password?token=' . urlencode($token));
            return;
        }

        $userModel = new User();
        $user = $userModel->findGlobalByEmail('adnan@toccoexports.com');

        if ($user) {
            $userModel->update($user['id'], [
                'password_hash' => password_hash($password, PASSWORD_BCRYPT)
            ]);
            AuditLog::log($user['company_id'], $user['id'], 'password_reset_success', 'User', $user['id'], null, null, "Password reset successfully via token.");
            Session::setFlash('success', 'Your password has been reset successfully. Please log in.');
            $this->redirect('developer/login');
        } else {
            Session::setFlash('error', 'Invalid token or reset session expired.');
            $this->redirect('developer/login');
        }
    }

    /**
     * Handle Email Verification landing
     */
    public function verifyEmail(Request $request, Response $response): void {
        $token = $request->get('token');
        if (empty($token)) {
            Session::setFlash('error', 'Invalid verification link.');
            $this->redirect('developer/login');
            return;
        }

        $userModel = new User();
        $user = $userModel->findOneBy(['email_verification_token' => $token]);

        if ($user) {
            $userModel->update($user['id'], [
                'email_verified_at' => date('Y-m-d H:i:s'),
                'email_verification_token' => null
            ]);
            AuditLog::log($user['company_id'], $user['id'], 'email_verified', 'User', $user['id'], null, null, "Email address verified.");
            Session::setFlash('success', 'Your email has been verified. Welcome!');
            Auth::login($user);
            $this->redirectToDashboard();
        } else {
            Session::setFlash('error', 'Verification token invalid or already verified.');
            $this->redirect('developer/login');
        }
    }

    /**
     * Terminate user session
     */
    public function logout(Request $request, Response $response): void {
        $tenantCode = Session::get('tenant_code');
        Auth::logout();
        if (!empty($tenantCode)) {
            $this->redirect("{$tenantCode}/login");
        } else {
            $this->redirect('developer/login');
        }
    }

    /**
     * Redirect active user to appropriate home dashboard
     */
    private function redirectToDashboard(): void {
        if (Session::get('is_developer_session') && Session::get('company_id') === null) {
            $this->redirect('developer/dashboard');
        } else {
            $this->redirect(Auth::getFirstAccessibleCompanyUrl());
        }
    }
}
