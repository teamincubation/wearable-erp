<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Material Requisitions</h3>
        <p class="text-secondary m-0">Internal requisitions and raw material requirement planning</p>
    </div>
    <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addRequisitionModal">
            <i class="fa-solid fa-file-signature me-1"></i> New Requisition
        </button>
    <?php endif; ?>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-list-check text-primary me-2"></i> Requisitions Log</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Requisition No</th>
                        <th>Style Code / Name</th>
                        <th>Buyer PO Link</th>
                        <th>Date Registered</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requisitions)): ?>
                        <?php foreach ($requisitions as $r): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= htmlspecialchars($r['requisition_no']) ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong class="text-dark"><?= htmlspecialchars($r['style_no']) ?></strong>
                                        <div class="text-secondary small"><?= htmlspecialchars($r['style_name']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($r['buyer_po_no']): ?>
                                        <span class="badge bg-light text-secondary font-monospace"><?= htmlspecialchars($r['buyer_po_no']) ?></span>
                                    <?php else: ?>
                                        <span class="text-secondary small">General WIP</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                                <td>
                                    <span class="badge badge-pepp badge-success text-capitalize"><?= htmlspecialchars($r['status']) ?></span>
                                </td>
                                <td class="text-end">
                                    <form action="<?= base_url('company/purchase/requisitions/delete/' . $r['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this requisition?');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-clipboard-list fs-1 mb-3 text-light"></i>
                                <p class="m-0">No purchase requisitions registered yet.</p>
                                <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                    <button class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addRequisitionModal">
                                        <i class="fa-solid fa-plus me-1"></i> Add First Requisition
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

<!-- Add Requisition Modal -->
<?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
    <div class="modal fade" id="addRequisitionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/purchase/requisitions/create') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Create Material Requisition</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Style <span class="text-danger">*</span></label>
                            <select name="style_id" class="form-select text-dark" required>
                                <option value="">-- Choose Style --</option>
                                <?php foreach ($styles as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['style_no']) ?> - <?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Buyer PO (Optional)</label>
                            <select name="po_id" class="form-select text-dark">
                                <option value="">-- Choose Active PO (WIP) --</option>
                                <?php foreach ($buyer_pos as $bp): ?>
                                    <option value="<?= $bp['id'] ?>"><?= htmlspecialchars($bp['po_no']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Requisition Reference Number <span class="text-danger">*</span></label>
                            <input type="text" name="requisition_no" class="form-control font-monospace" placeholder="e.g. REQ-TOCCO-2026-101" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Requisition Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Create Requisition</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
