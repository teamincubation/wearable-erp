<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Session;
use App\Models\AuditLog;

class PackingQrController extends Controller {

    private function ensureTablesExist(): void {
        $db = Database::getInstance();
        
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS `cartons` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `company_id` INT NOT NULL,
                    `carton_no` VARCHAR(50) NOT NULL,
                    `production_order_id` INT NOT NULL,
                    `destination_type` ENUM('client', 'warehouse', 'unassigned') DEFAULT 'unassigned',
                    `client_id` INT DEFAULT NULL,
                    `warehouse_id` INT DEFAULT NULL,
                    `max_capacity_pcs` INT DEFAULT 50,
                    `gross_weight_kg` DECIMAL(10,2) DEFAULT 0.00,
                    `net_weight_kg` DECIMAL(10,2) DEFAULT 0.00,
                    `volume_cbm` DECIMAL(10,3) DEFAULT 0.000,
                    `status` ENUM('draft', 'packed', 'dispatched', 'delivered') DEFAULT 'packed',
                    `tracking_no` VARCHAR(100) DEFAULT NULL,
                    `qr_code_data` TEXT DEFAULT NULL,
                    `notes` TEXT DEFAULT NULL,
                    `created_by` INT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (`company_id`),
                    INDEX (`carton_no`),
                    INDEX (`production_order_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (\PDOException $e) {}

        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS `carton_items` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `carton_id` INT NOT NULL,
                    `production_order_id` INT NOT NULL,
                    `product_qr_code` VARCHAR(150) DEFAULT NULL,
                    `size` VARCHAR(50) DEFAULT 'FREE',
                    `color` VARCHAR(50) DEFAULT 'N/A',
                    `qty` INT DEFAULT 1,
                    `assigned_by` INT DEFAULT NULL,
                    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (`carton_id`),
                    INDEX (`product_qr_code`),
                    INDEX (`production_order_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (\PDOException $e) {}

        // Auto-heal max_capacity_pcs column on cartons
        try {
            $chkCap = $db->query("SHOW COLUMNS FROM `cartons` LIKE 'max_capacity_pcs'");
            if (!$chkCap || $chkCap->rowCount() === 0) {
                $db->exec("ALTER TABLE `cartons` ADD COLUMN `max_capacity_pcs` INT DEFAULT 50 AFTER `warehouse_id`");
            }
        } catch (\PDOException $e) {}

        // Auto-heal carton_items columns for product_qr_code, qr_code, assigned_by, and assigned_at compatibility
        $cartonItemCols = [
            'qr_code' => "VARCHAR(150) DEFAULT NULL",
            'product_qr_code' => "VARCHAR(150) DEFAULT NULL",
            'assigned_by' => "INT DEFAULT NULL",
            'assigned_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];
        foreach ($cartonItemCols as $col => $type) {
            try {
                $checkCi = $db->query("SHOW COLUMNS FROM `carton_items` LIKE '{$col}'");
                if (!$checkCi || $checkCi->rowCount() === 0) {
                    $db->exec("ALTER TABLE `carton_items` ADD COLUMN `{$col}` {$type}");
                }
            } catch (\PDOException $e) {}
        }

        // Auto-heal production_stage_logs columns for qr_code, scanned_qr_code, employee_id, and operator_id compatibility
        try {
            $chkQr = $db->query("SHOW COLUMNS FROM `production_stage_logs` LIKE 'qr_code'");
            if (!$chkQr || $chkQr->rowCount() === 0) {
                $db->exec("ALTER TABLE `production_stage_logs` ADD COLUMN `qr_code` VARCHAR(150) DEFAULT NULL");
            }
        } catch (\PDOException $e) {}

        try {
            $chkSqr = $db->query("SHOW COLUMNS FROM `production_stage_logs` LIKE 'scanned_qr_code'");
            if (!$chkSqr || $chkSqr->rowCount() === 0) {
                $db->exec("ALTER TABLE `production_stage_logs` ADD COLUMN `scanned_qr_code` VARCHAR(150) DEFAULT NULL AFTER `qr_code`");
            }
        } catch (\PDOException $e) {}

        try {
            $chkEmp = $db->query("SHOW COLUMNS FROM `production_stage_logs` LIKE 'employee_id'");
            if (!$chkEmp || $chkEmp->rowCount() === 0) {
                $db->exec("ALTER TABLE `production_stage_logs` ADD COLUMN `employee_id` INT DEFAULT NULL");
            }
        } catch (\PDOException $e) {}

        try {
            $chkOp = $db->query("SHOW COLUMNS FROM `production_stage_logs` LIKE 'operator_id'");
            if (!$chkOp || $chkOp->rowCount() === 0) {
                $db->exec("ALTER TABLE `production_stage_logs` ADD COLUMN `operator_id` INT DEFAULT NULL AFTER `employee_id`");
            }
        } catch (\PDOException $e) {}

        try {
            $chkNotes = $db->query("SHOW COLUMNS FROM `production_stage_logs` LIKE 'notes'");
            if (!$chkNotes || $chkNotes->rowCount() === 0) {
                $db->exec("ALTER TABLE `production_stage_logs` ADD COLUMN `notes` TEXT DEFAULT NULL");
            }
        } catch (\PDOException $e) {}

        try {
            $chkRem = $db->query("SHOW COLUMNS FROM `production_stage_logs` LIKE 'edit_remarks'");
            if (!$chkRem || $chkRem->rowCount() === 0) {
                $db->exec("ALTER TABLE `production_stage_logs` ADD COLUMN `edit_remarks` VARCHAR(255) DEFAULT NULL");
            }
        } catch (\PDOException $e) {}
    }

    /**
     * Landing Page: Sealed Carton Boxes Overview
     * GET /company/packing-qr
     */
    public function index(Request $request, Response $response): void {
        $this->ensureTablesExist();
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        $search = trim((string)$request->get('search'));
        $filterBatch = trim((string)$request->get('batch_no'));
        $filterStatus = trim((string)$request->get('status')); // 'packed', 'dispatched', 'delivered'
        $filterDest = trim((string)$request->get('destination_type'));

        // Query all cartons with assigned counts, batch info, destination, shipment info
        $sql = "
            SELECT c.*,
                   pro.production_no, s.style_no, s.name as style_name, po.po_no as buyer_po_no,
                   (SELECT COALESCE(SUM(qty), 0) FROM carton_items WHERE carton_id = c.id) as assigned_qty,
                   (SELECT COUNT(*) FROM carton_items WHERE carton_id = c.id) as items_count,
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
        ";

        $params = [$companyId];

        if (!empty($search)) {
            $sql .= " AND (c.carton_no LIKE ? OR pro.production_no LIKE ? OR s.style_no LIKE ? OR b.name LIKE ? OR w.name LIKE ? OR shp.shipment_no LIKE ?)";
            $q = "%{$search}%";
            array_push($params, $q, $q, $q, $q, $q, $q);
        }
        if (!empty($filterBatch)) {
            $sql .= " AND pro.production_no = ?";
            $params[] = $filterBatch;
        }
        if (!empty($filterStatus)) {
            $sql .= " AND c.status = ?";
            $params[] = $filterStatus;
        }
        if (!empty($filterDest)) {
            $sql .= " AND c.destination_type = ?";
            $params[] = $filterDest;
        }

        $sql .= " ORDER BY c.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $cartons = $stmt->fetchAll() ?: [];

        // Compute Summary KPI Metrics
        $totalCartons = count($cartons);
        $totalAssignedUnits = 0;
        $totalCapacity = 0;
        $fullyPackedCartons = 0;

        foreach ($cartons as &$c) {
            $c['max_capacity_pcs'] = max(1, (int)($c['max_capacity_pcs'] ?: 50));
            $c['assigned_qty'] = (int)($c['assigned_qty'] ?: 0);
            $c['remaining_capacity'] = max(0, $c['max_capacity_pcs'] - $c['assigned_qty']);
            $c['fill_percentage'] = round(($c['assigned_qty'] / $c['max_capacity_pcs']) * 100, 1);

            $totalAssignedUnits += $c['assigned_qty'];
            $totalCapacity += $c['max_capacity_pcs'];
            if ($c['remaining_capacity'] == 0 && $c['assigned_qty'] > 0) {
                $fullyPackedCartons++;
            }
        }
        unset($c);

        // Fetch distinct batches for dropdown filters
        $stmtBatches = $db->prepare("
            SELECT DISTINCT pro.production_no 
            FROM cartons c 
            JOIN production_orders pro ON c.production_order_id = pro.id 
            WHERE c.company_id = ? ORDER BY pro.production_no ASC
        ");
        $stmtBatches->execute([$companyId]);
        $batchOptions = $stmtBatches->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        $this->renderView('company/packing_qr/index', [
            'title' => 'Packing QR | Sealed Carton Assignment Hub',
            'cartons' => $cartons,
            'totalCartons' => $totalCartons,
            'totalAssignedUnits' => $totalAssignedUnits,
            'totalCapacity' => $totalCapacity,
            'fullyPackedCartons' => $fullyPackedCartons,
            'batchOptions' => $batchOptions,
            'filters' => [
                'search' => $search,
                'batch_no' => $filterBatch,
                'status' => $filterStatus,
                'destination_type' => $filterDest
            ]
        ]);
    }

    /**
     * Show Carton Item Assignment Hub (Manual Mode & QR Scan Mode)
     * GET /company/packing-qr/assign/{id}
     */
    public function showAssign(Request $request, Response $response, string $id): void {
        $this->ensureTablesExist();
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        $cartonId = (int)$id;

        // Fetch Carton Details
        $stmtCtn = $db->prepare("
            SELECT c.*,
                   pro.production_no, po.quantity as target_qty, s.style_no, s.name as style_name, s.category as style_category, po.po_no as buyer_po_no,
                   b.name as client_name, w.name as warehouse_name,
                   shp.shipment_no, shp.status as shipment_status
            FROM cartons c
            LEFT JOIN production_orders pro ON c.production_order_id = pro.id
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN contacts b ON c.client_id = b.id
            LEFT JOIN warehouses w ON c.warehouse_id = w.id
            LEFT JOIN shipment_cartons sc ON c.id = sc.carton_id
            LEFT JOIN shipments shp ON sc.shipment_id = shp.id
            WHERE c.id = ? AND c.company_id = ? LIMIT 1
        ");
        $stmtCtn->execute([$cartonId, $companyId]);
        $carton = $stmtCtn->fetch();

        if (!$carton) {
            Session::setFlash('error', 'Carton record not found.');
            $this->redirect('company/packing-qr');
            return;
        }

        $carton['max_capacity_pcs'] = max(1, (int)($carton['max_capacity_pcs'] ?: 50));

        // Fetch currently assigned items in this carton
        $stmtItems = $db->prepare("
            SELECT ci.*, COALESCE(ci.product_qr_code, ci.qr_code) as product_qr_code, u.name as assigned_by_name
            FROM carton_items ci
            LEFT JOIN users u ON ci.assigned_by = u.id
            WHERE ci.carton_id = ?
            ORDER BY ci.id DESC
        ");
        $stmtItems->execute([$cartonId]);
        $assignedItems = $stmtItems->fetchAll() ?: [];

        $assignedQty = 0;
        foreach ($assignedItems as $item) {
            $assignedQty += (int)$item['qty'];
        }

        $remainingCapacity = max(0, $carton['max_capacity_pcs'] - $assignedQty);
        $completionPercent = min(100, round(($assignedQty / $carton['max_capacity_pcs']) * 100, 1));

        $this->renderView('company/packing_qr/assign', [
            'title' => "Pack & Assign | Carton {$carton['carton_no']}",
            'carton' => $carton,
            'assignedItems' => $assignedItems,
            'assignedQty' => $assignedQty,
            'remainingCapacity' => $remainingCapacity,
            'completionPercent' => $completionPercent
        ]);
    }

    /**
     * AJAX Endpoint: Get Eligible Packed Products for Manual Assignment
     * GET /company/packing-qr/api/eligible-products
     */
    public function getEligibleProducts(Request $request, Response $response): void {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        try {
            $this->ensureTablesExist();
            $companyId = Session::get('company_id');
            $db = Database::getInstance();

            $cartonId = (int)$request->get('carton_id');

            // 1. Fetch Carton & Production Order details
            $stmtCtn = $db->prepare("
                SELECT c.id as carton_id, c.carton_no, c.production_order_id, c.max_capacity_pcs,
                       pro.production_no, s.style_no, s.name as style_name,
                       po.po_no as buyer_po_no, COALESCE(po.quantity, 50) as target_qty
                FROM cartons c
                JOIN production_orders pro ON c.production_order_id = pro.id
                LEFT JOIN buyer_pos po ON pro.po_id = po.id
                LEFT JOIN styles s ON po.style_id = s.id
                WHERE c.id = ? AND c.company_id = ? LIMIT 1
            ");
            $stmtCtn->execute([$cartonId, $companyId]);
            $carton = $stmtCtn->fetch();

            if (!$carton) {
                echo json_encode(['success' => false, 'message' => 'Carton record not found.']);
                return;
            }

            $prodOrderId = (int)$carton['production_order_id'];
            $productionNo = $carton['production_no'];
            $styleNo = $carton['style_no'] ?: 'N/A';
            $buyerPo = $carton['buyer_po_no'] ?: 'N/A';
            $batchTotalQty = max(1, (int)$carton['target_qty']);

            // 2. Fetch total finished/packed quantity from production_stage_logs for this batch
            $stmtQty = $db->prepare("
                SELECT COALESCE(SUM(psl.qty_out), 0) 
                FROM production_stage_logs psl 
                WHERE psl.production_order_id = ? AND psl.company_id = ? 
                  AND LOWER(psl.stage) IN ('packing', 'carton_packing', 'finishing', 'qc_pass', 'completed')
            ");
            $stmtQty->execute([$prodOrderId, $companyId]);
            $packingCompletedQty = max(0, (int)$stmtQty->fetchColumn());

            // 3. Fetch logged product stage QRs for packing stage
            $stmtLogs = $db->prepare("
                SELECT psl.id, COALESCE(psl.scanned_qr_code, psl.qr_code) as scanned_qr_code, psl.stage, psl.qty_out, psl.created_at
                FROM production_stage_logs psl
                WHERE psl.company_id = ? 
                  AND psl.production_order_id = ?
                  AND LOWER(psl.stage) IN ('packing', 'carton_packing', 'finishing', 'qc_pass', 'completed')
                  AND (
                      (psl.scanned_qr_code IS NOT NULL AND psl.scanned_qr_code != '')
                      OR
                      (psl.qr_code IS NOT NULL AND psl.qr_code != '')
                  )
                ORDER BY psl.id ASC
            ");
            $stmtLogs->execute([$companyId, $prodOrderId]);
            $logs = $stmtLogs->fetchAll() ?: [];

            // Fetch assigned items map for company
            $stmtAssigned = $db->prepare("
                SELECT COALESCE(ci.product_qr_code, ci.qr_code) as product_qr_code, ci.carton_id, c.carton_no 
                FROM carton_items ci
                JOIN cartons c ON ci.carton_id = c.id
                WHERE c.company_id = ? 
                  AND (
                      (ci.product_qr_code IS NOT NULL AND ci.product_qr_code != '')
                      OR
                      (ci.qr_code IS NOT NULL AND ci.qr_code != '')
                  )
            ");
            $stmtAssigned->execute([$companyId]);
            $assignedRows = $stmtAssigned->fetchAll() ?: [];

            $assignedMap = [];
            foreach ($assignedRows as $asgn) {
                if (!empty($asgn['product_qr_code'])) {
                    $assignedMap[$asgn['product_qr_code']] = [
                        'carton_id' => $asgn['carton_id'],
                        'carton_no' => $asgn['carton_no']
                    ];
                }
            }

            $eligibleProducts = [];
            $seenQrs = [];

            // Add logged scanned QRs for packing stage (excluding any synthetic PROD- codes)
            foreach ($logs as $row) {
                $qr = trim((string)$row['scanned_qr_code']);
                if (empty($qr) || isset($seenQrs[$qr])) continue;
                if (str_contains(strtoupper($qr), 'PROD-')) continue; // Exclude PROD- included product QR codes
                $seenQrs[$qr] = true;

                $asgnInfo = $assignedMap[$qr] ?? null;
                $isAssigned = !empty($asgnInfo);
                $isCurrentCarton = $isAssigned && ($asgnInfo['carton_id'] == $cartonId);

                $eligibleProducts[] = [
                    'id' => $row['id'],
                    'qr_code' => $qr,
                    'production_no' => $productionNo,
                    'style_no' => $styleNo,
                    'buyer_po' => $buyerPo,
                    'size' => 'FREE',
                    'color' => 'N/A',
                    'qty' => max(1, (int)$row['qty_out']),
                    'stage' => 'Packed',
                    'is_assigned' => $isAssigned,
                    'existing_carton_no' => $asgnInfo ? $asgnInfo['carton_no'] : null,
                    'is_current_carton' => $isCurrentCarton,
                    'selectable' => !$isAssigned || $isCurrentCarton
                ];
            }

            echo json_encode([
                'success' => true,
                'carton_id' => $cartonId,
                'total_count' => count($eligibleProducts),
                'products' => $eligibleProducts
            ]);
        } catch (\Throwable $ex) {
            echo json_encode([
                'success' => false,
                'message' => 'Error loading products: ' . $ex->getMessage()
            ]);
        }
    }

    /**
     * AJAX Endpoint: QR Scan Validation Engine (Strict 8-Rule Validation)
     * POST /company/packing-qr/api/scan-product
     */
    public function scanProduct(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $this->ensureTablesExist();
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        $cartonId = (int)$request->get('carton_id');
        $productQr = trim((string)$request->get('product_qr'));
        $scannedSessionQrs = $request->get('session_qrs') ?: [];

        if (empty($productQr)) {
            echo json_encode(['success' => false, 'error_code' => 'EMPTY_QR', 'message' => 'Please scan or type a valid Product QR Code.']);
            return;
        }

        // Fetch Carton Details & Capacity Rules
        $stmtCtn = $db->prepare("
            SELECT c.*, pro.production_no,
                   (SELECT COALESCE(SUM(qty), 0) FROM carton_items WHERE carton_id = c.id) as current_assigned_qty
            FROM cartons c
            JOIN production_orders pro ON c.production_order_id = pro.id
            WHERE c.id = ? AND c.company_id = ? LIMIT 1
        ");
        $stmtCtn->execute([$cartonId, $companyId]);
        $carton = $stmtCtn->fetch();

        if (!$carton) {
            echo json_encode(['success' => false, 'error_code' => 'CARTON_NOT_FOUND', 'message' => 'Target Carton record not found.']);
            return;
        }

        $maxCap = max(1, (int)($carton['max_capacity_pcs'] ?: 50));
        $currentQty = (int)$carton['current_assigned_qty'];
        $sessionCount = count($scannedSessionQrs);
        $totalProjected = $currentQty + $sessionCount + 1;

        // Rule 1 & 2: Product QR exists for tenant company
        $stmtLog = $db->prepare("
            SELECT psl.*, COALESCE(psl.scanned_qr_code, psl.qr_code) as scanned_qr_code, pro.production_no, pro.id as prod_order_id, s.style_no, s.name as style_name, po.po_no as buyer_po_no
            FROM production_stage_logs psl
            JOIN production_orders pro ON psl.production_order_id = pro.id
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            WHERE (psl.scanned_qr_code = ? OR psl.qr_code = ?) AND psl.company_id = ?
            ORDER BY psl.id DESC LIMIT 1
        ");
        $stmtLog->execute([$productQr, $productQr, $companyId]);
        $product = $stmtLog->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'error_code' => 'PRODUCT_NOT_FOUND', 'message' => "Product QR '{$productQr}' does not exist or belong to this tenant."]);
            return;
        }

        // Rule 3: Belongs to permitted production order/batch of the carton
        if ((int)$product['production_order_id'] !== (int)$carton['production_order_id']) {
            echo json_encode([
                'success' => false,
                'error_code' => 'BATCH_MISMATCH',
                'message' => "Product QR belongs to Batch '{$product['production_no']}', but Carton requires Batch '{$carton['production_no']}'."
            ]);
            return;
        }

        // Rule 4: Passed production/packing stage
        $validStages = ['packing', 'carton_packing', 'finishing', 'qc_pass', 'completed'];
        if (!in_array(strtolower($product['stage']), $validStages)) {
            echo json_encode([
                'success' => false,
                'error_code' => 'INVALID_STAGE',
                'message' => "Product QR is at stage '" . ucfirst($product['stage']) . "' and has not passed Packing inspection."
            ]);
            return;
        }

        // Rule 5: Is not cancelled
        if (isset($product['status']) && strtolower($product['status']) === 'fail') {
            echo json_encode(['success' => false, 'error_code' => 'PRODUCT_REJECTED', 'message' => "Product QR failed inspection or was rejected."]);
            return;
        }

        // Rule 6: Not already assigned to another carton
        $stmtCheckAssigned = $db->prepare("
            SELECT ci.carton_id, c.carton_no 
            FROM carton_items ci 
            JOIN cartons c ON ci.carton_id = c.id 
            WHERE (ci.product_qr_code = ? OR ci.qr_code = ?) AND ci.carton_id != ? LIMIT 1
        ");
        $stmtCheckAssigned->execute([$productQr, $productQr, $cartonId]);
        $existingAssigned = $stmtCheckAssigned->fetch();

        if ($existingAssigned) {
            echo json_encode([
                'success' => false,
                'error_code' => 'ALREADY_ASSIGNED',
                'message' => "Product QR '{$productQr}' is already assigned to Carton '{$existingAssigned['carton_no']}'."
            ]);
            return;
        }

        // Rule 7: Has not been shipped/delivered
        if (in_array($carton['status'], ['dispatched', 'delivered'])) {
            echo json_encode(['success' => false, 'error_code' => 'CARTON_SHIPPED', 'message' => "Carton '{$carton['carton_no']}' is already dispatched/delivered."]);
            return;
        }

        // Rule 8: Not duplicated within current scan session
        if (in_array($productQr, $scannedSessionQrs)) {
            echo json_encode(['success' => false, 'error_code' => 'DUPLICATE_SCAN', 'message' => "Product QR '{$productQr}' was already scanned in this session."]);
            return;
        }

        $newAssignedTotal = $currentQty + $sessionCount + 1;
        $newRemainingCap = max(0, $maxCap - $newAssignedTotal);
        $completionPercent = min(100, round(($newAssignedTotal / $maxCap) * 100, 1));

        echo json_encode([
            'success' => true,
            'product' => [
                'qr_code' => $productQr,
                'production_no' => $product['production_no'],
                'style_no' => $product['style_no'],
                'buyer_po' => $product['buyer_po_no'] ?: 'N/A',
                'size' => 'FREE',
                'color' => 'N/A',
                'qty' => 1,
                'scanned_at' => date('H:i:s')
            ],
            'updated_assigned_qty' => $newAssignedTotal,
            'remaining_capacity' => $newRemainingCap,
            'completion_percent' => $completionPercent,
            'is_full' => ($newRemainingCap === 0)
        ]);
    }

    /**
     * AJAX Endpoint: Finalise & Link Products to Carton (Manual & QR Scan)
     * POST /company/packing-qr/api/assign-bulk
     */
    public function assignBulkProducts(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $this->ensureTablesExist();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        $cartonId = (int)$request->get('carton_id');
        $productQrs = $request->get('product_qrs'); // array of QR strings
        $assignmentMode = trim((string)($request->get('assignment_mode') ?: 'qr_scan'));

        if ($cartonId <= 0 || empty($productQrs) || !is_array($productQrs)) {
            echo json_encode(['success' => false, 'message' => 'No products selected for carton assignment.']);
            return;
        }

        // Fetch Carton Details
        $stmtCtn = $db->prepare("
            SELECT c.*, pro.production_no,
                   (SELECT COALESCE(SUM(qty), 0) FROM carton_items WHERE carton_id = c.id) as current_assigned_qty
            FROM cartons c
            JOIN production_orders pro ON c.production_order_id = pro.id
            WHERE c.id = ? AND c.company_id = ? LIMIT 1
        ");
        $stmtCtn->execute([$cartonId, $companyId]);
        $carton = $stmtCtn->fetch();

        if (!$carton) {
            echo json_encode(['success' => false, 'message' => 'Carton record not found.']);
            return;
        }

        $maxCap = max(1, (int)($carton['max_capacity_pcs'] ?: 50));
        $currentQty = (int)$carton['current_assigned_qty'];
        $newCount = count($productQrs);

        try {
            $db->beginTransaction();

            // Automatically unassign all product QR codes starting with "ITEM" or null/empty QR from this carton
            $stmtDelDummy = $db->prepare("
                DELETE FROM carton_items 
                WHERE carton_id = ? AND (
                    product_qr_code LIKE 'ITEM%' 
                    OR qr_code LIKE 'ITEM%' 
                    OR product_qr_code IS NULL 
                    OR product_qr_code = ''
                    OR qr_code IS NULL 
                    OR qr_code = ''
                )
            ");
            $stmtDelDummy->execute([$cartonId]);

            $stmtInsItem = $db->prepare("
                INSERT INTO carton_items (carton_id, production_order_id, qr_code, product_qr_code, size, color, qty, assigned_by, assigned_at)
                VALUES (?, ?, ?, ?, 'FREE', 'N/A', 1, ?, NOW())
                ON DUPLICATE KEY UPDATE assigned_at = NOW()
            ");

            $stmtStageLog = $db->prepare("
                INSERT INTO production_stage_logs (company_id, production_order_id, stage, employee_id, operator_id, qty_in, qty_out, qr_code, scanned_qr_code, notes, created_at)
                VALUES (?, ?, 'carton_assignment', ?, ?, 1, 1, ?, ?, ?, NOW())
            ");

            $addedCount = 0;
            foreach ($productQrs as $qr) {
                $cleanQr = trim((string)$qr);
                if (empty($cleanQr)) continue;

                // Check if already in this carton
                $stmtChk = $db->prepare("SELECT id FROM carton_items WHERE carton_id = ? AND (product_qr_code = ? OR qr_code = ?) LIMIT 1");
                $stmtChk->execute([$cartonId, $cleanQr, $cleanQr]);
                if ($stmtChk->fetch()) {
                    continue; // Skip duplicates
                }

                $stmtInsItem->execute([$cartonId, $carton['production_order_id'], $cleanQr, $cleanQr, $userId]);
                $stmtStageLog->execute([$companyId, $carton['production_order_id'], $userId, $userId, $cleanQr, $cleanQr, "Assigned to Carton {$carton['carton_no']} ({$assignmentMode})"]);
                $addedCount++;
            }

            $db->commit();

            AuditLog::log($companyId, $userId, 'assign_carton_items', 'Carton', $cartonId, null, null, "Assigned {$addedCount} products to Carton {$carton['carton_no']} via {$assignmentMode} mode");

            echo json_encode([
                'success' => true,
                'added_count' => $addedCount,
                'message' => "Successfully linked {$addedCount} products to Carton '{$carton['carton_no']}'!"
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to assign products: ' . $e->getMessage()]);
        }
    }

    /**
     * Authorised Product Reversal / Removal from Carton
     * POST /company/packing-qr/api/remove-product
     */
    public function removeProductFromCarton(Request $request, Response $response): void {
        header('Content-Type: application/json');
        $this->ensureTablesExist();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        $cartonId = (int)$request->get('carton_id');
        $itemId = (int)$request->get('item_id');
        $productQr = trim((string)$request->get('product_qr'));

        if ($cartonId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid carton ID for removal.']);
            return;
        }

        try {
            if ($itemId > 0) {
                $stmtDel = $db->prepare("DELETE FROM carton_items WHERE id = ? AND carton_id = ?");
                $stmtDel->execute([$itemId, $cartonId]);
            } else if (!empty($productQr)) {
                $stmtDel = $db->prepare("DELETE FROM carton_items WHERE carton_id = ? AND product_qr_code = ?");
                $stmtDel->execute([$cartonId, $productQr]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Specify item ID or Product QR code to unassign.']);
                return;
            }

            AuditLog::log($companyId, $userId, 'unassign_carton_item', 'Carton', $cartonId, null, null, "Removed product item from Carton #{$cartonId}");

            echo json_encode(['success' => true, 'message' => 'Item unassigned from carton successfully.']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to remove product: ' . $e->getMessage()]);
        }
    }

    /**
     * Enterprise 2-Way Lifecycle Traceability Page
     * GET /company/packing-qr/traceability
     */
    public function showTraceability(Request $request, Response $response): void {
        $this->ensureTablesExist();
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        $query = trim((string)$request->get('query'));
        $searchResult = null;
        $searchType = null; // 'product' or 'carton'

        if (!empty($query)) {
            // Check if query matches a Carton Code or Carton ID
            $stmtCtn = $db->prepare("
                SELECT c.*, pro.production_no, s.style_no, s.name as style_name, b.name as client_name, w.name as warehouse_name, shp.shipment_no
                FROM cartons c
                JOIN production_orders pro ON c.production_order_id = pro.id
                LEFT JOIN styles s ON pro.style_id = s.id
                LEFT JOIN contacts b ON c.client_id = b.id
                LEFT JOIN warehouses w ON c.warehouse_id = w.id
                LEFT JOIN shipment_cartons sc ON c.id = sc.carton_id
                LEFT JOIN shipments shp ON sc.shipment_id = shp.id
                WHERE c.company_id = ? AND (c.carton_no = ? OR c.id = ?) LIMIT 1
            ");
            $stmtCtn->execute([$companyId, $query, (int)$query]);
            $cartonMatch = $stmtCtn->fetch();

            if ($cartonMatch) {
                $searchType = 'carton';
                // Fetch items inside this carton
                $stmtItems = $db->prepare("
                    SELECT ci.*, psl.created_at as production_date
                    FROM carton_items ci
                    LEFT JOIN production_stage_logs psl ON (ci.product_qr_code = psl.scanned_qr_code OR ci.product_qr_code = psl.qr_code) AND psl.stage = 'packing'
                    WHERE ci.carton_id = ?
                    ORDER BY ci.id DESC
                ");
                $stmtItems->execute([$cartonMatch['id']]);
                $cartonMatch['items'] = $stmtItems->fetchAll() ?: [];

                $searchResult = $cartonMatch;
            } else {
                // Search as Product QR Code
                $stmtProd = $db->prepare("
                    SELECT psl.*, COALESCE(psl.scanned_qr_code, psl.qr_code) as scanned_qr_code, pro.production_no, s.style_no, s.name as style_name, po.po_no as buyer_po_no,
                           ci.carton_id, c.carton_no, c.status as carton_status, c.created_at as carton_packed_at,
                           b.name as client_name, w.name as warehouse_name,
                           shp.shipment_no, shp.status as shipment_status
                    FROM production_stage_logs psl
                    JOIN production_orders pro ON psl.production_order_id = pro.id
                    LEFT JOIN buyer_pos po ON pro.po_id = po.id
                    LEFT JOIN styles s ON po.style_id = s.id
                    LEFT JOIN carton_items ci ON (psl.scanned_qr_code = ci.product_qr_code OR psl.qr_code = ci.product_qr_code)
                    LEFT JOIN cartons c ON ci.carton_id = c.id
                    LEFT JOIN contacts b ON c.client_id = b.id
                    LEFT JOIN warehouses w ON c.warehouse_id = w.id
                    LEFT JOIN shipment_cartons sc ON c.id = sc.carton_id
                    LEFT JOIN shipments shp ON sc.shipment_id = shp.id
                    WHERE psl.company_id = ? AND (psl.scanned_qr_code = ? OR psl.qr_code = ?)
                    ORDER BY psl.id DESC LIMIT 1
                ");
                $stmtProd->execute([$companyId, $query, $query]);
                $productMatch = $stmtProd->fetch();

                if ($productMatch) {
                    $searchType = 'product';
                    // Fetch full stage timeline for this product
                    $stmtTimeline = $db->prepare("
                        SELECT psl.*, u.name as operator_name 
                        FROM production_stage_logs psl
                        LEFT JOIN users u ON (psl.employee_id = u.id OR psl.operator_id = u.id)
                        WHERE psl.company_id = ? AND (psl.scanned_qr_code = ? OR psl.qr_code = ?)
                        ORDER BY psl.id ASC
                    ");
                    $stmtTimeline->execute([$companyId, $query, $query]);
                    $productMatch['timeline'] = $stmtTimeline->fetchAll() ?: [];

                    $searchResult = $productMatch;
                }
            }
        }

        $this->renderView('company/packing_qr/traceability', [
            'title' => 'Traceability | Enterprise 2-Way QR Lifecycle',
            'query' => $query,
            'searchType' => $searchType,
            'searchResult' => $searchResult
        ]);
    }
}
