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

<ul class="nav nav-tabs mb-4 border-bottom-0">
    <li class="nav-item">
        <a class="nav-link text-secondary" href="<?= base_url('company/merchandising/costsheets') ?>">
            <i class="fa-solid fa-calculator me-1"></i> Cost Sheet Estimates
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active fw-semibold text-primary" href="<?= base_url('company/merchandising/buyerpos') ?>">
            <i class="fa-solid fa-file-contract me-1"></i> Buyer POs (Contracts)
        </a>
    </li>
</ul>

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
                            <select name="style_id" id="style_select_po" class="form-select text-dark" required>
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
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Order Qty (pcs) <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" id="po_overall_qty" class="form-control" placeholder="e.g. 5000" min="1" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Unit Price (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="unit_price" class="form-control" placeholder="e.g. 240.00" min="0.01" required>
                            </div>
                        </div>

                        <!-- Size-Wise Quantities Grid -->
                        <div id="size-quantities-container" class="mt-3 p-3 bg-light border rounded text-dark" style="display: none;">
                            <h6 class="fw-bold mb-1 text-primary" style="font-size: 13px;"><i class="fa-solid fa-arrows-left-right me-1"></i> Size breakdown Details</h6>
                            <p class="text-secondary small mb-3" style="font-size: 11px;">Allocate quantities across the active garment style sizes. Total size quantity cannot exceed the overall PO quantity.</p>
                            <div id="size-fields-wrapper" class="row row-cols-3 g-2"></div>
                            <div class="mt-3 text-secondary small d-flex justify-content-between pt-2 border-top" style="font-size: 12px;">
                                <span>Total size breakdown sum: <strong id="size-sum-indicator" class="text-dark">0</strong> pcs</span>
                                <span>Unallocated remaining: <strong id="size-rem-indicator" class="text-success">0</strong> pcs</span>
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

<script>
const styleSizeRanges = {
    <?php foreach ($styles as $s): ?>
        "<?= $s['id'] ?>": "<?= htmlspecialchars($s['size_range'] ?? '') ?>",
    <?php endforeach; ?>
};

document.addEventListener('DOMContentLoaded', function() {
    const styleSelect = document.getElementById('style_select_po');
    const qtyInput = document.getElementById('po_overall_qty');
    const container = document.getElementById('size-quantities-container');
    const wrapper = document.getElementById('size-fields-wrapper');
    const sumIndicator = document.getElementById('size-sum-indicator');
    const remIndicator = document.getElementById('size-rem-indicator');

    function updateSizeBreakdown() {
        const styleId = styleSelect.value;
        const sizeRangeStr = styleSizeRanges[styleId] || '';
        
        if (!styleId || !sizeRangeStr.trim()) {
            container.style.display = 'none';
            wrapper.innerHTML = '';
            return;
        }

        // Split sizes (comma separated)
        const sizes = sizeRangeStr.split(',').map(s => s.trim()).filter(s => s !== '');
        
        if (sizes.length === 0) {
            container.style.display = 'none';
            wrapper.innerHTML = '';
            return;
        }

        container.style.display = 'block';
        wrapper.innerHTML = '';

        sizes.forEach(size => {
            const col = document.createElement('div');
            col.className = 'col mb-2';
            col.innerHTML = `
                <label class="form-label small fw-bold mb-1 text-secondary" style="font-size: 11px;">Size ${size}</label>
                <input type="number" name="size_qty[${size}]" class="form-control form-control-sm size-qty-input font-monospace" min="0" value="0" style="font-size: 12px;">
            `;
            wrapper.appendChild(col);
        });

        // Add event listeners to new inputs
        document.querySelectorAll('.size-qty-input').forEach(input => {
            input.addEventListener('input', calculateSums);
        });

        calculateSums();
    }

    function calculateSums() {
        let sum = 0;
        document.querySelectorAll('.size-qty-input').forEach(input => {
            sum += parseInt(input.value) || 0;
        });

        const overall = parseInt(qtyInput.value) || 0;
        sumIndicator.innerText = sum.toLocaleString();
        
        const rem = overall - sum;
        remIndicator.innerText = rem.toLocaleString();

        if (rem < 0) {
            remIndicator.className = 'text-danger fw-bold';
        } else {
            remIndicator.className = 'text-success fw-bold';
        }
    }

    if (styleSelect) {
        styleSelect.addEventListener('change', updateSizeBreakdown);
    }
    if (qtyInput) {
        qtyInput.addEventListener('input', calculateSums);
    }

    // Form submission validation
    const form = container ? container.closest('form') : null;
    if (form) {
        form.addEventListener('submit', function(e) {
            let sum = 0;
            document.querySelectorAll('.size-qty-input').forEach(input => {
                sum += parseInt(input.value) || 0;
            });
            const overall = parseInt(qtyInput.value) || 0;

            if (sum > overall) {
                e.preventDefault();
                alert('Validation Error: The sum of size quantities (' + sum + ') cannot exceed the total order quantity (' + overall + ').');
                return false;
            }
        });
    }
});
</script>
