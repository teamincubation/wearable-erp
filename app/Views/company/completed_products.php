<style>
@media print {
    /* Hide unneeded site elements during printing */
    header, footer, nav, sidebar, .d-print-none, .modal-backdrop, .btn, .modal-footer, .modal-header .btn-close {
        display: none !important;
    }
    body {
        background: #fff !important;
        color: #000 !important;
        font-size: 12px !important;
    }
    .modal {
        position: relative !important;
        display: block !important;
        overflow: visible !important;
        background: transparent !important;
    }
    .modal-dialog {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
    }
    .modal-content {
        border: none !important;
        box-shadow: none !important;
    }
    .print-certificate-header {
        border-bottom: 2px solid #000;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }
    .print-op-breakdown {
        display: table-row-group !important;
    }
    @page {
        size: A4 portrait;
        margin: 12mm;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <a href="<?= base_url('company/production/orders') ?>" class="btn btn-sm btn-light border mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Production Orders</a>
        <h3 class="fw-bold m-0"><i class="fa-solid fa-box-archive text-success me-2"></i> Completed Products Archive</h3>
        <p class="text-secondary small m-0 mt-1">Archive of fully manufactured garment batches, Checking output, WIP operator logs, and printable dossiers</p>
    </div>
    <div>
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="window.print();">
            <i class="fa-solid fa-print me-1"></i> Print Summary PDF
        </button>
    </div>
</div>

<?php 
$totalBatches = count($completed_batches ?? []);
$totalOutputPcs = 0;
$totalRevenueVal = 0.00;
$totalProfitVal = 0.00;

if (!empty($completed_batches)) {
    foreach ($completed_batches as $b) {
        $totalOutputPcs += (int)($b['actual_produced_qty'] ?? 0);
        $totalRevenueVal += (float)($b['revenue'] ?? 0.00);
        $totalProfitVal += (float)($b['net_profit'] ?? 0.00);
    }
}
$avgMarginVal = ($totalRevenueVal > 0) ? round(($totalProfitVal / $totalRevenueVal) * 100, 1) : 0;
?>

<!-- Key Metrics Row -->
<div class="row g-3 mb-4 d-print-none">
    <div class="col-md-3 col-6">
        <div class="pepp-card p-3 d-flex align-items-center">
            <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                <i class="fa-solid fa-box-archive fs-3"></i>
            </div>
            <div>
                <div class="text-secondary small fw-semibold">Completed Batches</div>
                <h4 class="fw-bold m-0 text-dark"><?= number_format($totalBatches) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="pepp-card p-3 d-flex align-items-center">
            <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                <i class="fa-solid fa-circle-check fs-3"></i>
            </div>
            <div>
                <div class="text-secondary small fw-semibold">Checking Finished Output</div>
                <h4 class="fw-bold m-0 text-primary"><?= number_format($totalOutputPcs) ?> pcs</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="pepp-card p-3 d-flex align-items-center">
            <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3 text-info">
                <i class="fa-solid fa-money-bill-trend-up fs-3"></i>
            </div>
            <div>
                <div class="text-secondary small fw-semibold">Gross Manufacturing Value</div>
                <h4 class="fw-bold m-0 text-dark">₹<?= number_format($totalRevenueVal, 2) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="pepp-card p-3 d-flex align-items-center">
            <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                <i class="fa-solid fa-chart-line fs-3"></i>
            </div>
            <div>
                <div class="text-secondary small fw-semibold">Average Batch Profit Margin</div>
                <h4 class="fw-bold m-0 text-success"><?= $avgMarginVal ?>%</h4>
            </div>
        </div>
    </div>
</div>

<!-- Completed Batches Table -->
<div class="pepp-card">
    <div class="pepp-card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="pepp-card-title text-success m-0"><i class="fa-solid fa-circle-check me-2"></i> Completed Manufacturing Batches Directory</h5>
        <span class="badge bg-white text-secondary border"><?= $totalBatches ?> Records</span>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0 align-middle">
                <thead>
                    <tr class="bg-light">
                        <th>Batch Code</th>
                        <th>Buyer Client</th>
                        <th>Style Description</th>
                        <th>Target vs Checking Output</th>
                        <th>Wastage Rate</th>
                        <th>Work Duration</th>
                        <th>Financial Cost & Margin</th>
                        <th class="text-end d-print-none">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($completed_batches)): ?>
                        <?php foreach ($completed_batches as $b): ?>
                            <?php 
                            try {
                                $startTs = !empty($b['started_at']) ? (new \DateTime($b['started_at'], new \DateTimeZone('UTC')))->getTimestamp() : (new \DateTime($b['created_at'] ?? 'now', new \DateTimeZone('UTC')))->getTimestamp();
                                $endTs = !empty($b['completed_at']) ? (new \DateTime($b['completed_at'], new \DateTimeZone('UTC')))->getTimestamp() : time();
                            } catch (\Exception $e) {
                                $startTs = !empty($b['started_at']) ? strtotime($b['started_at']) : strtotime($b['created_at'] ?? 'now');
                                $endTs = !empty($b['completed_at']) ? strtotime($b['completed_at']) : time();
                            }
                            $diffSecs = max(0, $endTs - $startTs);
                            $days = floor($diffSecs / 86400);
                            $hrs = floor(($diffSecs % 86400) / 3600);
                            $mins = sprintf('%02d', floor(($diffSecs % 3600) / 60));
                            $durationStr = ($days > 0 ? "{$days}d " : "") . "{$hrs}h {$mins}m";
                            ?>
                            <tr>
                                <td>
                                    <strong class="text-success font-monospace fs-6"><?= htmlspecialchars($b['production_no']) ?></strong>
                                    <div class="text-secondary small font-monospace">PO: <?= htmlspecialchars($b['buyer_po_no']) ?></div>
                                </td>
                                <td>
                                    <strong class="text-dark d-block"><?= htmlspecialchars($b['buyer_name']) ?></strong>
                                    <span class="badge bg-light text-secondary border font-monospace"><?= htmlspecialchars($b['buyer_code']) ?></span>
                                </td>
                                <td>
                                    <strong class="text-dark d-block"><?= htmlspecialchars($b['style_no']) ?></strong>
                                    <span class="text-secondary small"><?= htmlspecialchars($b['style_name']) ?></span>
                                </td>
                                <td>
                                    <div class="font-monospace fw-bold text-success fs-6"><?= number_format($b['actual_produced_qty']) ?> pcs</div>
                                    <small class="text-secondary">Checking Output (Target: <?= number_format($b['po_target_qty']) ?> pcs)</small>
                                </td>
                                <td>
                                    <?php if ($b['wastage_qty'] > 0): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border font-monospace">
                                            <i class="fa-solid fa-trash-can me-1"></i> <?= number_format($b['wastage_qty']) ?> pcs (<?= $b['wastage_percentage'] ?>%)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border">
                                            <i class="fa-solid fa-circle-check me-1"></i> 0% Wastage
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small font-monospace text-dark"><i class="fa-regular fa-calendar-check me-1 text-success"></i> Ended <?= date('d M Y', strtotime($b['completed_at'] ?: $b['updated_at'])) ?></div>
                                    <span class="badge bg-light text-secondary border font-monospace"><i class="fa-solid fa-stopwatch me-1"></i> <?= $durationStr ?></span>
                                </td>
                                <td>
                                    <div class="font-monospace fw-bold text-success">₹<?= number_format($b['net_profit'], 2) ?> <span class="badge bg-success text-white small ms-1">+<?= $b['margin_percentage'] ?>%</span></div>
                                    <small class="text-secondary">Cost: ₹<?= number_format($b['total_expenses'], 2) ?></small>
                                </td>
                                <td class="text-end d-print-none">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" data-bs-toggle="modal" data-bs-target="#viewBatchDossier-<?= $b['id'] ?>">
                                        <i class="fa-regular fa-eye me-1"></i> View Dossier
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-box-archive fs-1 mb-3 text-light"></i>
                                <p class="m-0">No completed manufacturing batches in the archive yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Batch Dossier Modals -->
<?php if (!empty($completed_batches)): ?>
    <?php foreach ($completed_batches as $b): ?>
        <?php 
        try {
            $startTs = !empty($b['started_at']) ? (new \DateTime($b['started_at'], new \DateTimeZone('UTC')))->getTimestamp() : (new \DateTime($b['created_at'] ?? 'now', new \DateTimeZone('UTC')))->getTimestamp();
            $endTs = !empty($b['completed_at']) ? (new \DateTime($b['completed_at'], new \DateTimeZone('UTC')))->getTimestamp() : time();
        } catch (\Exception $e) {
            $startTs = !empty($b['started_at']) ? strtotime($b['started_at']) : strtotime($b['created_at'] ?? 'now');
            $endTs = !empty($b['completed_at']) ? strtotime($b['completed_at']) : time();
        }
        $diffSecs = max(0, $endTs - $startTs);
        $days = floor($diffSecs / 86400);
        $hrs = floor(($diffSecs % 86400) / 3600);
        $mins = sprintf('%02d', floor(($diffSecs % 3600) / 60));
        $durationStr = ($days > 0 ? "{$days}d " : "") . "{$hrs}h {$mins}m";
        ?>
        <div class="modal fade" id="viewBatchDossier-<?= $b['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content text-start" style="border-radius: 12px;">
                    <div class="modal-header bg-light d-print-none">
                        <div>
                            <h5 class="modal-title fw-bold m-0"><i class="fa-solid fa-box-archive text-success me-2"></i> Production Batch Dossier: <?= htmlspecialchars($b['production_no']) ?></h5>
                            <small class="text-secondary">Comprehensive manufacturing track, Checking output, financials, and operator stage logs</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-dark p-4 print-dossier-area">
                        <!-- Printable Header -->
                        <div class="d-none d-print-block print-certificate-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="fw-bold m-0 text-dark">WEARABLE ERP</h2>
                                    <h5 class="text-uppercase tracking-wide text-success m-0 mt-1">Production Batch Completion Certificate & Dossier</h5>
                                </div>
                                <div class="text-end font-monospace">
                                    <strong class="fs-5 text-dark"><?= htmlspecialchars($b['production_no']) ?></strong>
                                    <div class="small text-secondary">Date: <?= date('d M Y') ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Overview Header Grid -->
                        <div class="row g-3 p-3 bg-light rounded-3 border mb-4">
                            <div class="col-md-3 col-6">
                                <small class="text-secondary d-block">Buyer / Client</small>
                                <strong class="text-dark fs-6"><?= htmlspecialchars($b['buyer_name']) ?></strong>
                                <div class="small text-secondary font-monospace"><?= htmlspecialchars($b['buyer_code']) ?></div>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-secondary d-block">Garment Style</small>
                                <strong class="text-primary font-monospace fs-6"><?= htmlspecialchars($b['style_no']) ?></strong>
                                <span class="small text-secondary d-block"><?= htmlspecialchars($b['style_name']) ?></span>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-secondary d-block">PO Reference No</small>
                                <strong class="font-monospace text-dark fs-6"><?= htmlspecialchars($b['buyer_po_no']) ?></strong>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-secondary d-block">Work Execution Duration</small>
                                <span class="badge bg-success-subtle text-success border font-monospace fs-6"><i class="fa-solid fa-stopwatch me-1"></i> <?= $durationStr ?></span>
                            </div>
                        </div>

                        <!-- Quantities & Financial Performance Grid -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white h-100">
                                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-shirt me-1"></i> Checking Output & Wastage Breakdown</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary">Planned Target Quantity:</span>
                                        <strong class="font-monospace text-dark"><?= number_format($b['po_target_qty']) ?> pcs</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary">Actual Checking Finished Output:</span>
                                        <strong class="font-monospace text-success fs-6"><?= number_format($b['actual_produced_qty']) ?> pcs</strong>
                                    </div>
                                    <div class="d-flex justify-content-between pt-2 border-top">
                                        <span class="text-secondary">Total Wastage / Rejections:</span>
                                        <span class="badge bg-warning-subtle text-warning-emphasis font-monospace fw-bold fs-6">
                                            <?= number_format($b['wastage_qty']) ?> pcs (<?= $b['wastage_percentage'] ?>%)
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white h-100">
                                    <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-chart-pie me-1"></i> Batch Profitability & Financial Summary</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary">Total PO Contract Value:</span>
                                        <strong class="font-monospace text-dark">₹<?= number_format($b['revenue'], 2) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary">Total Production Expenses:</span>
                                        <strong class="font-monospace text-danger">-₹<?= number_format($b['total_expenses'], 2) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between pt-2 border-top">
                                        <span class="text-secondary fw-bold">Net Batch Profit Margin:</span>
                                        <strong class="font-monospace text-success fs-6">₹<?= number_format($b['net_profit'], 2) ?> (+<?= $b['margin_percentage'] ?>%)</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Unique Operator Stage Activity Tracking Logs -->
                        <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-users-gear text-primary me-2"></i> Unique Operator & Operational Stage Tracking Logs</h6>
                        <div class="table-responsive border rounded-3 mb-4">
                            <table class="table pepp-table mb-0 align-middle">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Operator / Employee Name</th>
                                        <th>Role / Workstation</th>
                                        <th>Operations Logged</th>
                                        <th class="text-end">Total Output & Rejections (Click for Stage Breakdown & QR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($b['operator_summary'])): ?>
                                        <?php foreach ($b['operator_summary'] as $opIdx => $op): ?>
                                            <tr>
                                                <td>
                                                    <strong class="text-dark d-block fs-6"><?= htmlspecialchars($op['name']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-secondary border font-monospace text-capitalize py-1.5 px-2.5"><?= htmlspecialchars($op['role']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary-subtle text-primary border font-monospace py-1.5 px-2.5">
                                                        <i class="fa-solid fa-list-check me-1"></i> <?= count($op['stages']) ?> Operation(s) Logged
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm font-monospace d-print-none" data-bs-toggle="modal" data-bs-target="#opStageModal-<?= $b['id'] ?>-<?= $opIdx ?>">
                                                        <i class="fa-solid fa-layer-group me-1 text-primary"></i> 
                                                        Good: <strong class="text-success"><?= number_format($op['total_good_qty']) ?></strong> | 
                                                        Rej: <strong class="text-danger"><?= number_format($op['total_waste_qty']) ?></strong>
                                                        <i class="fa-solid fa-arrow-up-right-from-square ms-1 text-secondary" style="font-size: 11px;"></i>
                                                    </button>
                                                    <div class="d-none d-print-block font-monospace">
                                                        <span class="text-success fw-bold">Good: <?= number_format($op['total_good_qty']) ?></span> | 
                                                        <span class="text-danger fw-bold">Rej: <?= number_format($op['total_waste_qty']) ?></span>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Print Mode Expanded Stage Breakdown -->
                                            <tr class="d-none d-print-table-row bg-light">
                                                <td colspan="4" class="p-3">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered mb-0">
                                                            <thead>
                                                                <tr class="bg-white">
                                                                    <th>Operational Stage</th>
                                                                    <th class="text-end">Good Output</th>
                                                                    <th class="text-end">Reject / Wastage</th>
                                                                    <th>Logged Timestamp & Duration</th>
                                                                    <th>Stage QR Code</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($op['stages'] as $stg): ?>
                                                                    <tr>
                                                                        <td class="text-capitalize fw-bold"><?= htmlspecialchars(str_replace('_', ' ', $stg['stage'])) ?></td>
                                                                        <td class="text-end font-monospace text-success"><?= number_format($stg['good_qty']) ?> pcs</td>
                                                                        <td class="text-end font-monospace text-danger"><?= number_format($stg['waste_qty']) ?> pcs</td>
                                                                        <td class="font-monospace small"><?= $stg['logged_at'] ?> (<?= $stg['duration'] ?>)</td>
                                                                        <td class="font-monospace small"><?= htmlspecialchars($stg['qr_code']) ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-secondary small">
                                                No operator tracking logs recorded for this batch.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Printable Sign-off Footer -->
                        <div class="d-none d-print-block pt-4 mt-4 border-top">
                            <div class="row text-center mt-5">
                                <div class="col-4">
                                    <div class="border-top pt-2 fw-semibold">Quality Inspector Sign</div>
                                </div>
                                <div class="col-4">
                                    <div class="border-top pt-2 fw-semibold">Floor Operations Head</div>
                                </div>
                                <div class="col-4">
                                    <div class="border-top pt-2 fw-semibold">Authorized Company Stamp</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-print-none">
                        <button type="button" class="btn btn-primary px-4 rounded-pill" onclick="window.print();"><i class="fa-solid fa-print me-1"></i> Print Batch PDF</button>
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Operator Operations Breakdown Popup Modals -->
<?php if (!empty($completed_batches)): ?>
    <?php foreach ($completed_batches as $b): ?>
        <?php if (!empty($b['operator_summary'])): ?>
            <?php foreach ($b['operator_summary'] as $opIdx => $op): ?>
                <div class="modal fade" id="opStageModal-<?= $b['id'] ?>-<?= $opIdx ?>" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content text-start" style="border-radius: 12px;">
                            <div class="modal-header bg-light">
                                <div>
                                    <h5 class="modal-title fw-bold text-dark m-0"><i class="fa-solid fa-users-gear text-primary me-2"></i> Operator Operations Breakdown: <?= htmlspecialchars($op['name']) ?></h5>
                                    <small class="text-secondary">Role: <strong><?= htmlspecialchars($op['role']) ?></strong> | Batch: <strong class="font-monospace"><?= htmlspecialchars($b['production_no']) ?></strong></small>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 text-dark">
                                <div class="p-3 bg-light rounded-3 border mb-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-secondary d-block">Operator / Employee</small>
                                        <strong class="text-dark fs-6"><?= htmlspecialchars($op['name']) ?></strong> (<span class="text-secondary"><?= htmlspecialchars($op['role']) ?></span>)
                                    </div>
                                    <div class="text-end font-monospace">
                                        <span class="badge bg-success-subtle text-success border fs-6 me-1">Good Output: <?= number_format($op['total_good_qty']) ?> pcs</span>
                                        <span class="badge bg-danger-subtle text-danger border fs-6">Rejections: <?= number_format($op['total_waste_qty']) ?> pcs</span>
                                    </div>
                                </div>

                                <div class="table-responsive border rounded-3">
                                    <table class="table pepp-table mb-0 align-middle">
                                        <thead>
                                            <tr class="bg-light">
                                                <th>Operational Stage</th>
                                                <th class="text-end">Good Output</th>
                                                <th class="text-end">Reject / Wastage</th>
                                                <th>Logged Timestamp & Duration</th>
                                                <th>Stage QR Code</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($op['stages'] as $stg): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-primary text-white text-capitalize py-1.5 px-2.5">
                                                            <?= htmlspecialchars(str_replace('_', ' ', $stg['stage'])) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end font-monospace fw-bold text-success">
                                                        <?= number_format($stg['good_qty']) ?> pcs
                                                    </td>
                                                    <td class="text-end font-monospace fw-bold text-danger">
                                                        <?= number_format($stg['waste_qty']) ?> pcs
                                                    </td>
                                                    <td>
                                                        <div class="small font-monospace text-dark">
                                                            <i class="fa-regular fa-clock me-1 text-primary"></i> <?= $stg['logged_at'] ?>
                                                        </div>
                                                        <small class="text-secondary font-monospace">(Duration: <?= $stg['duration'] ?>)</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border font-monospace py-1.5 px-2">
                                                            <i class="fa-solid fa-qrcode me-1 text-primary"></i> <?= htmlspecialchars($stg['qr_code']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary border px-4" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
