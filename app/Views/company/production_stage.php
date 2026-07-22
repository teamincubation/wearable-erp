<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= base_url('company/production/orders') ?>" class="btn btn-sm btn-light border mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Batches</a>
        <h3 class="fw-bold">WIP Operations Stage Tracker</h3>
        <p class="text-secondary m-0">Order: <strong class="font-monospace"><?= htmlspecialchars($order['production_no']) ?></strong> | Style: <strong><?= htmlspecialchars($order['style_no']) ?> (<?= htmlspecialchars($order['style_name']) ?>)</strong></p>
    </div>
    <div>
        <span class="badge bg-primary p-2.5 rounded-pill"><i class="fa-solid fa-bullseye me-1"></i> Target Contract: <?= number_format($order['target_qty']) ?> pcs</span>
    </div>
</div>

<div class="row g-4">
    <!-- Live WIP Pipelines -->
    <div class="col-12">
        <div class="pepp-card">
            <div class="pepp-card-header bg-light">
                <h5 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-arrow-right-left text-primary me-2"></i> Operational Stage WIP pipelines</h5>
            </div>
            <div class="pepp-card-body">
                <div class="row row-cols-2 row-cols-md-6 g-3">
                    <?php 
                        $stagesList = $stagesList ?? ['knitting', 'dyeing', 'compacting', 'relaxing', 'spreading', 'cutting', 'bundling', 'printing', 'embroidery', 'sewing', 'checking', 'thread_cutting', 'washing', 'ironing', 'packing', 'carton_packing', 'shipment'];
                        foreach ($stagesList as $stg):
                            $inVal = $wip_summary[$stg]['in'] ?? 0;
                            $outVal = $wip_summary[$stg]['out'] ?? 0;
                            $wasteVal = $wip_summary[$stg]['waste'] ?? 0;
                            $balance = $wip_summary[$stg]['wip_balance'] ?? 0;

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
                                        <div class="d-flex justify-content-between"><span>In:</span> <strong class="font-monospace text-dark"><?= number_format($inVal) ?></strong></div>
                                        <div class="d-flex justify-content-between"><span>Out:</span> <strong class="font-monospace text-dark"><?= number_format($outVal) ?></strong></div>
                                        <div class="d-flex justify-content-between"><span>Waste:</span> <strong class="font-monospace text-danger"><?= number_format($wasteVal) ?></strong></div>
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
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Stage History Journal</h5>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table pepp-table mb-0">
                        <thead>
                            <tr>
                                <th>Stage Name</th>
                                <th>Operator</th>
                                <th>Machine Code</th>
                                <th>Qty (In/Out/Waste)</th>
                                <th>Time Taken</th>
                                <th>Logged Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($history)): ?>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td><span class="badge bg-light text-dark text-capitalize"><?= str_replace('_', ' ', $h['stage']) ?></span></td>
                                        <td><?= htmlspecialchars($h['employee_name'] ?: 'System / Admin') ?></td>
                                        <td><?= htmlspecialchars($h['machine_name'] ?: 'Manual') ?></td>
                                        <td class="font-monospace text-dark small">
                                            In: <?= $h['qty_in'] ?><br>
                                            Out: <strong class="text-success"><?= $h['qty_out'] ?></strong><br>
                                            Waste: <span class="text-danger"><?= $h['waste_qty'] ?></span>
                                        </td>
                                        <td><span class="badge bg-light text-secondary"><?= $h['duration_minutes'] ?> mins</span></td>
                                        <td><?= date('d M Y H:i', strtotime($h['created_at'])) ?></td>
                                        <td class="text-end">
                                            <?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
                                                <button class="btn btn-sm btn-outline-primary rounded-pill px-2 me-1" data-bs-toggle="modal" data-bs-target="#editLogModal-<?= $h['id'] ?>" title="Edit Entry">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <form action="<?= base_url('company/production/stage-log/delete/' . $h['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this stage log? This will update active WIP counts.');">
                                                    <?= \App\Core\Session::csrfField() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Delete Entry">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>

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
            </div>
        </div>
    </div>
</div>
