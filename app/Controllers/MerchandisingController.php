<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\CostSheet;
use App\Models\BuyerPo;
use App\Models\Style;
use App\Models\Contact;
use App\Models\AuditLog;

/**
 * Merchandising (Cost Sheets & Orders) Controller
 * Full Stack Developer - Antigravity
 */
class MerchandisingController extends Controller {
    /**
     * Cost Sheets List View
     */
    public function costsheets(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch cost sheets with style information
        $stmt = $db->prepare("SELECT cs.*, s.style_no, s.name as style_name 
                             FROM cost_sheets cs
                             JOIN styles s ON cs.style_id = s.id
                             WHERE cs.company_id = ? AND cs.deleted_at IS NULL
                             ORDER BY cs.id DESC");
        $stmt->execute([$companyId]);
        $costsheets = $stmt->fetchAll() ?: [];

        // Fetch styles for the dropdown
        $styleModel = new Style();
        $styles = $styleModel->all();

        $this->renderView('company/costsheets', [
            'title' => 'Cost Sheets | Merchandising',
            'costsheets' => $costsheets,
            'styles' => $styles
        ]);
    }

    /**
     * Create Cost Sheet Estimate
     */
    public function createCostsheet(Request $request, Response $response): void {
        $styleId = (int)$request->get('style_id');
        $costSheetNo = trim($request->get('cost_sheet_no'));
        $yarnCost = (float)$request->get('yarn_cost');
        $fabricCost = (float)$request->get('fabric_cost');
        $processingCost = (float)$request->get('processing_cost');
        $accessoriesCost = (float)$request->get('accessories_cost');
        $packingCost = (float)$request->get('packing_cost');
        $marginPercentage = (float)$request->get('margin_percentage');

        if (empty($styleId) || empty($costSheetNo)) {
            Session::setFlash('error', 'Style selection and Cost Sheet Number are required.');
            $this->redirect('company/merchandising/costsheets');
        }

        // Calculate total cost
        $subtotal = $yarnCost + $fabricCost + $processingCost + $accessoriesCost + $packingCost;
        $totalCost = $subtotal * (1 + ($marginPercentage / 100));

        $costSheetModel = new CostSheet();
        $costSheetId = $costSheetModel->insert([
            'style_id' => $styleId,
            'cost_sheet_no' => $costSheetNo,
            'yarn_cost' => $yarnCost,
            'fabric_cost' => $fabricCost,
            'processing_cost' => $processingCost,
            'accessories_cost' => $accessoriesCost,
            'packing_cost' => $packingCost,
            'margin_percentage' => $marginPercentage,
            'total_cost' => $totalCost,
            'status' => 'active',
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_costsheet', 'CostSheet', $costSheetId, null, null, "Created cost sheet estimate: {$costSheetNo}");
        Session::setFlash('success', 'Cost Sheet estimate generated successfully.');
        $this->redirect('company/merchandising/costsheets');
    }

    /**
     * Edit Cost Sheet Estimate
     */
    public function editCostsheet(Request $request, Response $response, string $id): void {
        $styleId = (int)$request->get('style_id');
        $costSheetNo = trim($request->get('cost_sheet_no'));
        $yarnCost = (float)$request->get('yarn_cost');
        $fabricCost = (float)$request->get('fabric_cost');
        $processingCost = (float)$request->get('processing_cost');
        $accessoriesCost = (float)$request->get('accessories_cost');
        $packingCost = (float)$request->get('packing_cost');
        $marginPercentage = (float)$request->get('margin_percentage');

        if (empty($styleId) || empty($costSheetNo)) {
            Session::setFlash('error', 'Style selection and Cost Sheet Number are required.');
            $this->redirect('company/merchandising/costsheets');
        }

        // Calculate total cost
        $subtotal = $yarnCost + $fabricCost + $processingCost + $accessoriesCost + $packingCost;
        $totalCost = $subtotal * (1 + ($marginPercentage / 100));

        $costSheetModel = new CostSheet();
        $costSheet = $costSheetModel->find($id);
        if (!$costSheet) {
            Session::setFlash('error', 'Cost Sheet not found.');
            $this->redirect('company/merchandising/costsheets');
        }

        $costSheetModel->update($id, [
            'style_id' => $styleId,
            'cost_sheet_no' => $costSheetNo,
            'yarn_cost' => $yarnCost,
            'fabric_cost' => $fabricCost,
            'processing_cost' => $processingCost,
            'accessories_cost' => $accessoriesCost,
            'packing_cost' => $packingCost,
            'margin_percentage' => $marginPercentage,
            'total_cost' => $totalCost,
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_costsheet', 'CostSheet', $id, null, null, "Updated cost sheet estimate: {$costSheetNo}");
        Session::setFlash('success', 'Cost Sheet estimate updated successfully.');
        $this->redirect('company/merchandising/costsheets');
    }

    /**
     * Buyer POs Manager View
     */
    public function buyerpos(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch POs with style and buyer names
        $stmt = $db->prepare("SELECT po.*, s.style_no, s.name as style_name, c.name as buyer_name 
                             FROM buyer_pos po
                             JOIN styles s ON po.style_id = s.id
                             JOIN contacts c ON po.buyer_id = c.id
                             WHERE po.company_id = ? AND po.deleted_at IS NULL
                             ORDER BY po.id DESC");
        $stmt->execute([$companyId]);
        $orders = $stmt->fetchAll() ?: [];

        // Fetch buyers (contacts of type 'buyer')
        $contactModel = new Contact();
        $buyers = $contactModel->findBy(['type' => 'buyer', 'status' => 'active']);

        // Fetch styles and attach Tech Pack BOM status & current stock details
        $inventoryService = new \App\Services\InventoryService();
        $styleModel = new Style();
        $styles = $styleModel->all() ?: [];
        foreach ($styles as &$s) {
            $stmtTp = $db->prepare("SELECT bom_json FROM tech_packs WHERE style_id = ? AND deleted_at IS NULL LIMIT 1");
            $stmtTp->execute([$s['id']]);
            $bomJson = $stmtTp->fetchColumn();
            $bomItems = json_decode($bomJson ?: '[]', true) ?: [];

            $s['has_techpack'] = !empty($bomItems);

            foreach ($bomItems as &$bItem) {
                $cType = !empty($bItem['item_type']) ? $bItem['item_type'] : 'Accessories';
                $iName = !empty($bItem['item_name']) ? $bItem['item_name'] : '';
                $bItem['current_stock'] = $inventoryService->getStockLevel($companyId, $cType, $iName);
            }
            unset($bItem);

            $s['bom_items'] = $bomItems;
        }
        unset($s);

        $this->renderView('company/buyerpos', [
            'title' => 'Buyer Purchase Orders | Merchandising',
            'orders' => $orders,
            'buyers' => $buyers,
            'styles' => $styles
        ]);
    }

    /**
     * Create Buyer Purchase Order (PO)
     */
    public function createBuyerpo(Request $request, Response $response): void {
        $buyerId = (int)$request->get('buyer_id');
        $styleId = (int)$request->get('style_id');
        $poNo = trim($request->get('po_no'));
        $poDate = $request->get('po_date');
        $deliveryDate = $request->get('delivery_date');
        $quantity = (int)$request->get('quantity');
        $unitPrice = (float)$request->get('unit_price');

        if (empty($buyerId) || empty($styleId) || empty($poNo) || empty($poDate) || empty($deliveryDate) || $quantity <= 0 || $unitPrice <= 0) {
            Session::setFlash('error', 'All fields are required. Quantity and price must be greater than zero.');
            $this->redirect('company/merchandising/buyerpos');
            return;
        }

        // Validate size quantities
        $sizeQtys = $request->get('size_qty') ?: [];
        $totalSizeQty = 0;
        foreach ($sizeQtys as $sz => $q) {
            $totalSizeQty += (int)$q;
        }

        if ($totalSizeQty > $quantity) {
            Session::setFlash('error', "The sum of size quantities ({$totalSizeQty}) cannot exceed the total order quantity ({$quantity}).");
            $this->redirect('company/merchandising/buyerpos');
            return;
        }

        $sizesJson = json_encode($sizeQtys);
        $companyId = Session::get('company_id');

        // Ensure database table has sizes_json column
        $db = Database::getInstance();
        try {
            $db->query("SELECT sizes_json FROM buyer_pos LIMIT 1");
        } catch (\Exception $e) {
            $db->exec("ALTER TABLE buyer_pos ADD COLUMN sizes_json JSON DEFAULT NULL AFTER total_amount");
        }

        // Calculate total amount
        $totalAmount = $quantity * $unitPrice;

        $buyerPoModel = new BuyerPo();
        $poId = $buyerPoModel->insert([
            'buyer_id' => $buyerId,
            'style_id' => $styleId,
            'po_no' => $poNo,
            'po_date' => $poDate,
            'delivery_date' => $deliveryDate,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'sizes_json' => $sizesJson,
            'status' => 'draft',
            'created_by' => Session::get('user_id')
        ]);

        // Record material allocations based on Style Tech Pack BOM
        $stmtTp = $db->prepare("SELECT bom_json FROM tech_packs WHERE style_id = ? AND deleted_at IS NULL LIMIT 1");
        $stmtTp->execute([$styleId]);
        $bomJson = $stmtTp->fetchColumn();
        $bomItems = json_decode($bomJson ?: '[]', true) ?: [];

        if (!empty($bomItems)) {
            $inventoryService = new \App\Services\InventoryService();
            
            // Find active receiving/dispatch warehouse
            $stmtWh = $db->prepare("SELECT id FROM warehouses WHERE company_id = ? AND status = 'active' ORDER BY id ASC LIMIT 1");
            $stmtWh->execute([$companyId]);
            $warehouseId = (int)($stmtWh->fetchColumn() ?: 1);

            foreach ($bomItems as $bItem) {
                $cType = !empty($bItem['item_type']) ? $bItem['item_type'] : 'Accessories';
                $iName = !empty($bItem['item_name']) ? $bItem['item_name'] : '';
                $qtyPerPc = (float)($bItem['qty'] ?? 0.00);

                if (!empty($iName) && $qtyPerPc > 0) {
                    $requiredStock = $quantity * $qtyPerPc;
                    $inventoryService->recordTransaction(
                        $companyId,
                        $warehouseId,
                        $cType,
                        $iName,
                        -1 * $requiredStock,
                        'out',
                        'buyer_po',
                        $poId,
                        'PO-' . $poNo,
                        0.00,
                        Session::get('user_id'),
                        $bItem['bom_code'] ?? null
                    );
                }
            }
        }

        AuditLog::log($companyId, Session::get('user_id'), 'create_buyer_po', 'BuyerPo', $poId, null, null, "Created buyer PO: {$poNo}");
        Session::setFlash('success', 'Buyer Purchase Order created successfully and material requirements allocated.');
        $this->redirect('company/merchandising/buyerpos');
    }

    /**
     * Approve Buyer Purchase Order
     */
    public function approveBuyerpo(Request $request, Response $response, string $id): void {
        $buyerPoModel = new BuyerPo();
        $order = $buyerPoModel->find($id);

        if (!$order) {
            Session::setFlash('error', 'Order not found.');
            $this->redirect('company/merchandising/buyerpos');
        }

        $buyerPoModel->update($id, [
            'status' => 'approved',
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'approve_buyer_po', 'BuyerPo', (int)$id, null, null, "Approved buyer PO: {$order['po_no']}");
        Session::setFlash('success', 'Purchase Order approved successfully. Order is now active for production planning.');
        $this->redirect('company/merchandising/buyerpos');
    }

    public function deleteCostSheet(Request $request, Response $response, string $id): void {
        $model = new CostSheet();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Cost Sheet record deleted successfully.');
        $this->redirect('company/merchandising/costsheets');
    }

    public function deleteBuyerPo(Request $request, Response $response, string $id): void {
        $model = new BuyerPo();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Buyer Purchase Order deleted successfully.');
        $this->redirect('company/merchandising/buyerpos');
    }
}
