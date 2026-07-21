<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Cost Sheets Estimate</h3>
        <p class="text-secondary m-0">Merchandise cost projections and pricing calculator</p>
    </div>
    <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addCostSheetModal">
            <i class="fa-solid fa-calculator me-1"></i> New Cost Sheet
        </button>
    <?php endif; ?>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Style Cost Sheet Estimates</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Cost Sheet No</th>
                        <th>Style Code / Name</th>
                        <th>Yarn / Fabric</th>
                        <th>Processing Cost</th>
                        <th>Trim & Accs</th>
                        <th>Packing Cost</th>
                        <th>Margin %</th>
                        <th>Final Quote Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($costsheets)): ?>
                        <?php foreach ($costsheets as $cs): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= htmlspecialchars($cs['cost_sheet_no']) ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong class="text-dark"><?= htmlspecialchars($cs['style_no']) ?></strong>
                                        <div class="text-secondary small"><?= htmlspecialchars($cs['style_name']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">Yarn: ₹<?= number_format($cs['yarn_cost'], 2) ?></div>
                                    <div class="small">Fabric: ₹<?= number_format($cs['fabric_cost'], 2) ?></div>
                                </td>
                                <td>₹<?= number_format($cs['processing_cost'], 2) ?></td>
                                <td>₹<?= number_format($cs['accessories_cost'], 2) ?></td>
                                <td>₹<?= number_format($cs['packing_cost'], 2) ?></td>
                                <td>
                                    <span class="badge bg-light text-secondary"><?= htmlspecialchars($cs['margin_percentage']) ?>%</span>
                                </td>
                                <td>
                                    <strong class="text-success font-monospace">₹<?= number_format($cs['total_cost'], 2) ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-pepp badge-success text-capitalize"><?= htmlspecialchars($cs['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-calculator fs-1 mb-3 text-light"></i>
                                <p class="m-0">No cost sheets generated yet.</p>
                                <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                    <button class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addCostSheetModal">
                                        <i class="fa-solid fa-plus me-1"></i> Add First Cost Sheet
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Cost Sheet Modal -->
<?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
    <div class="modal fade" id="addCostSheetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/merchandising/costsheets/create') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Generate Cost Sheet Estimate</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Style <span class="text-danger">*</span></label>
                            <select name="style_id" class="form-select text-dark" required>
                                <option value="">-- Choose Garment Style --</option>
                                <?php foreach ($styles as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['style_no']) ?> - <?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Cost Sheet Reference Code <span class="text-danger">*</span></label>
                            <input type="text" name="cost_sheet_no" class="form-control font-monospace" placeholder="e.g. CST-TOCCO-2026-01" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Yarn Cost (per pc) (₹)</label>
                                <input type="number" step="0.01" name="yarn_cost" class="form-control" value="0.00" min="0">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Fabric Cost (per pc) (₹)</label>
                                <input type="number" step="0.01" name="fabric_cost" class="form-control" value="0.00" min="0">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Processing (Dye/Print) (₹)</label>
                                <input type="number" step="0.01" name="processing_cost" class="form-control" value="0.00" min="0">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Trim & Accessories (₹)</label>
                                <input type="number" step="0.01" name="accessories_cost" class="form-control" value="0.00" min="0">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Packaging Material (₹)</label>
                                <input type="number" step="0.01" name="packing_cost" class="form-control" value="0.00" min="0">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Profit Margin (%)</label>
                                <input type="number" step="0.1" name="margin_percentage" class="form-control" value="15.0" min="0">
                            </div>
                        </div>
                        <div class="form-text mt-2 text-primary"><i class="fa-solid fa-circle-info"></i> Quote price is computed as: Subtotal * (1 + margin / 100).</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Generate Estimate</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
