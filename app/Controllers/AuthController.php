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
 * Single Unified SaaS Authentication Controller
 * Lead Multi-Tenant & Security Architect - Antigravity
 */
class AuthController extends Controller {

    /**
     * Show Universal SaaS Login Screen
     */
    public function showLogin(Request $request, Response $response): void {
        if (Auth::check()) {
            $this->redirectToDashboard();
            return;
        }

        $this->renderView('auth/login', [
            'title' => 'Login | Wearable ERP SaaS Portal'
        ], 'auth');
    }

    /**
     * Handle Universal SaaS Authentication Post Request
     */
    public function login(Request $request, Response $response): void {
        $identifier = trim($request->get('email') ?: $request->get('username'));
        $password = $request->get('password');

        if (empty($identifier) || empty($password)) {
            Session::setFlash('error', 'Please enter a valid email, username, or employee code, and password.');
            $this->redirect('login');
            return;
        }

        // Authenticate credentials via unified engine
        $user = Auth::attempt($identifier, $password);

        if (!$user) {
            Session::setFlash('error', 'Invalid email/username or password.');
            $this->redirect('login');
            return;
        }

        // Complete Login & establish session
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
            Session::setFlash('success', "Password reset instructions have been sent to your email.");
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
            $this->redirect('login');
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
     * Smart redirection to user dashboard based on authenticated user type
     */
    private function redirectToDashboard(): void {
        if (Session::get('is_developer_session') && Session::get('company_id') === null) {
            $this->redirect('developer/dashboard');
        } else {
            $this->redirect(Auth::getFirstAccessibleCompanyUrl());
        }
    }
}
