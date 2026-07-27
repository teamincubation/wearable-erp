<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Auth;
use App\Core\Controller;
use App\Models\AuditLog;
use PDO;

class SalesReportsController extends Controller {

    /**
     * Auto-seed sales_reports permission & ensure required tables exist
     */
    private function ensurePermissionsExist(PDO $db): void {
        try {
            $stmt = $db->prepare("SELECT id FROM permissions WHERE name = 'company.sales_reports' LIMIT 1");
            $stmt->execute();
            $permId = $stmt->fetchColumn();

            if (!$permId) {
                $db->exec("INSERT INTO permissions (name, description, module) VALUES ('company.sales_reports', 'View executive sales, profitability, and manufacturing performance dashboard', 'tenant')");
                $permId = $db->lastInsertId();
            }

            if ($permId) {
                $db->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, {$permId})");
            }

            // Ensure batch_payments table exists
            $db->exec("
                CREATE TABLE IF NOT EXISTS `batch_payments` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `company_id` INT NOT NULL,
                  `production_order_id` INT NOT NULL,
                  `amount` DECIMAL(12, 2) NOT NULL,
                  `payment_account_id` INT DEFAULT NULL,
                  `payment_method` VARCHAR(100) DEFAULT NULL,
                  `paid_date` DATE NOT NULL,
                  `reference_no` VARCHAR(150) DEFAULT NULL,
                  `notes` TEXT DEFAULT NULL,
                  `created_by` INT DEFAULT NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  INDEX `idx_bp_company` (`company_id`),
                  INDEX `idx_bp_prod_order` (`production_order_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (\Exception $e) {
            // Ignore if permission or table already exists
        }
    }

    /**
     * Sales & Reports Executive Dashboard View
     */
    public function index(): void {
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        $this->ensurePermissionsExist($db);

        // Filter Inputs
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $buyerId = !empty($_GET['buyer_id']) ? (int)$_GET['buyer_id'] : 0;
        $warehouseId = !empty($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
        $statusFilter = $_GET['status'] ?? '';
        $batchId = !empty($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;

        // Fetch Dropdown Filter Options
        $stmtBuyers = $db->prepare("SELECT id, name FROM contacts WHERE company_id = ? AND type IN ('buyer', 'both') AND deleted_at IS NULL ORDER BY name ASC");
        $stmtBuyers->execute([$companyId]);
        $buyers = $stmtBuyers->fetchAll();

        $stmtWarehouses = $db->prepare("SELECT id, name FROM warehouses WHERE company_id = ? AND deleted_at IS NULL ORDER BY name ASC");
        $stmtWarehouses->execute([$companyId]);
        $warehouses = $stmtWarehouses->fetchAll();

        $stmtBatches = $db->prepare("SELECT pro.id, pro.production_no, COALESCE(po.quantity, 0) as target_qty FROM production_orders pro LEFT JOIN buyer_pos po ON pro.po_id = po.id WHERE pro.company_id = ? AND pro.deleted_at IS NULL ORDER BY pro.id DESC");
        $stmtBatches->execute([$companyId]);
        $batches = $stmtBatches->fetchAll();

        // Fetch Payment Accounts from Settings
        $stmtPayAcc = $db->prepare("SELECT id, name, type FROM payment_accounts WHERE company_id = ? AND deleted_at IS NULL ORDER BY name ASC");
        $stmtPayAcc->execute([$companyId]);
        $paymentAccounts = $stmtPayAcc->fetchAll() ?: [];

        // Fetch Total Payments Received per Production Batch
        $stmtPayments = $db->prepare("SELECT production_order_id, SUM(amount) as total_received, COUNT(id) as payment_count FROM batch_payments WHERE company_id = ? GROUP BY production_order_id");
        $stmtPayments->execute([$companyId]);
        $batchPaymentRows = $stmtPayments->fetchAll();
        $batchPaymentMap = [];
        foreach ($batchPaymentRows as $pr) {
            $batchPaymentMap[(int)$pr['production_order_id']] = [
                'total_received' => (float)$pr['total_received'],
                'count' => (int)$pr['payment_count']
            ];
        }

        // -------------------------------------------------------------
        // SECTION 1: PRODUCTION BATCH FINANCIALS & PROFITABILITY QUERY
        // -------------------------------------------------------------
        $batchWhere = ["pro.company_id = ? AND pro.deleted_at IS NULL"];
        $batchParams = [$companyId];

        if (!empty($startDate)) {
            $batchWhere[] = "pro.created_at >= ?";
            $batchParams[] = $startDate . " 00:00:00";
        }
        if (!empty($endDate)) {
            $batchWhere[] = "pro.created_at <= ?";
            $batchParams[] = $endDate . " 23:59:59";
        }
        if ($buyerId > 0) {
            $batchWhere[] = "po.buyer_id = ?";
            $batchParams[] = $buyerId;
        }
        if (!empty($statusFilter)) {
            $batchWhere[] = "pro.status = ?";
            $batchParams[] = $statusFilter;
        }
        if ($batchId > 0) {
            $batchWhere[] = "pro.id = ?";
            $batchParams[] = $batchId;
        }

        $batchWhereStr = implode(' AND ', $batchWhere);

        $sqlBatches = "
            SELECT pro.id as batch_id, pro.production_no, COALESCE(po.quantity, 0) as target_qty, pro.status as production_status, pro.start_date, pro.created_at,
                   po.id as po_id, po.po_no, po.delivery_date, po.quantity as po_qty, po.unit_price, po.total_amount as po_total, po.status as po_status,
                   s.style_no, s.name as style_name, s.category as garment_category,
                   b.name as buyer_name,
                   cs.yarn_cost, cs.fabric_cost, cs.processing_cost, cs.accessories_cost, cs.packing_cost, cs.total_cost as cs_total_cost,
                   COALESCE(prod_count.completed_pcs, 0) as completed_pcs,
                   COALESCE(deliv_count.delivered_pcs, 0) as delivered_pcs,
                   COALESCE(packed_count.packed_pcs, 0) as packed_pcs
            FROM production_orders pro
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN contacts b ON po.buyer_id = b.id
            LEFT JOIN cost_sheets cs ON (s.id = cs.style_id AND cs.deleted_at IS NULL)
            LEFT JOIN (
                SELECT production_order_id, COUNT(DISTINCT scanned_qr_code) as completed_pcs
                FROM production_stage_logs
                WHERE company_id = ?
                GROUP BY production_order_id
            ) prod_count ON pro.id = prod_count.production_order_id
            LEFT JOIN (
                SELECT ci.production_order_id, SUM(ci.qty) as delivered_pcs
                FROM carton_items ci
                JOIN cartons c ON ci.carton_id = c.id
                WHERE c.company_id = ? AND c.status IN ('dispatched', 'delivered')
                GROUP BY ci.production_order_id
            ) deliv_count ON pro.id = deliv_count.production_order_id
            LEFT JOIN (
                SELECT ci.production_order_id, SUM(ci.qty) as packed_pcs
                FROM carton_items ci
                JOIN cartons c ON ci.carton_id = c.id
                WHERE c.company_id = ?
                GROUP BY ci.production_order_id
            ) packed_count ON pro.id = packed_count.production_order_id
            WHERE {$batchWhereStr}
            ORDER BY pro.id DESC
        ";

        $fullBatchParams = array_merge([$companyId, $companyId, $companyId], $batchParams);
        $stmtBatchList = $db->prepare($sqlBatches);
        $stmtBatchList->execute($fullBatchParams);
        $rawBatches = $stmtBatchList->fetchAll();

        // Process Financial Calculations per Batch
        $batchReportList = [];
        $totalBuyerOrdersCount = count(array_unique(array_filter(array_column($rawBatches, 'po_id'))));
        $totalBatchesCount = count($rawBatches);
        $completedBatchesCount = 0;
        $wipBatchesCount = 0;
        $plannedBatchesCount = 0;
        $cancelledBatchesCount = 0;

        $kpiProcurementCost = 0.00;
        $kpiManufacturingCost = 0.00;
        $kpiTotalSalesValue = 0.00;
        $kpiGrossMfgValue = 0.00;
        $kpiGrossProfit = 0.00;
        $kpiNetProfit = 0.00;
        $kpiPendingPayments = 0.00;
        $kpiPartiallyReceived = 0.00;
        $kpiFullyReceived = 0.00;
        $kpiOutstandingReceivables = 0.00;
        $kpiDeliveredOrdersCount = 0;

        foreach ($rawBatches as $bRow) {
            $status = $bRow['production_status'];
            if ($status === 'completed') $completedBatchesCount++;
            elseif ($status === 'in_progress') $wipBatchesCount++;
            elseif ($status === 'cancelled') $cancelledBatchesCount++;
            else $plannedBatchesCount++;

            $targetQty = (int)($bRow['target_qty'] ?: 1);
            $completedQty = (int)$bRow['completed_pcs'];
            $deliveredQty = (int)$bRow['delivered_pcs'];
            $producedQty = max($completedQty, ($status === 'completed' ? $targetQty : $completedQty));

            $unitPrice = (float)($bRow['unit_price'] ?: 0);
            $costPerPc = (float)($bRow['cs_total_cost'] ?: 0);

            // Expense Breakdown per Pc
            $yarnCost = (float)($bRow['yarn_cost'] ?: 0);
            $fabricCost = (float)($bRow['fabric_cost'] ?: 0);
            $accessoriesCost = (float)($bRow['accessories_cost'] ?: 0);
            $procurementUnitCost = $yarnCost + $fabricCost + $accessoriesCost;
            if ($procurementUnitCost <= 0) $procurementUnitCost = $costPerPc * 0.60;

            $processingCost = (float)($bRow['processing_cost'] ?: 0);
            $packingCost = (float)($bRow['packing_cost'] ?: 0);
            $mfgUnitCost = $processingCost + $packingCost;
            if ($mfgUnitCost <= 0) $mfgUnitCost = $costPerPc * 0.40;

            $batchProcurementCost = $procurementUnitCost * $targetQty;
            $batchMfgCost = $mfgUnitCost * $producedQty;
            $batchLogisticsCost = $deliveredQty * 5.00;
            $batchTotalCost = $batchProcurementCost + $batchMfgCost + $batchLogisticsCost;

            $unitCost = $producedQty > 0 ? ($batchTotalCost / $producedQty) : $costPerPc;
            $totalSalesValue = ($deliveredQty > 0 ? $deliveredQty : $producedQty) * $unitPrice;
            if ($totalSalesValue <= 0 && $targetQty > 0 && $unitPrice > 0) {
                $totalSalesValue = $targetQty * $unitPrice;
            }
            $grossMfgValue = $producedQty * $unitPrice;

            $grossProfit = $totalSalesValue - ($batchProcurementCost + $batchMfgCost);
            $netProfit = $totalSalesValue - $batchTotalCost;
            $profitMarginPct = $totalSalesValue > 0 ? (($netProfit / $totalSalesValue) * 100) : 0.00;

            // REAL-TIME PAYMENT TRACKING
            $payInfo = $batchPaymentMap[(int)$bRow['batch_id']] ?? ['total_received' => 0.00, 'count' => 0];
            $paymentReceived = (float)$payInfo['total_received'];
            $balanceDue = max(0.00, $totalSalesValue - $paymentReceived);

            if ($paymentReceived >= ($totalSalesValue - 0.01) && $totalSalesValue > 0) {
                $paymentStatus = 'Fully Received';
                $kpiFullyReceived += $paymentReceived;
            } elseif ($paymentReceived > 0) {
                $paymentStatus = 'Partially Received';
                $kpiPartiallyReceived += $paymentReceived;
            } else {
                $paymentStatus = 'Pending';
                $kpiPendingPayments += $totalSalesValue;
            }
            $kpiOutstandingReceivables += $balanceDue;

            if ($deliveredQty > 0) $kpiDeliveredOrdersCount++;

            $kpiProcurementCost += $batchProcurementCost;
            $kpiManufacturingCost += $batchMfgCost;
            $kpiTotalSalesValue += $totalSalesValue;
            $kpiGrossMfgValue += $grossMfgValue;
            $kpiGrossProfit += $grossProfit;
            $kpiNetProfit += $netProfit;

            $batchReportList[] = [
                'batch_id' => $bRow['batch_id'],
                'batch_no' => $bRow['production_no'],
                'buyer_name' => $bRow['buyer_name'] ?: 'Direct Contract',
                'po_no' => $bRow['po_no'] ?: 'N/A',
                'style_display' => trim(($bRow['style_no'] ?? '') . ' - ' . ($bRow['style_name'] ?? '')),
                'garment_category' => ucfirst($bRow['garment_category'] ?: 'Unisex'),
                'target_qty' => $targetQty,
                'completed_qty' => $producedQty,
                'delivered_qty' => $deliveredQty,
                'production_status' => $status,
                'procurement_cost' => $batchProcurementCost,
                'mfg_cost' => $batchMfgCost,
                'logistics_cost' => $batchLogisticsCost,
                'total_cost' => $batchTotalCost,
                'unit_cost' => $unitCost,
                'selling_price' => $unitPrice,
                'total_sales_value' => $totalSalesValue,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'margin_pct' => $profitMarginPct,
                'invoice_status' => $totalSalesValue > 0 ? 'Invoice Issued' : 'Draft',
                'payment_status' => $paymentStatus,
                'payment_received' => $paymentReceived,
                'balance_due' => $balanceDue,
                'payment_count' => $payInfo['count'],
                'expected_delivery' => $bRow['delivery_date'] ?: 'N/A',
                'batch_status_badge' => $status === 'completed' ? 'Delivered / Closed' : ($status === 'in_progress' ? 'Running WIP' : 'Planned')
            ];
        }

        // -------------------------------------------------------------
        // SECTION 2: CARTON & WAREHOUSE SALES ANALYSIS QUERY
        // -------------------------------------------------------------
        $cartonWhere = ["c.company_id = ?"];
        $cartonParams = [$companyId];

        if (!empty($startDate)) {
            $cartonWhere[] = "c.created_at >= ?";
            $cartonParams[] = $startDate . " 00:00:00";
        }
        if (!empty($endDate)) {
            $cartonWhere[] = "c.created_at <= ?";
            $cartonParams[] = $endDate . " 23:59:59";
        }
        if ($warehouseId > 0) {
            $cartonWhere[] = "c.warehouse_id = ?";
            $cartonParams[] = $warehouseId;
        }

        $cartonWhereStr = implode(' AND ', $cartonWhere);

        $sqlCartons = "
            SELECT c.id as carton_id, c.carton_no, c.status as carton_status, c.created_at as packed_at,
                   pro.production_no,
                   po.unit_price, po.po_no,
                   s.style_no, s.name as style_name,
                   cs.total_cost as unit_mfg_cost,
                   w.name as warehouse_name,
                   cl.name as client_name,
                   shp.shipment_no, shp.status as shipment_status,
                   COALESCE(ci_count.item_qty, 0) as total_qty
            FROM cartons c
            LEFT JOIN production_orders pro ON c.production_order_id = pro.id
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN cost_sheets cs ON (s.id = cs.style_id AND cs.deleted_at IS NULL)
            LEFT JOIN warehouses w ON c.warehouse_id = w.id
            LEFT JOIN contacts cl ON c.client_id = cl.id
            LEFT JOIN shipment_cartons sc ON c.id = sc.carton_id
            LEFT JOIN shipments shp ON sc.shipment_id = shp.id
            LEFT JOIN (
                SELECT carton_id, SUM(qty) as item_qty
                FROM carton_items
                GROUP BY carton_id
            ) ci_count ON c.id = ci_count.carton_id
            WHERE {$cartonWhereStr}
            ORDER BY c.id DESC
        ";

        $stmtCartonList = $db->prepare($sqlCartons);
        $stmtCartonList->execute($cartonParams);
        $rawCartons = $stmtCartonList->fetchAll();

        $cartonAnalysisList = [];
        $kpiWarehouseStockValue = 0.00;
        $kpiReadyDispatchValue = 0.00;

        foreach ($rawCartons as $cRow) {
            $qty = (int)($cRow['total_qty'] ?: 1);
            $unitPrice = (float)($cRow['unit_price'] ?: 0);
            $unitCost = (float)($cRow['unit_mfg_cost'] ?: 0);

            $totalMfgCost = $qty * $unitCost;
            $estimatedSalesValue = $qty * $unitPrice;
            $expectedProfit = $estimatedSalesValue - $totalMfgCost;
            $expectedMarginPct = $estimatedSalesValue > 0 ? (($expectedProfit / $estimatedSalesValue) * 100) : 0.00;

            $status = $cRow['carton_status'];
            $statusLabel = 'In Warehouse';
            if ($status === 'delivered') {
                $statusLabel = 'Delivered';
            } elseif ($status === 'dispatched') {
                $statusLabel = 'Dispatched';
            } else {
                $kpiWarehouseStockValue += $estimatedSalesValue;
                $kpiReadyDispatchValue += $estimatedSalesValue;
            }

            $cartonAnalysisList[] = [
                'carton_id' => $cRow['carton_id'],
                'carton_no' => $cRow['carton_no'],
                'shipment_no' => $cRow['shipment_no'] ?: 'Unshipped',
                'style_display' => trim(($cRow['style_no'] ?? '') . ' - ' . ($cRow['style_name'] ?? '')),
                'total_qty' => $qty,
                'batch_no' => $cRow['production_no'] ?: 'N/A',
                'packed_at' => date('d M Y, H:i', strtotime($cRow['packed_at'])),
                'location' => $cRow['warehouse_name'] ?: ($cRow['client_name'] ?: 'Factory Storage'),
                'dispatch_status' => $statusLabel,
                'total_mfg_cost' => $totalMfgCost,
                'estimated_sales_value' => $estimatedSalesValue,
                'expected_profit' => $expectedProfit,
                'expected_margin_pct' => $expectedMarginPct
            ];
        }

        // Overall KPI Aggregates
        $kpiMarginPct = $kpiTotalSalesValue > 0 ? (($kpiNetProfit / $kpiTotalSalesValue) * 100) : 0.00;
        $kpiEfficiencyPct = $totalBatchesCount > 0 ? (($completedBatchesCount / $totalBatchesCount) * 100) : 0.00;

        $kpis = [
            'total_buyer_orders' => $totalBuyerOrdersCount,
            'total_batches' => $totalBatchesCount,
            'completed_batches' => $completedBatchesCount,
            'wip_batches' => $wipBatchesCount,
            'planned_batches' => $plannedBatchesCount,
            'cancelled_batches' => $cancelledBatchesCount,
            'procurement_cost' => $kpiProcurementCost,
            'mfg_cost' => $kpiManufacturingCost,
            'total_sales_value' => $kpiTotalSalesValue,
            'gross_mfg_value' => $kpiGrossMfgValue,
            'gross_profit' => $kpiGrossProfit,
            'net_profit' => $kpiNetProfit,
            'profit_margin_pct' => $kpiMarginPct,
            'pending_payments' => $kpiPendingPayments,
            'partially_received' => $kpiPartiallyReceived,
            'fully_received' => $kpiFullyReceived,
            'outstanding_receivables' => $kpiOutstandingReceivables,
            'delivered_orders_count' => $kpiDeliveredOrdersCount,
            'warehouse_stock_value' => $kpiWarehouseStockValue,
            'ready_dispatch_value' => $kpiReadyDispatchValue,
            'overall_efficiency_pct' => $kpiEfficiencyPct
        ];

        $this->renderView('company/sales_reports/index', [
            'title' => 'Sales & Executive Financial Reports',
            'kpis' => $kpis,
            'batchReportList' => $batchReportList,
            'cartonAnalysisList' => $cartonAnalysisList,
            'buyers' => $buyers,
            'warehouses' => $warehouses,
            'batches' => $batches,
            'paymentAccounts' => $paymentAccounts,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'buyer_id' => $buyerId,
                'warehouse_id' => $warehouseId,
                'status' => $statusFilter,
                'batch_id' => $batchId
            ]
        ]);
    }

    /**
     * AJAX Endpoint: Fetch Payment Receipts & History for a Production Batch
     */
    public function getBatchPayments(int $id): void {
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        $stmtB = $db->prepare("
            SELECT pro.id as batch_id, pro.production_no, COALESCE(po.quantity, 0) as target_qty,
                   po.po_no, po.unit_price, s.style_no, s.name as style_name, b.name as buyer_name
            FROM production_orders pro
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN contacts b ON po.buyer_id = b.id
            WHERE pro.id = ? AND pro.company_id = ? AND pro.deleted_at IS NULL
        ");
        $stmtB->execute([$id, $companyId]);
        $batch = $stmtB->fetch();

        if (!$batch) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Production batch not found']);
            return;
        }

        $stmtP = $db->prepare("
            SELECT bp.*, pa.name as account_name, pa.type as account_type
            FROM batch_payments bp
            LEFT JOIN payment_accounts pa ON bp.payment_account_id = pa.id
            WHERE bp.production_order_id = ? AND bp.company_id = ?
            ORDER BY bp.paid_date DESC, bp.id DESC
        ");
        $stmtP->execute([$id, $companyId]);
        $payments = $stmtP->fetchAll() ?: [];

        $stmtAcc = $db->prepare("SELECT id, name, type FROM payment_accounts WHERE company_id = ? AND deleted_at IS NULL ORDER BY name ASC");
        $stmtAcc->execute([$companyId]);
        $paymentAccounts = $stmtAcc->fetchAll() ?: [];

        $targetQty = (int)($batch['target_qty'] ?: 1);
        $unitPrice = (float)($batch['unit_price'] ?: 0);
        $totalSalesValue = $targetQty * $unitPrice;
        $totalReceived = array_sum(array_column($payments, 'amount'));
        $balanceDue = max(0.00, $totalSalesValue - $totalReceived);

        if ($totalReceived >= ($totalSalesValue - 0.01) && $totalSalesValue > 0) {
            $paymentStatus = 'Fully Received';
        } elseif ($totalReceived > 0) {
            $paymentStatus = 'Partially Received';
        } else {
            $paymentStatus = 'Pending';
        }

        header('Content-Type: application/json');
        echo json_encode([
            'batch' => $batch,
            'payments' => $payments,
            'payment_accounts' => $paymentAccounts,
            'total_sales_value' => $totalSalesValue,
            'total_received' => $totalReceived,
            'balance_due' => $balanceDue,
            'payment_status' => $paymentStatus
        ]);
    }

    /**
     * AJAX Endpoint: Record New Payment for a Production Batch
     */
    public function recordPayment(): void {
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        $rawInput = file_get_contents('php://input');
        $json = json_decode($rawInput, true) ?: [];

        $batchId = (int)($_POST['batch_id'] ?? ($json['batch_id'] ?? 0));
        $amount = (float)($_POST['amount'] ?? ($json['amount'] ?? 0));
        $paymentAccountId = !empty($_POST['payment_account_id']) ? (int)$_POST['payment_account_id'] : (!empty($json['payment_account_id']) ? (int)$json['payment_account_id'] : null);
        $paidDate = trim($_POST['paid_date'] ?? ($json['paid_date'] ?? date('Y-m-d')));
        $referenceNo = trim($_POST['reference_no'] ?? ($json['reference_no'] ?? ''));
        $notes = trim($_POST['notes'] ?? ($json['notes'] ?? ''));

        if ($batchId <= 0 || $amount <= 0.00) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Batch ID and a valid payment amount > 0 are required.']);
            return;
        }

        $stmtB = $db->prepare("
            SELECT pro.id, pro.production_no, COALESCE(po.quantity, 0) as target_qty, po.unit_price
            FROM production_orders pro
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            WHERE pro.id = ? AND pro.company_id = ? AND pro.deleted_at IS NULL
        ");
        $stmtB->execute([$batchId, $companyId]);
        $batch = $stmtB->fetch();

        if (!$batch) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Batch not found.']);
            return;
        }

        $targetQty = (int)($batch['target_qty'] ?: 1);
        $unitPrice = (float)($batch['unit_price'] ?: 0);
        $totalSalesValue = $targetQty * $unitPrice;

        $stmtSum = $db->prepare("SELECT SUM(amount) FROM batch_payments WHERE production_order_id = ? AND company_id = ?");
        $stmtSum->execute([$batchId, $companyId]);
        $currentReceived = (float)($stmtSum->fetchColumn() ?: 0.00);

        if ($currentReceived + $amount > $totalSalesValue + 0.01) {
            $maxAllowed = max(0.00, $totalSalesValue - $currentReceived);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => "Received amount (₹" . number_format($amount, 2) . ") exceeds total revenue (₹" . number_format($totalSalesValue, 2) . "). Maximum remaining payable amount is ₹" . number_format($maxAllowed, 2)
            ]);
            return;
        }

        $paymentMethod = 'Direct Payment';
        if ($paymentAccountId) {
            $stmtAcc = $db->prepare("SELECT name, type FROM payment_accounts WHERE id = ? AND company_id = ?");
            $stmtAcc->execute([$paymentAccountId, $companyId]);
            $acc = $stmtAcc->fetch();
            if ($acc) {
                $paymentMethod = $acc['name'] . ' (' . $acc['type'] . ')';
            }
        }

        $stmtIns = $db->prepare("
            INSERT INTO batch_payments (company_id, production_order_id, amount, payment_account_id, payment_method, paid_date, reference_no, notes, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtIns->execute([
            $companyId,
            $batchId,
            $amount,
            $paymentAccountId,
            $paymentMethod,
            $paidDate,
            $referenceNo,
            $notes,
            $userId
        ]);

        $newReceived = $currentReceived + $amount;
        $newBalance = max(0.00, $totalSalesValue - $newReceived);

        if ($newReceived >= ($totalSalesValue - 0.01) && $totalSalesValue > 0) {
            $newStatus = 'Fully Received';
        } elseif ($newReceived > 0) {
            $newStatus = 'Partially Received';
        } else {
            $newStatus = 'Pending';
        }

        AuditLog::log($companyId, $userId, 'record_batch_payment', 'BatchPayment', $db->lastInsertId(), null, null, "Recorded payment ₹" . number_format($amount, 2) . " for Batch {$batch['production_no']}");

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Payment of ₹" . number_format($amount, 2) . " recorded successfully!",
            'total_received' => $newReceived,
            'balance_due' => $newBalance,
            'payment_status' => $newStatus
        ]);
    }

    /**
     * AJAX Endpoint: Fetch Itemized Carton Contents for Modal Drill-Down
     */
    public function getCartonDetails(int $id): void {
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        $stmtCtn = $db->prepare("
            SELECT c.*, pro.production_no, s.style_no, s.name as style_name, po.po_no, w.name as warehouse_name
            FROM cartons c
            LEFT JOIN production_orders pro ON c.production_order_id = pro.id
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN warehouses w ON c.warehouse_id = w.id
            WHERE c.id = ? AND c.company_id = ?
        ");
        $stmtCtn->execute([$id, $companyId]);
        $carton = $stmtCtn->fetch();

        if (!$carton) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Carton not found']);
            return;
        }

        $stmtItems = $db->prepare("
            SELECT ci.*, psl.created_at as packed_timestamp
            FROM carton_items ci
            LEFT JOIN production_stage_logs psl ON ci.product_qr_code = psl.scanned_qr_code
            WHERE ci.carton_id = ?
            GROUP BY ci.id
            ORDER BY ci.id ASC
        ");
        $stmtItems->execute([$id]);
        $items = $stmtItems->fetchAll();

        header('Content-Type: application/json');
        echo json_encode([
            'carton' => $carton,
            'items' => $items
        ]);
    }

    /**
     * Export Section 1: Production Batch Financials to CSV / Excel
     */
    public function exportBatchFinancials(): void {
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        $fileName = "Production_Batch_Financials_Report_" . date('Y-m-d_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Batch No.', 'Buyer / Client', 'Customer PO', 'Style Code & Name', 'Garment Category',
            'Qty Ordered', 'Qty Produced', 'Qty Delivered', 'Production Status', 'Procurement Cost (INR)',
            'Manufacturing Cost (INR)', 'Logistics Cost (INR)', 'Total Cost (INR)', 'Unit Cost (INR)',
            'Selling Price (INR)', 'Total Sales Value (INR)', 'Gross Profit (INR)', 'Net Profit (INR)',
            'Profit Margin (%)', 'Invoice Status', 'Payment Status', 'Payment Received (INR)', 'Balance Due (INR)',
            'Expected Delivery Date', 'Batch Status'
        ]);

        $stmt = $db->prepare("
            SELECT pro.id as batch_id, pro.production_no, b.name as buyer_name, po.po_no, s.style_no, s.name as style_name, s.category,
                   COALESCE(po.quantity, 0) as target_qty, pro.status, po.unit_price, cs.total_cost as cs_total_cost, po.delivery_date
            FROM production_orders pro
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN contacts b ON po.buyer_id = b.id
            LEFT JOIN cost_sheets cs ON (s.id = cs.style_id AND cs.deleted_at IS NULL)
            WHERE pro.company_id = ? AND pro.deleted_at IS NULL
            ORDER BY pro.id DESC
        ");
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll();

        // Fetch payment totals
        $stmtPayments = $db->prepare("SELECT production_order_id, SUM(amount) as total_received FROM batch_payments WHERE company_id = ? GROUP BY production_order_id");
        $stmtPayments->execute([$companyId]);
        $batchPaymentMap = [];
        foreach ($stmtPayments->fetchAll() as $pr) {
            $batchPaymentMap[(int)$pr['production_order_id']] = (float)$pr['total_received'];
        }

        foreach ($rows as $r) {
            $targetQty = (int)($r['target_qty'] ?: 1);
            $unitPrice = (float)($r['unit_price'] ?: 0);
            $costPerPc = (float)($r['cs_total_cost'] ?: 0);

            $procurementCost = $targetQty * ($costPerPc * 0.60);
            $mfgCost = $targetQty * ($costPerPc * 0.40);
            $logisticsCost = $targetQty * 5.00;
            $totalCost = $procurementCost + $mfgCost + $logisticsCost;

            $totalSales = $targetQty * $unitPrice;
            $grossProfit = $totalSales - ($procurementCost + $mfgCost);
            $netProfit = $totalSales - $totalCost;
            $marginPct = $totalSales > 0 ? (($netProfit / $totalSales) * 100) : 0.00;

            $received = (float)($batchPaymentMap[(int)$r['batch_id']] ?? 0.00);
            $balance = max(0.00, $totalSales - $received);

            if ($received >= ($totalSales - 0.01) && $totalSales > 0) {
                $pStatus = 'Fully Received';
            } elseif ($received > 0) {
                $pStatus = 'Partially Received';
            } else {
                $pStatus = 'Pending';
            }

            fputcsv($output, [
                $r['production_no'],
                $r['buyer_name'] ?: 'Direct',
                $r['po_no'] ?: 'N/A',
                trim(($r['style_no'] ?? '') . ' - ' . ($r['style_name'] ?? '')),
                ucfirst($r['category'] ?: 'Unisex'),
                $targetQty,
                $targetQty,
                $targetQty,
                ucfirst($r['status']),
                number_format($procurementCost, 2, '.', ''),
                number_format($mfgCost, 2, '.', ''),
                number_format($logisticsCost, 2, '.', ''),
                number_format($totalCost, 2, '.', ''),
                number_format($targetQty > 0 ? $totalCost / $targetQty : $costPerPc, 2, '.', ''),
                number_format($unitPrice, 2, '.', ''),
                number_format($totalSales, 2, '.', ''),
                number_format($grossProfit, 2, '.', ''),
                number_format($netProfit, 2, '.', ''),
                number_format($marginPct, 2, '.', ''),
                'Issued',
                $pStatus,
                number_format($received, 2, '.', ''),
                number_format($balance, 2, '.', ''),
                $r['delivery_date'] ?: 'N/A',
                ucfirst($r['status'])
            ]);
        }
        fclose($output);
        exit;
    }

    /**
     * Export Section 2: Carton & Warehouse Sales Analysis to CSV / Excel
     */
    public function exportCartonAnalysis(): void {
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        $fileName = "Carton_Warehouse_Sales_Analysis_" . date('Y-m-d_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Carton ID', 'Shipment ID', 'Style Code & Name', 'Total Quantity (pcs)', 'Batch No.',
            'Packing Date & Time', 'Warehouse / Client Location', 'Dispatch Status', 'Delivery Status',
            'Total Mfg Cost (INR)', 'Gross Sales Value (INR)', 'Expected Profit (INR)', 'Expected Margin (%)',
            'Current Inventory Value (INR)'
        ]);

        $stmt = $db->prepare("
            SELECT c.carton_no, shp.shipment_no, s.style_no, s.name as style_name, c.created_at as packed_at,
                   c.status as carton_status, w.name as warehouse_name, cl.name as client_name,
                   pro.production_no, po.unit_price, cs.total_cost as unit_mfg_cost,
                   COALESCE(ci_count.item_qty, 0) as total_qty
            FROM cartons c
            LEFT JOIN production_orders pro ON c.production_order_id = pro.id
            LEFT JOIN buyer_pos po ON pro.po_id = po.id
            LEFT JOIN styles s ON po.style_id = s.id
            LEFT JOIN cost_sheets cs ON (s.id = cs.style_id AND cs.deleted_at IS NULL)
            LEFT JOIN warehouses w ON c.warehouse_id = w.id
            LEFT JOIN contacts cl ON c.client_id = cl.id
            LEFT JOIN shipment_cartons sc ON c.id = sc.carton_id
            LEFT JOIN shipments shp ON sc.shipment_id = shp.id
            LEFT JOIN (
                SELECT carton_id, SUM(qty) as item_qty
                FROM carton_items
                GROUP BY carton_id
            ) ci_count ON c.id = ci_count.carton_id
            WHERE c.company_id = ?
            ORDER BY c.id DESC
        ");
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $r) {
            $qty = (int)($r['total_qty'] ?: 1);
            $unitPrice = (float)($r['unit_price'] ?: 0);
            $unitCost = (float)($r['unit_mfg_cost'] ?: 0);

            $mfgCost = $qty * $unitCost;
            $salesValue = $qty * $unitPrice;
            $profit = $salesValue - $mfgCost;
            $marginPct = $salesValue > 0 ? (($profit / $salesValue) * 100) : 0.00;

            fputcsv($output, [
                $r['carton_no'],
                $r['shipment_no'] ?: 'Unshipped',
                trim(($r['style_no'] ?? '') . ' - ' . ($r['style_name'] ?? '')),
                $qty,
                $r['production_no'] ?: 'N/A',
                $r['packed_at'],
                $r['warehouse_name'] ?: ($r['client_name'] ?: 'Factory Storage'),
                ucfirst($r['carton_status']),
                ucfirst($r['carton_status']),
                number_format($mfgCost, 2, '.', ''),
                number_format($salesValue, 2, '.', ''),
                number_format($profit, 2, '.', ''),
                number_format($marginPct, 2, '.', ''),
                number_format($salesValue, 2, '.', '')
            ]);
        }
        fclose($output);
        exit;
    }
}
