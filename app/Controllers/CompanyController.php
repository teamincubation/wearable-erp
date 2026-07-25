<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use App\Models\Company;

/**
 * Tenant Operations Controller
 * Full Stack PHP Engineer - Antigravity
 */
class CompanyController extends Controller {
    /**
     * User Administration List
     */
    public function users(Request $request, Response $response): void {
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        // Retrieve query parameters
        $search = trim($request->get('search') ?? '');
        $filterDesignation = trim($request->get('filter_designation') ?? '');
        $filterRole = trim($request->get('filter_role') ?? '');
        $filterStatus = trim($request->get('filter_status') ?? '');

        // Base query - exclude super admin (role_id = 1) and restrict to company_id
        $sql = "SELECT * FROM users WHERE company_id = ? AND role_id != 1 AND deleted_at IS NULL";
        $params = [$companyId];

        // Apply Search by name, employee_code, phone
        if ($search !== '') {
            $sql .= " AND (name LIKE ? OR employee_code LIKE ? OR phone LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        // Apply designation filter
        if ($filterDesignation !== '') {
            $sql .= " AND designation = ?";
            $params[] = $filterDesignation;
        }

        // Apply role filter
        if ($filterRole !== '') {
            $sql .= " AND role_id = ?";
            $params[] = (int)$filterRole;
        }

        // Apply status filter
        if ($filterStatus !== '') {
            $sql .= " AND status = ?";
            $params[] = $filterStatus;
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll() ?: [];

        // Fetch all roles excluding Super Admin role
        $roles = $db->prepare("SELECT * FROM roles WHERE (company_id = ? OR company_id IS NULL) AND id != 1 AND deleted_at IS NULL ORDER BY name ASC");
        $roles->execute([$companyId]);
        $rolesList = $roles->fetchAll() ?: [];

        $stmtD = $db->prepare("SELECT * FROM designations WHERE company_id = ? AND deleted_at IS NULL ORDER BY title ASC");
        $stmtD->execute([$companyId]);
        $designations = $stmtD->fetchAll() ?: [];

        $this->renderView('company/users', [
            'title' => 'Employee Manager | ERP',
            'users' => $users,
            'roles' => $rolesList,
            'designations' => $designations
        ]);
    }

    /**
     * Add new company employee user
     */
    public function createUser(Request $request, Response $response): void {
        $name = $request->get('name');
        $email = trim($request->get('email'));
        $employeeCode = trim($request->get('employee_code'));
        $phone = $request->get('phone');
        $password = $request->get('password');
        $roleId = (int) $request->get('role_id');
        $baseSalary = (float)$request->get('base_salary');
        $designation = trim($request->get('designation'));

        if (empty($name) || empty($email) || empty($employeeCode) || empty($password) || empty($roleId)) {
            Session::setFlash('error', 'All fields are required, including Employee ID.');
            $this->redirect('company/users');
        }

        $userModel = new User();

        // Ensure Employee ID is unique per company
        $db = Database::getInstance();
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE company_id = ? AND employee_code = ? AND deleted_at IS NULL LIMIT 1");
        $stmtCheck->execute([Session::get('company_id'), $employeeCode]);
        if ($stmtCheck->fetch()) {
            Session::setFlash('error', 'This Employee ID is already registered for this company.');
            $this->redirect('company/users');
        }

        // Ensure email/username uniqueness globally across users and developer usernames
        $existing = $userModel->findGlobalByIdentifier($email);
        $stmtDevCheck = $db->prepare("SELECT id FROM companies WHERE dev_username = ? AND deleted_at IS NULL LIMIT 1");
        $stmtDevCheck->execute([$email]);
        if ($existing || $stmtDevCheck->fetch()) {
            Session::setFlash('error', "The Email/Username '{$email}' is already registered in the platform.");
            $this->redirect('company/users');
            return;
        }

        $userId = $userModel->insert([
            'name' => $name,
            'email' => $email,
            'employee_code' => $employeeCode,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role_id' => $roleId,
            'status' => 'active',
            'base_salary' => $baseSalary,
            'designation' => $designation ?: 'Staff',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_employee', 'User', $userId, null, null, "Created new employee account: {$name} (ID: {$employeeCode})");
        Session::setFlash('success', 'Employee account created successfully.');
        $this->redirect('company/users');
    }

    /**
     * Edit employee user profile details
     */
    public function editUser(Request $request, Response $response, string $id): void {
        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user) {
            Session::setFlash('error', 'Employee not found.');
            $this->redirect('company/users');
        }

        // Prevent self-editing account details in Employee Manager
        if ((int)$id === (int)Session::get('user_id')) {
            Session::setFlash('error', 'You cannot edit your own employee account details in Employee Manager.');
            $this->redirect('company/users');
            return;
        }

        $name = $request->get('name');
        $email = trim($request->get('email'));
        $employeeCode = trim($request->get('employee_code'));
        $phone = $request->get('phone');
        $roleId = (int) $request->get('role_id');
        $status = $request->get('status');
        $password = $request->get('password');
        $baseSalary = (float)$request->get('base_salary');
        $designation = trim($request->get('designation'));

        // Handle inactivity fields
        $inactiveReason = trim($request->get('inactive_reason') ?? '');
        $inactivityDate = trim($request->get('inactivity_date') ?? '');
        $inactivityRemarks = trim($request->get('inactivity_remarks') ?? '');

        if (empty($name) || empty($email) || empty($employeeCode) || empty($roleId)) {
            Session::setFlash('error', 'Name, Email/Username, Employee ID, and Role are required.');
            $this->redirect('company/users');
        }

        if ($status === 'inactive') {
            if (empty($inactiveReason) || empty($inactivityDate)) {
                Session::setFlash('error', 'Inactive Type and Inactivity Date are required when deactivating an account.');
                $this->redirect('company/users');
            }
            if (strtotime($inactivityDate) > time()) {
                Session::setFlash('error', 'Inactivity date cannot be in the future.');
                $this->redirect('company/users');
            }
        } else {
            $inactiveReason = null;
            $inactivityDate = null;
            $inactivityRemarks = null;
        }

        $db = Database::getInstance();

        // Ensure Employee ID uniqueness (excluding current user)
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE company_id = ? && employee_code = ? && id != ? && deleted_at IS NULL LIMIT 1");
        $stmtCheck->execute([Session::get('company_id'), $employeeCode, $id]);
        if ($stmtCheck->fetch()) {
            Session::setFlash('error', 'This Employee ID is already registered for another user in this company.');
            $this->redirect('company/users');
        }

        // Ensure Email/Username uniqueness globally (excluding current user)
        $stmtCheckEmail = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL LIMIT 1");
        $stmtCheckEmail->execute([$email, $id]);
        $stmtDevCheck = $db->prepare("SELECT id FROM companies WHERE dev_username = ? AND deleted_at IS NULL LIMIT 1");
        $stmtDevCheck->execute([$email]);
        if ($stmtCheckEmail->fetch() || $stmtDevCheck->fetch()) {
            Session::setFlash('error', "The Email/Username '{$email}' is already registered in the platform.");
            $this->redirect('company/users');
            return;
        }

        $updates = [
            'name' => $name,
            'email' => $email,
            'employee_code' => $employeeCode,
            'phone' => $phone,
            'role_id' => $roleId,
            'status' => $status,
            'inactive_reason' => $inactiveReason,
            'inactivity_date' => $inactivityDate,
            'inactivity_remarks' => $inactivityRemarks,
            'base_salary' => $baseSalary,
            'designation' => $designation ?: 'Staff',
            'updated_by' => Session::get('user_id')
        ];

        if (!empty($password)) {
            $updates['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $userModel->update($id, $updates);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'update_employee', 'User', $id, $user, $updates, "Updated employee details for: {$name}");
        Session::setFlash('success', 'Employee details updated successfully.');
        $this->redirect('company/users');
    }

    /**
     * Soft delete an employee
     */
    public function deleteUser(Request $request, Response $response, string $id): void {
        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user) {
            Session::setFlash('error', 'Employee not found.');
            $this->redirect('company/users');
        }

        // Prevent self-deletion
        if ((int) $id === (int) Session::get('user_id')) {
            Session::setFlash('error', 'You cannot delete your own account.');
            $this->redirect('company/users');
        }

        $userModel->delete($id, Session::get('user_id'));

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'delete_employee', 'User', $id, null, null, "Deactivated/Soft deleted user: {$user['name']}");
        Session::setFlash('success', 'Employee account deactivated successfully.');
        $this->redirect('company/users');
    }

    public function roles(Request $request, Response $response): void {
        $roleModel = new Role();
        $roles = $roleModel->all();

        // Load permissions list and restrict to active feature flags for this tenant
        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        
        $permissionsRaw = $db->query("SELECT * FROM permissions WHERE module = 'tenant'")->fetchAll();

        // Fetch enabled feature flags for this company
        $stmtFlags = $db->prepare("SELECT feature_key FROM feature_flags WHERE company_id = ? AND status = 'enabled' AND deleted_at IS NULL");
        $stmtFlags->execute([$companyId]);
        $enabledKeys = $stmtFlags->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        // Filter permissions
        $permissions = [];
        foreach ($permissionsRaw as $p) {
            if (in_array($p['name'], $enabledKeys)) {
                $permissions[] = $p;
            }
        }

        // Get permissions mapped to each role
        $rolePermissions = [];
        foreach ($roles as $role) {
            $mapped = $roleModel->getPermissions($role['id']);
            $rolePermissions[$role['id']] = array_column($mapped, 'id');
        }

        $this->renderView('company/roles', [
            'title' => 'Roles & Permissions',
            'roles' => $roles,
            'permissions' => $permissions,
            'role_permissions' => $rolePermissions
        ]);
    }

    /**
     * Create custom role
     */
    public function createRole(Request $request, Response $response): void {
        $name = $request->get('name');
        $desc = $request->get('description');
        $permissionIds = $request->get('permissions') ?: [];

        if (empty($name)) {
            Session::setFlash('error', 'Role name is required.');
            $this->redirect('company/roles');
        }

        $roleModel = new Role();
        $roleId = $roleModel->insert([
            'name' => $name,
            'description' => $desc,
            'is_system' => 0,
            'created_by' => Session::get('user_id')
        ]);

        $roleModel->syncPermissions($roleId, $permissionIds);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_role', 'Role', $roleId, null, null, "Created role {$name} with " . count($permissionIds) . " permissions.");
        Session::setFlash('success', 'Custom role and permissions created successfully.');
        $this->redirect('company/roles');
    }

    /**
     * Edit custom role and permissions
     */
    public function editRole(Request $request, Response $response, string $id): void {
        $roleModel = new Role();
        $role = $roleModel->find($id);

        if (!$role) {
            Session::setFlash('error', 'Role not found.');
            $this->redirect('company/roles');
        }

        $name = $request->get('name');
        $desc = $request->get('description');
        $permissionIds = $request->get('permissions') ?: [];

        if (empty($name)) {
            Session::setFlash('error', 'Role name cannot be empty.');
            $this->redirect('company/roles');
        }

        $roleModel->update($id, [
            'name' => $name,
            'description' => $desc,
            'updated_by' => Session::get('user_id')
        ]);

        $roleModel->syncPermissions($id, $permissionIds);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_role', 'Role', $id, null, null, "Updated role {$name} permissions.");
        Session::setFlash('success', 'Role permissions updated successfully.');
        $this->redirect('company/roles');
    }

    /**
     * Delete custom role
     */
    public function deleteRole(Request $request, Response $response, string $id): void {
        $roleModel = new Role();
        $role = $roleModel->find($id);

        if (!$role) {
            Session::setFlash('error', 'Role not found.');
            $this->redirect('company/roles');
        }

        if ($role['is_system']) {
            Session::setFlash('error', 'System roles cannot be deleted.');
            $this->redirect('company/roles');
        }

        $roleModel->update($id, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'delete_role', 'Role', $id, null, null, "Deleted custom role: {$role['name']}");
        Session::setFlash('success', 'Custom role deleted successfully.');
        $this->redirect('company/roles');
    }

    /**
     * Bulk delete custom roles
     */
    public function bulkDeleteRoles(Request $request, Response $response): void {
        $roleIds = $request->get('role_ids');
        if (empty($roleIds) || !is_array($roleIds)) {
            Session::setFlash('error', 'No roles selected.');
            $this->redirect('company/roles');
        }

        $roleModel = new Role();
        $count = 0;
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        foreach ($roleIds as $id) {
            $role = $roleModel->find($id);
            if ($role && !$role['is_system']) {
                $roleModel->update($id, [
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'updated_by' => $userId
                ]);
                $count++;
            }
        }

        if ($count > 0) {
            AuditLog::log($companyId, $userId, 'bulk_delete_roles', 'Role', null, null, null, "Bulk-deleted {$count} custom roles.");
            Session::setFlash('success', "Successfully deleted {$count} custom roles.");
        } else {
            Session::setFlash('error', 'No custom roles could be deleted.');
        }

        $this->redirect('company/roles');
    }

    /**
     * View Company-level Audit Trails
     */
    public function logs(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch company assigned timezone
        $stmtComp = $db->prepare("SELECT timezone FROM companies WHERE id = ? LIMIT 1");
        $stmtComp->execute([$companyId]);
        $companyTimezone = $stmtComp->fetchColumn() ?: 'Asia/Kolkata';

        // Query audit logs with user names
        $stmt = $db->prepare(
            "SELECT a.*, u.name as user_name FROM audit_logs a 
             LEFT JOIN users u ON a.user_id = u.id 
             WHERE a.company_id = ?
             ORDER BY a.id DESC LIMIT 100"
        );
        $stmt->execute([$companyId]);
        $logs = $stmt->fetchAll() ?: [];

        foreach ($logs as &$l) {
            if (!empty($l['created_at'])) {
                try {
                    $dt = new \DateTime($l['created_at'], new \DateTimeZone('UTC'));
                    $dt->setTimezone(new \DateTimeZone($companyTimezone));
                    $l['formatted_created_at'] = $dt->format('d-M-Y H:i:s');
                } catch (\Exception $ex) {
                    $l['formatted_created_at'] = date('d-M-Y H:i:s', strtotime($l['created_at']));
                }
            } else {
                $l['formatted_created_at'] = 'N/A';
            }
        }
        unset($l);

        $this->renderView('company/logs', [
            'title' => 'Audit Trails | ERP',
            'logs' => $logs,
            'timezone' => $companyTimezone
        ]);
    }

    /**
     * Tenant Settings config
     */
    public function settings(Request $request, Response $response): void {
        $companyId = Session::get('company_id');
        $companyModel = new Company();
        $company = $companyModel->find($companyId);

        // Fetch company specific settings
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM system_settings WHERE company_id = ? AND deleted_at IS NULL");
        $stmt->execute([$companyId]);
        $settingsRaw = $stmt->fetchAll();

        $settings = [];
        foreach ($settingsRaw as $setting) {
            $settings[$setting['setting_key']] = $setting['setting_value'];
        }

        // Fetch company specific payment accounts
        $stmtPay = $db->prepare("SELECT * FROM payment_accounts WHERE company_id = ? AND deleted_at IS NULL ORDER BY id DESC");
        $stmtPay->execute([$companyId]);
        $paymentAccounts = $stmtPay->fetchAll() ?: [];

        $companyWipStages = self::getCompanyWipStages($companyId);

        $this->renderView('company/settings', [
            'title' => 'Company Settings | ERP',
            'company' => $company,
            'settings' => $settings,
            'paymentAccounts' => $paymentAccounts,
            'companyWipStages' => $companyWipStages
        ]);
    }

    /**
     * Save company specific settings
     */
    public function saveSettings(Request $request, Response $response): void {
        $companyId = Session::get('company_id');
        $companyModel = new Company();

        // 1. Update general company card
        $companyModel->update($companyId, [
            'name' => $request->get('company_name'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'address' => $request->get('address'),
            'gstin' => $request->get('gstin'),
            'updated_by' => Session::get('user_id')
        ]);

        // 2. Save settings
        $db = Database::getInstance();
        $settings = [
            'currency' => $request->get('currency') ?: 'INR',
            'timezone' => $request->get('timezone') ?: 'Asia/Kolkata'
        ];



        foreach ($settings as $key => $val) {
            $stmtCheck = $db->prepare("SELECT id FROM system_settings WHERE company_id = ? AND setting_key = ? AND deleted_at IS NULL LIMIT 1");
            $stmtCheck->execute([$companyId, $key]);
            $existingId = $stmtCheck->fetchColumn();

            if ($existingId) {
                $stmtUpdate = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
                $stmtUpdate->execute([$val, Session::get('user_id'), $existingId]);
            } else {
                $stmtInsert = $db->prepare("INSERT INTO system_settings (company_id, setting_key, setting_value, created_by) VALUES (?, ?, ?, ?)");
                $stmtInsert->execute([$companyId, $key, $val, Session::get('user_id')]);
            }
        }

        AuditLog::log($companyId, Session::get('user_id'), 'update_company_settings', 'Company', $companyId, null, null, "Updated company profile card & settings.");
        Session::setFlash('success', 'Company profile and settings updated successfully.');
        $this->redirect('company/settings');
    }

    /**
     * Create a payment account
     */
    public function createPaymentAccount(Request $request, Response $response): void {
        $name = trim($request->get('name'));
        $type = trim($request->get('type'));
        $gstAccount = $request->get('gst_account') === 'Yes' ? 'Yes' : 'No';
        $gstPercent = (float)$request->get('gst_percent');

        if (empty($name) || empty($type)) {
            Session::setFlash('error', 'Account Name and Type are required.');
            $this->redirect('company/settings');
        }

        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        try {
            $stmt = $db->prepare("INSERT INTO payment_accounts (company_id, name, type, gst_account, gst_percent, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$companyId, $name, $type, $gstAccount, $gstPercent, $userId]);

            AuditLog::log($companyId, $userId, 'create_payment_account', 'PaymentAccount', $db->lastInsertId(), null, null, "Created payment account '{$name}' (Type: {$type})");
            Session::setFlash('success', "Payment account '{$name}' added successfully.");
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to create payment account: ' . $e->getMessage());
        }

        $this->redirect('company/settings');
    }

    /**
     * Edit a payment account
     */
    public function editPaymentAccount(Request $request, Response $response, string $id): void {
        $name = trim($request->get('name'));
        $type = trim($request->get('type'));
        $gstAccount = $request->get('gst_account') === 'Yes' ? 'Yes' : 'No';
        $gstPercent = (float)$request->get('gst_percent');

        if (empty($name) || empty($type)) {
            Session::setFlash('error', 'Account Name and Type are required.');
            $this->redirect('company/settings');
        }

        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        try {
            $stmt = $db->prepare("UPDATE payment_accounts SET name = ?, type = ?, gst_account = ?, gst_percent = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$name, $type, $gstAccount, $gstPercent, $id, $companyId]);

            AuditLog::log($companyId, $userId, 'edit_payment_account', 'PaymentAccount', $id, null, null, "Updated payment account '{$name}'");
            Session::setFlash('success', "Payment account updated successfully.");
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to update payment account: ' . $e->getMessage());
        }

        $this->redirect('company/settings');
    }

    /**
     * Delete a payment account (soft delete)
     */
    public function deletePaymentAccount(Request $request, Response $response, string $id): void {
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        try {
            $stmt = $db->prepare("UPDATE payment_accounts SET deleted_at = NOW() WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);

            AuditLog::log($companyId, $userId, 'delete_payment_account', 'PaymentAccount', $id, null, null, "Soft-deleted payment account ID {$id}");
            Session::setFlash('success', "Payment account deleted successfully.");
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to delete payment account: ' . $e->getMessage());
        }

        $this->redirect('company/settings');
    }

    public function saveMenuOrder(Request $request, Response $response): void {
        $raw = $_POST['sidebar_menu_order'] ?? $request->get('sidebar_menu_order');
        if (is_string($raw)) {
            $raw = html_entity_decode($raw, ENT_QUOTES, 'UTF-8');
        }
        
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($decoded) || empty($decoded)) {
            $response->json(['success' => false, 'error' => 'Invalid order content.'], 400);
            return;
        }

        // Clean values to valid string keys only
        $cleanKeys = array_values(array_filter($decoded, function($k) {
            return is_string($k) && preg_match('/^[a-z0-9_]+$/i', $k);
        }));

        if (empty($cleanKeys)) {
            $response->json(['success' => false, 'error' => 'No valid menu keys provided.'], 400);
            return;
        }

        $orderJson = json_encode($cleanKeys);

        $companyId = Session::get('company_id');
        $db = Database::getInstance();
        
        $stmtCheck = $db->prepare("SELECT id FROM system_settings WHERE company_id = ? AND setting_key = 'sidebar_menu_order' AND deleted_at IS NULL LIMIT 1");
        $stmtCheck->execute([$companyId]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            $stmtUpdate = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$orderJson, Session::get('user_id'), $existingId]);
        } else {
            $stmtInsert = $db->prepare("INSERT INTO system_settings (company_id, setting_key, setting_value, created_by) VALUES (?, 'sidebar_menu_order', ?, ?)");
            $stmtInsert->execute([$companyId, $orderJson, Session::get('user_id')]);
        }

        // Set migration version to 2 so system knows custom order is up to date
        $stmtVerChk = $db->prepare("SELECT id FROM system_settings WHERE company_id = ? AND setting_key = 'sidebar_menu_order_version' AND deleted_at IS NULL LIMIT 1");
        $stmtVerChk->execute([$companyId]);
        $existingVerId = $stmtVerChk->fetchColumn();
        if ($existingVerId) {
            $db->prepare("UPDATE system_settings SET setting_value = '2', updated_by = ?, updated_at = NOW() WHERE id = ?")->execute([Session::get('user_id'), $existingVerId]);
        } else {
            $db->prepare("INSERT INTO system_settings (company_id, setting_key, setting_value, created_by) VALUES (?, 'sidebar_menu_order_version', '2', ?)")->execute([$companyId, Session::get('user_id')]);
        }

        $response->json(['success' => true]);
    }

    /**
     * Save active WIP stages settings
     */
    public function saveWipStages(Request $request, Response $response): void {
        $companyId = Session::get('company_id');
        $activeStages = $request->get('active_stages') ?: [];
        $stagesJson = json_encode($activeStages);

        $db = Database::getInstance();
        $stmtCheck = $db->prepare("SELECT id FROM system_settings WHERE company_id = ? AND setting_key = 'active_production_stages' AND deleted_at IS NULL LIMIT 1");
        $stmtCheck->execute([$companyId]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            $stmtUpdate = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$stagesJson, Session::get('user_id'), $existingId]);
        } else {
            $stmtInsert = $db->prepare("INSERT INTO system_settings (company_id, setting_key, setting_value, created_by) VALUES (?, 'active_production_stages', ?, ?)");
            $stmtInsert->execute([$companyId, $stagesJson, Session::get('user_id')]);
        }

        Session::setFlash('success', 'Active WIP stages configuration saved successfully.');
        $this->redirect('company/settings');
    }

    /**
     * Retrieve company WIP master stages
     */
    public static function getCompanyWipStages(int $companyId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = 'active_production_stages' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->execute([$companyId]);
        $raw = $stmt->fetchColumn();

        $defaultStages = [
            ['key' => 'knitting', 'name' => 'Knitting'],
            ['key' => 'dyeing', 'name' => 'Dyeing'],
            ['key' => 'compacting', 'name' => 'Compacting'],
            ['key' => 'relaxing', 'name' => 'Relaxing'],
            ['key' => 'spreading', 'name' => 'Spreading'],
            ['key' => 'cutting', 'name' => 'Cutting'],
            ['key' => 'bundling', 'name' => 'Bundling'],
            ['key' => 'printing', 'name' => 'Printing'],
            ['key' => 'embroidery', 'name' => 'Embroidery'],
            ['key' => 'sewing', 'name' => 'Sewing'],
            ['key' => 'checking', 'name' => 'Checking / Trim'],
            ['key' => 'thread_cutting', 'name' => 'Thread Cutting'],
            ['key' => 'washing', 'name' => 'Washing'],
            ['key' => 'ironing', 'name' => 'Ironing / Pressing'],
            ['key' => 'packing', 'name' => 'Packing'],
            ['key' => 'carton_packing', 'name' => 'Carton Packing'],
            ['key' => 'shipment', 'name' => 'Shipment']
        ];

        if (!$raw) {
            return $defaultStages;
        }

        $decoded = json_decode(html_entity_decode($raw), true);
        if (!is_array($decoded) || empty($decoded)) {
            return $defaultStages;
        }

        $result = [];
        foreach ($decoded as $item) {
            if (is_string($item)) {
                $result[] = [
                    'key' => $item,
                    'name' => ucwords(str_replace('_', ' ', $item))
                ];
            } elseif (is_array($item) && isset($item['key'])) {
                $result[] = [
                    'key' => $item['key'],
                    'name' => $item['name'] ?? ucwords(str_replace('_', ' ', $item['key']))
                ];
            }
        }

        return $result;
    }

    /**
     * Add new WIP operational stage
     */
    public function addWipStage(Request $request, Response $response): void {
        $companyId = Session::get('company_id');
        $name = trim($request->get('stage_name'));
        $key = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', trim($request->get('stage_key') ?: $name))));

        if (empty($name) || empty($key)) {
            Session::setFlash('error', 'Stage Name and System Key are required.');
            $this->redirect('company/settings');
            return;
        }

        $stages = self::getCompanyWipStages($companyId);
        foreach ($stages as $s) {
            if ($s['key'] === $key) {
                Session::setFlash('error', "WIP Stage key '{$key}' already exists.");
                $this->redirect('company/settings');
                return;
            }
        }

        $stages[] = [
            'key' => $key,
            'name' => $name
        ];

        $this->saveWipStagesSetting($companyId, $stages);
        Session::setFlash('success', "New WIP stage '{$name}' added successfully.");
        $this->redirect('company/settings');
    }

    /**
     * Edit existing WIP operational stage
     */
    public function editWipStage(Request $request, Response $response): void {
        $companyId = Session::get('company_id');
        $targetKey = trim($request->get('original_key'));
        $name = trim($request->get('stage_name'));

        if (empty($targetKey) || empty($name)) {
            Session::setFlash('error', 'Stage Key and Name are required.');
            $this->redirect('company/settings');
            return;
        }

        $stages = self::getCompanyWipStages($companyId);
        $found = false;
        foreach ($stages as &$s) {
            if ($s['key'] === $targetKey) {
                $s['name'] = $name;
                $found = true;
                break;
            }
        }
        unset($s);

        if (!$found) {
            Session::setFlash('error', 'Specified WIP Stage not found.');
            $this->redirect('company/settings');
            return;
        }

        $this->saveWipStagesSetting($companyId, $stages);
        Session::setFlash('success', "WIP stage '{$name}' updated successfully.");
        $this->redirect('company/settings');
    }

    /**
     * Delete WIP operational stage
     */
    public function deleteWipStage(Request $request, Response $response, string $key): void {
        $companyId = Session::get('company_id');
        $targetKey = trim($key);

        $stages = self::getCompanyWipStages($companyId);
        $filtered = [];
        foreach ($stages as $s) {
            if ($s['key'] !== $targetKey) {
                $filtered[] = $s;
            }
        }

        $this->saveWipStagesSetting($companyId, array_values($filtered));
        Session::setFlash('success', 'WIP stage removed successfully.');
        $this->redirect('company/settings');
    }

    /**
     * Save WIP stages array into system_settings
     */
    private function saveWipStagesSetting(int $companyId, array $stages): void {
        $stagesJson = json_encode($stages);
        $db = Database::getInstance();
        
        $stmtCheck = $db->prepare("SELECT id FROM system_settings WHERE company_id = ? AND setting_key = 'active_production_stages' AND deleted_at IS NULL LIMIT 1");
        $stmtCheck->execute([$companyId]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            $stmtUpdate = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$stagesJson, Session::get('user_id'), $existingId]);
        } else {
            $stmtInsert = $db->prepare("INSERT INTO system_settings (company_id, setting_key, setting_value, created_by) VALUES (?, 'active_production_stages', ?, ?)");
            $stmtInsert->execute([$companyId, $stagesJson, Session::get('user_id')]);
        }
    }
}
