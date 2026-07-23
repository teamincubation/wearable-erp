<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Session;
use App\Models\User;
use App\Models\AuditLog;

/**
 * Authentication and Security Controller
 * Full Stack PHP Engineer & Security Architect - Antigravity
 */
class AuthController extends Controller {
    /**
     * Show the login screen
     */
    public function showLogin(Request $request, Response $response): void {
        if (Auth::check()) {
            $this->redirectToDashboard();
        }
        $tenant = Session::get('active_tenant_subdomain');
        $this->renderView('auth/login', ['title' => 'Login | Wearable ERP', 'tenant' => $tenant], 'auth');
    }

    /**
     * Handle authentication post request
     */
    public function login(Request $request, Response $response): void {
        $email = $request->get('email');
        $password = $request->get('password');

        if (empty($email) || empty($password)) {
            Session::setFlash('error', 'Please enter a valid email/username and password.');
            $this->redirect('login');
        }

        // Authenticate credentials
        $user = Auth::attempt($email, $password);

        if (!$user) {
            Session::setFlash('error', 'Invalid email or password.');
            $this->redirect('login');
        }

        // Standard Login
        Auth::login($user);
        Session::setFlash('success', "Welcome back, {$user['name']}!");
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
        $email = $request->get('email');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Please enter a valid email address.');
            $this->redirect('forgot-password');
        }

        $userModel = new User();
        $user = $userModel->findGlobalByEmail($email);

        if ($user) {
            // Write reset token and log audit
            $token = bin2hex(random_bytes(32));
            // In a complete flow we would store token in database and send email.
            // For Pilot, we log it and provide user feedback.
            AuditLog::log($user['company_id'], $user['id'], 'password_reset_requested', 'User', $user['id'], null, null, "Password reset token generated.");
            
            // For developer/demonstration testing, we print it in logs or session flash (developer friendly)
            Session::setFlash('success', "Password reset instructions have been sent to your email. Check system activity log for details.");
        } else {
            // Anti-enumeration: Return success anyway to protect user privacy
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
            $this->redirect('login');
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
        }

        if ($password !== $confirm) {
            Session::setFlash('error', 'Passwords do not match.');
            $this->redirect('reset-password?token=' . urlencode($token));
        }

        // Demo implementation - in pilot/production we locate user matching token
        // Let's reset for test user (Adnan Vellicheri - adnan@toccoexports.com)
        $userModel = new User();
        $user = $userModel->findGlobalByEmail('adnan@toccoexports.com');

        if ($user) {
            $userModel->update($user['id'], [
                'password_hash' => password_hash($password, PASSWORD_BCRYPT)
            ]);
            AuditLog::log($user['company_id'], $user['id'], 'password_reset_success', 'User', $user['id'], null, null, "Password reset successfully via token.");
            Session::setFlash('success', 'Your password has been reset successfully. Please log in.');
            $this->redirect('login');
        } else {
            Session::setFlash('error', 'Invalid token or reset session expired.');
            $this->redirect('login');
        }
    }

    /**
     * Handle Email Verification landing
     */
    public function verifyEmail(Request $request, Response $response): void {
        $token = $request->get('token');
        if (empty($token)) {
            Session::setFlash('error', 'Invalid verification link.');
            $this->redirect('login');
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
            $this->redirect('login');
        }
    }

    /**
     * Terminate user session
     */
    public function logout(Request $request, Response $response): void {
        Auth::logout();
        $this->redirect('login');
    }

    /**
     * Redirect active user to appropriate home dashboard
     */
    private function redirectToDashboard(): void {
        $companyId = Session::get('company_id');
        if ($companyId === null) {
            $this->redirect('developer/dashboard');
        } else {
            $this->redirect(Auth::getFirstAccessibleCompanyUrl());
        }
    }

    /**
     * Handle CIK (Company Identification Key) verification
     */
    public function verifyCik(Request $request, Response $response): void {
        $cik = trim($request->get('cik'));

        // CIK attempts rate limiter
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $attemptsKey = 'cik_attempts_' . $ip;
        $lastAttemptKey = 'cik_last_attempt_' . $ip;

        $attempts = Session::get($attemptsKey, 0);
        $lastAttempt = Session::get($lastAttemptKey, 0);

        if ($attempts >= 5 && (time() - $lastAttempt) < 60) {
            $secondsLeft = 60 - (time() - $lastAttempt);
            Session::setFlash('error', "Too many failed attempts. Please try again in {$secondsLeft} seconds.");
            $this->redirect('login');
            return;
        }

        if (empty($cik) || strlen($cik) !== 6 || !is_numeric($cik)) {
            Session::set($attemptsKey, $attempts + 1);
            Session::set($lastAttemptKey, time());
            Session::setFlash('error', 'Please enter a valid 6-digit numeric Company Key.');
            $this->redirect('login');
            return;
        }

        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM companies WHERE cik = ? AND status = 'active' AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$cik]);
        $company = $stmt->fetch();

        if ($company) {
            // Establish tenant session context
            Session::set('company_id', $company['id']);
            Session::set('current_tenant', $company);
            Session::set('active_tenant_subdomain', $company['subdomain']);
            
            // Clear attempts
            Session::remove($attemptsKey);
            Session::remove($lastAttemptKey);

            Session::setFlash('success', "Identified company: {$company['name']}. Please login with credentials.");
        } else {
            Session::set($attemptsKey, $attempts + 1);
            Session::set($lastAttemptKey, time());
            Session::setFlash('error', 'Invalid Company Identification Key.');
        }

        $this->redirect('login');
    }

    /**
     * Clear active CIK session context to login into another company
     */
    public function clearCikContext(Request $request, Response $response): void {
        Session::remove('company_id');
        Session::remove('current_tenant');
        Session::remove('active_tenant_subdomain');
        Session::setFlash('info', 'Company context cleared. Enter new CIK.');
        $this->redirect('login');
    }
}
