<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Goods Receipt Notes (GRN)</h3>
        <p class="text-secondary m-0">Log inbound materials shipments, inspect quantities, and update inventory stock ledgers</p>
    </div>
    <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addGrnModal">
            <i class="fa-solid fa-receipt me-1"></i> Register GRN
        </button>
    <?php endif; ?>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-clipboard-check text-primary me-2"></i> Goods Receipt Logs</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>GRN Number</th>
                        <th>Supplier PO Reference</th>
                        <th>Receipt Date</th>
                        <th>Invoice Ref</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($grns)): ?>
                        <?php foreach ($grns as $g): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= htmlspecialchars($g['grn_no']) ?></strong>
                                </td>
                                <td>
                                    <?php if ($g['po_no']): ?>
                                        <span class="badge bg-light text-secondary font-monospace"><?= htmlspecialchars($g['po_no']) ?></span>
                                    <?php else: ?>
                                        <span class="text-secondary small">Direct Receipt</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d M Y', strtotime($g['date'])) ?></td>
                                <td><?= htmlspecialchars($g['invoice_no'] ?: 'N/A') ?></td>
                                <td>
                                    <span class="badge badge-pepp badge-success text-capitalize"><?= htmlspecialchars($g['status']) ?></span>
                                </td>
                                <td class="text-end">
                                    <form action="<?= base_url('company/purchase/grn/delete/' . $g['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this Goods Receipt Note?');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-warehouse fs-1 mb-3 text-light"></i>
                                <p class="m-0">No Goods Receipt Notes logged in the ledger yet.</p>
                                <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                    <button class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addGrnModal">
                                        <i class="fa-solid fa-plus me-1"></i> Log First GRN
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

<!-- Add GRN Modal -->
<?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
    <div class="modal fade" id="addGrnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <form action="<?= base_url('company/purchase/grns/create') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Log Goods Receipt Note (GRN)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Link Supplier PO (Optional)</label>
                                <select name="po_id" class="form-select text-dark">
                                    <option value="">-- Direct Store Receipt --</option>
                                    <?php foreach ($supplier_pos as $sp): ?>
                                        <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['po_no']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">GRN Number <span class="text-danger">*</span></label>
                                <input type="text" name="grn_no" class="form-control font-monospace" placeholder="e.g. GRN-2026-0001" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Receipt Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Supplier Invoice Number</label>
                                <input type="text" name="invoice_no" class="form-control" placeholder="e.g. INV-901">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Destination Warehouse <span class="text-danger">*</span></label>
                                <select name="warehouse_id" class="form-select text-dark" required>
                                    <option value="">-- Select Target Warehouse --</option>
                                    <?php foreach ($warehouses as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars(ucfirst($w['type'])) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Accepted stock balances will immediately increment in this warehouse ledger.</div>
                            </div>
                        </div>

                        <!-- Dynamic Items Table -->
                        <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="fa-solid fa-pallet me-1"></i> Materials Received list</h6>
                        <table class="table table-bordered table-sm" id="grnItemsTable">
                            <thead>
                                <tr class="table-light">
                                    <th>Item Type</th>
                                    <th>Item Description</th>
                                    <th style="width: 100px;">Qty Recd</th>
                                    <th style="width: 100px;">Qty Accepted</th>
                                    <th style="width: 100px;">Qty Rejected</th>
                                    <th>Batch Lot / Heat No</th>
                                    <th class="text-center" style="width: 40px;">X</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="item_type[]" class="form-select form-select-sm">
                                            <option value="fabric">Fabric</option>
                                            <option value="yarn">Yarn</option>
                                            <option value="accessories" selected>Accessories</option>
                                            <option value="chemical">Chemicals</option>
                                            <option value="packing">Packing Materials</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="item_name[]" class="form-control form-control-sm" placeholder="Grey cotton lot, Button line, Polybags" required></td>
                                    <td><input type="number" step="0.01" name="qty_received[]" class="form-control form-control-sm" value="0.00" required></td>
                                    <td><input type="number" step="0.01" name="qty_accepted[]" class="form-control form-control-sm text-success fw-bold" value="0.00" required></td>
                                    <td><input type="number" step="0.01" name="qty_rejected[]" class="form-control form-control-sm text-danger" value="0.00" required></td>
                                    <td><input type="text" name="batch_no[]" class="form-control form-control-sm font-monospace" placeholder="e.g. BT-LOT-901"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-grn-item-btn p-0"><i class="fa-solid fa-circle-xmark"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addGrnItemRowBtn"><i class="fa-solid fa-plus me-1"></i> Add Material Line</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Log GRN & Stock Ledger</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsTable = document.getElementById('grnItemsTable').querySelector('tbody');
    
    document.getElementById('addGrnItemRowBtn').addEventListener('click', function() {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select name="item_type[]" class="form-select form-select-sm">
                    <option value="fabric">Fabric</option>
                    <option value="yarn">Yarn</option>
                    <option value="accessories" selected>Accessories</option>
                    <option value="chemical">Chemicals</option>
                    <option value="packing">Packing Materials</option>
                </select>
            </td>
            <td><input type="text" name="item_name[]" class="form-control form-control-sm" placeholder="Item name" required></td>
            <td><input type="number" step="0.01" name="qty_received[]" class="form-control form-control-sm" value="0.00" required></td>
            <td><input type="number" step="0.01" name="qty_accepted[]" class="form-control form-control-sm text-success fw-bold" value="0.00" required></td>
            <td><input type="number" step="0.01" name="qty_rejected[]" class="form-control form-control-sm text-danger" value="0.00" required></td>
            <td><input type="text" name="batch_no[]" class="form-control form-control-sm font-monospace" placeholder="Batch lot code"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-grn-item-btn p-0"><i class="fa-solid fa-circle-xmark"></i></button></td>
        `;
        itemsTable.appendChild(row);
        bindGrnRemoveButtons();
    });

    function bindGrnRemoveButtons() {
        document.querySelectorAll('.remove-grn-item-btn').forEach(function(btn) {
            btn.onclick = function() {
                this.closest('tr').remove();
            };
        });
    }

    bindGrnRemoveButtons();
});
</script>
