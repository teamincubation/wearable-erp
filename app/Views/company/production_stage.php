<?php
    $tzStr = $tenantTimezone ?? 'Asia/Kolkata';

    $queryParams = array_filter([
        'filter_stage' => $filterStage ?? '',
        'filter_status' => $filterStatus ?? '',
        'filter_operator' => $filterOperator ?? '',
        'filter_date' => $filterDate ?? '',
        'search' => $filterSearch ?? ''
    ]);
    $filterQueryStr = http_build_query($queryParams);
    $exportUrl = base_url('company/production/stage/' . $order['id'] . '/export') . ($filterQueryStr ? '?' . $filterQueryStr : '');
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <a href="<?= base_url('company/production/orders') ?>" class="btn btn-sm btn-light border mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Batches</a>
        <h3 class="fw-bold m-0">WIP Operations Stage Tracker</h3>
        <p class="text-secondary m-0 mt-1">Order: <strong class="font-monospace"><?= htmlspecialchars($order['production_no']) ?></strong> | Style: <strong><?= htmlspecialchars($order['style_no']) ?> (<?= htmlspecialchars($order['style_name']) ?>)</strong></p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= base_url('company/production/stage/' . $order['id'] . '/live-report') ?>" target="_blank" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold d-flex align-items-center" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
            <span class="spinner-grow spinner-grow-sm text-danger me-2" role="status" style="width: 10px; height: 10px;"></span>
            <i class="fa-solid fa-desktop me-2"></i> Stage Live Report
        </a>
        <span class="badge bg-primary p-2.5 rounded-pill"><i class="fa-solid fa-bullseye me-1"></i> Target Contract: <?= number_format($order['target_qty']) ?> pcs</span>
        <?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
            <button type="button" class="btn btn-danger rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#completeBatchModal">
                <i class="fa-solid fa-flag-checkered me-1"></i> Mark Production Completed
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Live WIP Pipelines (Request 4: X-Axis Tech Pack Stage Order) -->
    <div class="col-12">
        <div class="pepp-card">
            <div class="pepp-card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-arrow-right-left text-primary me-2"></i> Operational Stage WIP Pipelines (Tech Pack Order Sequence)</h5>
                <span class="badge bg-light text-secondary border font-monospace">Sorted by Order #</span>
            </div>
            <div class="pepp-card-body">
                <div class="row row-cols-2 row-cols-md-6 g-3">
                    <?php 
                        $stagesList = $stagesList ?? ['knitting', 'dyeing', 'compacting', 'relaxing', 'spreading', 'cutting', 'bundling', 'printing', 'embroidery', 'sewing', 'checking', 'thread_cutting', 'washing', 'ironing', 'packing', 'carton_packing', 'shipment'];
                        foreach ($stagesList as $stg):
                            $cleanStgKey = strtolower(trim((string)preg_replace('/^(#|\d+[\.\-\:\)]\s*|(Stage|Step)\s*\d+[\.\-\:\)]?\s*)/i', '', (string)$stg)));
                            $cleanStgKey = trim((string)preg_replace('/[^a-z0-9]+/', '_', $cleanStgKey), '_');
                            $stgData = $wip_summary[$stg] ?? $wip_summary[$cleanStgKey] ?? $wip_summary[strtolower($stg)] ?? $wip_summary[str_replace(' ', '_', strtolower($stg))] ?? [];
                            $inVal = $stgData['in'] ?? 0;
                            $outVal = $stgData['out'] ?? 0;
                            $wasteVal = $stgData['waste'] ?? 0;
                            $balance = $stgData['wip_balance'] ?? 0;

                            // Determine status & color scheme
                            if ($inVal == 0 && $outVal == 0) {
                                $statusLabel = 'Not Started';
                                $badgeClass = 'bg-secondary-subtle text-secondary';
                                $cardClass = 'bg-light-subtle border-light-subtle';
                                $borderStyle = 'border-top: 4px solid #cbd5e1;';
                            } elseif ($balance > 0) {
                                $statusLabel = 'In Progress';
                                $badgeClass = 'bg-warning-subtle text-warning-emphasis';
                                $cardClass = 'bg-warning-subtle border-warning-subtle';
                                $borderStyle = 'border-top: 4px solid #f59e0b;';
                            } else {
                                $statusLabel = 'Completed';
                                $badgeClass = 'bg-success-subtle text-success';
                                $cardClass = 'bg-success-subtle border-success-subtle';
                                $borderStyle = 'border-top: 4px solid #10b981;';
                            }
                    ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm border-0 <?= $cardClass ?>" style="<?= $borderStyle ?> border-radius: 12px; transition: transform 0.2s;">
                                <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="text-uppercase small text-secondary fw-bold" style="font-size: 10px; letter-spacing: 0.5px;"><?= str_replace('_', ' ', $stg) ?></div>
                                        <div class="fs-3 fw-bold font-monospace my-2 text-dark"><?= number_format($balance) ?></div>
                                        <span class="badge <?= $badgeClass ?> small mb-2" style="font-size: 10px;"><?= $statusLabel ?></span>
                                    </div>
                                    <div class="text-secondary small border-top pt-2 mt-2" style="font-size: 10px; border-color: rgba(0,0,0,0.05) !important;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>In:</span> 
                                            <?php if ($inVal > 0): ?>
                                                <strong class="font-monospace badge bg-primary text-white px-2 py-0.5" style="font-size: 10px;"><?= number_format($inVal) ?></strong>
                                            <?php else: ?>
                                                <span class="font-monospace text-muted opacity-50">0</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>Out:</span> 
                                            <?php if ($outVal > 0): ?>
                                                <strong class="font-monospace badge bg-success text-white px-2 py-0.5" style="font-size: 10px;"><?= number_format($outVal) ?></strong>
                                            <?php else: ?>
                                                <span class="font-monospace text-muted opacity-50">0</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Waste:</span> 
                                            <?php if ($wasteVal > 0): ?>
                                                <strong class="font-monospace badge bg-danger text-white px-2 py-0.5" style="font-size: 10px;"><?= number_format($wasteVal) ?></strong>
                                            <?php else: ?>
                                                <span class="font-monospace text-muted opacity-50">0</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Log Form -->
    <?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
        <div class="col-md-4">
            <div class="pepp-card">
                <div class="pepp-card-header">
                    <h5 class="pepp-card-title"><i class="fa-solid fa-square-plus text-primary me-2"></i> Log Operational Activity</h5>
                </div>
                <div class="pepp-card-body">
                    <form action="<?= base_url('company/production/stage/' . $order['id'] . '/log') ?>" method="POST">
                        <?= \App\Core\Session::csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Production Stage <span class="text-danger">*</span></label>
                            <select name="stage" class="form-select text-dark" required>
                                <option value="">-- Choose Stage --</option>
                                <?php foreach ($stagesList as $stg): ?>
                                    <option value="<?= $stg ?>"><?= str_replace('_', ' ', ucfirst($stg)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Machine Link</label>
                                <select name="machine_id" class="form-select text-dark">
                                    <option value="">-- Manual (None) --</option>
                                    <?php foreach ($machines as $m): ?>
                                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['code']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Operator Link</label>
                                <select name="employee_id" class="form-select text-dark">
                                    <option value="">-- Choose Employee --</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-bold">Qty In</label>
                                <input type="number" name="qty_in" class="form-control" placeholder="In" min="0" value="0">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold">Qty Out</label>
                                <input type="number" name="qty_out" class="form-control" placeholder="Out" min="0" value="0">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold">Wastage Qty</label>
                                <input type="number" name="waste_qty" class="form-control" value="0" min="0">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Start Date</label>
                                <input type="date" name="start_date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Start Time</label>
                                <input type="time" name="start_time" class="form-control text-dark" value="<?= date('H:i') ?>" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">End Date</label>
                                <input type="date" name="end_date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">End Time</label>
                                <input type="time" name="end_time" class="form-control text-dark" value="<?= date('H:i') ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill mt-2">Log Activity & Update WIP</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- History Logs Table -->
    <div class="<?= \App\Core\Auth::hasPermission('company.production.manage') ? 'col-md-8' : 'col-12' ?>">
        <div class="pepp-card">
            <div class="pepp-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="pepp-card-title m-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Real-Time Activity Feed (Timezone: <?= htmlspecialchars($tzStr) ?>)</h5>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <?php if (!empty($history) && \App\Core\Auth::hasPermission('company.production.manage')): ?>
                        <button type="button" onclick="triggerSecurityDeleteModal('<?= base_url('company/production/stage/' . $order['id'] . '/clear-logs') ?>', 'Are you sure you want to DELETE ALL activity feed logs for Batch #<?= htmlspecialchars($order['production_no']) ?>?')" class="btn btn-sm btn-outline-danger rounded-pill fw-bold">
                            <i class="fa-solid fa-trash-can me-1"></i> Clear All Logs
                        </button>
                    <?php endif; ?>
                    <a href="<?= $exportUrl ?>" class="btn btn-sm btn-outline-success rounded-pill fw-bold">
                        <i class="fa-solid fa-file-excel me-1"></i> Export History (Excel)
                    </a>
                </div>
            </div>
            <div class="pepp-card-body p-0">
                <!-- Activity Feed Filter Bar -->
                <div class="p-3 bg-light border-bottom">
                    <form method="GET" action="<?= base_url('company/production/stage/' . $order['id']) ?>" id="activityFilterForm" class="row g-2 align-items-center">
                        <div class="col-md-2 col-6">
                            <select name="filter_stage" class="form-select form-select-sm text-dark" onchange="this.form.submit()">
                                <option value="">-- All Stages --</option>
                                <?php foreach ($stagesList as $stg): ?>
                                    <option value="<?= $stg ?>" <?= (($filterStage ?? '') === $stg) ? 'selected' : '' ?>><?= str_replace('_', ' ', ucfirst($stg)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 col-6">
                            <select name="filter_status" class="form-select form-select-sm text-dark" onchange="this.form.submit()">
                                <option value="">-- All Statuses --</option>
                                <option value="pass" <?= (($filterStatus ?? '') === 'pass') ? 'selected' : '' ?>>PASS (Output)</option>
                                <option value="fail" <?= (($filterStatus ?? '') === 'fail') ? 'selected' : '' ?>>FAIL (Defect/Waste)</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-6">
                            <select name="filter_operator" class="form-select form-select-sm text-dark" onchange="this.form.submit()">
                                <option value="">-- All Operators --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= (($filterOperator ?? 0) == $emp['id']) ? 'selected' : '' ?>><?= htmlspecialchars($emp['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 col-6">
                            <input type="date" name="filter_date" class="form-control form-control-sm text-dark" value="<?= htmlspecialchars($filterDate ?? '') ?>" onchange="this.form.submit()" placeholder="Select Date">
                        </div>
                        <div class="col-md-3 col-10">
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" class="form-control text-dark" value="<?= htmlspecialchars($filterSearch ?? '') ?>" placeholder="Search Tag / Operator / Machine...">
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
                            </div>
                        </div>
                        <div class="col-md-1 col-2 text-end">
                            <?php if (!empty($filterStage) || !empty($filterStatus) || !empty($filterOperator) || !empty($filterDate) || !empty($filterSearch)): ?>
                                <a href="<?= base_url('company/production/stage/' . $order['id']) ?>" class="btn btn-sm btn-outline-secondary w-100" title="Clear Filters"><i class="fa-solid fa-rotate-left"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                    <table class="table pepp-table mb-0">
                        <thead>
                            <tr>
                                <th>Stage Name</th>
                                <th>Operator</th>
                                <th>Machine / Code</th>
                                <th>Qty (In/Out/Waste)</th>
                                <th>Duration</th>
                                <th>Logged Date & Time (Standard Timezone)</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($history)): ?>
                                <?php foreach ($history as $h): ?>
                                    <?php 
                                        $dtH = new \DateTime($h['created_at'], new \DateTimeZone('UTC'));
                                        try { $dtH->setTimezone(new \DateTimeZone($tzStr)); } catch (\Exception $e) {}
                                        $rawHStage = trim((string)($h['stage'] ?? ''));
                                        $displayHStage = !empty($rawHStage) ? str_replace('_', ' ', $rawHStage) : 'Production Stage';
                                    ?>
                                    <tr>
                                        <td><span class="badge bg-light text-dark text-capitalize"><?= htmlspecialchars($displayHStage) ?></span></td>
                                        <td><?= htmlspecialchars($h['employee_name'] ?: 'System / Admin') ?></td>
                                        <td><?= htmlspecialchars($h['machine_name'] ?: ($h['qr_code'] ? 'QR: ' . $h['qr_code'] : 'Manual')) ?></td>
                                        <td class="font-monospace small">
                                            <?php if ($h['qty_in'] > 0): ?>
                                                <span class="badge bg-primary text-white mb-1">In: <?= $h['qty_in'] ?></span><br>
                                            <?php else: ?>
                                                <span class="text-secondary opacity-50 small">In: 0</span><br>
                                            <?php endif; ?>

                                            <?php if ($h['qty_out'] > 0): ?>
                                                <span class="badge bg-success text-white mb-1">Out: <?= $h['qty_out'] ?></span><br>
                                            <?php else: ?>
                                                <span class="text-secondary opacity-50 small">Out: 0</span><br>
                                            <?php endif; ?>

                                            <?php if ($h['waste_qty'] > 0): ?>
                                                <span class="badge bg-danger text-white">Waste: <?= $h['waste_qty'] ?></span>
                                            <?php else: ?>
                                                <span class="text-secondary opacity-50 small">Waste: 0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-secondary"><?= $h['duration_minutes'] ?> mins</span></td>
                                        <td>
                                            <strong class="text-dark font-monospace" style="font-size: 12px;"><?= $dtH->format('d M Y, h:i A') ?></strong>
                                            <?php if (!empty($h['edited_by_name']) || !empty($h['edited_at'])): ?>
                                                <?php
                                                $dtEdit = !empty($h['edited_at']) ? new \DateTime($h['edited_at'], new \DateTimeZone('UTC')) : null;
                                                if ($dtEdit) { try { $dtEdit->setTimezone(new \DateTimeZone($tzStr)); } catch (\Exception $e) {} }
                                                $editTimeStr = $dtEdit ? $dtEdit->format('d M Y, h:i A') : '';
                                                ?>
                                                <small class="d-block font-monospace mt-1" style="color: #d97706; font-size: 11px; line-height: 1.3;">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i> Edited by <strong><?= htmlspecialchars($h['edited_by_name'] ?: 'Admin') ?></strong><?= $editTimeStr ? ' on ' . $editTimeStr : '' ?><?= !empty($h['edit_remarks']) ? ' &mdash; &ldquo;' . htmlspecialchars($h['edit_remarks']) . '&rdquo;' : '' ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
                                                <button class="btn btn-sm btn-outline-primary rounded-pill px-2 me-1" data-bs-toggle="modal" data-bs-target="#editLogModal-<?= $h['id'] ?>" title="Edit Entry">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button type="button" onclick="triggerSecurityDeleteModal('<?= base_url('company/production/stage-log/delete/' . $h['id']) ?>', 'Are you sure you want to delete this stage log entry (Tag: <?= htmlspecialchars($h['qr_code'] ?: 'Manual Log') ?>)?')" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Delete Entry">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>

                                                <!-- Edit Log Modal -->
                                                <div class="modal fade text-start" id="editLogModal-<?= $h['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <form action="<?= base_url('company/production/stage-log/edit/' . $h['id']) ?>" method="POST">
                                                            <?= \App\Core\Session::csrfField() ?>
                                                            <div class="modal-content text-dark" style="border-radius: 12px;">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Edit Stage Activity Log</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body text-start">
                                                                    <div class="mb-3">
                                                                        <label class="form-label small fw-bold">Production Stage <span class="text-danger">*</span></label>
                                                                        <select name="stage" class="form-select text-dark" required>
                                                                            <?php foreach ($stagesList as $stg): ?>
                                                                                <option value="<?= $stg ?>" <?= $h['stage'] === $stg ? 'selected' : '' ?>><?= str_replace('_', ' ', ucfirst($stg)) ?></option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="row g-2 mb-3">
                                                                        <div class="col-6">
                                                                            <label class="form-label small fw-bold">Machine Link</label>
                                                                            <select name="machine_id" class="form-select text-dark">
                                                                                <option value="">-- Manual (None) --</option>
                                                                                <?php foreach ($machines as $m): ?>
                                                                                    <option value="<?= $m['id'] ?>" <?= $h['machine_id'] == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['code']) ?>)</option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <label class="form-label small fw-bold">Operator Link</label>
                                                                            <select name="employee_id" class="form-select text-dark">
                                                                                <option value="">-- Choose Employee --</option>
                                                                                <?php foreach ($employees as $emp): ?>
                                                                                    <option value="<?= $emp['id'] ?>" <?= $h['employee_id'] == $emp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($emp['name']) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row g-2 mb-3">
                                                                        <div class="col-4">
                                                                            <label class="form-label small fw-bold">Qty In</label>
                                                                            <input type="number" name="qty_in" class="form-control" value="<?= $h['qty_in'] ?>" min="0">
                                                                        </div>
                                                                        <div class="col-4">
                                                                            <label class="form-label small fw-bold">Qty Out</label>
                                                                            <input type="number" name="qty_out" class="form-control" value="<?= $h['qty_out'] ?>" min="0">
                                                                        </div>
                                                                        <div class="col-4">
                                                                            <label class="form-label small fw-bold">Wastage Qty</label>
                                                                            <input type="number" name="waste_qty" class="form-control" value="<?= $h['waste_qty'] ?>" min="0">
                                                                        </div>
                                                                    </div>
                                                                    <div class="row g-2 mb-3">
                                                                        <div class="col-6">
                                                                            <label class="form-label small fw-bold">Start Date</label>
                                                                            <input type="date" name="start_date" class="form-control text-dark" value="<?= date('Y-m-d', strtotime($h['start_time'])) ?>" required>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <label class="form-label small fw-bold">Start Time</label>
                                                                            <input type="time" name="start_time" class="form-control text-dark" value="<?= date('H:i', strtotime($h['start_time'])) ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row g-2 mb-3">
                                                                        <div class="col-6">
                                                                            <label class="form-label small fw-bold">End Date</label>
                                                                            <input type="date" name="end_date" class="form-control text-dark" value="<?= $h['end_time'] ? date('Y-m-d', strtotime($h['end_time'])) : date('Y-m-d') ?>" required>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <label class="form-label small fw-bold">End Time</label>
                                                                            <input type="time" name="end_time" class="form-control text-dark" value="<?= $h['end_time'] ? date('H:i', strtotime($h['end_time'])) : date('H:i') ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label small fw-bold text-dark">Edit Remarks / Reason <span class="text-muted fw-normal">(Optional)</span></label>
                                                                        <input type="text" name="edit_remarks" class="form-control text-dark" value="<?= htmlspecialchars($h['edit_remarks'] ?? '') ?>" placeholder="e.g. Changed status to PASS after quality re-inspection">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-secondary small">Locked</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center p-5 text-secondary">
                                        <i class="fa-solid fa-history fs-1 mb-3 text-light"></i>
                                        <p class="m-0">No operational activities logged for this batch yet.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Controls -->
                <?php if (isset($totalPages) && $totalPages > 1): ?>
                <?php $pageQuery = $filterQueryStr ? '&' . $filterQueryStr : ''; ?>
                <div class="card-footer bg-white border-top py-3">
                    <nav aria-label="Page navigation" class="m-0">
                        <ul class="pagination pagination-sm justify-content-center m-0">
                            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-pill-start px-3" href="?page=<?= $currentPage - 1 ?><?= $pageQuery ?>"><i class="fa-solid fa-angle-left"></i></a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                                    <a class="page-link px-3" href="?page=<?= $i ?><?= $pageQuery ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-pill-end px-3" href="?page=<?= $currentPage + 1 ?><?= $pageQuery ?>"><i class="fa-solid fa-angle-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal for Completing Batch -->
<div class="modal fade" id="completeBatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= base_url('company/production/complete/' . $order['id']) ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content text-start" style="border-radius: 12px;">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-flag-checkered me-2"></i> Confirm Batch Completion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start p-4">
                    <div class="p-3 bg-danger bg-opacity-10 border border-danger rounded-3 mb-3 text-danger">
                        <strong class="d-block"><i class="fa-solid fa-triangle-exclamation me-1"></i> Are you sure you want to mark this batch as COMPLETED?</strong>
                        <span class="small">This will mark production batch <strong><?= htmlspecialchars($order['production_no']) ?></strong> as completed and move it to the Completed Products Archive.</span>
                    </div>
                    <ul class="small text-secondary m-0 ps-3">
                        <li>Live work duration timer will be stopped.</li>
                        <li>Final quantities, wastage rates, and WIP operator logs will be compiled into the Batch Dossier.</li>
                        <li>The batch will be archived in <strong>Completed Products</strong>.</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Mark Completed Now</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Track Unit Lifecycle History Modal (Request 6) -->
<div class="modal fade" id="trackQrUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content text-start" style="border-radius: 16px;">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-qrcode text-primary me-2"></i> Complete Unit Stage Lifecycle History</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-start" id="track-qr-modal-body">
                <div class="text-center py-4">
                    <span class="spinner-border text-primary" role="status"></span>
                    <p class="mt-2 text-secondary">Fetching stage lifecycle logs...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const trackForm = document.getElementById('track-qr-unit-form');
    const trackInput = document.getElementById('track-qr-unit-input');
    const trackModalEl = document.getElementById('trackQrUnitModal');
    const trackModalBody = document.getElementById('track-qr-modal-body');

    if (trackForm && trackInput) {
        trackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const qrCode = trackInput.value.trim();
            if (!qrCode) return;

            const modal = new bootstrap.Modal(trackModalEl);
            trackModalBody.innerHTML = `
                <div class="text-center py-4">
                    <span class="spinner-border text-primary" role="status"></span>
                    <p class="mt-2 text-secondary small font-monospace">Fetching complete lifecycle history for <strong>${qrCode}</strong>...</p>
                </div>
            `;
            modal.show();

            fetch(`<?= base_url('company/production/track-qr-unit') ?>?qr_code=${encodeURIComponent(qrCode)}&batch_id=<?= $order['id'] ?>`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.logs && data.logs.length > 0) {
                        let cartonCardHtml = '';
                        if (data.carton_info) {
                            const c = data.carton_info;
                            let statusBadge = `<span class="badge bg-primary text-white px-3 py-1.5 rounded-pill"><i class="fa-solid fa-boxes-packing me-1"></i> ${c.status_label}</span>`;
                            if (c.status === 'delivered') {
                                statusBadge = `<span class="badge bg-success text-white px-3 py-1.5 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> Delivered</span>`;
                            } else if (c.status === 'dispatched') {
                                statusBadge = `<span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill"><i class="fa-solid fa-truck-fast me-1"></i> Dispatched ${c.shipment_no ? '(' + c.shipment_no + ')' : ''}</span>`;
                            }

                            cartonCardHtml = `
                                <div class="p-3 rounded-3 mb-3 font-monospace" style="background: #1e293b; border: 1px solid #334155; color: #f8fafc;">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-2 border-bottom" style="border-color: rgba(255,255,255,0.1) !important;">
                                        <div>
                                            <span class="badge bg-primary text-white font-monospace fs-6 px-2.5 py-1 me-2" style="background: #2563eb !important;">
                                                <i class="fa-solid fa-box-archive me-1"></i> ${c.carton_no}
                                            </span>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle font-monospace" style="font-size: 11px;">
                                                <i class="fa-solid fa-location-dot me-1"></i> Dest: ${c.destination}
                                            </span>
                                        </div>
                                        <div>${statusBadge}</div>
                                    </div>
                                    <div class="row g-2" style="font-size: 11.5px; color: #cbd5e1;">
                                        <div class="col-12 col-md-6">
                                            <i class="fa-solid fa-calendar-check text-primary me-1"></i> <strong>Carton Packed:</strong> ${c.packed_at_formatted || 'N/A'}
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <i class="fa-solid fa-truck text-warning me-1"></i> <strong>Shipment:</strong> ${c.shipment_no || 'Pending Shipment'}
                                        </div>
                                        ${c.courier_details ? `
                                            <div class="col-12 col-md-6">
                                                <i class="fa-solid fa-route text-info me-1"></i> <strong>Courier / Vehicle:</strong> ${c.courier_details}
                                            </div>
                                        ` : ''}
                                        ${c.tracking_no ? `
                                            <div class="col-12 col-md-6">
                                                <i class="fa-solid fa-barcode text-success me-1"></i> <strong>Tracking ID:</strong> ${c.tracking_no}
                                            </div>
                                        ` : ''}
                                        ${c.dispatched_at_formatted ? `
                                            <div class="col-12 col-md-6">
                                                <i class="fa-solid fa-clock text-muted me-1"></i> <strong>Dispatch Date:</strong> ${c.dispatched_at_formatted}
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        } else {
                            cartonCardHtml = `
                                <div class="p-2.5 rounded-3 mb-3 font-monospace small" style="background: rgba(51, 65, 85, 0.4); border: 1px solid #334155; color: #94a3b8;">
                                    <i class="fa-solid fa-box-open text-warning me-1.5"></i> <strong>Carton Assignment:</strong> Not yet linked to any sealed carton box.
                                </div>
                            `;
                        }

                        let html = `
                            <div class="p-3 rounded-3 mb-3" style="background: #0f172a; border: 1px solid #1e293b;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary text-white font-monospace fw-bold me-2 px-2.5 py-1" style="font-size: 11px; background: #2563eb !important; color: #ffffff !important;">QR / CODE</span>
                                        <strong class="font-monospace fs-5 text-white fw-bold" style="color: #ffffff !important; letter-spacing: 0.05em;">${data.qr_code}</strong>
                                    </div>
                                    <span class="badge bg-success text-white px-3 py-1.5 rounded-pill fw-bold" style="font-size: 12px; background: #10b981 !important; color: #ffffff !important;">
                                        <i class="fa-solid fa-check-double me-1"></i> ${data.total_stages} Stages Tracked
                                    </span>
                                </div>
                            </div>
                            ${cartonCardHtml}
                            <div class="table-responsive border-0" style="background: #0f172a; border-radius: 12px;">
                                <table class="table table-hover align-middle mb-0" style="font-size: 12.5px; background-color: #0f172a !important; color: #f8fafc !important; --bs-table-bg: #0f172a; --bs-table-color: #f8fafc;">
                                    <thead>
                                        <tr style="background-color: #1e293b !important; color: #94a3b8 !important;">
                                            <th style="background-color: #1e293b !important; color: #94a3b8 !important; padding: 12px 14px;">WIP STAGE</th>
                                            <th style="background-color: #1e293b !important; color: #94a3b8 !important; padding: 12px 14px;">STATUS</th>
                                            <th style="background-color: #1e293b !important; color: #94a3b8 !important; padding: 12px 14px;">UPDATED BY (OPERATOR)</th>
                                            <th style="background-color: #1e293b !important; color: #94a3b8 !important; padding: 12px 14px;">LOGGED DATE & TIME</th>
                                            <th style="background-color: #1e293b !important; color: #94a3b8 !important; padding: 12px 14px;">DURATION</th>
                                        </tr>
                                    </thead>
                                    <tbody style="background-color: #0f172a !important;">
                        `;

                        data.logs.forEach(l => {
                            const badge = l.status === 'PASS' ? 'bg-success' : 'bg-danger';
                            html += `
                                <tr>
                                    <td style="background-color: #0f172a !important; color: #38bdf8 !important; border-bottom: 1px solid rgba(255,255,255,0.06);"><strong class="font-monospace text-uppercase" style="color: #38bdf8 !important; font-weight: 700;">${l.stage}</strong></td>
                                    <td style="background-color: #0f172a !important; border-bottom: 1px solid rgba(255,255,255,0.06);"><span class="badge ${badge} text-white font-monospace px-2.5 py-1" style="color: #ffffff !important; font-weight: 700;">${l.status}</span></td>
                                    <td style="background-color: #0f172a !important; color: #ffffff !important; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                        <div class="fw-bold text-white" style="color: #ffffff !important;">${l.operator_name}</div>
                                        <small style="color: #94a3b8 !important; font-size: 11px;">${l.operator_role}</small>
                                    </td>
                                    <td style="background-color: #0f172a !important; color: #ffffff !important; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                        <div class="fw-bold font-monospace text-white" style="color: #ffffff !important;">${l.updated_at}</div>
                                        <small style="color: #94a3b8 !important; font-size: 11px;">${l.time_ago}</small>
                                    </td>
                                    <td style="background-color: #0f172a !important; border-bottom: 1px solid rgba(255,255,255,0.06);"><span class="badge text-white font-monospace" style="color: #ffffff !important; background: #334155 !important;">${l.duration}</span></td>
                                </tr>
                            `;
                        });

                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                        trackModalBody.innerHTML = html;
                    } else {
                        trackModalBody.innerHTML = `
                            <div class="alert alert-warning text-center py-4 my-2">
                                <i class="fa-solid fa-circle-exclamation fs-2 mb-2 text-warning"></i>
                                <h6 class="fw-bold text-dark">No Stage History Logs Found</h6>
                                <p class="small text-secondary mb-0">No operational logs recorded yet for item tag <strong>${qrCode}</strong> in this batch.</p>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    console.error(err);
                    trackModalBody.innerHTML = `
                        <div class="alert alert-danger text-center py-3 my-2">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Failed to communicate with production tracking server.
                        </div>
                    `;
                });
        });
    }
});
</script>

<!-- Security Confirmation DELETE Prompt Modal -->
<div class="modal fade text-start" id="securityDeleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg text-dark" style="border-radius: 16px;">
            <form id="securityDeleteConfirmForm" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Security Confirmation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <p class="mb-2 text-dark fw-semibold" id="securityDeleteModalTargetText">Are you sure you want to delete this record?</p>
                    <div class="alert alert-warning border-0 rounded-3 p-3 text-secondary small mb-3">
                        <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i> This action cannot be undone. To proceed, please type <strong class="text-danger font-monospace">DELETE</strong> in the box below.
                    </div>
                    <label class="form-label fw-semibold small text-secondary">Confirmation Phrase:</label>
                    <input type="text" id="securityDeleteConfirmInput" name="confirm_code" class="form-control form-control-lg font-monospace text-center fw-bold" placeholder="Type DELETE to confirm" autocomplete="off" required style="letter-spacing: 2px;">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="securityDeleteSubmitBtn" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold" disabled>
                        <i class="fa-solid fa-trash me-1"></i> Confirm Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function triggerSecurityDeleteModal(actionUrl, targetMessage) {
        const form = document.getElementById('securityDeleteConfirmForm');
        const textEl = document.getElementById('securityDeleteModalTargetText');
        const inputEl = document.getElementById('securityDeleteConfirmInput');
        const btnEl = document.getElementById('securityDeleteSubmitBtn');

        if (form && inputEl && btnEl) {
            form.action = actionUrl;
            if (textEl) textEl.innerText = targetMessage || 'Are you sure you want to proceed with deletion?';
            inputEl.value = '';
            btnEl.disabled = true;

            const modal = new bootstrap.Modal(document.getElementById('securityDeleteConfirmModal'));
            modal.show();

            inputEl.oninput = function() {
                btnEl.disabled = (inputEl.value.trim() !== 'DELETE');
            };
        }
    }
</script>
</script>
