<?php
namespace App\Services;

use App\Core\Database;
use Exception;
use PDO;

/**
 * Reusable Inventory Ledger & Stock Movement Service
 * Senior Solution Architect - Antigravity
 */
class InventoryService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Record a transaction in the stock ledger
     */
    public function recordTransaction(
        int $companyId,
        int $warehouseId,
        string $itemType,
        string $itemName,
        float $quantity, // Positive for IN, Negative for OUT
        string $type, // 'in', 'out', 'transfer', 'adjustment'
        string $referenceType, // 'grn', 'production', 'transfer', 'adjustment'
        ?int $referenceId = null,
        ?string $batchNo = null,
        float $unitPrice = 0.00,
        ?int $userId = null,
        ?string $bomCode = null
    ): int {
        $sql = "INSERT INTO inventory_transactions (
                    company_id, warehouse_id, item_type, bom_code, item_name, 
                    quantity, type, reference_type, reference_id, 
                    batch_no, unit_price, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $companyId,
            $warehouseId,
            $itemType,
            $bomCode,
            $itemName,
            $quantity,
            $type,
            $referenceType,
            $referenceId,
            $batchNo,
            $unitPrice,
            $userId
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get the current stock balance of a specific item in a warehouse
     */
    public function getStockLevel(int $companyId, string $itemType, string $itemName, ?int $warehouseId = null): float {
        $sql = "SELECT SUM(quantity) as balance 
                FROM inventory_transactions 
                WHERE company_id = ? AND item_type = ? AND item_name = ?";
        
        $params = [$companyId, $itemType, $itemName];

        if ($warehouseId !== null) {
            $sql .= " AND warehouse_id = ?";
            $params[] = $warehouseId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return (float) ($result['balance'] ?? 0.00);
    }

    /**
     * Get all active stock list in a company
     */
    public function getInventorySummary(int $companyId, ?int $warehouseId = null): array {
        $sql = "SELECT item_type, item_name, 
                       SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as total_received,
                       SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as total_used,
                       SUM(quantity) as current_balance,
                       COALESCE(NULLIF(AVG(CASE WHEN quantity > 0 AND unit_price > 0 THEN unit_price ELSE NULL END), 0), AVG(unit_price), 0) as avg_price
                FROM inventory_transactions 
                WHERE company_id = ?";
        $params = [$companyId];

        if ($warehouseId !== null) {
            $sql .= " AND warehouse_id = ?";
            $params[] = $warehouseId;
        }

        $sql .= " GROUP BY item_type, item_name HAVING current_balance > 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Execute a stock transfer between warehouses
     */
    public function transferStock(
        int $companyId,
        int $fromWarehouseId,
        int $toWarehouseId,
        string $itemType,
        string $itemName,
        float $quantity,
        ?string $batchNo = null,
        float $unitPrice = 0.00,
        ?int $userId = null
    ): void {
        if ($quantity <= 0) {
            throw new Exception("Transfer quantity must be greater than zero.");
        }

        // Check if sufficient stock is available in fromWarehouseId
        $available = $this->getStockLevel($companyId, $itemType, $itemName, $fromWarehouseId);
        if ($available < $quantity) {
            throw new Exception("Insufficient stock in the source warehouse. Available: $available, Request: $quantity");
        }

        // Record stock OUT from source
        $this->recordTransaction(
            $companyId,
            $fromWarehouseId,
            $itemType,
            $itemName,
            -$quantity,
            'transfer',
            'transfer',
            null,
            $batchNo,
            $unitPrice,
            $userId
        );

        // Record stock IN to destination
        $this->recordTransaction(
            $companyId,
            $toWarehouseId,
            $itemType,
            $itemName,
            $quantity,
            'transfer',
            'transfer',
            null,
            $batchNo,
            $unitPrice,
            $userId
        );
    }
}
