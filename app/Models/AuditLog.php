<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Activity and Audit Logs Model
 * Full Stack PHP Engineer & DevOps - Antigravity
 */
class AuditLog extends Model {
    protected string $table = 'audit_logs';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true; // Tenant isolation enabled
    protected bool $useSoftDeletes = false; // Audit logs do not have deleted_at column

    /**
     * Log a security or transaction activity statically
     */
    public static function log(
        ?int $companyId,
        ?int $userId,
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): void {
        try {
            $db = Database::getInstance();
            $sql = "INSERT INTO audit_logs (
                        company_id, user_id, action, model_type, model_id, 
                        old_values, new_values, description, ip_address, user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $companyId,
                $userId,
                $action,
                $modelType,
                $modelId,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            // Silently capture logging exception to prevent main flow interruption
        }
    }
}
