<?php
namespace App\Services;

use App\Core\Database;
use Exception;
use PDO;

/**
 * Reusable Production Workflow Tracking Service
 * Senior Solution Architect - Antigravity
 */
class ProductionService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Log a production stage activity and update WIP
     */
    public function logProductionStage(
        int $companyId,
        int $productionOrderId,
        string $stage, // knitting, dyeing, compacting, sewing, packing, etc.
        ?int $machineId,
        ?int $employeeId,
        int $qtyIn,
        int $qtyOut,
        int $wasteQty,
        string $startTime,
        string $endTime,
        ?int $userId = null
    ): int {
        // Calculate duration in minutes
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        $durationMinutes = $end > $start ? (int) (($end - $start) / 60) : 0;

        // Sanitize FK fields to prevent constraint failures
        $validEmployeeId = null;
        if (!empty($employeeId) && (int)$employeeId > 0 && (int)$employeeId !== 999999) {
            $stmtCheck = $this->db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
            $stmtCheck->execute([(int)$employeeId]);
            if ($stmtCheck->fetchColumn()) {
                $validEmployeeId = (int)$employeeId;
            }
        }

        $validMachineId = null;
        if (!empty($machineId) && (int)$machineId > 0) {
            $stmtCheck = $this->db->prepare("SELECT id FROM machines WHERE id = ? LIMIT 1");
            $stmtCheck->execute([(int)$machineId]);
            if ($stmtCheck->fetchColumn()) {
                $validMachineId = (int)$machineId;
            }
        }

        $validCreatedBy = null;
        if (!empty($userId) && (int)$userId > 0 && (int)$userId !== 999999) {
            $stmtCheck = $this->db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
            $stmtCheck->execute([(int)$userId]);
            if ($stmtCheck->fetchColumn()) {
                $validCreatedBy = (int)$userId;
            }
        }

        $sql = "INSERT INTO production_stage_logs (
                    company_id, production_order_id, stage, machine_id, 
                    employee_id, qty_in, qty_out, waste_qty, 
                    start_time, end_time, duration_minutes, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $companyId,
            $productionOrderId,
            $stage,
            $validMachineId,
            $validEmployeeId,
            $qtyIn,
            $qtyOut,
            $wasteQty,
            $startTime,
            $endTime,
            $durationMinutes,
            $validCreatedBy
        ]);

        $logId = (int) $this->db->lastInsertId();

        // If machine utilization is logged, insert into machine_utilization_logs
        if ($machineId !== null && $durationMinutes > 0) {
            $this->recordMachineUtilization($companyId, $machineId, $productionOrderId, date('Y-m-d', $start), $durationMinutes, 0, null, $userId);
        }

        return $logId;
    }

    /**
     * Record machine utilization metrics
     */
    public function recordMachineUtilization(
        int $companyId,
        int $machineId,
        int $productionOrderId,
        string $date,
        int $runtimeMinutes,
        int $downtimeMinutes = 0,
        ?string $reasonDowntime = null,
        ?int $userId = null
    ): void {
        $sql = "INSERT INTO machine_utilization_logs (
                    company_id, machine_id, production_order_id, date, 
                    runtime_minutes, downtime_minutes, reason_downtime, 
                    created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $companyId,
            $machineId,
            $productionOrderId,
            $date,
            $runtimeMinutes,
            $downtimeMinutes,
            $reasonDowntime,
            $userId
        ]);
    }

    /**
     * Calculate Work in Progress (WIP) balances for a production order
     */
    public function getOrderWipSummary(int $companyId, int $productionOrderId): array {
        $sql = "SELECT stage, SUM(qty_in) as total_in, SUM(qty_out) as total_out, SUM(waste_qty) as total_waste
                FROM production_stage_logs 
                WHERE company_id = ? AND production_order_id = ?
                GROUP BY stage";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$companyId, $productionOrderId]);
        $stages = $stmt->fetchAll() ?: [];

        $wip = [];

        foreach ($stages as $s) {
            $rawStage = (string)$s['stage'];
            $cleanKey = strtolower(trim((string)preg_replace('/^(#|\d+[\.\-\:\)]\s*|(Stage|Step)\s*\d+[\.\-\:\)]?\s*)/i', '', $rawStage)));
            $cleanKey = trim((string)preg_replace('/[^a-z0-9]+/', '_', $cleanKey), '_');

            if (empty($cleanKey)) {
                $cleanKey = strtolower(trim($rawStage));
            }

            $in = (int)$s['total_in'];
            $out = (int)$s['total_out'];
            $waste = (int)$s['total_waste'];

            // Aggregate metrics under cleanKey
            if (!isset($wip[$cleanKey])) {
                $wip[$cleanKey] = ['in' => 0, 'out' => 0, 'waste' => 0, 'wip_balance' => 0];
            }
            $wip[$cleanKey]['in'] += $in;
            $wip[$cleanKey]['out'] += $out;
            $wip[$cleanKey]['waste'] += $waste;
            $wip[$cleanKey]['wip_balance'] += ($in - $out - $waste);

            // Also aggregate metrics under rawStage if different
            if ($rawStage !== $cleanKey) {
                if (!isset($wip[$rawStage])) {
                    $wip[$rawStage] = ['in' => 0, 'out' => 0, 'waste' => 0, 'wip_balance' => 0];
                }
                $wip[$rawStage]['in'] += $in;
                $wip[$rawStage]['out'] += $out;
                $wip[$rawStage]['waste'] += $waste;
                $wip[$rawStage]['wip_balance'] += ($in - $out - $waste);
            }
        }

        // Fetch batch stages list to populate all stage key/name aliases
        $batchStagesObj = \App\Controllers\ProductionController::getBatchStagesList($productionOrderId, $companyId);
        foreach ($batchStagesObj as $stg) {
            $stgKey = is_array($stg) ? ($stg['key'] ?? '') : (string)$stg;
            $stgName = is_array($stg) ? ($stg['name'] ?? '') : (string)$stg;
            
            $cleanStgKey = strtolower(trim((string)preg_replace('/^(#|\d+[\.\-\:\)]\s*|(Stage|Step)\s*\d+[\.\-\:\)]?\s*)/i', '', $stgKey)));
            $cleanStgKey = trim((string)preg_replace('/[^a-z0-9]+/', '_', $cleanStgKey), '_');

            if (isset($wip[$cleanStgKey])) {
                $wip[$stgKey] = $wip[$cleanStgKey];
                $wip[$stgName] = $wip[$cleanStgKey];
                $wip[strtolower($stgName)] = $wip[$cleanStgKey];
            }
        }

        // Populate all possible string variations (spaces, uppercase, ucwords, dashes) for every entry in wip
        $allKeys = array_keys($wip);
        foreach ($allKeys as $k) {
            $data = $wip[$k];
            $cleanK = strtolower(trim((string)preg_replace('/^(#|\d+[\.\-\:\)]\s*|(Stage|Step)\s*\d+[\.\-\:\)]?\s*)/i', '', (string)$k)));
            $cleanK = trim((string)preg_replace('/[^a-z0-9]+/', '_', $cleanK), '_');
            
            $wip[$cleanK] = $data;
            $spaceName = str_replace('_', ' ', $cleanK);
            $wip[$spaceName] = $data;
            $wip[strtolower($spaceName)] = $data;
            $wip[strtoupper($spaceName)] = $data;
            $wip[ucwords($spaceName)] = $data;
            $wip[str_replace(' ', '_', $k)] = $data;
        }

        return $wip;
    }

    /**
     * Get machine utilization report for dashboard charts
     */
    public function getMachineUtilizationReport(int $companyId, int $daysLimit = 7): array {
        $sql = "SELECT m.name as machine_name, m.code as machine_code, 
                       SUM(mul.runtime_minutes) as total_runtime, 
                       SUM(mul.downtime_minutes) as total_downtime
                FROM machine_utilization_logs mul
                JOIN machines m ON mul.machine_id = m.id
                WHERE mul.company_id = ? AND mul.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY m.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$companyId, $daysLimit]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Shared logic to retrieve unit production logs, carton assignment, and dispatch/shipment tracking history
     */
    public function getUnitTrackingHistory(int $companyId, string $qrCode, int $batchId = 0): array {
        $qrCode = trim($qrCode);
        if (empty($qrCode)) {
            return [
                'success' => false,
                'message' => 'Please enter a valid QR Code or serial number.'
            ];
        }

        // Get company timezone
        $stmtComp = $this->db->prepare("SELECT timezone FROM companies WHERE id = ?");
        $stmtComp->execute([$companyId]);
        $tzStr = $stmtComp->fetchColumn() ?: 'Asia/Kolkata';

        // 1. Search stage logs matching QR code or partial match
        $stmtLogs = $this->db->prepare("
            SELECT psl.*, u.name as operator_name, r.name as operator_role, u_edit.name as edited_by_name, pro.production_no
            FROM production_stage_logs psl
            LEFT JOIN users u ON psl.employee_id = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN users u_edit ON psl.edited_by = u_edit.id
            LEFT JOIN production_orders pro ON psl.production_order_id = pro.id
            WHERE psl.company_id = ? AND (psl.qr_code = ? OR psl.scanned_qr_code = ? OR psl.qr_code LIKE ? OR psl.scanned_qr_code LIKE ?)
            ORDER BY psl.id ASC
        ");
        $stmtLogs->execute([$companyId, $qrCode, $qrCode, "%{$qrCode}%", "%{$qrCode}%"]);
        $logs = $stmtLogs->fetchAll() ?: [];

        // If no logs found by QR code, but batchId is provided:
        if (empty($logs) && $batchId > 0) {
            $stmtLogs2 = $this->db->prepare("
                SELECT psl.*, u.name as operator_name, r.name as operator_role, u_edit.name as edited_by_name, pro.production_no
                FROM production_stage_logs psl
                LEFT JOIN users u ON psl.employee_id = u.id
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN users u_edit ON psl.edited_by = u_edit.id
                LEFT JOIN production_orders pro ON psl.production_order_id = pro.id
                WHERE psl.company_id = ? AND psl.production_order_id = ? AND (psl.qr_code LIKE ? OR psl.scanned_qr_code LIKE ?)
                ORDER BY psl.id ASC
            ");
            $stmtLogs2->execute([$companyId, $batchId, "%{$qrCode}%", "%{$qrCode}%"]);
            $logs = $stmtLogs2->fetchAll() ?: [];
        }

        // Determine Batch Code & Full QR Code
        $batchCode = '';
        $foundProdOrderId = 0;
        foreach ($logs as $l) {
            if (!empty($l['production_no'])) {
                $batchCode = $l['production_no'];
            }
            if (!empty($l['production_order_id'])) {
                $foundProdOrderId = (int)$l['production_order_id'];
            }
        }

        if (empty($batchCode) && $batchId > 0) {
            $stmtB = $this->db->prepare("SELECT production_no FROM production_orders WHERE id = ? AND company_id = ?");
            $stmtB->execute([$batchId, $companyId]);
            $batchCode = (string)$stmtB->fetchColumn();
            $foundProdOrderId = $batchId;
        }

        // Auto construct full QR code e.g. B2507002-XXL-0001 if input is XXL-0001
        $fullQrCode = $qrCode;
        if (!empty($batchCode) && !str_starts_with(strtoupper($qrCode), strtoupper($batchCode))) {
            $fullQrCode = $batchCode . '-' . ltrim($qrCode, '-');
        }

        // Format stage logs
        $formattedLogs = [];
        foreach ($logs as $l) {
            $dt = new \DateTime($l['created_at'] ?? $l['start_time'] ?? 'now', new \DateTimeZone('UTC'));
            try { $dt->setTimezone(new \DateTimeZone($tzStr)); } catch (\Exception $ex) {}

            $dtEdit = !empty($l['edited_at']) ? new \DateTime($l['edited_at'], new \DateTimeZone('UTC')) : null;
            if ($dtEdit) { try { $dtEdit->setTimezone(new \DateTimeZone($tzStr)); } catch (\Exception $ex) {} }

            $isPass = ((int)($l['qty_out'] ?? 0) > 0 && (int)($l['waste_qty'] ?? 0) === 0);

            $formattedLogs[] = [
                'stage' => str_replace('_', ' ', strtoupper($l['stage'])),
                'status' => $isPass ? 'PASS' : 'FAIL',
                'operator_name' => $l['operator_name'] ?: 'System Operator',
                'operator_role' => $l['operator_role'] ?: 'Production Staff',
                'good_qty' => (int)($l['qty_out'] ?? 1),
                'waste_qty' => (int)($l['waste_qty'] ?? 0),
                'updated_at' => $dt->format('d M Y, h:i A'),
                'time_ago' => \App\Helpers\TimezoneHelper::timeAgo($l['created_at'] ?? 'now'),
                'duration' => !empty($l['duration_minutes']) ? $l['duration_minutes'] . ' mins' : '1 mins',
                'edited_by_name' => $l['edited_by_name'] ?? null,
                'edited_at_formatted' => $dtEdit ? $dtEdit->format('d M Y, h:i A') : null,
                'edit_remarks' => $l['edit_remarks'] ?? null
            ];
        }

        // Fetch Carton & Dispatch/Delivery details using fullQrCode, qrCode, or batch/order ID
        $cartonInfo = null;
        $stmtCarton = $this->db->prepare("
            SELECT ci.carton_id, ci.assigned_at,
                   c.carton_no, c.destination_type, c.status as carton_status, c.tracking_no, c.created_at as carton_created_at,
                   b.name as client_name, w.name as warehouse_name,
                   shp.shipment_no, shp.status as shipment_status, shp.vehicle_courier_details, shp.dispatch_note, shp.created_at as dispatch_date
            FROM carton_items ci
            JOIN cartons c ON ci.carton_id = c.id
            LEFT JOIN contacts b ON c.client_id = b.id
            LEFT JOIN warehouses w ON c.warehouse_id = w.id
            LEFT JOIN shipment_cartons sc ON c.id = sc.carton_id
            LEFT JOIN shipments shp ON sc.shipment_id = shp.id
            WHERE c.company_id = ? AND (
                ci.product_qr_code = ? OR ci.qr_code = ? OR
                ci.product_qr_code = ? OR ci.qr_code = ? OR
                ci.product_qr_code LIKE ? OR ci.qr_code LIKE ?
            )
            ORDER BY ci.id DESC LIMIT 1
        ");
        $stmtCarton->execute([
            $companyId,
            $fullQrCode, $fullQrCode,
            $qrCode, $qrCode,
            "%{$qrCode}%", "%{$qrCode}%"
        ]);
        $ctnRow = $stmtCarton->fetch();

        if (!$ctnRow && $foundProdOrderId > 0) {
            $stmtCarton2 = $this->db->prepare("
                SELECT ci.carton_id, ci.assigned_at,
                       c.carton_no, c.destination_type, c.status as carton_status, c.tracking_no, c.created_at as carton_created_at,
                       b.name as client_name, w.name as warehouse_name,
                       shp.shipment_no, shp.status as shipment_status, shp.vehicle_courier_details, shp.dispatch_note, shp.created_at as dispatch_date
                FROM carton_items ci
                JOIN cartons c ON ci.carton_id = c.id
                LEFT JOIN contacts b ON c.client_id = b.id
                LEFT JOIN warehouses w ON c.warehouse_id = w.id
                LEFT JOIN shipment_cartons sc ON c.id = sc.carton_id
                LEFT JOIN shipments shp ON sc.shipment_id = shp.id
                WHERE c.company_id = ? AND c.production_order_id = ?
                ORDER BY ci.id DESC LIMIT 1
            ");
            $stmtCarton2->execute([$companyId, $foundProdOrderId]);
            $ctnRow = $stmtCarton2->fetch();
        }

        if ($ctnRow) {
            $dest = ($ctnRow['destination_type'] === 'client') 
                ? ($ctnRow['client_name'] ?: 'Client Direct') 
                : (($ctnRow['destination_type'] === 'warehouse') 
                    ? ($ctnRow['warehouse_name'] ?: 'Company Warehouse') 
                    : 'Unassigned');

            $cStatusStr = match($ctnRow['carton_status']) {
                'dispatched' => 'Dispatched',
                'delivered' => 'Delivered',
                'packed' => 'Packed & Sealed in Carton',
                default => ucfirst((string)$ctnRow['carton_status'])
            };

            $dtCtn = !empty($ctnRow['carton_created_at']) ? new \DateTime($ctnRow['carton_created_at'], new \DateTimeZone('UTC')) : null;
            if ($dtCtn) { try { $dtCtn->setTimezone(new \DateTimeZone($tzStr)); } catch (\Exception $e) {} }

            $dtDisp = !empty($ctnRow['dispatch_date']) ? new \DateTime($ctnRow['dispatch_date'], new \DateTimeZone('UTC')) : null;
            if ($dtDisp) { try { $dtDisp->setTimezone(new \DateTimeZone($tzStr)); } catch (\Exception $e) {} }

            $cartonInfo = [
                'carton_id' => (int)$ctnRow['carton_id'],
                'carton_no' => $ctnRow['carton_no'],
                'destination' => $dest,
                'destination_type' => ucfirst((string)$ctnRow['destination_type']),
                'status' => $ctnRow['carton_status'],
                'status_label' => $cStatusStr,
                'tracking_no' => $ctnRow['tracking_no'] ?: null,
                'shipment_no' => $ctnRow['shipment_no'] ?: null,
                'shipment_status' => $ctnRow['shipment_status'] ? ucfirst((string)$ctnRow['shipment_status']) : null,
                'courier_details' => $ctnRow['vehicle_courier_details'] ?: null,
                'dispatch_note' => $ctnRow['dispatch_note'] ?: null,
                'packed_at_formatted' => $dtCtn ? $dtCtn->format('d M Y, h:i A') : null,
                'dispatched_at_formatted' => $dtDisp ? $dtDisp->format('d M Y, h:i A') : null
            ];
        }

        return [
            'success' => true,
            'qr_code' => $fullQrCode,
            'total_stages' => count($formattedLogs),
            'carton_info' => $cartonInfo,
            'logs' => $formattedLogs
        ];
    }
}
