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
use PDO;

/**
 * Developer SaaS Portal Controller
 * Full Stack PHP Engineer & Lead Architect - Antigravity
 */
class DeveloperController extends Controller {

    /**
     * Auto-purge any remnant TOCCO Exports sample data if present in DB
     */
    private function purgeToccoIfPresent(\PDO $db): void {
        try {
            $stmtT = $db->query("SELECT id FROM companies WHERE LOWER(subdomain) = 'tocco' OR LOWER(name) LIKE '%tocco%'");
            $toccoIds = $stmtT->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            if (!empty($toccoIds)) {
                $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
                foreach ($toccoIds as $tId) {
                    $stmtTables = $db->query("SELECT DISTINCT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'company_id'");
                    $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN) ?: [];
                    foreach ($tables as $tbl) {
                        if ($tbl === 'companies') continue;
                        try {
                            $db->prepare("DELETE FROM `{$tbl}` WHERE company_id = ?")->execute([(int)$tId]);
                        } catch (\Exception $ex) {}
                    }
                    try {
                        $db->prepare("DELETE FROM role_permissions WHERE role_id IN (SELECT id FROM roles WHERE company_id = ?)")->execute([(int)$tId]);
                    } catch (\Exception $ex) {}
                    $db->prepare("DELETE FROM companies WHERE id = ?")->execute([(int)$tId]);
                }
                $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            }
        } catch (\Exception $ex) {}
    }

    /**
     * Dev Portal Dashboard
     */
    public function dashboard(Request $request, Response $response): void {
        $db = Database::getInstance();
        $this->purgeToccoIfPresent($db);

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
        $db = Database::getInstance();
        $this->purgeToccoIfPresent($db);
        $stmt = $db->query("
            SELECT c.*, 
                   u.id as admin_id,
                   u.name as admin_name, 
                   u.email as admin_email, 
                   u.phone as admin_phone
            FROM companies c
            LEFT JOIN users u ON u.id = (
                SELECT u2.id FROM users u2 
                LEFT JOIN roles r ON u2.role_id = r.id 
                WHERE u2.company_id = c.id AND u2.deleted_at IS NULL 
                ORDER BY (CASE WHEN r.name LIKE '%Admin%' THEN 1 ELSE 2 END), u2.id ASC 
                LIMIT 1
            )
            WHERE c.deleted_at IS NULL
            ORDER BY c.id DESC
        ");
        $companies = $stmt->fetchAll() ?: [];

        $plans = $db->query("SELECT * FROM subscription_plans WHERE deleted_at IS NULL")->fetchAll();

        // Ensure required tenant permissions exist dynamically in permissions table
        $requiredTenantPerms = [
            ['name' => 'company.sales_reports', 'description' => 'Executive Sales, Profitability & Manufacturing Reports', 'module' => 'tenant'],
            ['name' => 'company.production.rfid_tracking', 'description' => 'Access QR Code / RFID Production Scanner page', 'module' => 'tenant'],
            ['name' => 'company.dispatch.view', 'description' => 'View finished goods dispatch and packing hub', 'module' => 'tenant'],
            ['name' => 'company.dispatch.manage', 'description' => 'Manage carton packing, printing QR labels, and dispatching shipments', 'module' => 'tenant'],
            ['name' => 'company.packing.qr', 'description' => 'Access Packing QR Module for Carton Product Assignments', 'module' => 'tenant']
        ];
        foreach ($requiredTenantPerms as $permInfo) {
            try {
                $stmtCheck = $db->prepare("SELECT id FROM permissions WHERE name = ?");
                $stmtCheck->execute([$permInfo['name']]);
                $permId = $stmtCheck->fetchColumn();
                if (!$permId) {
                    $stmtIns = $db->prepare("INSERT INTO permissions (name, description, module) VALUES (?, ?, ?)");
                    $stmtIns->execute([$permInfo['name'], $permInfo['description'], $permInfo['module']]);
                    $permId = $db->lastInsertId();
                    if ($permId) {
                        $db->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, " . (int)$permId . ")");
                    }
                }
            } catch (\Exception $ex) {
                // Ignore duplicate errors
            }
        }

        // Fetch all tenant permissions
        $tenantPermissions = $db->query("SELECT * FROM permissions WHERE module = 'tenant' ORDER BY name ASC")->fetchAll();

        // Fetch all feature flags for each company
        $flagsRaw = $db->query("SELECT * FROM feature_flags WHERE deleted_at IS NULL")->fetchAll();
        $companyFlags = [];
        foreach ($flagsRaw as $flag) {
            $companyFlags[$flag['company_id']][$flag['feature_key']] = [
                'status' => $flag['status'],
                'expiry_date' => $flag['expiry_date'],
                'label' => $flag['label']
            ];
        }

        $this->renderView('developer/companies', [
            'title' => 'Company Manager | SaaS Admin',
            'companies' => $companies,
            'plans' => $plans,
            'allPermissions' => $tenantPermissions,
            'companyFlags' => $companyFlags
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
        $timezone = $request->get('timezone') ?: 'Asia/Kolkata';
        $currency = $request->get('currency') ?: 'INR';

        // Added parameters: Super Admin credentials and optional documents
        $adminEmail = $request->get('admin_email') ?: "admin@{$subdomain}.mywellgro.online";
        $adminPassword = $request->get('admin_password') ?: 'Admin@1234';
        $adminPhone = $request->get('admin_phone') ?: $phone;

        $tcAgreement = $request->get('tc_agreement') ?: null;
        $paymentSlip = $request->get('payment_slip') ?: null;

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
            return;
        }

        // Validate Super Admin login email uniqueness globally
        $stmtCheckAdminEmail = $db->prepare("SELECT id FROM users WHERE (email = ? OR employee_code = ?) AND deleted_at IS NULL LIMIT 1");
        $stmtCheckAdminEmail->execute([$adminEmail, $adminEmail]);
        if ($stmtCheckAdminEmail->fetch()) {
            Session::setFlash('error', "The Tenant Super Admin email/username '{$adminEmail}' is already registered in the platform.");
            $this->redirect('developer/companies');
            return;
        }

        // Logo Upload Processing
        $logoPath = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoFile = $_FILES['logo'];
            $ext = pathinfo($logoFile['name'], PATHINFO_EXTENSION);
            $logoName = $subdomain . '_logo_' . time() . '.' . $ext;
            
            $targetDir = dirname(__DIR__, 2) . '/uploads/logos/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            if (move_uploaded_file($logoFile['tmp_name'], $targetDir . $logoName)) {
                $logoPath = 'uploads/logos/' . $logoName;
            }
        }

        try {
            $db->beginTransaction();

            // 1. Insert Company
            $stmtPlanCycle = $db->prepare("SELECT billing_cycle FROM subscription_plans WHERE id = ?");
            $stmtPlanCycle->execute([$planId]);
            $planCycle = $stmtPlanCycle->fetchColumn() ?: 'monthly';

            $expiresAt = ($planCycle === 'lifetime') ? null : ($request->get('subscription_expires_at') ?: null);

            $companyModel = new Company();
            $companyId = $companyModel->insert([
                'name' => $name,
                'subdomain' => $subdomain,
                'email' => $email,
                'phone' => $phone,
                'subscription_plan_id' => $planId,
                'subscription_status' => 'trial',
                'subscription_expires_at' => $expiresAt,
                'status' => 'active',
                'tc_agreement' => $tcAgreement,
                'payment_slip' => $paymentSlip,
                'timezone' => $timezone,
                'currency' => $currency,
                'logo' => $logoPath,
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

            // 4. Create Default Company Admin User using input Super Admin credentials
            $userModel = new User();
            // Set company scope dynamically for insert
            $originalScope = \App\Core\Model::getActiveCompanyId();
            \App\Core\Model::setActiveCompanyId($companyId);

            $userModel->insert([
                'company_id' => $companyId,
                'role_id' => $adminRoleId,
                'name' => $name . ' Admin',
                'email' => $adminEmail,
                'password_hash' => password_hash($adminPassword, PASSWORD_BCRYPT),
                'phone' => $adminPhone,
                'status' => 'active',
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_by' => Session::get('user_id')
            ]);

            // Restore active tenant scope
            \App\Core\Model::setActiveCompanyId($originalScope);

            // 5. Save dynamic assigned permissions/features for company
            $stmtPlan = $db->prepare("SELECT billing_cycle FROM subscription_plans WHERE id = ?");
            $stmtPlan->execute([$planId]);
            $planCycle = $stmtPlan->fetchColumn() ?: 'monthly';

            $assignedPermissions = $request->get('permissions') ?: [];
            $expiryDates = $request->get('expiry_date') ?: [];
            $featureLabels = $request->get('labels') ?: [];

            // Add company.dashboard as a default permission if not selected
            if (!in_array('company.dashboard', $assignedPermissions)) {
                $assignedPermissions[] = 'company.dashboard';
            }

            // Insert assigned permissions as feature flags
            $stmtFeature = $db->prepare("INSERT INTO feature_flags (company_id, feature_key, status, expiry_date, label) VALUES (?, ?, 'enabled', ?, ?)");
            foreach ($assignedPermissions as $feat) {
                $expDate = (!empty($expiryDates[$feat]) && $planCycle !== 'lifetime') ? $expiryDates[$feat] : null;
                $labelVal = !empty($featureLabels[$feat]) ? $featureLabels[$feat] : 'no_label';
                $stmtFeature->execute([$companyId, $feat, $expDate, $labelVal]);
            }

            // 6. Generate License
            $licenseKey = 'LIC-' . strtoupper($subdomain) . '-' . date('Y') . '-' . rand(1000, 9999);
            $stmtLic = $db->prepare("INSERT INTO license_keys (company_id, license_key, status, expires_at) VALUES (?, ?, 'active', ?)");
            $stmtLic->execute([$companyId, $licenseKey, date('Y-m-d H:i:s', strtotime('+1 year'))]);

            $db->commit();

            AuditLog::log(null, Session::get('user_id'), 'onboard_company', 'Company', $companyId, null, null, "Onboarded company: {$name} with subdomain: {$subdomain}");
            Session::setFlash('success', "Company '{$name}' onboarded successfully. Admin Email: {$adminEmail}");

        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Failed to onboard company: ' . $e->getMessage());
        }

        $this->redirect('developer/companies');
    }

    /**
     * Edit Company parameters including Super Admin login credentials
     */
    public function editCompany(Request $request, Response $response, string $id): void {
        $companyModel = new Company();
        $company = $companyModel->find($id);

        if (!$company) {
            Session::setFlash('error', 'Company not found.');
            $this->redirect('developer/companies');
        }

        $name = trim($request->get('name')) ?: $company['name'];
        $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $request->get('subdomain'))) ?: $company['subdomain'];
        $email = trim($request->get('email')) ?: $company['email'];
        $phone = trim($request->get('phone')) ?: $company['phone'];
        $status = $request->get('status') ?: $company['status'];
        $planId = (int) ($request->get('subscription_plan_id') ?: $company['subscription_plan_id']);
        $tcAgreement = $request->get('tc_agreement') ?: null;
        $paymentSlip = $request->get('payment_slip') ?: null;
        $timezone = $request->get('timezone') ?: $company['timezone'];
        $currency = $request->get('currency') ?: $company['currency'];

        // Admin user fields & Dev backdoor credentials
        $adminName = trim($request->get('admin_name'));
        $adminEmail = trim($request->get('admin_email'));
        $adminPhone = trim($request->get('admin_phone'));
        $adminPassword = $request->get('admin_password');

        $db = Database::getInstance();

        if (!empty($adminEmail)) {
            $stmtCheckAE = $db->prepare("SELECT id FROM users WHERE (email = ? OR employee_code = ?) AND company_id != ? AND deleted_at IS NULL LIMIT 1");
            $stmtCheckAE->execute([$adminEmail, $adminEmail, $id]);
            if ($stmtCheckAE->fetch()) {
                Session::setFlash('error', "The Tenant Super Admin email '{$adminEmail}' is already registered to another user/company.");
                $this->redirect('developer/companies');
                return;
            }
        }

        // Logo Upload Processing
        $logoPath = $company['logo'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoFile = $_FILES['logo'];
            $ext = pathinfo($logoFile['name'], PATHINFO_EXTENSION);
            $logoName = $subdomain . '_logo_' . time() . '.' . $ext;
            
            $targetDir = dirname(__DIR__, 2) . '/uploads/logos/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            if (move_uploaded_file($logoFile['tmp_name'], $targetDir . $logoName)) {
                $logoPath = 'uploads/logos/' . $logoName;
            }
        }

        try {
            $db->beginTransaction();

            // 1. Update company record
            $stmtPlanCycle = $db->prepare("SELECT billing_cycle FROM subscription_plans WHERE id = ?");
            $stmtPlanCycle->execute([$planId]);
            $planCycle = $stmtPlanCycle->fetchColumn() ?: 'monthly';

            $expiresAt = ($planCycle === 'lifetime') ? null : ($request->get('subscription_expires_at') ?: null);

            $updateCompData = [
                'name' => $name,
                'subdomain' => $subdomain,
                'email' => $email,
                'phone' => $phone,
                'status' => $status,
                'subscription_plan_id' => $planId,
                'subscription_expires_at' => $expiresAt,
                'tc_agreement' => $tcAgreement,
                'payment_slip' => $paymentSlip,
                'timezone' => $timezone,
                'currency' => $currency,
                'logo' => $logoPath,
                'updated_by' => Session::get('user_id')
            ];

            $companyModel->update($id, $updateCompData);

            // 2. Find and update the tenant super admin user credentials
            $stmtUser = $db->prepare("
                SELECT u.* FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.company_id = ? AND r.name LIKE '%Admin%'
                LIMIT 1
            ");
            $stmtUser->execute([$id]);
            $adminUser = $stmtUser->fetch();

            if ($adminUser) {
                $sql = "UPDATE users SET company_id = ?, status = 'active'";
                $params = [(int)$id];

                if (!empty($adminEmail)) {
                    $sql .= ", email = ?";
                    $params[] = $adminEmail;
                }
                if (!empty($adminName)) {
                    $sql .= ", name = ?";
                    $params[] = $adminName;
                }
                if ($adminPhone !== '') {
                    $sql .= ", phone = ?";
                    $params[] = $adminPhone;
                }
                if (!empty($adminPassword)) {
                    $sql .= ", password_hash = ?";
                    $params[] = password_hash($adminPassword, PASSWORD_BCRYPT);
                }

                $sql .= ", updated_by = ?, updated_at = NOW() WHERE id = ?";
                $params[] = Session::get('user_id');
                $params[] = $adminUser['id'];

                $stmtUpdateUser = $db->prepare($sql);
                $stmtUpdateUser->execute($params);
            } else {
                // Create Super Admin user if missing
                $stmtRole = $db->prepare("SELECT id FROM roles WHERE company_id = ? AND name LIKE '%Admin%' LIMIT 1");
                $stmtRole->execute([(int)$id]);
                $roleId = $stmtRole->fetchColumn() ?: 2;

                $stmtInsertUser = $db->prepare("
                    INSERT INTO users (company_id, role_id, name, email, password_hash, phone, status, email_verified_at, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
                ");
                $stmtInsertUser->execute([
                    (int)$id,
                    $roleId,
                    $adminName ?: ($name . ' Admin'),
                    $adminEmail ?: $email,
                    password_hash($adminPassword ?: 'Admin@1234', PASSWORD_BCRYPT),
                    $adminPhone ?: $phone
                ]);
            }

            // 3. Save dynamic assigned permissions/features for company
            $stmtPlan = $db->prepare("SELECT billing_cycle FROM subscription_plans WHERE id = ?");
            $stmtPlan->execute([$planId]);
            $planCycle = $stmtPlan->fetchColumn() ?: 'monthly';

            $assignedPermissions = $request->get('permissions') ?: [];
            $expiryDates = $request->get('expiry_date') ?: [];
            $featureLabels = $request->get('labels') ?: [];

            // Add company.dashboard as a default permission if not selected
            if (!in_array('company.dashboard', $assignedPermissions)) {
                $assignedPermissions[] = 'company.dashboard';
            }

            // Remove old permissions/features
            $db->prepare("DELETE FROM feature_flags WHERE company_id = ?")->execute([$id]);

            // Insert assigned permissions as feature flags
            $stmtFeature = $db->prepare("INSERT INTO feature_flags (company_id, feature_key, status, expiry_date, label) VALUES (?, ?, 'enabled', ?, ?)");
            foreach ($assignedPermissions as $feat) {
                $expDate = (!empty($expiryDates[$feat]) && $planCycle !== 'lifetime') ? $expiryDates[$feat] : null;
                $labelVal = !empty($featureLabels[$feat]) ? $featureLabels[$feat] : 'no_label';
                $stmtFeature->execute([$id, $feat, $expDate, $labelVal]);
            }

            // Sync assigned permissions into role_permissions for Company Admin roles
            $stmtAdminRoles = $db->prepare("SELECT id FROM roles WHERE company_id = ? AND (name LIKE '%Admin%' OR is_system = 1)");
            $stmtAdminRoles->execute([(int)$id]);
            $adminRoleIds = $stmtAdminRoles->fetchAll(\PDO::FETCH_COLUMN) ?: [];

            if (!empty($adminRoleIds) && !empty($assignedPermissions)) {
                $placeholders = implode(',', array_fill(0, count($assignedPermissions), '?'));
                $stmtGetPermIds = $db->prepare("SELECT id FROM permissions WHERE name IN ({$placeholders})");
                $stmtGetPermIds->execute($assignedPermissions);
                $permIds = $stmtGetPermIds->fetchAll(\PDO::FETCH_COLUMN) ?: [];

                $stmtInsRP = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($adminRoleIds as $rId) {
                    foreach ($permIds as $pId) {
                        $stmtInsRP->execute([(int)$rId, (int)$pId]);
                    }
                }
            }

            $db->commit();

            AuditLog::log(null, Session::get('user_id'), 'update_company_details', 'Company', (int)$id, null, null, "Updated company tenant details and super admin credentials for {$name}");
            Session::setFlash('success', 'Tenant company details and super admin login credentials updated successfully.');
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Failed to update tenant details: ' . $e->getMessage());
        }

        $this->redirect('developer/companies');
    }

    /**
     * Hard Delete Tenant Company (Global Admin Only)
     */
    public function deleteCompany(Request $request, Response $response, string $id): void {
        $companyModel = new Company();
        $company = $companyModel->find($id);

        if (!$company) {
            Session::setFlash('error', 'Company not found.');
            $this->redirect('developer/companies');
        }

        $db = Database::getInstance();
        try {
            // Disable foreign key checks for clean tenant hard deletion
            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

            // 1. Delete indirect child tables linked via user_id or role_id
            try {
                $db->prepare("DELETE FROM role_permissions WHERE role_id IN (SELECT id FROM roles WHERE company_id = ?)")->execute([$id]);
            } catch (\Exception $ex) {}

            try {
                $db->prepare("DELETE FROM user_sessions WHERE user_id IN (SELECT id FROM users WHERE company_id = ?)")->execute([$id]);
            } catch (\Exception $ex) {}

            // 2. Explicit list of all tenant data tables
            $tablesWithCompanyId = [
                'users', 'roles', 'feature_flags', 'license_keys', 'system_settings',
                'styles', 'tech_packs', 'bom_categories', 'contacts', 'branches',
                'warehouses', 'warehouse_types', 'departments', 'machines', 'shifts',
                'uoms', 'taxes_gst', 'company_holidays', 'payment_accounts', 'employee_loans',
                'designations', 'cik_history', 'style_variables', 'cost_sheets', 'buyer_pos',
                'purchase_requisitions', 'purchase_orders', 'purchase_order_items', 'grns',
                'grn_items', 'supplier_invoices', 'inventory_transactions', 'production_orders',
                'production_stage_logs', 'quality_inspections', 'employee_attendance',
                'payroll_records', 'tally_vouchers', 'audit_logs'
            ];

            // 3. Dynamically discover any additional database tables containing company_id column
            try {
                $stmtSchema = $db->query("
                    SELECT DISTINCT TABLE_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                      AND COLUMN_NAME = 'company_id'
                ");
                $schemaTables = $stmtSchema->fetchAll(\PDO::FETCH_COLUMN) ?: [];
                $tablesWithCompanyId = array_values(array_unique(array_merge($tablesWithCompanyId, $schemaTables)));
            } catch (\Exception $ex) {}

            // Execute purge for every table containing company_id
            foreach ($tablesWithCompanyId as $table) {
                if ($table === 'companies') continue;
                try {
                    $stmt = $db->prepare("DELETE FROM `{$table}` WHERE company_id = ?");
                    $stmt->execute([$id]);
                } catch (\Exception $ex) {}
            }

            // 4. Hard delete company record itself from companies table
            $stmt = $db->prepare("DELETE FROM companies WHERE id = ?");
            $stmt->execute([$id]);

            // Restore foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

            AuditLog::log(null, Session::get('user_id'), 'hard_delete_company', 'Company', (int)$id, null, null, "Hard deleted company tenant and all associated data: {$company['name']} ({$company['subdomain']})");
            Session::setFlash('success', "Tenant company '{$company['name']}' and all its associated records across all system tables have been permanently deleted.");
        } catch (\Exception $e) {
            try {
                $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            } catch (\Exception $ex) {}
            Session::setFlash('error', 'Failed to delete company: ' . $e->getMessage());
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
     * Edit Subscription Plan
     */
    public function editSubscriptionPlan(Request $request, Response $response, string $id): void {
        $name = $request->get('name');
        $code = strtolower(preg_replace('/[^a-z0-9_]/', '', $request->get('code')));
        $cycle = $request->get('billing_cycle');
        $price = (float) $request->get('price');
        $users = (int) $request->get('max_users');
        $branches = (int) $request->get('max_branches');
        $storage = (int) $request->get('max_storage_mb');
        $api = $request->get('api_access') ? 1 : 0;
        $status = $request->get('status') ?: 'active';

        if (empty($name) || empty($code) || empty($cycle)) {
            Session::setFlash('error', 'Please fill in all plan details.');
            $this->redirect('developer/subscriptions');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE subscription_plans 
             SET name = ?, code = ?, billing_cycle = ?, price = ?, max_users = ?, max_branches = ?, max_storage_mb = ?, api_access = ?, status = ?, updated_by = ?
             WHERE id = ?"
        );
        $stmt->execute([$name, $code, $cycle, $price, $users, $branches, $storage, $api, $status, Session::get('user_id'), $id]);

        AuditLog::log(null, Session::get('user_id'), 'edit_plan', 'SubscriptionPlan', (int)$id, null, null, "Updated plan: {$name}");
        Session::setFlash('success', 'Subscription plan updated successfully.');
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
        $rawSettings = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE company_id IS NULL AND deleted_at IS NULL")->fetchAll();
        $settings = [];
        foreach ($rawSettings as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $companies = $db->query("SELECT id, name, subdomain FROM companies WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll() ?: [];

        $this->renderView('developer/settings', [
            'title' => 'SaaS Settings',
            'settings' => $settings,
            'companies' => $companies
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
