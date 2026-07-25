<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\Warehouse;

/**
 * Finished Goods Dispatch & Packing Hub Controller
 * Wearable ERP Engine
 */
class DispatchController extends Controller {

    /**
     * Self-healing Database Table Initialization
     */
    private static function ensureTablesExist(): void {
        $db = Database::getInstance();
        try {
            $db->query("SELECT 1 FROM cartons LIMIT 1");
        } catch (\Exception $e) {
            try {
                $db->exec("
                    CREATE TABLE IF NOT EXISTS `cartons` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `company_id` INT NOT NULL,
                      `carton_no` VARCHAR(50) NOT NULL,
                      `production_order_id` INT DEFAULT NULL,
                      `destination_type` ENUM('client', 'warehouse', 'unassigned') DEFAULT 'unassigned',
                      `client_id` INT DEFAULT NULL,
                      `warehouse_id` INT DEFAULT NULL,
                      `gross_weight_kg` DECIMAL(10,2) DEFAULT 0.00,
                      `net_weight_kg` DECIMAL(10,2) DEFAULT 0.00,
                      `volume_cbm` DECIMAL(10,3) DEFAULT 0.00,
                      `status` ENUM('draft', 'packed', 'dispatched', 'delivered') DEFAULT 'packed',
                      `qr_code_data` TEXT DEFAULT NULL,
                      `notes` TEXT DEFAULT NULL,
                      `created_by` INT DEFAULT NULL,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      INDEX `idx_carton_comp` (`company_id`),
                      INDEX `idx_carton_order` (`production_order_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS `carton_items` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `carton_id` INT NOT NULL,
                      `production_order_id` INT NOT NULL,
                      `qr_code` VARCHAR(100) DEFAULT NULL,
                      `size` VARCHAR(50) DEFAULT NULL,
                      `color` VARCHAR(50) DEFAULT NULL,
                      `qty` INT NOT NULL DEFAULT 1,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      CONSTRAINT `fk_ci_carton` FOREIGN KEY (`carton_id`) REFERENCES `cartons` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS `shipments` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `company_id` INT NOT NULL,
                      `shipment_no` VARCHAR(50) NOT NULL,
                      `destination_type` ENUM('client', 'warehouse') NOT NULL,
                      `client_id` INT DEFAULT NULL,
                      `warehouse_id` INT DEFAULT NULL,
                      `dispatch_note` TEXT DEFAULT NULL,
                      `vehicle_courier_details` VARCHAR(255) DEFAULT NULL,
                      `tracking_no` VARCHAR(100) DEFAULT NULL,
                      `dispatch_date` DATE DEFAULT NULL,
                      `expected_delivery_date` DATE DEFAULT NULL,
                      `status` ENUM('pending', 'dispatched', 'in_transit', 'delivered', 'cancelled') DEFAULT 'dispatched',
                      `created_by` INT DEFAULT NULL,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      INDEX `idx_shipment_comp` (`company_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS `shipment_cartons` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `shipment_id` INT NOT NULL,
                      `carton_id` INT NOT NULL,
                      CONSTRAINT `fk_sc_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
                      CONSTRAINT `fk_sc_carton` FOREIGN KEY (`carton_id`) REFERENCES `cartons` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
            } catch (\Exception $ex) {}
        }

        // Auto-heal tracking_no column in cartons table
        try {
            $db->query("SELECT tracking_no FROM cartons LIMIT 1");
        } catch (\Exception $e) {
            try {
                $db->exec("ALTER TABLE `cartons` ADD COLUMN `tracking_no` VARCHAR(100) DEFAULT NULL AFTER `qr_code_data`");
            } catch (\Exception $ex) {}
        }
    }

    /**
     * Central Finished Goods Dispatch Hub Page
     * GET /company/dispatch
     */
    public function index(Request $request, Response $response): void {
        self::ensureTablesExist();
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        // Search & Filter Parameters
        $filterBatch = trim((string)$request->get('filter_batch'));
        $filterStyle = trim((string)$request->get('filter_style'));
        $filterBuyer = trim((string)$request->get('filter_buyer'));
        $filterPacking = trim((string)$request->get('filter_packing'));
        $filterCarton = trim((string)$request->get('filter_carton'));
        $filterShipment = trim((string)$request->get('filter_shipment'));
        $search = trim((string)($request->get('search') ?: $request->get('q')));

        // Fetch Finished Goods Batches with Packing & Production Counts
        $stmtBatches = $db->prepare("
            SELECT pro.id as production_order_id, pro.production_no, pro.status as order_status, pro.created_at as batch_created_at,
                   s.style_no, s.name as style_name, s.category as style_category,
                   po.po_no as buyer_po_no, po.quantity as target_qty,
                   b.name as buyer_name, b.id as buyer_id,
                   (SELECT COALESCE(SUM(psl.qty_out), 0) FROM production_stage_logs psl WHERE psl.production_order_id = pro.id AND psl.company_id = ? AND psl.stage IN ('packing', 'carton_packing', 'shipment')) as finished_output_qty,
                   (SELECT COALESCE(SUM(ci.qty), 0) FROM carton_items ci JOIN cartons c ON ci.carton_id = c.id WHERE c.production_order_id = pro.id AND c.company_id = ?) as packed_in_cartons_qty
            FROM production_orders pro
            JOIN buyer_pos po ON pro.po_id = po.id
            JOIN styles s ON po.style_id = s.id
            LEFT JOIN contacts b ON po.buyer_id = b.id
            WHERE pro.company_id = ? AND pro.deleted_at IS NULL
            ORDER BY pro.id DESC
        ");
        $stmtBatches->execute([$companyId, $companyId, $companyId]);
        $allBatches = $stmtBatches->fetchAll() ?: [];

        // Apply PHP-side filters for total precision
        $filteredBatches = array_filter($allBatches, function($b) use ($filterBatch, $filterStyle, $filterBuyer, $filterPacking, $search) {
            if (!empty($filterBatch) && strpos(strtolower($b['production_no']), strtolower($filterBatch)) === false) return false;
            if (!empty($filterStyle) && strpos(strtolower($b['style_no']), strtolower($filterStyle)) === false && strpos(strtolower($b['style_name']), strtolower($filterStyle)) === false) return false;
            if (!empty($filterBuyer) && (int)$b['buyer_id'] !== (int)$filterBuyer) return false;
            
            $packedQty = (int)$b['packed_in_cartons_qty'];
            $finishedQty = (int)$b['finished_output_qty'];
            $unpackedBal = max(0, $finishedQty - $packedQty);

            if ($filterPacking === 'unpacked' && $packedQty > 0) return false;
            if ($filterPacking === 'partially_packed' && ($packedQty == 0 || $unpackedBal <= 0)) return false;
            if ($filterPacking === 'fully_packed' && ($packedQty == 0 || $unpackedBal > 0)) return false;

            if (!empty($search)) {
                $q = strtolower($search);
                $match = (strpos(strtolower($b['production_no']), $q) !== false) ||
                         (strpos(strtolower($b['style_no']), $q) !== false) ||
                         (strpos(strtolower($b['style_name']), $q) !== false) ||
                         (strpos(strtolower($b['buyer_po_no']), $q) !== false) ||
                         (strpos(strtolower($b['buyer_name'] ?? ''), $q) !== false);
                if (!$match) return false;
            }
            return true;
        });

        // Fetch Cartons List
        $stmtCartons = $db->prepare("
            SELECT c.*, pro.production_no, s.style_no, s.name as style_name, po.po_no as buyer_po_no,
                   (SELECT COALESCE(SUM(qty), 0) FROM carton_items WHERE carton_id = c.id) as total_items_qty,
                   u.name as created_by_name,
                   b.name as client_name, w.name as warehouse_name,
                   shp.shipment_no, shp.status as shipment_status
            FROM cartons c
            LEFT JOIN production_orders pro ON c.production_order_id = pro.id
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN users u ON c.created_by = u.id
            LEFT JOIN contacts b ON c.client_id = b.id
            LEFT JOIN warehouses w ON c.warehouse_id = w.id
            LEFT JOIN shipment_cartons sc ON c.id = sc.carton_id
            LEFT JOIN shipments shp ON sc.shipment_id = shp.id
            WHERE c.company_id = ?
            ORDER BY c.id DESC
        ");
        $stmtCartons->execute([$companyId]);
        $allCartons = $stmtCartons->fetchAll() ?: [];

        // Apply Carton Status filter if selected
        $filteredCartons = array_filter($allCartons, function($c) use ($filterCarton, $filterShipment, $search) {
            if (!empty($filterCarton) && $c['status'] !== $filterCarton) return false;
            if (!empty($filterShipment) && ($c['shipment_status'] ?? 'unshipped') !== $filterShipment) return false;
            if (!empty($search)) {
                $q = strtolower($search);
                $match = (strpos(strtolower($c['carton_no']), $q) !== false) ||
                         (strpos(strtolower($c['production_no'] ?? ''), $q) !== false) ||
                         (strpos(strtolower($c['style_no'] ?? ''), $q) !== false) ||
                         (strpos(strtolower($c['shipment_no'] ?? ''), $q) !== false);
                if (!$match) return false;
            }
            return true;
        });

        // Fetch Registered Buyers/Clients & Warehouses
        $contactModel = new Contact();
        $buyers = $contactModel->findBy(['type' => 'buyer']);

        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->all();

        // Calculate KPI Metrics
        $totalFinishedUnits = array_sum(array_column($allBatches, 'finished_output_qty'));
        $totalPackedUnits = array_sum(array_column($allBatches, 'packed_in_cartons_qty'));
        $totalCartonsCount = count($allCartons);
        $packedCartonsCount = count(array_filter($allCartons, fn($c) => $c['status'] === 'packed'));
        $dispatchedCartonsCount = count(array_filter($allCartons, fn($c) => in_array($c['status'], ['dispatched', 'delivered'])));

        $this->renderView('company/dispatch/index', [
            'title' => 'Finished Goods Dispatch Hub | Wearable ERP',
            'batches' => array_values($filteredBatches),
            'allBatches' => $allBatches,
            'cartons' => array_values($filteredCartons),
            'allCartons' => $allCartons,
            'buyers' => $buyers,
            'warehouses' => $warehouses,
            'totalFinishedUnits' => $totalFinishedUnits,
            'totalPackedUnits' => $totalPackedUnits,
            'totalCartonsCount' => $totalCartonsCount,
            'packedCartonsCount' => $packedCartonsCount,
            'dispatchedCartonsCount' => $dispatchedCartonsCount,
            'filterBatch' => $filterBatch,
            'filterStyle' => $filterStyle,
            'filterBuyer' => $filterBuyer,
            'filterPacking' => $filterPacking,
            'filterCarton' => $filterCarton,
            'filterShipment' => $filterShipment,
            'search' => $search
        ]);
    }

    /**
     * AJAX/POST: Create New Carton Package
     * POST /company/dispatch/cartons/create
     */
    public function createCarton(Request $request, Response $response): void {
        self::ensureTablesExist();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        $productionOrderId = (int)$request->get('production_order_id');
        $includeWeights = (bool)$request->get('include_weight_details');
        $grossWeight = $includeWeights ? (float)($request->get('gross_weight_kg') ?: 0.0) : 0.00;
        $netWeight = $includeWeights ? (float)($request->get('net_weight_kg') ?: 0.0) : 0.00;
        $volumeCbm = $includeWeights ? (float)($request->get('volume_cbm') ?: 0.0) : 0.000;
        $notes = trim((string)$request->get('notes'));
        $destinationType = trim((string)($request->get('destination_type') ?: 'unassigned'));
        $clientId = ($destinationType === 'client' && $request->get('client_id')) ? (int)$request->get('client_id') : null;
        $warehouseId = ($destinationType === 'warehouse' && $request->get('warehouse_id')) ? (int)$request->get('warehouse_id') : null;

        // Breakdown items array (e.g. sizes and quantities)
        $itemsJson = $request->get('items_json');
        $items = !empty($itemsJson) ? json_decode($itemsJson, true) : [];

        if ($productionOrderId <= 0) {
            Session::setFlash('error', 'Please select a valid production batch to pack.');
            $this->redirect('company/dispatch');
            return;
        }

        // Validate unpacked balance for this batch to prevent over-packing
        $stmtFinished = $db->prepare("
            SELECT COALESCE(SUM(psl.qty_out), 0) 
            FROM production_stage_logs psl 
            WHERE psl.production_order_id = ? AND psl.company_id = ? AND psl.stage IN ('packing', 'carton_packing', 'shipment')
        ");
        $stmtFinished->execute([$productionOrderId, $companyId]);
        $finishedOutput = (int)$stmtFinished->fetchColumn();

        $stmtPacked = $db->prepare("
            SELECT COALESCE(SUM(ci.qty), 0) 
            FROM carton_items ci 
            JOIN cartons c ON ci.carton_id = c.id 
            WHERE c.production_order_id = ? AND c.company_id = ?
        ");
        $stmtPacked->execute([$productionOrderId, $companyId]);
        $alreadyPacked = (int)$stmtPacked->fetchColumn();

        $unpackedBalance = max(0, $finishedOutput - $alreadyPacked);
        $totalQtyRequested = max(1, (int)($request->get('total_qty') ?: 1));

        if ($unpackedBalance <= 0) {
            Session::setFlash('error', 'Cannot pack carton: This production batch is already fully packed (0 pcs unpacked balance).');
            $this->redirect('company/dispatch');
            return;
        }

        if ($totalQtyRequested > $unpackedBalance) {
            Session::setFlash('error', "Quantity limit exceeded: Cannot pack {$totalQtyRequested} pcs. Maximum remaining unpacked balance for this batch is {$unpackedBalance} pcs.");
            $this->redirect('company/dispatch');
            return;
        }

        // Auto-generate Carton ID: CTN-YYYY-XXXX
        $yearStr = date('Y');
        $stmtLast = $db->prepare("SELECT id FROM cartons WHERE company_id = ? ORDER BY id DESC LIMIT 1");
        $stmtLast->execute([$companyId]);
        $nextNum = ((int)$stmtLast->fetchColumn()) + 1;
        $cartonNo = sprintf("CTN-%s-%04d", $yearStr, $nextNum);

        // QR Code Payload
        $qrPayload = json_encode([
            'carton_no' => $cartonNo,
            'batch_id' => $productionOrderId,
            'gross_weight' => $grossWeight,
            'net_weight' => $netWeight,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        try {
            $db->beginTransaction();

            $stmtIns = $db->prepare("
                INSERT INTO cartons 
                (company_id, carton_no, production_order_id, destination_type, client_id, warehouse_id, gross_weight_kg, net_weight_kg, volume_cbm, status, qr_code_data, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'packed', ?, ?, ?)
            ");
            $stmtIns->execute([
                $companyId,
                $cartonNo,
                $productionOrderId,
                $destinationType,
                $clientId,
                $warehouseId,
                $grossWeight,
                $netWeight,
                $volumeCbm,
                $qrPayload,
                $notes,
                $userId
            ]);
            $cartonId = (int)$db->lastInsertId();

            // Insert Carton Items
            $stmtItem = $db->prepare("
                INSERT INTO carton_items (carton_id, production_order_id, qr_code, size, color, qty)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if (!empty($items) && is_array($items)) {
                foreach ($items as $item) {
                    $stmtItem->execute([
                        $cartonId,
                        $productionOrderId,
                        $item['qr_code'] ?? null,
                        $item['size'] ?? 'FREE',
                        $item['color'] ?? 'N/A',
                        max(1, (int)($item['qty'] ?? 1))
                    ]);
                }
            } else {
                // Default fallback item if no breakdown specified
                $qty = max(1, (int)($request->get('total_qty') ?: 1));
                $stmtItem->execute([
                    $cartonId,
                    $productionOrderId,
                    null,
                    'ALL',
                    'ASSORTED',
                    $qty
                ]);
            }

            $db->commit();

            AuditLog::log($companyId, $userId, 'create_carton', 'Carton', $cartonId, null, null, "Created carton package {$cartonNo} for batch ID {$productionOrderId}");
            Session::setFlash('success', "Carton package '{$cartonNo}' created and sealed successfully!");
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Failed to create carton package: ' . $e->getMessage());
        }

        $this->redirect('company/dispatch');
    }

    /**
     * Reopen / Unpack Carton Package
     * POST /company/dispatch/cartons/{id}/reopen
     */
    public function reopenCarton(Request $request, Response $response, string $id): void {
        self::ensureTablesExist();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        $cartonId = (int)$id;
        $stmtCtn = $db->prepare("SELECT * FROM cartons WHERE id = ? AND company_id = ? LIMIT 1");
        $stmtCtn->execute([$cartonId, $companyId]);
        $carton = $stmtCtn->fetch();

        if (!$carton) {
            Session::setFlash('error', 'Carton package not found.');
            $this->redirect('company/dispatch');
            return;
        }

        if (in_array($carton['status'], ['dispatched', 'delivered'])) {
            Session::setFlash('error', 'Cannot reopen carton: Package has already been dispatched/delivered!');
            $this->redirect('company/dispatch');
            return;
        }

        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM carton_items WHERE carton_id = ?")->execute([$cartonId]);
            $db->prepare("DELETE FROM cartons WHERE id = ? AND company_id = ?")->execute([$cartonId, $companyId]);
            $db->commit();

            AuditLog::log($companyId, $userId, 'reopen_carton', 'Carton', $cartonId, null, null, "Reopened and unpacked carton package {$carton['carton_no']}");
            Session::setFlash('success', "Carton '{$carton['carton_no']}' unpacked and reopened successfully.");
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Failed to unpack carton: ' . $e->getMessage());
        }

        $this->redirect('company/dispatch');
    }

    /**
     * Render Thermal PDF Printable Barcode/QR Label for Cartons
     * GET /company/dispatch/cartons/print
     */
    public function printCartonQr(Request $request, Response $response): void {
        self::ensureTablesExist();
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        $cartonIdsStr = trim((string)($request->get('carton_ids') ?: $request->get('carton_id')));
        if (empty($cartonIdsStr)) {
            echo "No carton specified for printing.";
            exit;
        }

        $idsArray = array_map('intval', explode(',', $cartonIdsStr));
        $placeholders = implode(',', array_fill(0, count($idsArray), '?'));
        $params = array_merge([$companyId], $idsArray);

        $stmt = $db->prepare("
            SELECT c.*, pro.production_no, s.style_no, s.name as style_name, po.po_no as buyer_po_no,
                   b.name as client_name, w.name as warehouse_name,
                   (SELECT COALESCE(SUM(qty), 0) FROM carton_items WHERE carton_id = c.id) as total_pcs
            FROM cartons c
            LEFT JOIN production_orders pro ON c.production_order_id = pro.id
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN contacts b ON c.client_id = b.id
            LEFT JOIN warehouses w ON c.warehouse_id = w.id
            WHERE c.company_id = ? AND c.id IN ({$placeholders})
            ORDER BY c.id ASC
        ");
        $stmt->execute($params);
        $cartonsList = $stmt->fetchAll() ?: [];

        if (empty($cartonsList)) {
            echo "Cartons not found.";
            exit;
        }

        // Fetch item breakdown for each carton
        foreach ($cartonsList as &$c) {
            $stmtItem = $db->prepare("SELECT size, color, SUM(qty) as item_qty FROM carton_items WHERE carton_id = ? GROUP BY size, color");
            $stmtItem->execute([$c['id']]);
            $c['items'] = $stmtItem->fetchAll() ?: [];
        }
        unset($c);

        // Render printable thermal label view
        $this->renderView('company/dispatch/print_label', [
            'title' => 'Print Carton Labels | Wearable ERP',
            'cartons' => $cartonsList
        ], 'blank');
    }

    /**
     * Create Structured Shipment Dispatch to Client or Company Warehouse
     * POST /company/dispatch/shipments/create
     */
    public function createShipment(Request $request, Response $response): void {
        self::ensureTablesExist();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        $destinationType = trim((string)$request->get('destination_type'));
        $clientId = $request->get('client_id') ? (int)$request->get('client_id') : null;
        $warehouseId = $request->get('warehouse_id') ? (int)$request->get('warehouse_id') : null;
        $dispatchNote = trim((string)$request->get('dispatch_note'));
        $vehicleDetails = trim((string)$request->get('vehicle_courier_details'));
        $trackingNo = trim((string)$request->get('tracking_no'));
        $dispatchDate = trim((string)$request->get('dispatch_date')) ?: date('Y-m-d');
        $expectedDeliveryDate = trim((string)$request->get('expected_delivery_date'));

        $cartonIds = $request->get('carton_ids');
        if (empty($cartonIds) || !is_array($cartonIds)) {
            Session::setFlash('error', 'Please select at least one packed carton to dispatch.');
            $this->redirect('company/dispatch');
            return;
        }

        // Fetch destination details from the selected carton record to guarantee accuracy
        $firstCid = (int)$cartonIds[0];
        $stmtCtnDest = $db->prepare("SELECT destination_type, client_id, warehouse_id FROM cartons WHERE id = ? AND company_id = ? LIMIT 1");
        $stmtCtnDest->execute([$firstCid, $companyId]);
        $ctnDest = $stmtCtnDest->fetch();
        if ($ctnDest) {
            if (empty($destinationType) || $destinationType === 'unassigned') {
                $destinationType = $ctnDest['destination_type'] ?: 'client';
            }
            if (!$clientId && $destinationType === 'client') {
                $clientId = $ctnDest['client_id'];
            }
            if (!$warehouseId && $destinationType === 'warehouse') {
                $warehouseId = $ctnDest['warehouse_id'];
            }
        }

        // Auto-generate Shipment ID: SHP-YYYY-XXXX
        $yearStr = date('Y');
        $stmtLast = $db->prepare("SELECT id FROM shipments WHERE company_id = ? ORDER BY id DESC LIMIT 1");
        $stmtLast->execute([$companyId]);
        $nextNum = ((int)$stmtLast->fetchColumn()) + 1;
        $shipmentNo = sprintf("SHP-%s-%04d", $yearStr, $nextNum);

        try {
            $db->beginTransaction();

            $stmtShp = $db->prepare("
                INSERT INTO shipments
                (company_id, shipment_no, destination_type, client_id, warehouse_id, dispatch_note, vehicle_courier_details, tracking_no, dispatch_date, expected_delivery_date, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'dispatched', ?)
            ");
            $stmtShp->execute([
                $companyId,
                $shipmentNo,
                $destinationType,
                $clientId,
                $warehouseId,
                $dispatchNote,
                $vehicleDetails,
                $trackingNo,
                $dispatchDate,
                !empty($expectedDeliveryDate) ? $expectedDeliveryDate : null,
                $userId
            ]);
            $shipmentId = (int)$db->lastInsertId();

            $stmtSc = $db->prepare("INSERT INTO shipment_cartons (shipment_id, carton_id) VALUES (?, ?)");
            $stmtUpdCtn = $db->prepare("UPDATE cartons SET status = 'dispatched', destination_type = ?, client_id = ?, warehouse_id = ? WHERE id = ? AND company_id = ?");

            foreach ($cartonIds as $cid) {
                $cInt = (int)$cid;
                $stmtSc->execute([$shipmentId, $cInt]);
                $stmtUpdCtn->execute([$destinationType, $clientId, $warehouseId, $cInt, $companyId]);
            }

            // Inventory Ledger Stock Sync if dispatched to Company Warehouse
            if ($destinationType === 'warehouse' && $warehouseId > 0) {
                $stmtItems = $db->prepare("
                    SELECT ci.size, ci.color, SUM(ci.qty) as total_qty
                    FROM carton_items ci
                    JOIN cartons c ON ci.carton_id = c.id
                    WHERE c.id IN (" . implode(',', array_fill(0, count($cartonIds), '?')) . ")
                    GROUP BY ci.size, ci.color
                ");
                $stmtItems->execute(array_map('intval', $cartonIds));
                $groupedItems = $stmtItems->fetchAll() ?: [];

                $stmtLedger = $db->prepare("
                    INSERT INTO inventory_balances (company_id, warehouse_id, item_type, size, color, quantity, last_updated_by)
                    VALUES (?, ?, 'Finished Goods', ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), updated_at = CURRENT_TIMESTAMP
                ");

                foreach ($groupedItems as $g) {
                    try {
                        $stmtLedger->execute([
                            $companyId,
                            $warehouseId,
                            $g['size'] ?: 'FREE',
                            $g['color'] ?: 'N/A',
                            (int)$g['total_qty'],
                            $userId
                        ]);
                    } catch (\Exception $ex) {}
                }
            }

            $db->commit();

            AuditLog::log($companyId, $userId, 'create_shipment', 'Shipment', $shipmentId, null, null, "Created consignment shipment {$shipmentNo} with " . count($cartonIds) . " cartons.");
            Session::setFlash('success', "Shipment consignment '{$shipmentNo}' dispatched successfully!");
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Failed to dispatch shipment: ' . $e->getMessage());
        }

        $this->redirect('company/dispatch');
    }

    /**
     * Update Shipment Status & Delivery Confirmation
     * POST /company/dispatch/shipments/{id}/status
     */
    public function updateShipmentStatus(Request $request, Response $response, string $id): void {
        self::ensureTablesExist();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        $shipmentId = (int)$id;
        $status = trim((string)$request->get('status'));

        $stmtShp = $db->prepare("SELECT * FROM shipments WHERE id = ? AND company_id = ? LIMIT 1");
        $stmtShp->execute([$shipmentId, $companyId]);
        $shipment = $stmtShp->fetch();

        if (!$shipment) {
            Session::setFlash('error', 'Shipment record not found.');
            $this->redirect('company/dispatch');
            return;
        }

        try {
            $db->beginTransaction();

            $db->prepare("UPDATE shipments SET status = ? WHERE id = ? AND company_id = ?")->execute([$status, $shipmentId, $companyId]);

            // Sync linked cartons status
            $cartonStatus = ($status === 'delivered') ? 'delivered' : (($status === 'cancelled') ? 'packed' : 'dispatched');
            $stmtCtnIds = $db->prepare("SELECT carton_id FROM shipment_cartons WHERE shipment_id = ?");
            $stmtCtnIds->execute([$shipmentId]);
            $cIds = $stmtCtnIds->fetchAll(\PDO::FETCH_COLUMN) ?: [];

            if (!empty($cIds)) {
                $db->prepare("UPDATE cartons SET status = ? WHERE id IN (" . implode(',', array_map('intval', $cIds)) . ") AND company_id = ?")->execute([$cartonStatus, $companyId]);
            }

            $db->commit();

            AuditLog::log($companyId, $userId, 'update_shipment_status', 'Shipment', $shipmentId, null, null, "Updated shipment {$shipment['shipment_no']} status to {$status}");
            Session::setFlash('success', "Shipment {$shipment['shipment_no']} status updated to " . strtoupper($status) . ".");
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Failed to update shipment status: ' . $e->getMessage());
        }

        $this->redirect('company/dispatch');
    }

    /**
     * Quick Action: Update Carton Dispatch Status & Tracking ID
     * POST /company/dispatch/cartons/{id}/status
     */
    public function updateCartonStatus(Request $request, Response $response, string $id): void {
        self::ensureTablesExist();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        $cartonId = (int)$id;
        $targetStatus = trim((string)$request->get('status')); // 'dispatched' or 'delivered'

        $stmtCtn = $db->prepare("SELECT * FROM cartons WHERE id = ? AND company_id = ? LIMIT 1");
        $stmtCtn->execute([$cartonId, $companyId]);
        $carton = $stmtCtn->fetch();

        if (!$carton) {
            Session::setFlash('error', 'Carton record not found.');
            $this->redirect('company/dispatch');
            return;
        }

        try {
            if ($targetStatus === 'dispatched') {
                // Verify that this carton is linked to a shipment consignment
                $stmtCheckShp = $db->prepare("SELECT shipment_id FROM shipment_cartons WHERE carton_id = ? LIMIT 1");
                $stmtCheckShp->execute([$cartonId]);
                $shipmentId = $stmtCheckShp->fetchColumn();

                if (!$shipmentId) {
                    Session::setFlash('error', "Carton '{$carton['carton_no']}' cannot be dispatched directly without a shipment. Please create a shipment consignment first to link this carton.");
                    $this->redirect('company/dispatch');
                    return;
                }

                $trackingNo = !empty($carton['tracking_no']) ? $carton['tracking_no'] : $carton['carton_no'];
                $stmtUpd = $db->prepare("UPDATE cartons SET status = 'dispatched', tracking_no = ? WHERE id = ? AND company_id = ?");
                $stmtUpd->execute([$trackingNo, $cartonId, $companyId]);
                
                AuditLog::log($companyId, $userId, 'dispatch_carton', 'Carton', $cartonId, null, null, "Dispatched carton {$carton['carton_no']} with Tracking ID {$trackingNo}");
                Session::setFlash('success', "Carton package '{$carton['carton_no']}' marked as DISPATCHED! Tracking ID: {$trackingNo}");
            } elseif ($targetStatus === 'delivered') {
                $stmtUpd = $db->prepare("UPDATE cartons SET status = 'delivered' WHERE id = ? AND company_id = ?");
                $stmtUpd->execute([$cartonId, $companyId]);
                
                AuditLog::log($companyId, $userId, 'deliver_carton', 'Carton', $cartonId, null, null, "Updated carton {$carton['carton_no']} status to Item Moved (Delivered at destination)");
                Session::setFlash('success', "Carton package '{$carton['carton_no']}' status updated to ITEM MOVED (Delivered at destination)!");
            }
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to update carton status: ' . $e->getMessage());
        }

        $this->redirect('company/dispatch');
    }
}
