<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Stock Transaction Ledger</h3>
        <p class="text-secondary m-0">Permanent audit records of raw materials and accessories stock movements</p>
    </div>
    <div class="d-flex">
        <a href="<?= base_url('company/inventory/balances') ?>" class="btn btn-outline-secondary rounded-pill px-4 me-2">
            <i class="fa-solid fa-list-check me-1"></i> View Balances Summary
        </a>
        <?php if (\App\Core\Auth::hasPermission('company.inventory.manage')): ?>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#transferStockModal">
                <i class="fa-solid fa-right-left me-1"></i> Transfer Stock
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Inventory Ledger entries</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Warehouse</th>
                        <th>Item Category</th>
                        <th>Item Description</th>
                        <th>Quantity Ledger</th>
                        <th>Movement Type</th>
                        <th>Reference Source</th>
                        <th>Batch / Lot No</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><span class="text-secondary small font-monospace">#<?= $t['id'] ?></span></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($t['warehouse_name']) ?></strong></td>
                                <td><span class="badge bg-light text-secondary text-capitalize"><?= htmlspecialchars($t['item_type']) ?></span></td>
                                <td><?= htmlspecialchars($t['item_name']) ?></td>
                                <td>
                                    <strong class="<?= $t['quantity'] >= 0 ? 'text-success' : 'text-danger' ?> font-monospace">
                                        <?= $t['quantity'] >= 0 ? '+' : '' ?><?= number_format($t['quantity'], 2) ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge badge-pepp 
                                        <?php 
                                            if ($t['type'] === 'in') echo 'badge-success';
                                            elseif ($t['type'] === 'out') echo 'badge-danger';
                                            elseif ($t['type'] === 'transfer') echo 'badge-info';
                                            else echo 'badge-warning';
                                        ?>">
                                        <?= htmlspecialchars(ucfirst($t['type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark font-monospace text-uppercase">
                                        <?= htmlspecialchars($t['reference_type']) ?>
                                    </span>
                                </td>
                                <td><span class="font-monospace text-secondary small"><?= htmlspecialchars($t['batch_no'] ?: 'N/A') ?></span></td>
                                <td><?= date('d M Y H:i', strtotime($t['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-boxes-stacked fs-1 mb-3 text-light"></i>
                                <p class="m-0">No stock ledger entries generated yet. Log a GRN to add stock.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Stock Transfer Modal -->
<?php if (\App\Core\Auth::hasPermission('company.inventory.manage')): ?>
    <div class="modal fade" id="transferStockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/inventory/transfer') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Transfer Stock between Warehouses</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Source Warehouse (From) <span class="text-danger">*</span></label>
                            <select name="from_warehouse_id" class="form-select text-dark" required>
                                <option value="">-- Select Source Warehouse --</option>
                                <?php foreach ($warehouses as $w): ?>
                                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars(ucfirst($w['type'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Destination Warehouse (To) <span class="text-danger">*</span></label>
                            <select name="to_warehouse_id" class="form-select text-dark" required>
                                <option value="">-- Select Destination Warehouse --</option>
                                <?php foreach ($warehouses as $w): ?>
                                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars(ucfirst($w['type'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Item Type <span class="text-danger">*</span></label>
                            <select name="item_type" class="form-select text-dark" required>
                                <option value="fabric">Fabric</option>
                                <option value="yarn">Yarn</option>
                                <option value="accessories">Accessories</option>
                                <option value="chemical">Chemicals</option>
                                <option value="packing">Packing Materials</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Item / Description Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" class="form-control" placeholder="e.g. Round Neck tag, Grey jersey fabric" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Quantity <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="quantity" class="form-control" placeholder="e.g. 50" min="0.01" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Batch / Lot Code</label>
                                <input type="text" name="batch_no" class="form-control font-monospace" placeholder="e.g. LOT-90">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Execute Transfer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
