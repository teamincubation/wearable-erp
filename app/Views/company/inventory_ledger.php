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
                        <th class="text-end">Actions</th>
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
                                <td class="text-end">
                                    <form action="<?= base_url('company/inventory/delete/' . $t['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this stock transaction log?');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center p-5 text-secondary">
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
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-right-left text-primary me-2"></i> Transfer Stock between Warehouses</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Source Warehouse (From) <span class="text-danger">*</span></label>
                            <select name="from_warehouse_id" id="transferFromWhSelect" class="form-select text-dark" required>
                                <option value="">-- Select Source Warehouse --</option>
                                <?php foreach ($warehouses as $w): ?>
                                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars(ucfirst($w['type'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Destination Warehouse (To) <span class="text-danger">*</span></label>
                            <select name="to_warehouse_id" id="transferToWhSelect" class="form-select text-dark" required>
                                <option value="">-- Select Destination Warehouse --</option>
                                <?php foreach ($warehouses as $w): ?>
                                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars(ucfirst($w['type'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Available Item Category <span class="text-danger">*</span></label>
                            <select name="item_type" id="transferItemTypeSelect" class="form-select text-dark" required>
                                <option value="">-- First Select Source Warehouse --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Item / Description Name <span class="text-danger">*</span></label>
                            <select name="item_name" id="transferItemNameSelect" class="form-select text-dark" required>
                                <option value="">-- First Select Category --</option>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Quantity <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="quantity" id="transferQtyInput" class="form-control text-dark font-monospace" placeholder="e.g. 50" min="0.01" required>
                                <div id="availStockNotice" class="small fw-semibold mt-1"></div>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">Batch / Lot Code</label>
                                <input type="text" name="batch_no" class="form-control font-monospace" placeholder="e.g. LOT-90">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="transferSubmitBtn" class="btn btn-primary px-4">Execute Transfer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const warehouseStock = <?= json_encode($warehouseStockData ?? []) ?>;

    const fromWhSelect = document.getElementById('transferFromWhSelect');
    const toWhSelect = document.getElementById('transferToWhSelect');
    const typeSelect = document.getElementById('transferItemTypeSelect');
    const nameSelect = document.getElementById('transferItemNameSelect');
    const qtyInput = document.getElementById('transferQtyInput');
    const availNotice = document.getElementById('availStockNotice');
    const submitBtn = document.getElementById('transferSubmitBtn');

    let currentMaxQty = 0;

    if (fromWhSelect && typeSelect && nameSelect) {
        // 1. Source Warehouse Change Event
        fromWhSelect.addEventListener('change', function() {
            const whId = parseInt(this.value);
            typeSelect.innerHTML = '<option value="">-- Choose Category --</option>';
            nameSelect.innerHTML = '<option value="">-- Choose Item / Description --</option>';
            if (availNotice) availNotice.innerHTML = '';
            if (qtyInput) {
                qtyInput.value = '';
                qtyInput.removeAttribute('max');
            }
            if (submitBtn) submitBtn.disabled = false;

            if (!whId) return;

            // Extract unique categories available in the selected warehouse
            const availableCategories = [...new Set(
                warehouseStock
                    .filter(s => parseInt(s.warehouse_id) === whId && parseFloat(s.available_qty) > 0)
                    .map(s => s.item_type)
            )];

            if (availableCategories.length === 0) {
                typeSelect.innerHTML = '<option value="">-- No Stock Available in Facility --</option>';
                return;
            }

            availableCategories.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat;
                opt.textContent = cat;
                typeSelect.appendChild(opt);
            });
        });

        // 2. Item Category Change Event
        typeSelect.addEventListener('change', function() {
            const whId = parseInt(fromWhSelect.value);
            const selectedCat = this.value;
            nameSelect.innerHTML = '<option value="">-- Choose Item / Description --</option>';
            if (availNotice) availNotice.innerHTML = '';
            if (qtyInput) {
                qtyInput.value = '';
                qtyInput.removeAttribute('max');
            }
            if (submitBtn) submitBtn.disabled = false;

            if (!whId || !selectedCat) return;

            const items = warehouseStock.filter(s => parseInt(s.warehouse_id) === whId && s.item_type === selectedCat && parseFloat(s.available_qty) > 0);
            
            if (items.length === 0) {
                nameSelect.innerHTML = '<option value="">-- No Items Available under Category --</option>';
                return;
            }

            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.item_name;
                opt.setAttribute('data-qty', item.available_qty);
                opt.textContent = `${item.item_name} (Available: ${parseFloat(item.available_qty).toFixed(2)})`;
                nameSelect.appendChild(opt);
            });
        });

        // 3. Item Name Change Event
        nameSelect.addEventListener('change', function() {
            const selectedOpt = this.options[this.selectedIndex];
            if (availNotice) availNotice.innerHTML = '';

            if (selectedOpt && selectedOpt.hasAttribute('data-qty')) {
                currentMaxQty = parseFloat(selectedOpt.getAttribute('data-qty'));
                if (qtyInput) {
                    qtyInput.setAttribute('max', currentMaxQty);
                    qtyInput.value = currentMaxQty;
                }
                if (availNotice) {
                    availNotice.innerHTML = `<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Max Available in Stock: <strong>${currentMaxQty.toFixed(2)}</strong></span>`;
                }
                if (submitBtn) submitBtn.disabled = false;
            } else {
                currentMaxQty = 0;
                if (qtyInput) qtyInput.removeAttribute('max');
            }
        });

        // 4. Quantity Input Realtime Validation
        if (qtyInput) {
            qtyInput.addEventListener('input', function() {
                const val = parseFloat(this.value) || 0;
                if (currentMaxQty > 0 && val > currentMaxQty) {
                    if (availNotice) {
                        availNotice.innerHTML = `<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Cannot transfer more than available stock (${currentMaxQty.toFixed(2)})</span>`;
                    }
                    if (submitBtn) submitBtn.disabled = true;
                } else if (currentMaxQty > 0 && val <= currentMaxQty) {
                    if (availNotice) {
                        availNotice.innerHTML = `<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Max Available in Stock: <strong>${currentMaxQty.toFixed(2)}</strong></span>`;
                    }
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        }
    }
});
</script>
