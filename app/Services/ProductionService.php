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
            $machineId,
            $employeeId,
            $qtyIn,
            $qtyOut,
            $wasteQty,
            $startTime,
            $endTime,
            $durationMinutes,
            $userId
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
            // WIP at a stage = Qty In - Qty Out - Waste Qty
            $wip[$s['stage']] = [
                'in' => (int) $s['total_in'],
                'out' => (int) $s['total_out'],
                'waste' => (int) $s['total_waste'],
                'wip_balance' => (int) ($s['total_in'] - $s['total_out'] - $s['total_waste'])
            ];
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
}
