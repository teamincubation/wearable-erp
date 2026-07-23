<?php
$dbAccounts = \App\Core\Database::getInstance();
$stmtAccs = $dbAccounts->prepare("SELECT * FROM payment_accounts WHERE company_id = ? AND deleted_at IS NULL");
$stmtAccs->execute([\App\Core\Session::get('company_id')]);
$paymentAccountsList = $stmtAccs->fetchAll() ?: [];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Supplier Purchase Orders</h3>
        <p class="text-secondary m-0">Procure raw materials, yarn, fabric, and packing materials from suppliers</p>
    </div>
    <div>
        <a href="<?= base_url('company/inventory/balances') ?>" class="btn btn-outline-primary rounded-pill px-4 me-2">
            <i class="fa-solid fa-boxes-stacked me-1"></i> Stock Inventory
        </a>
        <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSupplierPoModal">
                <i class="fa-solid fa-cart-plus me-1"></i> New Purchase Order
            </button>
        <?php endif; ?>
    </div>
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
                        <th>BOM Category</th>
                        <th>Stock Received In (Warehouse)</th>
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
                                <td>
                                    <?php if (!empty($o['categories'])): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($o['categories'] as $cat): ?>
                                                <span class="badge bg-light text-dark border text-capitalize"><?= htmlspecialchars($cat) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-secondary small">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><i class="fa-solid fa-warehouse me-1 text-primary"></i> <?= htmlspecialchars($o['warehouse_name'] ?: 'Default Warehouse') ?></span>
                                </td>
                                <td><?= date('d M Y', strtotime($o['date'])) ?></td>
                                <td><strong class="text-dark">₹<?= number_format($o['total_amount'], 2) ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-pepp 
                                            <?php 
                                                if ($o['status'] === 'grn_completed') echo 'badge-success';
                                                elseif ($o['status'] === 'draft') echo 'badge-warning';
                                                elseif ($o['status'] === 'approved') echo 'badge-info';
                                                else echo 'badge-secondary';
                                            ?>">
                                            <?= ($o['status'] === 'approved') ? 'Pending' : (($o['status'] === 'grn_completed') ? 'GRN Completed' : htmlspecialchars(ucfirst($o['status']))) ?>
                                        </span>
                                        <?php if ($o['status'] === 'draft'): ?>
                                            <form action="<?= base_url('company/purchase/orders/update-status/' . $o['id']) ?>" method="POST" class="d-inline ms-2">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="btn btn-sm btn-link p-0 text-primary border-0 bg-transparent" title="Approve & Mark Pending">
                                                    <i class="fa-solid fa-circle-play fs-6"></i>
                                                </button>
                                            </form>
                                        <?php elseif ($o['status'] === 'approved'): ?>
                                            <button type="button" class="btn btn-sm btn-link p-0 text-success border-0 bg-transparent ms-2 po-payment-trigger-btn" 
                                                    data-po-id="<?= $o['id'] ?>" 
                                                    title="Mark GRN Completed & Add Payment">
                                                <i class="fa-solid fa-circle-check fs-6"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                        <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#editSupplierPoModal-<?= $o['id'] ?>"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                                        <form action="<?= base_url('company/purchase/orders/delete/' . $o['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this supplier purchase order?');">
                                            <?= \App\Core\Session::csrfField() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                        </form>
                                    <?php endif; ?>
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
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Supplier / Vendor <span class="text-danger">*</span></label>
                                <select name="supplier_id" class="form-select text-dark" required>
                                    <option value="">-- Choose Vendor --</option>
                                    <?php foreach ($suppliers as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['code']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Receiving Warehouse <span class="text-danger">*</span></label>
                                <select name="warehouse_id" class="form-select text-dark" required>
                                    <option value="">-- Choose Warehouse --</option>
                                    <?php foreach ($warehouses as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars(ucfirst($w['type'])) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Supplier PO Number <span class="text-danger">*</span></label>
                                <input type="text" name="po_no" class="form-control font-monospace" placeholder="e.g. SPO-2026-001" required>
                            </div>
                            <div class="col-md-2">
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

<?php if (!empty($orders)): ?>
    <?php foreach ($orders as $o): ?>
        <!-- Edit Supplier PO Modal -->
        <div class="modal fade" id="editSupplierPoModal-<?= $o['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form action="<?= base_url('company/purchase/orders/edit/' . $o['id']) ?>" method="POST">
                    <?= \App\Core\Session::csrfField() ?>
                    <div class="modal-content text-start" style="border-radius: 12px;">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Edit Supplier Purchase Order: <?= htmlspecialchars($o['po_no']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-dark">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Supplier / Vendor <span class="text-danger">*</span></label>
                                    <select name="supplier_id" class="form-select text-dark" required>
                                        <?php foreach ($suppliers as $s): ?>
                                            <option value="<?= $s['id'] ?>" <?= ($s['id'] == $o['supplier_id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['code']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Receiving Warehouse <span class="text-danger">*</span></label>
                                    <select name="warehouse_id" class="form-select text-dark" required>
                                        <option value="">-- Choose Warehouse --</option>
                                        <?php foreach ($warehouses as $w): ?>
                                            <option value="<?= $w['id'] ?>" <?= ($w['id'] == ($o['warehouse_id'] ?? null)) ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars(ucfirst($w['type'])) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Supplier PO Number <span class="text-danger">*</span></label>
                                    <input type="text" name="po_no" class="form-control font-monospace text-dark" value="<?= htmlspecialchars($o['po_no']) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">PO Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date" class="form-control text-dark" value="<?= htmlspecialchars(date('Y-m-d', strtotime($o['date']))) ?>" required>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Order Status</label>
                                    <select name="status" class="form-select text-dark edit-po-status-select" data-po-id="<?= $o['id'] ?>">
                                        <option value="draft" <?= ($o['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                                        <option value="approved" <?= ($o['status'] === 'approved') ? 'selected' : '' ?>>Pending</option>
                                        <option value="grn_completed" <?= ($o['status'] === 'grn_completed') ? 'selected' : '' ?>>GRN Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-8 edit-po-payment-container-<?= $o['id'] ?>" style="display: <?= ($o['status'] === 'grn_completed') ? 'flex' : 'none' ?>; gap: 15px;">
                                    <div class="flex-grow-1">
                                        <label class="form-label small fw-bold">Payment Account <span class="text-danger">*</span></label>
                                        <select name="payment_account_id" class="form-select text-dark edit-po-payment-account" <?= ($o['status'] === 'grn_completed') ? 'required' : '' ?>>
                                            <option value="">-- Choose Account --</option>
                                            <?php foreach ($paymentAccountsList as $acc): ?>
                                                <option value="<?= $acc['id'] ?>" <?= ($acc['id'] == $o['payment_account_id']) ? 'selected' : '' ?>><?= htmlspecialchars($acc['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div style="width: 180px;">
                                        <label class="form-label small fw-bold">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" name="payment_date" class="form-control text-dark edit-po-payment-date" value="<?= $o['payment_date'] ?: date('Y-m-d') ?>" <?= ($o['status'] === 'grn_completed') ? 'required' : '' ?>>
                                    </div>
                                </div>
                            </div>

                            <!-- Line Items Edit Table -->
                            <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="fa-solid fa-list me-1"></i> Order Items List</h6>
                            <table class="table table-bordered table-sm align-middle" id="editPoItemsTable-<?= $o['id'] ?>">
                                <thead>
                                    <tr class="bg-light text-dark">
                                        <th style="width: 28%;">Category</th>
                                        <th style="width: 38%;">Item Name / Spec</th>
                                        <th style="width: 15%;">Quantity</th>
                                        <th style="width: 15%;">Unit Rate</th>
                                        <th style="width: 4%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $db = \App\Core\Database::getInstance();
                                    $stmtItems = $db->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
                                    $stmtItems->execute([$o['id']]);
                                    $orderItems = $stmtItems->fetchAll() ?: [];
                                    foreach ($orderItems as $item): 
                                    ?>
                                        <tr>
                                            <td>
                                                <select name="item_type[]" class="form-select form-select-sm text-dark">
                                                    <?php if (!empty($categories)): ?>
                                                        <?php foreach ($categories as $cat): ?>
                                                            <option value="<?= htmlspecialchars($cat['name']) ?>" <?= ($cat['name'] === $item['item_type']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?> (<?= htmlspecialchars($cat['code']) ?>)</option>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <option value="Fabric" <?= ($item['item_type'] === 'Fabric') ? 'selected' : '' ?>>Fabric</option>
                                                        <option value="Yarn" <?= ($item['item_type'] === 'Yarn') ? 'selected' : '' ?>>Yarn</option>
                                                        <option value="Accessories" <?= ($item['item_type'] === 'Accessories') ? 'selected' : '' ?>>Accessories</option>
                                                        <option value="Chemicals" <?= ($item['item_type'] === 'Chemicals') ? 'selected' : '' ?>>Chemicals</option>
                                                        <option value="Packing" <?= ($item['item_type'] === 'Packing') ? 'selected' : '' ?>>Packing Materials</option>
                                                    <?php endif; ?>
                                                </select>
                                            </td>
                                            <td><input type="text" name="item_name[]" class="form-control form-control-sm text-dark" value="<?= htmlspecialchars($item['item_name']) ?>" required></td>
                                            <td><input type="number" step="0.01" name="quantity[]" class="form-control form-control-sm text-dark" value="<?= htmlspecialchars($item['quantity']) ?>" required></td>
                                            <td><input type="number" step="0.01" name="unit_price[]" class="form-control form-control-sm text-dark" value="<?= htmlspecialchars($item['unit_price']) ?>" required></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-item-btn p-0"><i class="fa-solid fa-circle-xmark"></i></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-outline-secondary add-edit-po-item-row-btn" data-target-table="editPoItemsTable-<?= $o['id'] ?>"><i class="fa-solid fa-plus me-1"></i> Add Item Line</button>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary px-4">Update Order</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Complete PO Payment Modal -->
<div class="modal fade" id="poPaymentModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog">
        <form id="poPaymentForm" method="POST" action="">
            <?= \App\Core\Session::csrfField() ?>
            <input type="hidden" name="status" value="grn_completed">
            <div class="modal-content text-start" style="border-radius: 12px;">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Complete PO & Mark Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-dark">
                    <p class="text-secondary small mb-3">To mark this Purchase Order as <strong>GRN Completed</strong>, select the payment account and booking date.</p>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Payment Account <span class="text-danger">*</span></label>
                        <select name="payment_account_id" class="form-select text-dark" required>
                            <option value="">-- Choose Account --</option>
                            <?php foreach ($paymentAccountsList as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?> (<?= htmlspecialchars($acc['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Submit & Complete GRN</button>
                </div>
            </div>
        </form>
    </div>
</div>

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

    document.querySelectorAll('.add-edit-po-item-row-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const tableId = this.getAttribute('data-target-table');
            const targetTableBody = document.getElementById(tableId).querySelector('tbody');
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
            targetTableBody.appendChild(row);
            bindPoRemoveButtons();
        });
    });

    function bindPoRemoveButtons() {
        document.querySelectorAll('.remove-item-btn').forEach(function(btn) {
            btn.onclick = function() {
                this.closest('tr').remove();
            };
        });
    }

    bindPoRemoveButtons();

    // Trigger Payment Modal from table checkmark button
    const poPaymentModalEl = document.getElementById('poPaymentModal');
    const poPaymentForm = document.getElementById('poPaymentForm');
    const bsPoPaymentModal = new bootstrap.Modal(poPaymentModalEl);

    document.querySelectorAll('.po-payment-trigger-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const poId = this.getAttribute('data-po-id');
            poPaymentForm.setAttribute('action', `<?= base_url('company/purchase/orders/update-status/') ?>` + poId);
            bsPoPaymentModal.show();
        });
    });

    // Dynamic fields toggle in edit modal status select
    document.querySelectorAll('.edit-po-status-select').forEach(select => {
        select.addEventListener('change', function() {
            const poId = this.getAttribute('data-po-id');
            const container = document.querySelector('.edit-po-payment-container-' + poId);
            if (!container) return;
            const accSelect = container.querySelector('.edit-po-payment-account');
            const dateInput = container.querySelector('.edit-po-payment-date');
            
            if (this.value === 'grn_completed') {
                container.style.display = 'flex';
                accSelect.required = true;
                dateInput.required = true;
            } else {
                container.style.display = 'none';
                accSelect.required = false;
                dateInput.required = false;
            }
        });
    });
});
</script>
