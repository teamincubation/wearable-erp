<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\Company;
use App\Models\AuditLog;
use App\Models\User;

/**
 * Developer SaaS Portal Controller
 * Full Stack PHP Engineer & Lead Architect - Antigravity
 */
class DeveloperController extends Controller {
    /**
     * Dev Portal Dashboard
     */
    public function dashboard(Request $request, Response $response): void {
        $db = Database::getInstance();

        // 1. Get statistics
        $companiesCount = $db->query("SELECT COUNT(*) FROM companies WHERE deleted_at IS NULL")->fetchColumn();
        $usersCount = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND company_id IS NOT NULL")->fetchColumn();
        $plansCount = $db->query("SELECT COUNT(*) FROM subscription_plans WHERE deleted_at IS NULL")->fetchColumn();
        
        // 2. Fetch companies list
        $companyModel = new Company();
        $companies = $companyModel->all();

        // 3. Fetch latest platform version
        $latestVersion = $db->query("SELECT version FROM system_versions ORDER BY id DESC LIMIT 1")->fetchColumn() ?: '1.0.0';

        $this->renderView('developer/dashboard', [
            'title' => 'Developer Portal Dashboard',
            'companies_count' => $companiesCount,
            'users_count' => $usersCount,
            'plans_count' => $plansCount,
            'companies' => $companies,
            'latest_version' => $latestVersion
        ], 'developer');
    }

    /**
     * Manage Onboarded Companies
     */
    public function companies(Request $request, Response $response): void {
        $companyModel = new Company();
        $companies = $companyModel->all();

        $db = Database::getInstance();
        $plans = $db->query("SELECT id, name FROM subscription_plans WHERE status = 'active' AND deleted_at IS NULL")->fetchAll();

        $this->renderView('developer/companies', [
            'title' => 'Company Manager | SaaS Admin',
            'companies' => $companies,
            'plans' => $plans
        ], 'developer');
    }

