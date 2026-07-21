<?php
namespace App\Core;

use PDO;
use Exception;

/**
 * Base MVC Model with Multi-Tenant Safety Boundaries
 * Database Architect - Antigravity
 */
class Model {
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    
    // Set to false for system-wide tables like subscription_plans or system_versions
    protected bool $isMultiTenant = true;

    // Set to false for tables that do not support soft deletes (e.g. audit_logs)
    protected bool $useSoftDeletes = true;

    // Active global company context set by TenantMiddleware
    protected static ?int $activeCompanyId = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Set the global active company tenant ID
     */
    public static function setActiveCompanyId(?int $companyId): void {
        self::$activeCompanyId = $companyId;
    }

    /**
     * Get the global active company tenant ID
     */
    public static function getActiveCompanyId(): ?int {
        return self::$activeCompanyId;
    }

    /**
     * Helper to prepare and execute standard statements with parameter binding
     */
    protected function execute(string $sql, array $params = []): \PDOStatement {
        // Enforce tenant isolation for raw queries if applicable
        if ($this->isMultiTenant && self::$activeCompanyId !== null) {
            // Very simple check: if company_id is not mentioned in raw SQL, alert or automatically append.
            // For safety, developers should use the built-in CRUD helpers below which handle this automatically.
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            $sessionVal = class_exists('\App\Core\Session') ? \App\Core\Session::get('company_id') : null;
            $msg = $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params) . " | ActiveTenant: " . var_export(self::$activeCompanyId, true) . " | Session company_id: " . var_export($sessionVal, true);
            throw new \PDOException($msg, (int)$e->getCode(), $e);
        }
    }

    /**
     * Build dynamic SQL condition for tenant-scoping
     */
    protected function applyTenantScope(string $sql, array &$params): string {
        if ($this->isMultiTenant) {
            $tenantId = self::$activeCompanyId ?? (class_exists('\App\Core\Session') ? \App\Core\Session::get('company_id') : null);
            if ($tenantId !== null) {
                $condition = "company_id = :active_tenant_id";
                $params['active_tenant_id'] = $tenantId;

                // Check if WHERE clause is already in the query
                if (stripos($sql, ' WHERE ') !== false) {
                    // Find where to inject
                    // Usually we append it at the end of the WHERE section
                    $sql = preg_replace('/(\bwhere\b)/i', '$1 ' . $condition . ' AND ', $sql, 1);
                } else {
                    // Append WHERE clause before ORDER BY, LIMIT, etc.
                    if (preg_match('/(\border by\b|\blimit\b)/i', $sql, $matches, PREG_OFFSET_CAPTURE)) {
                        $offset = $matches[0][1];
                        $sql = substr($sql, 0, $offset) . " WHERE " . $condition . " " . substr($sql, $offset);
                    } else {
                        $sql .= " WHERE " . $condition;
                    }
                }
            }
        }
        return $sql;
    }

    /**
     * Find a record by its primary key (automatically tenant-scoped)
     */
    public function find(mixed $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :primary_id" . ($this->useSoftDeletes ? " AND deleted_at IS NULL" : "");
        $params = ['primary_id' => $id];

        $sql = $this->applyTenantScope($sql, $params);
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all records (automatically tenant-scoped)
     */
    public function all(string $orderBy = 'id DESC'): array {
        $sql = "SELECT * FROM {$this->table}" . ($this->useSoftDeletes ? " WHERE deleted_at IS NULL" : "");
        $params = [];

        $sql = $this->applyTenantScope($sql, $params);
        $sql .= " ORDER BY {$orderBy}";

        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Find a single record matching specific criteria
     */
    public function findOneBy(array $criteria): ?array {
        $conditions = [];
        if ($this->useSoftDeletes) {
            $conditions[] = "deleted_at IS NULL";
        }
        $params = [];

        foreach ($criteria as $key => $value) {
            $conditions[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $sql = "SELECT * FROM {$this->table}";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql = $this->applyTenantScope($sql, $params);

        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all records matching specific criteria
     */
    public function findBy(array $criteria, string $orderBy = 'id DESC'): array {
        $conditions = [];
        if ($this->useSoftDeletes) {
            $conditions[] = "deleted_at IS NULL";
        }
        $params = [];

        foreach ($criteria as $key => $value) {
            $conditions[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $sql = "SELECT * FROM {$this->table}";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql = $this->applyTenantScope($sql, $params);
        $sql .= " ORDER BY {$orderBy}";

        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert a new record into the table
     */
    public function insert(array $data): int {
        // Automatically inject company_id if multi-tenant and active tenant context exists
        if ($this->isMultiTenant) {
            $tenantId = self::$activeCompanyId ?? (class_exists('\App\Core\Session') ? \App\Core\Session::get('company_id') : null);
            if ($tenantId !== null) {
                $data['company_id'] = $tenantId;
            }
        }

        // Set creation audit fields if present in table structure
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->execute($sql, $data);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing record
     */
    public function update(mixed $id, array $data): bool {
        // Set update audit fields
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $fields = [];
        $params = ['primary_id' => $id];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = :primary_id" . ($this->useSoftDeletes ? " AND deleted_at IS NULL" : ""),
            $this->table,
            implode(', ', $fields),
            $this->primaryKey
        );

        $sql = $this->applyTenantScope($sql, $params);
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Soft delete a record
     */
    public function delete(mixed $id, ?int $deletedBy = null): bool {
        if ($this->useSoftDeletes) {
            $data = [
                'deleted_at' => date('Y-m-d H:i:s')
            ];
            if ($deletedBy !== null) {
                $data['updated_by'] = $deletedBy;
            }
            return $this->update($id, $data);
        } else {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :primary_id";
            $params = ['primary_id' => $id];
            $sql = $this->applyTenantScope($sql, $params);
            $stmt = $this->execute($sql, $params);
            return $stmt->rowCount() > 0;
        }
    }
}
