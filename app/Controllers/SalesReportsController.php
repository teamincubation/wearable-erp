<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Auth;
use PDO;

class SalesReportsController {

    /**
     * Auto-seed sales_reports permission if not exists
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
                // Assign to Company Admin role (role_id = 2) if not mapped
                $db->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, {$permId})");
            }
        } catch (\Exception $e) {
            // Ignore if permission already exists or schema mismatch
        }
    }

    /**
     * Sales & Reports Executive Dashboard View
     */
    public function index(): void {
        Auth::requirePermission('company.sales_reports');
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

        $stmtBatches = $db->prepare("SELECT id, production_no, target_qty FROM production_orders WHERE company_id = ? AND deleted_at IS NULL ORDER BY id DESC");
        $stmtBatches->execute([$companyId]);
        $batches = $stmtBatches->fetchAll();

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
            SELECT pro.id as batch_id, pro.production_no, pro.target_qty, pro.status as production_status, pro.start_date, pro.created_at,
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
            if ($procurementUnitCost <= 0) $procurementUnitCost = $costPerPc * 0.60; // 60% default estimation if not specified

            $processingCost = (float)($bRow['processing_cost'] ?: 0);
            $packingCost = (float)($bRow['packing_cost'] ?: 0);
            $mfgUnitCost = $processingCost + $packingCost;
            if ($mfgUnitCost <= 0) $mfgUnitCost = $costPerPc * 0.40;

            $batchProcurementCost = $procurementUnitCost * $targetQty;
            $batchMfgCost = $mfgUnitCost * $producedQty;
            $batchLogisticsCost = $deliveredQty * 5.00; // Rs 5 per pc logistics allocation
            $batchTotalCost = $batchProcurementCost + $batchMfgCost + $batchLogisticsCost;

            $unitCost = $producedQty > 0 ? ($batchTotalCost / $producedQty) : $costPerPc;
            $totalSalesValue = ($deliveredQty > 0 ? $deliveredQty : $producedQty) * $unitPrice;
            $grossMfgValue = $producedQty * $unitPrice;

            $grossProfit = $totalSalesValue - ($batchProcurementCost + $batchMfgCost);
            $netProfit = $totalSalesValue - $batchTotalCost;
            $profitMarginPct = $totalSalesValue > 0 ? (($netProfit / $totalSalesValue) * 100) : 0.00;

            // Payment Calculations
            $poStatus = $bRow['po_status'];
            if ($poStatus === 'approved' || $status === 'completed') {
                $paymentStatus = 'Fully Received';
                $paymentReceived = $totalSalesValue;
                $balanceDue = 0.00;
                $kpiFullyReceived += $totalSalesValue;
            } elseif ($status === 'in_progress') {
                $paymentStatus = 'Partially Received';
                $paymentReceived = $totalSalesValue * 0.50;
                $balanceDue = $totalSalesValue * 0.50;
                $kpiPartiallyReceived += $paymentReceived;
            } else {
                $paymentStatus = 'Pending';
                $paymentReceived = 0.00;
                $balanceDue = $totalSalesValue;
                $kpiPendingPayments += $totalSalesValue;
            }

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

        $stmtCartons = $db->prepare($sqlCartons);
        $stmtCartons->execute($cartonParams);
        $rawCartons = $stmtCartons->fetchAll();

        $cartonAnalysisList = [];
        $kpiWarehouseStockValue = 0.00;
        $kpiReadyDispatchValue = 0.00;

        foreach ($rawCartons as $cRow) {
            $qty = (int)($cRow['total_qty'] ?: 1);
            $unitPrice = (float)($cRow['unit_price'] ?: 0);
            $unitMfgCost = (float)($cRow['unit_mfg_cost'] ?: 0);

            $totalMfgCost = $qty * $unitMfgCost;
            $estimatedSalesValue = $qty * $unitPrice;
            $expectedProfit = $estimatedSalesValue - $totalMfgCost;
            $expectedMarginPct = $estimatedSalesValue > 0 ? (($expectedProfit / $estimatedSalesValue) * 100) : 0.00;

            $status = $cRow['carton_status'];
            if ($status === 'packed' || $status === 'in_warehouse') {
                $kpiWarehouseStockValue += $estimatedSalesValue;
                $kpiReadyDispatchValue += $estimatedSalesValue;
            }

            $location = $cRow['warehouse_name'] ?: ($cRow['client_name'] ?: 'Factory Central Storage');

            $cartonAnalysisList[] = [
                'carton_id' => $cRow['carton_id'],
                'carton_no' => $cRow['carton_no'],
                'shipment_no' => $cRow['shipment_no'] ?: 'Unshipped',
                'style_display' => trim(($cRow['style_no'] ?? '') . ' - ' . ($cRow['style_name'] ?? '')),
                'total_qty' => $qty,
                'batch_no' => $cRow['production_no'] ?: 'N/A',
                'packed_at' => $cRow['packed_at'],
                'location' => $location,
                'dispatch_status' => ucfirst($status),
                'delivery_status' => ucfirst($cRow['shipment_status'] ?: $status),
                'total_mfg_cost' => $totalMfgCost,
                'gross_mfg_value' => $estimatedSalesValue,
                'estimated_sales_value' => $estimatedSalesValue,
                'expected_profit' => $expectedProfit,
                'expected_margin_pct' => $expectedMarginPct,
                'current_inventory_value' => $estimatedSalesValue
            ];
        }

        // Overall KPI Aggregates
        $kpiMarginPct = $kpiTotalSalesValue > 0 ? (($kpiNetProfit / $kpiTotalSalesValue) * 100) : 0.00;
        $kpiOutstandingReceivables = max(0, $kpiTotalSalesValue - ($kpiFullyReceived + $kpiPartiallyReceived));
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

        view('company/sales_reports/index', [
            'title' => 'Sales & Executive Financial Reports',
            'kpis' => $kpis,
            'batchReportList' => $batchReportList,
            'cartonAnalysisList' => $cartonAnalysisList,
            'buyers' => $buyers,
            'warehouses' => $warehouses,
            'batches' => $batches,
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
     * AJAX Endpoint: Fetch Itemized Carton Contents for Modal Drill-Down
     */
    public function getCartonDetails(int $id): void {
        Auth::requirePermission('company.sales_reports');
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
        Auth::requirePermission('company.sales_reports');
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
            SELECT pro.production_no, b.name as buyer_name, po.po_no, s.style_no, s.name as style_name, s.category,
                   pro.target_qty, pro.status, po.unit_price, cs.total_cost as cs_total_cost, po.delivery_date
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
                'Fully Received',
                number_format($totalSales, 2, '.', ''),
                '0.00',
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
        Auth::requirePermission('company.sales_reports');
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
