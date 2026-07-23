<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= base_url('company/production/orders') ?>" class="btn btn-sm btn-light border mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Production Orders</a>
        <h3 class="fw-bold m-0"><i class="fa-solid fa-box-archive text-success me-2"></i> Completed Products Archive</h3>
        <p class="text-secondary small m-0 mt-1">Archive of fully manufactured garment batches, WIP operator logs, financial performance, and batch dossiers</p>
    </div>
    <div>
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="window.print();">
            <i class="fa-solid fa-print me-1"></i> Print Archive PDF
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
                <i class="fa-solid fa-shirt fs-3"></i>
            </div>
            <div>
                <div class="text-secondary small fw-semibold">Total Produced Garments</div>
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
                        <th>Target / Produced Qty</th>
                        <th>Wastage Rate</th>
                        <th>Work Duration</th>
                        <th>Financial Cost & Margin</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($completed_batches)): ?>
                        <?php foreach ($completed_batches as $b): ?>
                            <?php 
                            $startTs = !empty($b['started_at']) ? strtotime($b['started_at']) : strtotime($b['created_at']);
                            $endTs = !empty($b['completed_at']) ? strtotime($b['completed_at']) : time();
                            $diffSecs = max(0, $endTs - $startTs);
                            $days = floor($diffSecs / 86400);
                            $hrs = floor(($diffSecs % 86400) / 3600);
                            $mins = floor(($diffSecs % 3600) / 60);
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
                                    <div class="font-monospace fw-bold text-dark"><?= number_format($b['actual_produced_qty']) ?> pcs</div>
                                    <small class="text-secondary">Target: <?= number_format($b['po_target_qty']) ?> pcs</small>
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
                                <td class="text-end">
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
        $startTs = !empty($b['started_at']) ? strtotime($b['started_at']) : strtotime($b['created_at']);
        $endTs = !empty($b['completed_at']) ? strtotime($b['completed_at']) : time();
        $diffSecs = max(0, $endTs - $startTs);
        $days = floor($diffSecs / 86400);
        $hrs = floor(($diffSecs % 86400) / 3600);
        $mins = floor(($diffSecs % 3600) / 60);
        $durationStr = ($days > 0 ? "{$days}d " : "") . "{$hrs}h {$mins}m";
        ?>
        <div class="modal fade" id="viewBatchDossier-<?= $b['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content text-start" style="border-radius: 12px;">
                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title fw-bold m-0"><i class="fa-solid fa-box-archive text-success me-2"></i> Production Batch Dossier: <?= htmlspecialchars($b['production_no']) ?></h5>
                            <small class="text-secondary">Comprehensive manufacturing track, financials, wastage, and WIP stage operator logs</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-dark">
                        <!-- Overview Header -->
                        <div class="row g-3 p-3 bg-light rounded-3 border mb-4">
                            <div class="col-md-3 col-6">
                                <small class="text-secondary d-block">Buyer / Client</small>
                                <strong class="text-dark fs-6"><?= htmlspecialchars($b['buyer_name']) ?></strong>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-secondary d-block">Garment Style</small>
                                <strong class="text-primary font-monospace fs-6"><?= htmlspecialchars($b['style_no']) ?></strong>
                                <span class="small text-secondary d-block"><?= htmlspecialchars($b['style_name']) ?></span>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-secondary d-block">PO Reference No</small>
                                <strong class="font-monospace text-dark"><?= htmlspecialchars($b['buyer_po_no']) ?></strong>
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
                                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-shirt me-1"></i> Production Output & Wastage Breakdown</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary">Planned Target Quantity:</span>
                                        <strong class="font-monospace text-dark"><?= number_format($b['po_target_qty']) ?> pcs</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary">Actual Finished Output:</span>
                                        <strong class="font-monospace text-success fs-6"><?= number_format($b['actual_produced_qty']) ?> pcs</strong>
                                    </div>
                                    <div class="d-flex justify-content-between pt-2 border-top">
                                        <span class="text-secondary">Wastage / Rejections:</span>
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

                        <!-- Detailed WIP Stage Operator Logs -->
                        <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-users-gear text-primary me-2"></i> WIP Operational Stage Operator Tracking Logs</h6>
                        <div class="table-responsive border rounded-3">
                            <table class="table pepp-table mb-0 align-middle">
                                <thead>
                                    <tr class="bg-light">
                                        <th>WIP Operational Stage</th>
                                        <th>Operator / Employee Name</th>
                                        <th>Role / Workstation</th>
                                        <th class="text-end">Good Output</th>
                                        <th class="text-end">Reject Qty</th>
                                        <th>Logged Timestamp</th>
                                        <th>Stage Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($b['stage_logs'])): ?>
                                        <?php foreach ($b['stage_logs'] as $log): ?>
                                            <tr>
                                                <td><span class="badge bg-primary text-white text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $log['stage'])) ?></span></td>
                                                <td><strong class="text-dark"><?= htmlspecialchars($log['operator_name'] ?: 'System Operator') ?></strong></td>
                                                <td><span class="text-secondary small text-capitalize"><?= htmlspecialchars($log['operator_role'] ?: 'Floor Supervisor') ?></span></td>
                                                <td class="text-end font-monospace fw-bold text-success"><?= number_format($log['good_qty'] ?? 0) ?></td>
                                                <td class="text-end font-monospace fw-bold text-danger"><?= number_format($log['reject_qty'] ?? 0) ?></td>
                                                <td><span class="text-secondary small font-monospace"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></span></td>
                                                <td><span class="text-secondary small"><?= htmlspecialchars($log['remarks'] ?: 'Completed without defects') ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-secondary small">
                                                No granular WIP stage logs recorded for this batch.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print();"><i class="fa-solid fa-print me-1"></i> Print Dossier PDF</button>
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
