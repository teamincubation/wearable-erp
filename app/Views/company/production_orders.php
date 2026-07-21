<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Production Orders</h3>
        <p class="text-secondary m-0">Plan, launch, and monitor active garment manufacturing batches</p>
    </div>
    <div class="d-flex">
        <a href="<?= base_url('company/production/quality') ?>" class="btn btn-outline-secondary rounded-pill px-4 me-2">
            <i class="fa-solid fa-clipboard-check me-1"></i> Quality Inspections
        </a>
        <?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addProductionOrderModal">
                <i class="fa-solid fa-industry me-1"></i> Plan New Batch
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-industry text-primary me-2"></i> Active Manufacturing Batches</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Batch Code No</th>
                        <th>Linked Buyer PO</th>
                        <th>Style Description</th>
                        <th>Target Qty</th>
                        <th>Date Launched</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= htmlspecialchars($o['production_no']) ?></strong>
                                </td>
                                <td><span class="badge bg-light text-secondary font-monospace"><?= htmlspecialchars($o['buyer_po_no']) ?></span></td>
                                <td>
                                    <div>
                                        <strong class="text-dark"><?= htmlspecialchars($o['style_no']) ?></strong>
                                        <div class="text-secondary small"><?= htmlspecialchars($o['style_name']) ?></div>
                                    </div>
                                </td>
                                <td class="fw-bold font-monospace"><?= number_format($o['target_qty']) ?> pcs</td>
                                <td><?= date('d M Y', strtotime($o['start_date'])) ?></td>
                                <td>
                                    <span class="badge badge-pepp 
                                        <?php 
                                            if ($o['status'] === 'completed') echo 'badge-success';
                                            elseif ($o['status'] === 'running') echo 'badge-info';
                                            elseif ($o['status'] === 'pending') echo 'badge-warning';
                                            else echo 'badge-danger';
                                        ?>">
                                        <?= htmlspecialchars(ucfirst($o['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('company/production/stage/' . $o['id']) ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                                        <i class="fa-solid fa-list-check me-1"></i> Stage Tracker / WIP
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-industry fs-1 mb-3 text-light"></i>
                                <p class="m-0">No active production order batches registered.</p>
                                <?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
                                    <button class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addProductionOrderModal">
                                        <i class="fa-solid fa-plus me-1"></i> Plan First Batch
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

<!-- Add Production Order Modal -->
<?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
    <div class="modal fade" id="addProductionOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/production/orders/create') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Plan Production Batch</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Link Approved Buyer PO <span class="text-danger">*</span></label>
                            <select name="po_id" class="form-select text-dark" required>
                                <option value="">-- Select Approved PO Contract --</option>
                                <?php foreach ($buyer_pos as $bp): ?>
                                    <option value="<?= $bp['id'] ?>"><?= htmlspecialchars($bp['po_no']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Production Batch Number <span class="text-danger">*</span></label>
                            <input type="text" name="production_no" class="form-control font-monospace" placeholder="e.g. BATCH-TOCCO-001" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Launch Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Plan Batch</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
