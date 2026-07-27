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

        // Query available positive stock per warehouse for live stock transfer filtering
        $stmtStock = $db->prepare("
            SELECT warehouse_id, MAX(item_type) as item_type, item_name, SUM(quantity) as available_qty 
            FROM inventory_transactions 
            WHERE company_id = ? 
            GROUP BY warehouse_id, item_name 
            HAVING available_qty > 0
        ");
        $stmtStock->execute([$companyId]);
        $warehouseStockData = $stmtStock->fetchAll() ?: [];

        $this->renderView('company/inventory_ledger', [
            'title' => 'Stock Transaction Ledger | ERP',
            'transactions' => $transactions,
            'warehouses' => $warehouses,
            'categories' => $categories,
            'warehouseStockData' => $warehouseStockData
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
        $db = Database::getInstance();

        // Attach procurement history details to each stock item for the view modal
        foreach ($summary as &$item) {
            $stmtPoDetails = $db->prepare("
                SELECT po.po_no, po.status, c.name as supplier_name, acc.name as account_name, po.payment_date, w.name as warehouse_name,
                       poi.item_name, poi.item_type, poi.quantity, poi.unit_price, poi.total_price
                FROM purchase_order_items poi
                JOIN purchase_orders po ON poi.po_id = po.id
                JOIN contacts c ON po.supplier_id = c.id
                LEFT JOIN payment_accounts acc ON po.payment_account_id = acc.id
                LEFT JOIN warehouses w ON po.warehouse_id = w.id
                WHERE poi.company_id = ? AND poi.item_name = ? AND po.deleted_at IS NULL
                ORDER BY po.id DESC
            ");
            $stmtPoDetails->execute([$companyId, $item['item_name']]);
            $item['po_receipts'] = $stmtPoDetails->fetchAll() ?: [];
        }
        unset($item);

        $this->renderView('company/inventory_balances', [
            'title' => 'Inventory Balances | ERP',
            'summary' => $summary
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
        $db = Database::getInstance();

        // Enforce available stock quantity check before transfer execution
        $stmtAvail = $db->prepare("
            SELECT SUM(quantity) as available_qty 
            FROM inventory_transactions 
            WHERE company_id = ? AND warehouse_id = ? AND item_name = ?
        ");
        $stmtAvail->execute([$companyId, $fromWarehouseId, $itemName]);
        $availQty = (float)($stmtAvail->fetchColumn() ?: 0.00);

        if ($quantity > $availQty) {
            Session::setFlash('error', "Transfer Failed: Requested quantity (" . number_format($quantity, 2) . ") exceeds available stock (" . number_format($availQty, 2) . ") in source facility.");
            $this->redirect('company/inventory/ledger');
            return;
        }

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

        $barcodeText = "{$styleNo}-{$size}-{$bundleNo}";

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
