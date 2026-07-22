<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Production Orders</h3>
        <p class="text-secondary m-0">Plan, launch, and monitor active garment manufacturing batches</p>
    </div>
    <div class="d-flex align-items-center">
        <button class="btn btn-outline-info rounded-pill px-3 me-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#productionWorkflowHelpModal" style="border-width: 2px;" type="button">
            <i class="fa-solid fa-circle-question me-1"></i> How It Works?
        </button>
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
                                    <a href="<?= base_url('company/production/stage/' . $o['id']) ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill me-1">
                                        <i class="fa-solid fa-list-check me-1"></i> Stage Tracker / WIP
                                    </a>
                                    <form action="<?= base_url('company/production/orders/delete/' . $o['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this production order?');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
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
                                <?php if (empty($buyer_pos)): ?>
                                    <option value="">-- No Approved Buyer POs Available --</option>
                                <?php else: ?>
                                    <option value="">-- Select Approved PO Contract --</option>
                                    <?php foreach ($buyer_pos as $bp): ?>
                                        <option value="<?= $bp['id'] ?>">
                                            <?= htmlspecialchars($bp['po_no']) ?> | Buyer: <?= htmlspecialchars($bp['buyer_name']) ?> (<?= htmlspecialchars($bp['buyer_code']) ?>)<?= !empty($bp['brand_name']) ? ' - Brand: ' . htmlspecialchars($bp['brand_name']) : '' ?> | Style: <?= htmlspecialchars($bp['style_no']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($buyer_pos)): ?>
                                <div class="form-text text-danger mt-1 small">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> You must first create and approve a Buyer Purchase Order contract under <a href="<?= base_url('company/merchandising/buyerpos') ?>" class="text-danger fw-semibold text-decoration-underline">Merchandising > Buyer POs (Contracts)</a>.
                                </div>
                            <?php endif; ?>
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

<!-- Production Workflow Help Modal -->
<div class="modal fade" id="productionWorkflowHelpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content text-start" style="border-radius: 16px; border: none; box-shadow: var(--shadow-lg);">
            <div class="modal-header bg-info text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-circle-question me-2"></i> How Production Planning Works - Step-by-Step</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-dark p-4">
                <p class="text-secondary small mb-4">Follow this step-by-step workflow to plan, track, and complete a garment manufacturing batch in the ERP. Click on any shortcut to navigate directly to that section.</p>
                
                <div class="row g-4 position-relative">
                    <!-- Step 1 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">1</span>
                                    <h6 class="fw-bold mb-0 text-dark">Register Buyer / Client</h6>
                                </div>
                                <p class="text-secondary small mb-2">Add the buyer/client details first to establish customer profiles in the ERP database.</p>
                                <a href="<?= base_url('company/buyers') ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Go to Buyers Master
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">2</span>
                                    <h6 class="fw-bold mb-0 text-dark">Define Styles in Style Master</h6>
                                </div>
                                <p class="text-secondary small mb-2">Register style codes, style names, and design specifics for the items you plan to manufacture.</p>
                                <a href="<?= base_url('company/styles') ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Go to Style Master
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">3</span>
                                    <h6 class="fw-bold mb-0 text-dark">Book & Approve Buyer PO</h6>
                                </div>
                                <p class="text-secondary small mb-2">Create a Buyer Purchase Order (Contract) under Merchandising, link it to a Style, and set its status to **Approved**.</p>
                                <a href="<?= base_url('company/merchandising/buyerpos') ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Go to Buyer POs
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">4</span>
                                    <h6 class="fw-bold mb-0 text-dark">Configure Active WIP Stages</h6>
                                </div>
                                <p class="text-secondary small mb-2">Determine which manufacturing/WIP operational stages should be active or inactive in ERP settings.</p>
                                <a href="<?= base_url('company/settings') ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Go to ERP Settings
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">5</span>
                                    <h6 class="fw-bold mb-0 text-dark">Plan & Launch Production Batch</h6>
                                </div>
                                <p class="text-secondary small mb-2">Click **Plan New Batch** on this page, link it to the Approved Buyer PO, and assign a unique Batch Code number.</p>
                                <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#addProductionOrderModal">
                                    <i class="fa-solid fa-plus me-1"></i> Plan New Batch Now
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 6 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">6</span>
                                    <h6 class="fw-bold mb-0 text-dark">Move WIP Pipelines & Inspect</h6>
                                </div>
                                <p class="text-secondary small mb-2">Track garment quantities through the active operational stages and log Quality Inspections to finalize the batch.</p>
                                <a href="<?= base_url('company/production/quality') ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Go to Quality Control
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Got It, Close</button>
            </div>
        </div>
    </div>
</div>

