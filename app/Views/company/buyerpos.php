<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Buyer Purchase Orders</h3>
        <p class="text-secondary m-0">Manage incoming production contracts and client specifications</p>
    </div>
    <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addBuyerPoModal">
            <i class="fa-solid fa-file-contract me-1"></i> Register Buyer PO
        </button>
    <?php endif; ?>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-file-invoice text-primary me-2"></i> Buyer Production Orders Queue</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Buyer Client</th>
                        <th>Style Code / Name</th>
                        <th>Order Qty</th>
                        <th>Unit Price</th>
                        <th>Total Amount</th>
                        <th>PO Date</th>
                        <th>Delivery Due Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= htmlspecialchars($o['po_no']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($o['buyer_name']) ?></td>
                                <td>
                                    <div>
                                        <strong class="text-dark"><?= htmlspecialchars($o['style_no']) ?></strong>
                                        <div class="text-secondary small"><?= htmlspecialchars($o['style_name']) ?></div>
                                    </div>
                                </td>
                                <td class="fw-bold font-monospace"><?= number_format($o['quantity']) ?> pcs</td>
                                <td>₹<?= number_format($o['unit_price'], 2) ?></td>
                                <td class="text-success font-monospace">₹<?= number_format($o['total_amount'], 2) ?></td>
                                <td><?= date('d M Y', strtotime($o['po_date'])) ?></td>
                                <td>
                                    <span class="text-dark fw-semibold"><?= date('d M Y', strtotime($o['delivery_date'])) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-pepp 
                                        <?php 
                                            if ($o['status'] === 'approved') echo 'badge-success';
                                            elseif ($o['status'] === 'draft') echo 'badge-warning';
                                            else echo 'badge-danger';
                                        ?>">
                                        <?= htmlspecialchars(ucfirst($o['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($o['status'] === 'draft' && \App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                        <form action="<?= base_url('company/merchandising/buyerpos/approve/' . $o['id']) ?>" method="POST" class="d-inline">
                                            <?= \App\Core\Session::csrfField() ?>
                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 me-1">
                                                <i class="fa-solid fa-circle-check me-1"></i> Approve PO
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="<?= base_url('company/merchandising/buyerpos/delete/' . $o['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this buyer PO?');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-file-signature fs-1 mb-3 text-light"></i>
                                <p class="m-0">No buyer PO contracts registered yet.</p>
                                <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                    <button class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addBuyerPoModal">
                                        <i class="fa-solid fa-plus me-1"></i> Register First Buyer PO
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

<!-- Add Buyer PO Modal -->
<?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
    <div class="modal fade" id="addBuyerPoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/merchandising/buyerpos/create') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Register Buyer Contract (PO)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Buyer / Client <span class="text-danger">*</span></label>
                            <select name="buyer_id" class="form-select text-dark" required>
                                <option value="">-- Choose Buyer Contact --</option>
                                <?php foreach ($buyers as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">If the buyer is not listed, add them in ERP Settings -> Contacts database first.</div>
                        </div>
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
                            <label class="form-label small fw-bold">Buyer PO Reference Number <span class="text-danger">*</span></label>
                            <input type="text" name="po_no" class="form-control font-monospace" placeholder="e.g. PO-TOCCO-2026-9021" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">PO Booking Date <span class="text-danger">*</span></label>
                                <input type="date" name="po_date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Delivery Due Date <span class="text-danger">*</span></label>
                                <input type="date" name="delivery_date" class="form-control text-dark" required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Order Qty (pcs) <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" placeholder="e.g. 5000" min="1" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Unit Price (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="unit_price" class="form-control" placeholder="e.g. 240.00" min="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Register Order</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
