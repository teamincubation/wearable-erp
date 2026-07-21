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
                        $stagesList = ['knitting', 'dyeing', 'compacting', 'relaxing', 'spreading', 'cutting', 'bundling', 'printing', 'embroidery', 'sewing', 'checking', 'thread_cutting', 'washing', 'ironing', 'packing', 'carton_packing', 'shipment'];
                        foreach ($stagesList as $stg):
                            $inVal = $wip_summary[$stg]['in'] ?? 0;
                            $outVal = $wip_summary[$stg]['out'] ?? 0;
                            $wasteVal = $wip_summary[$stg]['waste'] ?? 0;
                            $balance = $wip_summary[$stg]['wip_balance'] ?? 0;
                    ?>
                        <div class="col">
                            <div class="border rounded p-3 text-center h-100 bg-light-subtle shadow-sm">
                                <div class="text-uppercase small text-secondary fw-bold" style="font-size: 10px;"><?= str_replace('_', ' ', $stg) ?></div>
                                <div class="fs-4 fw-bold font-monospace my-2 text-dark"><?= number_format($balance) ?></div>
                                <div class="text-secondary small" style="font-size: 9px;">
                                    In: <?= $inVal ?><br>
                                    Out: <?= $outVal ?><br>
                                    Waste: <?= $wasteVal ?>
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
                                <label class="form-label small fw-bold">Qty In <span class="text-danger">*</span></label>
                                <input type="number" name="qty_in" class="form-control" placeholder="In" min="1" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold">Qty Out <span class="text-danger">*</span></label>
                                <input type="number" name="qty_out" class="form-control" placeholder="Out" min="0" required>
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
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center p-5 text-secondary">
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
