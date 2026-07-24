<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Completed Buyer Contracts Archive</h3>
        <p class="text-secondary m-0">Archive of fully fulfilled buyer purchase orders and completed manufacturing contracts</p>
    </div>
    <div class="d-flex align-items-center">
        <a href="<?= base_url('company/merchandising/buyerpos') ?>" class="btn btn-outline-primary rounded-pill px-4 me-2">
            <i class="fa-solid fa-file-contract me-1"></i> Active Buyer POs
        </a>
        <a href="<?= base_url('company/merchandising/costsheets') ?>" class="btn rounded-pill px-4 shadow-sm text-dark border-0" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); font-weight: 700; letter-spacing: 0.3px; box-shadow: 0 4px 14px rgba(255, 152, 0, 0.4) !important;">
            <i class="fa-solid fa-calculator me-1"></i> Cost Sheet Estimates <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
    </div>
</div>

<ul class="nav nav-tabs mb-4 border-bottom-0">
    <li class="nav-item">
        <a class="nav-link text-secondary fw-semibold" href="<?= base_url('company/merchandising/buyerpos') ?>">
            <i class="fa-solid fa-file-contract me-1"></i> Buyer POs (Contracts)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active fw-bold text-success" href="<?= base_url('company/merchandising/completed-contracts') ?>">
            <i class="fa-solid fa-circle-check me-1"></i> Completed Contracts Archive
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-secondary fw-semibold" href="<?= base_url('company/merchandising/costsheets') ?>">
            <i class="fa-solid fa-calculator me-1"></i> Cost Sheet Estimates
        </a>
    </li>
</ul>

<!-- Completed Buyer Contracts Archive Section -->
<div class="pepp-card">
    <div class="pepp-card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="pepp-card-title text-success m-0"><i class="fa-solid fa-circle-check me-2"></i> Completed Buyer Contracts Archive</h5>
        <span class="badge bg-white text-secondary border"><?= count($completed_orders ?? []) ?> Completed Contract(s)</span>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0 align-middle">
                <thead>
                    <tr class="bg-light">
                        <th>PO Number</th>
                        <th>Buyer Client</th>
                        <th>Style Code / Name</th>
                        <th>Order Qty</th>
                        <th>Unit Price</th>
                        <th>Total Contract Value</th>
                        <th>PO Date</th>
                        <th>Delivery Due Date</th>
                        <th>Completion Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($completed_orders)): ?>
                        <?php foreach ($completed_orders as $co): ?>
                            <tr>
                                <td>
                                    <strong class="text-success font-monospace fs-6"><?= htmlspecialchars($co['po_no']) ?></strong>
                                </td>
                                <td>
                                    <strong class="text-dark d-block"><?= htmlspecialchars($co['buyer_name']) ?></strong>
                                    <span class="badge bg-light text-secondary border font-monospace"><?= htmlspecialchars($co['buyer_code'] ?? '') ?></span>
                                </td>
                                <td>
                                    <div>
                                        <strong class="text-dark"><?= htmlspecialchars($co['style_no']) ?></strong>
                                        <div class="text-secondary small"><?= htmlspecialchars($co['style_name']) ?></div>
                                    </div>
                                </td>
                                <td><strong class="font-monospace text-dark"><?= number_format($co['quantity']) ?> pcs</strong></td>
                                <td><span class="font-monospace text-dark">₹<?= number_format($co['unit_price'], 2) ?></span></td>
                                <td><strong class="font-monospace text-success fs-6">₹<?= number_format($co['total_amount'], 2) ?></strong></td>
                                <td><span class="small text-secondary font-monospace"><?= date('d-M-Y', strtotime($co['po_date'])) ?></span></td>
                                <td><span class="small text-secondary font-monospace"><?= date('d-M-Y', strtotime($co['delivery_date'])) ?></span></td>
                                <td>
                                    <span class="badge bg-success text-white font-monospace text-uppercase px-2.5 py-1.5">
                                        <i class="fa-solid fa-circle-check me-1"></i> Completed
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-box-archive fs-1 mb-3 text-light"></i>
                                <p class="m-0">No completed buyer contracts recorded in the archive yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
