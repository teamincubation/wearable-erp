<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Tally Prime Accounts Integration</h3>
        <p class="text-secondary m-0">Export financial ledgers and vouchers directly to Tally Prime / ERP 9</p>
    </div>
    <div class="d-flex">
        <a href="<?= base_url('company/tally/vouchers/csv') ?>" class="btn btn-outline-secondary rounded-pill px-4 me-2">
            <i class="fa-solid fa-file-csv me-1"></i> Export Ledgers CSV
        </a>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addVoucherModal">
            <i class="fa-solid fa-file-invoice-dollar me-1"></i> Add Account Entry
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Unexported Queue Card -->
    <div class="col-md-7">
        <div class="pepp-card">
            <div class="pepp-card-header bg-light">
                <h5 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-list-ol text-primary me-2"></i> Pending Integration Queue (Unexported)</h5>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table pepp-table mb-0">
                        <thead>
                            <tr>
                                <th>Voucher Type / No</th>
                                <th>Ledger Name</th>
                                <th>Total Amount</th>
                                <th>Date</th>
                                <th class="text-end">XML Import Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($unexported)): ?>
                                <?php foreach ($unexported as $v): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-secondary text-uppercase"><?= htmlspecialchars($v['voucher_type']) ?></span>
                                            <div class="font-monospace text-primary small mt-1"><?= htmlspecialchars($v['voucher_no']) ?></div>
                                        </td>
                                        <td><strong><?= htmlspecialchars($v['ledger_name']) ?></strong></td>
                                        <td class="font-monospace text-dark fw-bold">₹<?= number_format($v['amount'], 2) ?></td>
                                        <td><?= date('d M Y', strtotime($v['date'])) ?></td>
                                        <td class="text-end">
                                            <a href="<?= base_url('company/tally/vouchers/download/' . $v['id']) ?>" class="btn btn-sm btn-outline-success px-3 rounded-pill">
                                                <i class="fa-solid fa-download me-1"></i> Tally XML
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center p-5 text-secondary">
                                        <i class="fa-solid fa-check-circle fs-1 mb-3 text-light"></i>
                                        <p class="m-0">Voucher queue is fully synchronized! No pending exports.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Exported History Card -->
    <div class="col-md-5">
        <div class="pepp-card">
            <div class="pepp-card-header bg-light">
                <h5 class="pepp-card-title m-0 text-secondary"><i class="fa-solid fa-history text-secondary me-2"></i> Exported Logs History</h5>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table pepp-table mb-0">
                        <thead>
                            <tr>
                                <th>Voucher Details</th>
                                <th>Amount</th>
                                <th>Sync Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($exported)): ?>
                                <?php foreach ($exported as $e): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark"><?= htmlspecialchars($e['voucher_no']) ?></strong>
                                            <div class="text-secondary small text-uppercase"><?= htmlspecialchars($e['voucher_type']) ?> - <?= htmlspecialchars($e['ledger_name']) ?></div>
                                        </td>
                                        <td class="font-monospace text-dark">₹<?= number_format($e['amount'], 2) ?></td>
                                        <td><span class="text-secondary small"><?= date('d M Y H:i', strtotime($e['exported_at'])) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center p-5 text-secondary">
                                        <i class="fa-solid fa-history fs-1 mb-3 text-light"></i>
                                        <p class="m-0">No vouchers exported yet.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Voucher Modal -->
<div class="modal fade" id="addVoucherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('company/tally/vouchers/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Account Ledger Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Voucher Type <span class="text-danger">*</span></label>
                        <select name="voucher_type" class="form-select text-dark" required>
                            <option value="sales">Sales Invoice (Receipts)</option>
                            <option value="purchase">Purchase Bill (Payments)</option>
                            <option value="payment">Cash / Bank Payment</option>
                            <option value="receipt">Cash / Bank Receipt</option>
                            <option value="contra">Contra (Bank Transfer)</option>
                            <option value="journal">Journal Voucher</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Voucher / Bill Number <span class="text-danger">*</span></label>
                        <input type="text" name="voucher_no" class="form-control font-monospace" placeholder="e.g. VCH-2026-901" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Account Entry Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Party / Ledger Account Name <span class="text-danger">*</span></label>
                        <input type="text" name="ledger_name" class="form-control" placeholder="e.g. TOCCO Exports Sales, Supplier Account" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Voucher Value Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 50000.00" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Narration Notes</label>
                        <textarea name="narration" class="form-control" rows="3" placeholder="Optional brief narration of payment/bill entries..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4">Generate Voucher</button>
                </div>
            </div>
        </form>
    </div>
</div>
