<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\InventoryTransaction;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Models\AuditLog;

/**
 * Inventory Stock Ledger Operations Controller
 * Full Stack Developer - Antigravity
 */
class InventoryController extends Controller {
    /**
     * View Detailed Stock Transactions Ledger
     */
    public function ledger(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch ledger entries with warehouse name
        $stmt = $db->prepare("SELECT it.*, w.name as warehouse_name 
                             FROM inventory_transactions it
                             JOIN warehouses w ON it.warehouse_id = w.id
                             WHERE it.company_id = ?
                             ORDER BY it.id DESC LIMIT 100");
        $stmt->execute([$companyId]);
        $transactions = $stmt->fetchAll() ?: [];

        // Fetch warehouses
        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->all();

        // Fetch BOM categories
        $stmtCat = $db->prepare("SELECT * FROM bom_categories WHERE company_id = ? AND deleted_at IS NULL ORDER BY name ASC");
        $stmtCat->execute([$companyId]);
        $categories = $stmtCat->fetchAll() ?: [];

        $this->renderView('company/inventory_ledger', [
            'title' => 'Stock Transaction Ledger | ERP',
            'transactions' => $transactions,
            'warehouses' => $warehouses,
            'categories' => $categories
        ]);
    }

    /**
     * View Inventory Balances Overview
     */
    public function balances(Request $request, Response $response): void {
        $companyId = Session::get('company_id');
        $inventoryService = new InventoryService();

        // Get summaries
        $summary = $inventoryService->getInventorySummary($companyId);

        // Fetch GRN Completed Purchase Orders from Procurement
        $db = Database::getInstance();
        $stmtPO = $db->prepare(
            "SELECT po.*, c.name as supplier_name, acc.name as account_name
             FROM purchase_orders po
             JOIN contacts c ON po.supplier_id = c.id
             LEFT JOIN payment_accounts acc ON po.payment_account_id = acc.id
             WHERE po.company_id = ? AND po.status = 'grn_completed' AND po.deleted_at IS NULL
             ORDER BY po.payment_date DESC, po.id DESC"
        );
        $stmtPO->execute([$companyId]);
        $grnCompletedPOs = $stmtPO->fetchAll() ?: [];

        // Load items for each PO
        foreach ($grnCompletedPOs as &$po) {
            $stmtItems = $db->prepare("SELECT item_name, item_type, quantity, unit_price FROM purchase_order_items WHERE po_id = ?");
            $stmtItems->execute([$po['id']]);
            $po['items'] = $stmtItems->fetchAll() ?: [];
        }

        $this->renderView('company/inventory_balances', [
            'title' => 'Inventory Balances | ERP',
            'summary' => $summary,
            'grnCompletedPOs' => $grnCompletedPOs
        ]);
    }

    /**
     * Execute Stock Transfer between Warehouses
     */
    public function transfer(Request $request, Response $response): void {
        $fromWarehouseId = (int)$request->get('from_warehouse_id');
        $toWarehouseId = (int)$request->get('to_warehouse_id');
        $itemType = $request->get('item_type');
        $itemName = trim($request->get('item_name'));
        $quantity = (float)$request->get('quantity');
        $batchNo = trim($request->get('batch_no'));

        if (empty($fromWarehouseId) || empty($toWarehouseId) || empty($itemType) || empty($itemName) || $quantity <= 0) {
            Session::setFlash('error', 'All fields are required. Transfer quantity must be positive.');
            $this->redirect('company/inventory/ledger');
        }

        if ($fromWarehouseId === $toWarehouseId) {
            Session::setFlash('error', 'Source and destination warehouses cannot be the same.');
            $this->redirect('company/inventory/ledger');
        }

        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $inventoryService = new InventoryService();

        try {
            $inventoryService->transferStock(
                $companyId,
                $fromWarehouseId,
                $toWarehouseId,
                $itemType,
                $itemName,
                $quantity,
                $batchNo,
                0.00,
                $userId
            );

            AuditLog::log($companyId, $userId, 'stock_transfer', 'InventoryTransaction', null, null, null, "Transferred {$quantity} {$itemType} from warehouse {$fromWarehouseId} to {$toWarehouseId}");
            Session::setFlash('success', 'Stock transfer completed successfully.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Transfer Failed: ' . $e->getMessage());
        }

        $this->redirect('company/inventory/ledger');
    }

    /**
     * Render cutting ticket barcode view
     */
    public function barcode(Request $request, Response $response): void {
        $styleNo = $request->get('style_no') ?: 'STY-1001';
        $size = $request->get('size') ?: 'M';
        $bundleNo = $request->get('bundle_no') ?: 'BDL-001';
        $qty = $request->get('qty') ?: '50';

        $barcodeText = "TOCCO-{$styleNo}-{$size}-{$bundleNo}";

        $this->renderView('company/barcode', [
            'title' => 'Barcode Ticket | ERP',
            'style_no' => $styleNo,
            'size' => $size,
            'bundle_no' => $bundleNo,
            'qty' => $qty,
            'barcode_text' => $barcodeText
        ]);
    }

    public function deleteTransaction(Request $request, Response $response, string $id): void {
        $model = new InventoryTransaction();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Inventory transaction record deleted successfully.');
        $this->redirect('company/inventory/ledger');
    }
}
