<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Quality Control (AQL Inspections)</h3>
        <p class="text-secondary m-0">Inbound and in-process AQL audits, defect tracking, and rework control</p>
    </div>
    <?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addInspectionModal">
            <i class="fa-solid fa-clipboard-check me-1"></i> Log Quality Check
        </button>
    <?php endif; ?>
</div>

<div class="pepp-card">
    <div class="pepp-card-header d-flex justify-content-between align-items-center">
        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-square-check text-primary me-2"></i> Quality Assurance Logs</h5>
        <?php if (!empty($inspections) && \App\Core\Auth::hasPermission('company.production.manage')): ?>
            <button type="button" onclick="triggerSecurityDeleteModal('<?= base_url('company/production/quality/clear-all') ?>', 'Are you sure you want to DELETE ALL Quality Control inspection records?')" class="btn btn-sm btn-outline-danger rounded-pill fw-bold">
                <i class="fa-solid fa-trash-can me-1"></i> Delete All Inspections
            </button>
        <?php endif; ?>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Audit ID</th>
                        <th>Ref Type</th>
                        <th>Ref ID</th>
                        <th>Inspected Qty</th>
                        <th>Passed Qty</th>
                        <th>Failed Qty</th>
                        <th>Defects Count</th>
                        <th>Rework / Reject</th>
                        <th>AQL Status</th>
                        <th>Inspector</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inspections)): ?>
                        <?php foreach ($inspections as $qi): ?>
                            <?php 
                                $defects = json_decode($qi['defects_json'] ?? '[]', true) ?: [];
                                $defectCount = array_sum($defects);
                            ?>
                            <tr>
                                <td><span class="text-secondary small font-monospace">#<?= $qi['id'] ?></span></td>
                                <td><span class="badge bg-light text-secondary text-uppercase"><?= htmlspecialchars($qi['reference_type']) ?></span></td>
                                <td><span class="font-monospace text-dark font-semibold">Ref #<?= $qi['reference_id'] ?></span></td>
                                <td><?= number_format($qi['inspected_qty']) ?> pcs</td>
                                <td class="text-success fw-bold"><?= number_format($qi['passed_qty']) ?></td>
                                <td class="text-danger fw-bold"><?= number_format($qi['failed_qty']) ?></td>
                                <td>
                                    <?php if ($defectCount > 0): ?>
                                        <span class="badge bg-warning text-dark" title="<?= htmlspecialchars(json_encode($defects)) ?>">
                                            <?= $defectCount ?> Defects
                                        </span>
                                    <?php else: ?>
                                        <span class="text-secondary small">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small">Rework: <?= $qi['rework_qty'] ?></div>
                                    <div class="small">Reject: <?= $qi['reject_qty'] ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-pepp <?= $qi['aql_status'] === 'pass' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= htmlspecialchars(strtoupper($qi['aql_status'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($qi['inspector_name'] ?: 'Inspector') ?></td>
                                <td><?= date('d M Y', strtotime($qi['created_at'])) ?></td>
                                <td class="text-end">
                                    <form action="<?= base_url('company/production/quality/delete/' . $qi['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this AQL inspection log?');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-clipboard-check fs-1 mb-3 text-light"></i>
                                <p class="m-0">No quality inspections registered yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Quality Inspection Modal -->
<?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
    <div class="modal fade" id="addInspectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="<?= base_url('company/production/quality/create') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Register Quality Check / AQL Audit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Audit Reference Category <span class="text-danger">*</span></label>
                                <select name="reference_type" class="form-select text-dark" required>
                                    <option value="production">Production Order Batch</option>
                                    <option value="grn">GRN Material Delivery</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Select Reference Batch ID <span class="text-danger">*</span></label>
                                <select name="reference_id" class="form-select text-dark" required>
                                    <option value="">-- Choose Reference --</option>
                                    <?php foreach ($orders as $o): ?>
                                        <option value="<?= $o['id'] ?>">Production Order #<?= $o['id'] ?> (<?= htmlspecialchars($o['production_no']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Audit AQL Verdict <span class="text-danger">*</span></label>
                                <select name="aql_status" class="form-select text-dark" required>
                                    <option value="pass">PASS (Meets limits)</option>
                                    <option value="fail">FAIL (Rejected)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Inspected Qty (pcs) <span class="text-danger">*</span></label>
                                <input type="number" name="inspected_qty" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Passed Qty <span class="text-danger">*</span></label>
                                <input type="number" name="passed_qty" class="form-control text-success fw-bold" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Failed Qty <span class="text-danger">*</span></label>
                                <input type="number" name="failed_qty" class="form-control text-danger" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Rework Qty</label>
                                <input type="number" name="rework_qty" class="form-control" value="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Reject Qty</label>
                                <input type="number" name="reject_qty" class="form-control" value="0">
                            </div>
                        </div>

                        <!-- Defect breakdown logs -->
                        <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="fa-solid fa-circle-exclamation me-1"></i> Defect Category Checklist</h6>
                        <div id="defectsList">
                            <div class="row g-2 mb-2 defect-row">
                                <div class="col-6">
                                    <select name="defect_key[]" class="form-select form-select-sm">
                                        <option value="">-- Choose Defect Type --</option>
                                        <option value="Stitching Skip">Stitching Skip</option>
                                        <option value="Measurement Out">Measurement Out (Specs mismatch)</option>
                                        <option value="Oil Mark / Stain">Oil Mark / Stain</option>
                                        <option value="Dyeing Spot">Dyeing Spot</option>
                                        <option value="Hole / Knitting Defect">Hole / Knitting Defect</option>
                                        <option value="Thread Ends Untrimmed">Thread Ends Untrimmed</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <input type="number" name="defect_val[]" class="form-control form-control-sm" placeholder="Defects count" value="0">
                                </div>
                                <div class="col-2 text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger remove-defect-row-btn p-0"><i class="fa-solid fa-circle-xmark"></i></button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addDefectRowBtn"><i class="fa-solid fa-plus me-1"></i> Add Defect Line</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Save Audit Check</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const defectsList = document.getElementById('defectsList');
    
    document.getElementById('addDefectRowBtn').addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 defect-row';
        row.innerHTML = `
            <div class="col-6">
                <select name="defect_key[]" class="form-select form-select-sm">
                    <option value="">-- Choose Defect Type --</option>
                    <option value="Stitching Skip">Stitching Skip</option>
                    <option value="Measurement Out">Measurement Out</option>
                    <option value="Oil Mark / Stain">Oil Mark / Stain</option>
                    <option value="Dyeing Spot">Dyeing Spot</option>
                    <option value="Hole / Knitting Defect">Hole / Knitting Defect</option>
                    <option value="Thread Ends Untrimmed">Thread Ends Untrimmed</option>
                </select>
            </div>
            <div class="col-4">
                <input type="number" name="defect_val[]" class="form-control form-control-sm" placeholder="Defects count" value="0">
            </div>
            <div class="col-2 text-center">
                <button type="button" class="btn btn-sm btn-link text-danger remove-defect-row-btn p-0"><i class="fa-solid fa-circle-xmark"></i></button>
            </div>
        `;
        defectsList.appendChild(row);
        bindDefectRemoveButtons();
    });

    function bindDefectRemoveButtons() {
        document.querySelectorAll('.remove-defect-row-btn').forEach(function(btn) {
            btn.onclick = function() {
                this.closest('.defect-row').remove();
            };
        });
    }

    bindDefectRemoveButtons();
});
</script>

