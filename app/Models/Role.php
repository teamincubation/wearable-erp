<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Role & Permission Model
 * ERP Solution Architect - Antigravity
 */
class Role extends Model {
    protected string $table = 'roles';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true; // Tenant isolation enabled
    protected bool $useSoftDeletes = true; // Soft deletes enabled

    /**
     * Retrieve all permission records mapped to a role
     */
    public function getPermissions(int $roleId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT p.* FROM permissions p
             INNER JOIN role_permissions rp ON p.id = rp.permission_id
             WHERE rp.role_id = ?"
        );
        $stmt->execute([$roleId]);
        return $stmt->fetchAll();
    }

    /**
     * Sync permissions to role: deletes existing mapping and assigns new ones
     */
    public function syncPermissions(int $roleId, array $permissionIds): void {
        $db = Database::getInstance();
        
        // Remove existing associations
        $stmt = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$roleId]);

        // Insert new ones
        if (!empty($permissionIds)) {
            $stmtInsert = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($permissionIds as $permId) {
                $stmtInsert->execute([$roleId, (int) $permId]);
            }
        }
    }
}