    /**
     * Create/Onboard dynamic Tenant Company
     */
    public function createCompany(Request $request, Response $response): void {
        $name = $request->get('name');
        $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $request->get('subdomain')));
        $email = $request->get('email');
        $phone = $request->get('phone');
        $planId = (int) $request->get('subscription_plan_id');

        if (empty($name) || empty($subdomain) || empty($email) || empty($planId)) {
            Session::setFlash('error', 'Please fill in all required company details.');
            $this->redirect('developer/companies');
        }

        $db = Database::getInstance();

        // Check if subdomain is unique
        $stmtCheck = $db->prepare("SELECT id FROM companies WHERE subdomain = ? AND deleted_at IS NULL");
        $stmtCheck->execute([$subdomain]);
        if ($stmtCheck->fetch()) {
            Session::setFlash('error', 'Subdomain is already registered. Please choose another one.');
            $this->redirect('developer/companies');
        }

        try {
            $db->beginTransaction();

            // 1. Insert Company
            $companyModel = new Company();
            $companyId = $companyModel->insert([
                'name' => $name,
                'subdomain' => $subdomain,
                'email' => $email,
                'phone' => $phone,
                'subscription_plan_id' => $planId,
                'subscription_status' => 'trial',
                'subscription_expires_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
                'status' => 'active',
                'created_by' => Session::get('user_id')
            ]);

            // 2. Create Default Roles for new company
            $stmtRole = $db->prepare("INSERT INTO roles (company_id, name, description, is_system) VALUES (?, ?, ?, 1)");
            
            // Admin role
            $stmtRole->execute([$companyId, 'Company Admin', 'Administrator for ERP tenant']);
            $adminRoleId = $db->lastInsertId();

            // Manager role
            $stmtRole->execute([$companyId, 'Production Manager', 'Responsible for factory floor processes']);
            $managerRoleId = $db->lastInsertId();

            // 3. Map default admin role to tenant permissions
            $stmtPermissions = $db->prepare("SELECT id FROM permissions WHERE module = 'tenant'");
            $stmtPermissions->execute();
            $tenantPermissions = $stmtPermissions->fetchAll(\PDO::FETCH_COLUMN);

            $stmtMap = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($tenantPermissions as $permId) {
                $stmtMap->execute([$adminRoleId, $permId]);
            }

            // 4. Create Default Company Admin User
            // Password: Admin@1234
            $userModel = new User();
            // Set company scope dynamically for insert
            $originalScope = \App\Core\Model::getActiveCompanyId();
            \App\Core\Model::setActiveCompanyId($companyId);

            $userModel->insert([
                'company_id' => $companyId,
                'role_id' => $adminRoleId,
                'name' => $name . ' Admin',
                'email' => "admin@{$subdomain}.mywellgro.online",
                'password_hash' => password_hash('Admin@1234', PASSWORD_BCRYPT),
                'status' => 'active',
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_by' => Session::get('user_id')
            ]);

            // Restore active tenant scope
            \App\Core\Model::setActiveCompanyId($originalScope);

            // 5. Seed default features for company
            $stmtFeature = $db->prepare("INSERT INTO feature_flags (company_id, feature_key, status) VALUES (?, ?, 'enabled')");
            $defaultFeatures = ['inventory', 'purchase', 'production', 'hr', 'payroll', 'crm', 'barcode'];
            foreach ($defaultFeatures as $feat) {
                $stmtFeature->execute([$companyId, $feat]);
            }

            // 6. Generate License
            $licenseKey = 'LIC-' . strtoupper($subdomain) . '-' . date('Y') . '-' . rand(1000, 9999);
            $stmtLic = $db->prepare("INSERT INTO license_keys (company_id, license_key, status, expires_at) VALUES (?, ?, 'active', ?)");
            $stmtLic->execute([$companyId, $licenseKey, date('Y-m-d H:i:s', strtotime('+1 year'))]);

            $db->commit();

            AuditLog::log(null, Session::get('user_id'), 'onboard_company', 'Company', $companyId, null, null, "Onboarded company: {$name} with subdomain: {$subdomain}");
            Session::setFlash('success', "Company '{$name}' onboarded successfully. Credentials: admin@{$subdomain}.mywellgro.online / Admin@1234");

        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Failed to onboard company: ' . $e->getMessage());
        }

        $this->redirect('developer/companies');
    }

    /**
     * Edit Company parameters
     */
    public function editCompany(Request $request, Response $response, string $id): void {
        $companyModel = new Company();
        $company = $companyModel->find($id);

        if (!$company) {
            Session::setFlash('error', 'Company not found.');
            $this->redirect('developer/companies');
        }

        $status = $request->get('status');
        $planId = (int) $request->get('subscription_plan_id');

        if ($status && in_array($status, ['active', 'inactive', 'suspended'])) {
            $companyModel->update($id, [
                'status' => $status,
                'subscription_plan_id' => $planId,
                'updated_by' => Session::get('user_id')
            ]);

            AuditLog::log(null, Session::get('user_id'), 'update_company', 'Company', $id, null, null, "Updated company status to: {$status}");
            Session::setFlash('success', 'Company details updated successfully.');
        }

        $this->redirect('developer/companies');
    }

    /**
     * Subscription Plans Configuration
     */
    public function subscriptions(Request $request, Response $response): void {
        $db = Database::getInstance();
        $plans = $db->query("SELECT * FROM subscription_plans WHERE deleted_at IS NULL")->fetchAll();

        $this->renderView('developer/subscriptions', [
            'title' => 'Subscription Plans',
            'plans' => $plans
        ], 'developer');
    }

    /**
     * Create Subscription Plan
     */
    public function createSubscriptionPlan(Request $request, Response $response): void {
        $name = $request->get('name');
        $code = strtolower(preg_replace('/[^a-z0-9_]/', '', $request->get('code')));
        $cycle = $request->get('billing_cycle');
        $price = (float) $request->get('price');
        $users = (int) $request->get('max_users');
        $branches = (int) $request->get('max_branches');
        $storage = (int) $request->get('max_storage_mb');
        $api = $request->get('api_access') ? 1 : 0;

        if (empty($name) || empty($code) || empty($cycle)) {
            Session::setFlash('error', 'Please fill in all plan details.');
            $this->redirect('developer/subscriptions');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO subscription_plans (name, code, billing_cycle, price, max_users, max_branches, max_storage_mb, api_access, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $code, $cycle, $price, $users, $branches, $storage, $api, Session::get('user_id')]);

        AuditLog::log(null, Session::get('user_id'), 'create_plan', 'SubscriptionPlan', $db->lastInsertId(), null, null, "Created plan: {$name}");
        Session::setFlash('success', 'Subscription plan created successfully.');
        $this->redirect('developer/subscriptions');
    }

    /**
     * Versioning Manager
     */
    public function versions(Request $request, Response $response): void {
        $db = Database::getInstance();
        $versions = $db->query("SELECT * FROM system_versions WHERE deleted_at IS NULL ORDER BY id DESC")->fetchAll();

        $this->renderView('developer/versions', [
            'title' => 'Platform Releases',
            'versions' => $versions
        ], 'developer');
    }

    /**
     * Release dynamic version
     */
    public function createVersion(Request $request, Response $response): void {
        $version = $request->get('version');
        $notes = $request->get('release_notes');

        if (empty($version)) {
            Session::setFlash('error', 'Version tag is required.');
            $this->redirect('developer/versions');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO system_versions (version, release_notes, status, created_by) VALUES (?, ?, 'active', ?)");
        $stmt->execute([$version, $notes, Session::get('user_id')]);

        AuditLog::log(null, Session::get('user_id'), 'release_version', 'SystemVersion', $db->lastInsertId(), null, null, "Released platform version: {$version}");
        Session::setFlash('success', "System updated. Release {$version} deployed successfully.");
        $this->redirect('developer/versions');
    }

    /**
     * Global Platform Audit logs
     */
    public function logs(Request $request, Response $response): void {
        $db = Database::getInstance();
        $logs = $db->query(
            "SELECT a.*, u.name as user_name FROM audit_logs a 
             LEFT JOIN users u ON a.user_id = u.id 
             ORDER BY a.id DESC LIMIT 50"
        )->fetchAll();

        $this->renderView('developer/logs', [
            'title' => 'Platform Audits',
            'logs' => $logs
        ], 'developer');
    }

    /**
     * Global System configurations
     */
    public function settings(Request $request, Response $response): void {
        $db = Database::getInstance();
        $settings = $db->query("SELECT * FROM system_settings WHERE company_id IS NULL AND deleted_at IS NULL")->fetchAll();

        $this->renderView('developer/settings', [
            'title' => 'SaaS Settings',
            'settings' => $settings
        ], 'developer');
    }

    /**
     * Save global SaaS configurations
     */
    public function saveSettings(Request $request, Response $response): void {
        $db = Database::getInstance();
        $settings = $request->all();

        unset($settings['csrf_token']); // Remove CSRF before loops

        foreach ($settings as $key => $val) {
            $stmt = $db->prepare(
                "INSERT INTO system_settings (company_id, setting_key, setting_value, updated_by) 
                 VALUES (NULL, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)"
            );
            $stmt->execute([$key, $val, Session::get('user_id')]);
        }

        AuditLog::log(null, Session::get('user_id'), 'update_global_settings', null, null, null, null, "Updated system settings.");
        Session::setFlash('success', 'Global system settings saved successfully.');
        $this->redirect('developer/settings');
    }

    /**
     * Dev Portal - SaaS Module Marketplace
     */
    public function marketplace(Request $request, Response $response): void {
        $this->renderView('developer/marketplace', [
            'title' => 'SaaS Module Marketplace',
        ], 'developer');
    }

    /**
     * Dev Portal - Database Diagnostics Monitor
     */
    public function dbMonitor(Request $request, Response $response): void {
        $db = Database::getInstance();

        // Gather database stats
        $tables = $db->query("SHOW TABLE STATUS")->fetchAll() ?: [];
        $vars = $db->query("SHOW VARIABLES LIKE 'version%'")->fetchAll() ?: [];

        $this->renderView('developer/db_monitor', [
            'title' => 'Database Diagnostics Monitor',
            'tables' => $tables,
            'variables' => $vars
        ], 'developer');
    }

    /**
     * Dev Portal - System Cron Jobs Logs
     */
    public function cronJobs(Request $request, Response $response): void {
        $db = Database::getInstance();
        $cronLogs = $db->query("SELECT * FROM cron_logs ORDER BY id DESC LIMIT 50")->fetchAll() ?: [];

        $this->renderView('developer/cron_jobs', [
            'title' => 'System Cron Jobs Logs',
            'cron_logs' => $cronLogs
        ], 'developer');
    }
}
