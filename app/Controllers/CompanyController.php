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
        $userModel = new User();
        $users = $userModel->all();

        $roleModel = new Role();
        $roles = $roleModel->all();

        $this->renderView('company/users', [
            'title' => 'Employee Manager | ERP',
            'users' => $users,
            'roles' => $roles
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

        // Ensure email/username uniqueness globally
        $existing = $userModel->findGlobalByIdentifier($email);
        if ($existing) {
            Session::setFlash('error', 'This Email/Username is already registered.');
            $this->redirect('company/users');
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

        $name = $request->get('name');
        $email = trim($request->get('email'));
        $employeeCode = trim($request->get('employee_code'));
        $phone = $request->get('phone');
        $roleId = (int) $request->get('role_id');
        $status = $request->get('status');
        $password = $request->get('password');
        $baseSalary = (float)$request->get('base_salary');

        if (empty($name) || empty($email) || empty($employeeCode) || empty($roleId)) {
            Session::setFlash('error', 'Name, Email/Username, Employee ID, and Role are required.');
            $this->redirect('company/users');
        }

        $db = Database::getInstance();

        // Ensure Employee ID uniqueness (excluding current user)
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE company_id = ? AND employee_code = ? AND id != ? AND deleted_at IS NULL LIMIT 1");
        $stmtCheck->execute([Session::get('company_id'), $employeeCode, $id]);
        if ($stmtCheck->fetch()) {
            Session::setFlash('error', 'This Employee ID is already registered for another user in this company.');
            $this->redirect('company/users');
        }

        // Ensure Email/Username uniqueness (excluding current user)
        $stmtCheckEmail = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL LIMIT 1");
        $stmtCheckEmail->execute([$email, $id]);
        if ($stmtCheckEmail->fetch()) {
            Session::setFlash('error', 'This Email/Username is already registered.');
            $this->redirect('company/users');
        }

        $updates = [
            'name' => $name,
            'email' => $email,
            'employee_code' => $employeeCode,
            'phone' => $phone,
            'role_id' => $roleId,
            'status' => $status,
            'base_salary' => $baseSalary,
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

    /**
     * Custom Roles & Permission Manager List
     */
    public function roles(Request $request, Response $response): void {
        $roleModel = new Role();
        $roles = $roleModel->all();

        // Load permissions list
        $db = Database::getInstance();
        $permissions = $db->query("SELECT * FROM permissions WHERE module = 'tenant'")->fetchAll();

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
     * View Company-level Audit Trails
     */
    public function logs(Request $request, Response $response): void {
        $auditModel = new AuditLog();
        // The query is automatically tenant-scoped to the active company ID
        $logs = $auditModel->all('id DESC LIMIT 100');

        // Since we need the user names as well, query with join securely:
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT a.*, u.name as user_name FROM audit_logs a 
             LEFT JOIN users u ON a.user_id = u.id 
             WHERE a.company_id = ?
             ORDER BY a.id DESC LIMIT 100"
        );
        $stmt->execute([Session::get('company_id')]);
        $logs = $stmt->fetchAll();

        $this->renderView('company/logs', [
            'title' => 'Audit Trails | ERP',
            'logs' => $logs
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

        $this->renderView('company/settings', [
            'title' => 'Company Settings | ERP',
            'company' => $company,
            'settings' => $settings
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
            'timezone' => $request->get('timezone') ?: 'Asia/Kolkata',
            'mfa_required' => $request->get('mfa_required') ? '1' : '0'
        ];

        foreach ($settings as $key => $val) {
            $stmt = $db->prepare(
                "INSERT INTO system_settings (company_id, setting_key, setting_value, updated_by) 
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)"
            );
            $stmt->execute([$companyId, $key, $val, Session::get('user_id')]);
        }

        AuditLog::log($companyId, Session::get('user_id'), 'update_company_settings', 'Company', $companyId, null, null, "Updated company profile card & settings.");
        Session::setFlash('success', 'Company profile and settings updated successfully.');
        $this->redirect('company/settings');
    }
}
