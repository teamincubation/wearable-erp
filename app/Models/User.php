<?php
namespace App\Models;

use App\Core\Model;

/**
 * User Entity Model
 * Developer & Database Architect - Antigravity
 */
class User extends Model {
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true; // Tenant isolation enabled

    public function findGlobalByEmail(string $email): ?array {
        $originalTenantScope = $this->isMultiTenant;
        $this->isMultiTenant = false;
        
        $user = $this->findOneBy(['email' => $email]);
        
        $this->isMultiTenant = $originalTenantScope;
        return $user;
    }

    /**
     * Search globally by email/username or by employee code
     */
    public function findGlobalByIdentifier(string $identifier): ?array {
        $originalTenantScope = $this->isMultiTenant;
        $this->isMultiTenant = false;
        
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE (email = ? OR employee_code = ?) AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch() ?: null;
        
        $this->isMultiTenant = $originalTenantScope;
        return $user;
    }

    /**
     * Retrieve all active company employees excluding super admin (role_id = 1)
     */
    public function getActiveCompanyEmployees(): array {
        $db = \App\Core\Database::getInstance();
        $companyId = \App\Core\Session::get('company_id');
        $stmt = $db->prepare("SELECT * FROM users WHERE company_id = ? AND role_id != 1 AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Retrieve all company employees excluding super admin (role_id = 1)
     */
    public function getAllCompanyEmployees(string $orderBy = 'id DESC'): array {
        $db = \App\Core\Database::getInstance();
        $companyId = \App\Core\Session::get('company_id');
        $stmt = $db->prepare("SELECT * FROM users WHERE company_id = ? AND role_id != 1 AND deleted_at IS NULL ORDER BY {$orderBy}");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Create a new company employee user with a hashed password
     */
    public function createEmployee(array $data): int {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);
        }
        return $this->insert($data);
    }
}
