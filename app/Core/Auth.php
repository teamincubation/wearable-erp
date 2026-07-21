<?php
namespace App\Core;

use App\Models\User;
use App\Models\AuditLog;
use Exception;

/**
 * Authentication and Authorization Manager
 * Full Stack PHP Engineer & Security Architect - Antigravity
 */
class Auth {
    /**
     * Authenticate a user by email and password
     */
    public static function attempt(string $email, string $password): ?array {
        $userModel = new User();
        $user = $userModel->findGlobalByEmail(trim($email));

        if (!$user) {
            self::logAuthActivity(null, 'login_failed_email', "Failed login attempt for email: {$email}");
            return null;
        }

        // Check if user status is suspended or inactive
        if ($user['status'] !== 'active') {
            self::logAuthActivity($user['id'], 'login_blocked', "Login blocked for user status: {$user['status']}", $user['company_id']);
            return null;
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            self::logAuthActivity($user['id'], 'login_failed_password', "Failed login attempt (wrong password) for user: {$email}", $user['company_id']);
            return null;
        }

        // If the user belongs to a company, verify company status is active
        if ($user['company_id'] !== null) {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT id, status, subscription_expires_at FROM companies WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$user['company_id']]);
            $company = $stmt->fetch();

            if (!$company) {
                // Self-healing fallback: If company_id does not exist, point to first active company
                $stmt2 = $db->prepare("SELECT id, status FROM companies WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1");
                $stmt2->execute();
                $company = $stmt2->fetch();
                if ($company) {
                    $db->prepare("UPDATE users SET company_id = ? WHERE id = ?")->execute([$company['id'], $user['id']]);
                    $user['company_id'] = $company['id'];
                }
            }

            if (!$company || ($company['status'] !== 'active' && $company['status'] !== null)) {
                self::logAuthActivity($user['id'], 'login_company_suspended', "Login blocked: Tenant company is not active", $user['company_id']);
                return null;
            }
        }

        return $user;
    }

    /**
     * Complete the login by setting session variables
     */
    public static function login(array $user): void {
        Session::start();
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('user_email', $user['email']);
        Session::set('user_name', $user['name']);
        Session::set('company_id', $user['company_id']);
        Session::set('role_id', $user['role_id']);

        // Load permissions
        $permissions = self::loadUserPermissions($user['id']);
        Session::set('permissions', $permissions);

        self::logAuthActivity($user['id'], 'login_success', "User logged in successfully", $user['company_id']);
    }

    /**
     * Check if a user is authenticated
     */
    public static function check(): bool {
        Session::start();
        return Session::has('user_id');
    }

    /**
     * Retrieve the currently logged-in user array
     */
    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }
        $userModel = new User();
        return $userModel->find(Session::get('user_id'));
    }

    /**
     * Check if the authenticated user has a specific permission
     */
    public static function hasPermission(string $permission): bool {
        if (!self::check()) {
            return false;
        }
        $permissions = Session::get('permissions', []);
        return in_array($permission, $permissions);
    }

    /**
     * Log out the current user
     */
    public static function logout(): void {
        if (self::check()) {
            self::logAuthActivity(Session::get('user_id'), 'logout', "User logged out", Session::get('company_id'));
        }
        Session::destroy();
    }

    /**
     * Load permission names for a user via their role
     */
    private static function loadUserPermissions(int $userId): array {
        try {
            $db = Database::getInstance();
            // User -> Role -> RolePermissions -> Permissions
            $sql = "SELECT p.name FROM permissions p
                    INNER JOIN role_permissions rp ON p.id = rp.permission_id
                    INNER JOIN users u ON rp.role_id = u.role_id
                    WHERE u.id = ? AND u.deleted_at IS NULL";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Log an authentication-related activity in the database
     */
    private static function logAuthActivity(?int $userId, string $action, string $description, ?int $companyId = null): void {
        try {
            $db = Database::getInstance();
            $sql = "INSERT INTO audit_logs (company_id, user_id, action, description, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $companyId,
                $userId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            // Silently fail if db log fails to prevent auth blockage
        }
    }
}
