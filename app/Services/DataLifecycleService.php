<?php
namespace App\Services;

use App\Core\Database;
use App\Models\AuditLog;
use App\Core\Auth;
use PDO;

class DataLifecycleService {

    /**
     * Delete all operational data for a tenant (Reset Tenant)
     * Preserves: companies, users, subscriptions, settings
     * Deletes bottom-up to avoid constraint errors if any exist.
     */
    public static function resetTenantData($companyId, $developerUserId) {
        $db = Database::getInstance();
        $pdo = $db->getPDO();
        
        try {
            $pdo->beginTransaction();

            $tablesToTruncate = [
                'batch_payments',
                'cik_history',
                'quality_inspections',
                'production_stage_logs',
                'production_orders',
                'inventory_transactions',
                'supplier_invoices',
                'grn_items',
                'grns',
                'purchase_order_items',
                'purchase_orders',
                'purchase_requisitions',
                'buyer_pos',
                'tech_packs',
                'cost_sheets',
                'style_variables',
                'styles',
                'tally_vouchers',
                'payroll_records',
                'employee_attendance',
                'employee_loans',
                'company_holidays',
                'taxes_gst',
                'bom_categories',
                'uoms',
                'contacts',
                'warehouse_types',
                'machines',
                'shifts',
                'departments',
                'warehouses',
                'branches',
                'designations',
                'payment_accounts',
            ];

            // Some tables might have foreign keys to each other.
            // Temporarily disable foreign key checks for this session to ensure clean wipe of operational data.
            // This is safe because we are wiping ALL operational data for this company_id.
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");

            foreach ($tablesToTruncate as $table) {
                // Check if company_id column exists just to be safe
                $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE 'company_id'");
                $stmt->execute();
                if ($stmt->fetch()) {
                    $delStmt = $pdo->prepare("DELETE FROM `{$table}` WHERE company_id = ?");
                    $delStmt->execute([$companyId]);
                }
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

            // Log this extreme action
            AuditLog::log('tenant_reset', 'company', $companyId, [
                'action' => 'All operational business data deleted by Developer',
                'developer_id' => $developerUserId
            ], $companyId);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            // Re-enable foreign key checks in case of error
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            error_log("Tenant Reset Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Soft delete a record in any table
     */
    public static function softDelete($table, $id, $companyId, $userId) {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("UPDATE `{$table}` SET deleted_at = NOW(), deleted_by = ? WHERE id = ? AND company_id = ?");
            $success = $stmt->execute([$userId, $id, $companyId]);
            if ($success) {
                AuditLog::log('soft_delete', $table, $id, ['deleted_by' => $userId], $companyId);
            }
            return $success;
        } catch (\Exception $e) {
            error_log("Soft Delete Error on {$table}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hard delete a record with centralized cascade logic
     */
    public static function hardDelete($entityType, $id, $companyId, $userId) {
        $db = Database::getInstance();
        $pdo = $db->getPDO();

        try {
            $pdo->beginTransaction();

            switch ($entityType) {
                case 'style':
                    self::cascadeStyle($pdo, $id, $companyId);
                    $pdo->prepare("DELETE FROM styles WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
                    break;
                case 'buyer_po':
                    self::cascadeBuyerPO($pdo, $id, $companyId);
                    $pdo->prepare("DELETE FROM buyer_pos WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
                    break;
                case 'production_order':
                    self::cascadeProductionOrder($pdo, $id, $companyId);
                    $pdo->prepare("DELETE FROM production_orders WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
                    break;
                case 'contact': // Buyer or Supplier
                    // Delete POs, Invoices, GRNs, etc. related to this contact
                    $pdo->prepare("DELETE FROM buyer_pos WHERE buyer_id = ? AND company_id = ?")->execute([$id, $companyId]);
                    $pdo->prepare("DELETE FROM purchase_orders WHERE supplier_id = ? AND company_id = ?")->execute([$id, $companyId]);
                    $pdo->prepare("DELETE FROM contacts WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
                    break;
                default:
                    // Generic delete if no special cascade rules apply
                    $table = $entityType; // Mapping could be improved
                    $pdo->prepare("DELETE FROM `{$table}` WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
                    break;
            }

            AuditLog::log('hard_delete', $entityType, $id, ['deleted_by' => $userId], $companyId);
            $pdo->commit();
            return true;

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Hard Delete Cascade Error on {$entityType}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cascade delete logic for Styles
     */
    private static function cascadeStyle($pdo, $styleId, $companyId) {
        // 1. Tech Packs
        $pdo->prepare("DELETE FROM tech_packs WHERE style_id = ? AND company_id = ?")->execute([$styleId, $companyId]);
        // 2. Cost Sheets
        $pdo->prepare("DELETE FROM cost_sheets WHERE style_id = ? AND company_id = ?")->execute([$styleId, $companyId]);
        // 3. Style Variables
        $pdo->prepare("DELETE FROM style_variables WHERE style_id = ?")->execute([$styleId]); // No company_id on mapping table if not present
        
        // 4. Buyer POs related to this Style
        $stmt = $pdo->prepare("SELECT id FROM buyer_pos WHERE style_id = ? AND company_id = ?");
        $stmt->execute([$styleId, $companyId]);
        $pos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($pos as $poId) {
            self::cascadeBuyerPO($pdo, $poId, $companyId);
            $pdo->prepare("DELETE FROM buyer_pos WHERE id = ? AND company_id = ?")->execute([$poId, $companyId]);
        }
    }

    /**
     * Cascade delete logic for Buyer POs
     */
    private static function cascadeBuyerPO($pdo, $poId, $companyId) {
        // Find related production orders
        $stmt = $pdo->prepare("SELECT id FROM production_orders WHERE po_id = ? AND company_id = ?");
        $stmt->execute([$poId, $companyId]);
        $batches = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($batches as $batchId) {
            self::cascadeProductionOrder($pdo, $batchId, $companyId);
            $pdo->prepare("DELETE FROM production_orders WHERE id = ? AND company_id = ?")->execute([$batchId, $companyId]);
        }
    }

    /**
     * Cascade delete logic for Production Orders
     */
    private static function cascadeProductionOrder($pdo, $batchId, $companyId) {
        // 1. Stage logs
        $pdo->prepare("DELETE FROM production_stage_logs WHERE production_order_id = ? AND company_id = ?")->execute([$batchId, $companyId]);
        // 2. Quality inspections
        $pdo->prepare("DELETE FROM quality_inspections WHERE production_order_id = ? AND company_id = ?")->execute([$batchId, $companyId]);
        // 3. Stock tracking (Inventory transactions related to this batch)
        $pdo->prepare("DELETE FROM inventory_transactions WHERE reference_type = 'production' AND reference_id = ? AND company_id = ?")->execute([$batchId, $companyId]);
    }
}
