<?php
namespace App\Models;

use App\Core\Model;

/**
 * Tenant Company Model
 * Full Stack PHP Engineer - Antigravity
 */
class Company extends Model {
    protected string $table = 'companies';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = false; // Global table, no company_id scoping

    /**
     * Fetch active subscription plan details for the company
     */
    public function getSubscriptionPlan(int $companyId): ?array {
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare(
            "SELECT c.*, p.name as plan_name, p.max_users, p.max_branches, p.max_storage_mb, p.api_access 
             FROM companies c
             LEFT JOIN subscription_plans p ON c.subscription_plan_id = p.id
             WHERE c.id = ? AND c.deleted_at IS NULL"
        );
        $stmt->execute([$companyId]);
        return $stmt->fetch() ?: null;
    }
}
