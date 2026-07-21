<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Supplier Purchase Orders</h3>
        <p class="text-secondary m-0">Procure raw materials, yarn, fabric, and packing materials from suppliers</p>
    </div>
    <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSupplierPoModal">
            <i class="fa-solid fa-cart-plus me-1"></i> New Purchase Order
        </button>
    <?php endif; ?>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-truck-loading text-primary me-2"></i> Active Purchase Orders Queue</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Supplier / Vendor</th>
                        <th>PO Date</th>
                        <th>Total Cost</th>
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
                                <td><?= htmlspecialchars($o['supplier_name']) ?></td>
                                <td><?= date('d M Y', strtotime($o['date'])) ?></td>
                                <td><strong class="text-dark">₹<?= number_format($o['total_amount'], 2) ?></strong></td>
                                <td>
                                    <span class="badge badge-pepp 
                                        <?php 
                                            if ($o['status'] === 'grn_completed') echo 'badge-success';
                                            elseif ($o['status'] === 'draft') echo 'badge-warning';
                                            else echo 'badge-info';
                                        ?>">
                                        <?= htmlspecialchars(str_replace('_', ' ', ucfirst($o['status']))) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form action="<?= base_url('company/purchase/orders/delete/' . $o['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this supplier purchase order?');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-file-invoice-dollar fs-1 mb-3 text-light"></i>
                                <p class="m-0">No supplier purchase orders registered yet.</p>
                                <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                    <button class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addSupplierPoModal">
                                        <i class="fa-solid fa-plus me-1"></i> Generate First Purchase Order
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

<!-- Add Supplier PO Modal -->
<?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
    <div class="modal fade" id="addSupplierPoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="<?= base_url('company/purchase/orders/create') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Generate Supplier Purchase Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Supplier / Vendor <span class="text-danger">*</span></label>
                                <select name="supplier_id" class="form-select text-dark" required>
                                    <option value="">-- Choose Vendor --</option>
                                    <?php foreach ($suppliers as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['code']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Supplier PO Number <span class="text-danger">*</span></label>
                                <input type="text" name="po_no" class="form-control font-monospace" placeholder="e.g. SPO-2026-001" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">PO Booking Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- Dynamic Items Addition -->
                        <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="fa-solid fa-list me-1"></i> Order Items list</h6>
                      <?php 
$catOptions = '';
if (!empty($categories)) {
    foreach ($categories as $cat) {
        $catOptions .= '<option value="' . htmlspecialchars($cat['name']) . '">' . htmlspecialchars($cat['name']) . ' (' . htmlspecialchars($cat['code']) . ')</option>';
    }
} else {
    $catOptions = '<option value="Fabric">Fabric</option><option value="Yarn">Yarn</option><option value="Accessories">Accessories</option><option value="Chemicals">Chemicals</option><option value="Packing">Packing Materials</option>';
}
?>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Line Items & Procurement Specifications <span class="text-danger">*</span></label>
                            <table class="table table-bordered table-sm align-middle" id="poItemsTable">
                                <thead>
                                    <tr class="bg-light">
                                        <th style="width: 28%;">Category</th>
                                        <th style="width: 38%;">Item Name / Spec</th>
                                        <th style="width: 15%;">Quantity</th>
                                        <th style="width: 15%;">Unit Rate</th>
                                        <th style="width: 4%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select name="item_type[]" class="form-select form-select-sm">
                                                <?= $catOptions ?>
                                            </select>
                                        </td>
                                        <td><input type="text" name="item_name[]" class="form-control form-control-sm" placeholder="e.g. Grey Jersey Knit, Tags, Buttons" required></td>
                                        <td><input type="number" step="0.01" name="quantity[]" class="form-control form-control-sm" value="0.00" required></td>
                                        <td><input type="number" step="0.01" name="unit_price[]" class="form-control form-control-sm" value="0.00" required></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-item-btn p-0"><i class="fa-solid fa-circle-xmark"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="addPoItemRowBtn"><i class="fa-solid fa-plus me-1"></i> Add Item Line</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Generate Order</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsTable = document.getElementById('poItemsTable').querySelector('tbody');
    const catOptionsHtml = `<?= str_replace(["\r", "\n"], '', $catOptions) ?>`;
    
    document.getElementById('addPoItemRowBtn').addEventListener('click', function() {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select name="item_type[]" class="form-select form-select-sm">
                    ${catOptionsHtml}
                </select>
            </td>
            <td><input type="text" name="item_name[]" class="form-control form-control-sm" placeholder="Item name" required></td>
            <td><input type="number" step="0.01" name="quantity[]" class="form-control form-control-sm" value="0.00" required></td>
            <td><input type="number" step="0.01" name="unit_price[]" class="form-control form-control-sm" value="0.00" required></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-item-btn p-0"><i class="fa-solid fa-circle-xmark"></i></button></td>
        `;
        itemsTable.appendChild(row);
        bindPoRemoveButtons();
    });

    function bindPoRemoveButtons() {
        document.querySelectorAll('.remove-item-btn').forEach(function(btn) {
            btn.onclick = function() {
                this.closest('tr').remove();
            };
        });
    }

    bindPoRemoveButtons();
});
</script>