<!-- Security Confirmation DELETE Prompt Modal -->
<div class="modal fade text-start" id="securityDeleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg text-dark" style="border-radius: 16px;">
            <form id="securityDeleteConfirmForm" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Security Confirmation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <p class="mb-2 text-dark fw-semibold" id="securityDeleteModalTargetText">Are you sure you want to delete this record?</p>
                    <div class="alert alert-warning border-0 rounded-3 p-3 text-secondary small mb-3">
                        <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i> This action cannot be undone. To proceed, please type <strong class="text-danger font-monospace">DELETE</strong> in the box below.
                    </div>
                    <label class="form-label fw-semibold small text-secondary">Confirmation Phrase:</label>
                    <input type="text" id="securityDeleteConfirmInput" name="confirm_code" class="form-control form-control-lg font-monospace text-center fw-bold" placeholder="Type DELETE to confirm" autocomplete="off" required style="letter-spacing: 2px;">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="securityDeleteSubmitBtn" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold" disabled>
                        <i class="fa-solid fa-trash me-1"></i> Confirm Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function triggerSecurityDeleteModal(actionUrl, targetMessage) {
        const form = document.getElementById('securityDeleteConfirmForm');
        const textEl = document.getElementById('securityDeleteModalTargetText');
        const inputEl = document.getElementById('securityDeleteConfirmInput');
        const btnEl = document.getElementById('securityDeleteSubmitBtn');

        if (form && inputEl && btnEl) {
            form.action = actionUrl;
            if (textEl) textEl.innerText = targetMessage || 'Are you sure you want to proceed with deletion?';
            inputEl.value = '';
            btnEl.disabled = true;

            const modal = new bootstrap.Modal(document.getElementById('securityDeleteConfirmModal'));
            modal.show();

            inputEl.oninput = function() {
                btnEl.disabled = (inputEl.value.trim() !== 'DELETE');
            };
        }
    }
</script>
