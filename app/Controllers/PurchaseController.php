<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseOrder;
use App\Models\Grn;
use App\Models\Style;
use App\Models\BuyerPo;
use App\Models\Contact;
use App\Models\Warehouse;
use App\Models\BomCategory;
use App\Services\InventoryService;
use App\Models\AuditLog;

/**
 * Procurement (Purchase Requisitions, Supplier POs, & GRN) Controller
 * Full Stack Developer - Antigravity
 */
class PurchaseController extends Controller {
    /**
     * Purchase Requisitions List View
     */
    public function requisitions(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch requisitions joined with style & buyer PO
        $stmt = $db->prepare("SELECT pr.*, s.style_no, s.name as style_name, po.po_no as buyer_po_no
                             FROM purchase_requisitions pr
                             JOIN styles s ON pr.style_id = s.id
                             LEFT JOIN buyer_pos po ON pr.po_id = po.id
                             WHERE pr.company_id = ? AND pr.deleted_at IS NULL
                             ORDER BY pr.id DESC");
        $stmt->execute([$companyId]);
        $requisitions = $stmt->fetchAll() ?: [];

        // Fetch styles & buyer POs
        $styleModel = new Style();
        $styles = $styleModel->all();

        $stmt = $db->prepare("
            SELECT po.id, po.po_no, c.name as buyer_name, c.code as buyer_code, c.brand_name
            FROM buyer_pos po
            JOIN contacts c ON po.buyer_id = c.id
            WHERE po.company_id = ? AND po.status IN ('approved', 'active') AND c.status = 'active' AND po.deleted_at IS NULL AND c.deleted_at IS NULL
            ORDER BY po.id DESC
        ");
        $stmt->execute([$companyId]);
        $buyerPos = $stmt->fetchAll() ?: [];

        $this->renderView('company/requisitions', [
            'title' => 'Purchase Requisitions | Procurement',
            'requisitions' => $requisitions,
            'styles' => $styles,
            'buyer_pos' => $buyerPos
        ]);
    }

    /**
     * Create Material Requisition
     */
    public function createRequisition(Request $request, Response $response): void {
        $styleId = (int)$request->get('style_id');
        $poId = $request->get('po_id') ? (int)$request->get('po_id') : null;
        $reqNo = trim($request->get('requisition_no'));
        $date = $request->get('date');

        if (empty($styleId) || empty($reqNo) || empty($date)) {
            Session::setFlash('error', 'Style selection, Requisition Number, and Date are required.');
            $this->redirect('company/purchase/requisitions');
        }

        $requisitionModel = new PurchaseRequisition();
        $reqId = $requisitionModel->insert([
            'style_id' => $styleId,
            'po_id' => $poId,
            'requisition_no' => $reqNo,
            'date' => $date,
            'status' => 'draft',
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_requisition', 'PurchaseRequisition', $reqId, null, null, "Created purchase requisition: {$reqNo}");
        Session::setFlash('success', 'Purchase Requisition created successfully.');
        $this->redirect('company/purchase/requisitions');
    }

    /**
     * Supplier Purchase Orders List View
     */
    public function orders(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch supplier POs with supplier details
        $stmt = $db->prepare("SELECT po.*, c.name as supplier_name 
                             FROM purchase_orders po
                             JOIN contacts c ON po.supplier_id = c.id
                             WHERE po.company_id = ? AND po.deleted_at IS NULL
                             ORDER BY po.id DESC");
        $stmt->execute([$companyId]);
        $orders = $stmt->fetchAll() ?: [];

        // Fetch suppliers (contacts of type 'supplier')
        $contactModel = new Contact();
        $suppliers = $contactModel->findBy(['type' => 'supplier', 'status' => 'active']);

        $bomCatModel = new BomCategory();
        $categories = $bomCatModel->all();

        $this->renderView('company/purchaseorders', [
            'title' => 'Supplier Purchase Orders | Procurement',
            'orders' => $orders,
            'suppliers' => $suppliers,
            'categories' => $categories
        ]);
    }

    /**
     * Create Supplier Purchase Order & Items
     */
    public function createOrder(Request $request, Response $response): void {
        $supplierId = (int)$request->get('supplier_id');
        $poNo = trim($request->get('po_no'));
        $date = $request->get('date');
        
        $itemNames = $request->get('item_name') ?: [];
        $itemTypes = $request->get('item_type') ?: [];
        $quantities = $request->get('quantity') ?: [];
        $prices = $request->get('unit_price') ?: [];

        if (empty($supplierId) || empty($poNo) || empty($date) || empty($itemNames)) {
            Session::setFlash('error', 'Supplier details, PO Number, and at least one item are required.');
            $this->redirect('company/purchase/orders');
        }

        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        try {
            $db->beginTransaction();

            $poModel = new PurchaseOrder();
            $poId = $poModel->insert([
                'supplier_id' => $supplierId,
                'po_no' => $poNo,
                'date' => $date,
                'total_amount' => 0.00, // Updated later
                'status' => 'active',
                'created_by' => Session::get('user_id')
            ]);

            $totalAmount = 0.00;
            for ($i = 0; $i < count($itemNames); $i++) {
                if (!empty($itemNames[$i])) {
                    $qty = (float)($quantities[$i] ?? 0);
                    $price = (float)($prices[$i] ?? 0);
                    $total = $qty * $price;
                    $totalAmount += $total;

                    $stmt = $db->prepare("INSERT INTO purchase_order_items (
                                            company_id, po_id, item_type, item_name, quantity, unit_price, total_price, created_at
                                        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $companyId,
                        $poId,
                        $itemTypes[$i] ?? 'accessories',
                        trim($itemNames[$i]),
                        $qty,
                        $price,
                        $total
                    ]);
                }
            }

            // Update PO total amount
            $poModel->update($poId, [
                'total_amount' => $totalAmount
            ]);

            $db->commit();

            AuditLog::log($companyId, Session::get('user_id'), 'create_supplier_po', 'PurchaseOrder', $poId, null, null, "Created supplier purchase order: {$poNo}");
            Session::setFlash('success', 'Supplier Purchase Order created and saved successfully.');
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Failed to save Supplier PO: ' . $e->getMessage());
        }

        $this->redirect('company/purchase/orders');
    }

    /**
     * Goods Receipt Notes (GRN) View
     */
    public function grns(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch GRNs
        $stmt = $db->prepare("SELECT g.*, po.po_no, c.name as supplier_name 
                             FROM grns g
                             LEFT JOIN purchase_orders po ON g.po_id = po.id
                             LEFT JOIN contacts c ON po.supplier_id = c.id
                             WHERE g.company_id = ? AND g.deleted_at IS NULL
                             ORDER BY g.id DESC");
        $stmt->execute([$companyId]);
        $grns = $stmt->fetchAll() ?: [];

        // Fetch active supplier POs
        $stmt = $db->prepare("SELECT id, po_no FROM purchase_orders WHERE company_id = ? AND deleted_at IS NULL");
        $stmt->execute([$companyId]);
        $supplierPOs = $stmt->fetchAll() ?: [];

        // Fetch warehouses
        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->all();

        $this->renderView('company/grns', [
            'title' => 'Goods Receipt Notes (GRN) | Procurement',
            'grns' => $grns,
            'supplier_pos' => $supplierPOs,
            'warehouses' => $warehouses
        ]);
    }

    /**
     * Create Goods Receipt Note (GRN) and ledger inventory stock
     */
    public function createGrn(Request $request, Response $response): void {
        $poId = $request->get('po_id') ? (int)$request->get('po_id') : null;
        $grnNo = trim($request->get('grn_no'));
        $date = $request->get('date');
        $invoiceNo = trim($request->get('invoice_no'));
        $warehouseId = (int)$request->get('warehouse_id');

        $itemNames = $request->get('item_name') ?: [];
        $itemTypes = $request->get('item_type') ?: [];
        $qtysReceived = $request->get('qty_received') ?: [];
        $qtysAccepted = $request->get('qty_accepted') ?: [];
        $qtysRejected = $request->get('qty_rejected') ?: [];
        $batches = $request->get('batch_no') ?: [];

        if (empty($grnNo) || empty($date) || empty($warehouseId) || empty($itemNames)) {
            Session::setFlash('error', 'GRN Number, Date, Warehouse, and at least one item are required.');
            $this->redirect('company/purchase/grns');
        }

        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        try {
            $db->beginTransaction();

            $grnModel = new Grn();
            $grnId = $grnModel->insert([
                'po_id' => $poId,
                'grn_no' => $grnNo,
                'date' => $date,
                'invoice_no' => $invoiceNo,
                'status' => 'approved',
                'created_by' => $userId
            ]);

            $inventoryService = new InventoryService();

            for ($i = 0; $i < count($itemNames); $i++) {
                if (!empty($itemNames[$i])) {
                    $received = (float)($qtysReceived[$i] ?? 0);
                    $accepted = (float)($qtysAccepted[$i] ?? 0);
                    $rejected = (float)($qtysRejected[$i] ?? 0);
                    $batch = trim($batches[$i] ?? '');

                    // Insert GRN Item
                    $stmt = $db->prepare("INSERT INTO grn_items (
                                            company_id, grn_id, item_type, item_name, qty_received, qty_accepted, qty_rejected, batch_no, warehouse_id, created_by, created_at
                                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $companyId,
                        $grnId,
                        $itemTypes[$i] ?? 'accessories',
                        trim($itemNames[$i]),
                        $received,
                        $accepted,
                        $rejected,
                        $batch,
                        $warehouseId,
                        $userId
                    ]);

                    // Ledger Hook: Update inventory stock levels immediately for accepted quantities
                    if ($accepted > 0) {
                        $inventoryService->recordTransaction(
                            $companyId,
                            $warehouseId,
                            $itemTypes[$i] ?? 'accessories',
                            trim($itemNames[$i]),
                            $accepted,
                            'in',
                            'grn',
                            $grnId,
                            $batch,
                            0.00, // Value populated from PO if linked, otherwise zero default
                            $userId
                        );
                    }
                }
            }

            // Update Supplier PO status to grn_completed if PO is linked
            if ($poId) {
                $db->prepare("UPDATE purchase_orders SET status = 'grn_completed' WHERE id = ?")->execute([$poId]);
            }

            $db->commit();

            AuditLog::log($companyId, $userId, 'create_grn', 'Grn', $grnId, null, null, "Created Goods Receipt Note: {$grnNo} and logged stock entries");
            Session::setFlash('success', 'GRN created and stock levels updated in ledger.');
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Failed to create GRN: ' . $e->getMessage());
        }

        $this->redirect('company/purchase/grns');
    }

    public function deleteRequisition(Request $request, Response $response, string $id): void {
        $model = new PurchaseRequisition();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Purchase Requisition deleted successfully.');
        $this->redirect('company/purchase/requisitions');
    }

    public function deleteOrder(Request $request, Response $response, string $id): void {
        $model = new PurchaseOrder();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Supplier Purchase Order deleted successfully.');
        $this->redirect('company/purchase/orders');
    }

    public function deleteGrn(Request $request, Response $response, string $id): void {
        $model = new Grn();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'GRN record deleted successfully.');
        $this->redirect('company/purchase/grns');
    }
}
