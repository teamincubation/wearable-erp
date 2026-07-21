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

    /**
     * Bypasses the multi-tenant scope to search for users globally (e.g., during general login)
     */
    public function findGlobalByEmail(string $email): ?array {
        $originalTenantScope = $this->isMultiTenant;
        $this->isMultiTenant = false;
        
        $user = $this->findOneBy(['email' => $email]);
        
        $this->isMultiTenant = $originalTenantScope;
        return $user;
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
