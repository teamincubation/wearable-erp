<!-- EXECUTIVE SALES & FINANCIAL REPORTS HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold">Executive Sales & Financial Reports</h3>
        <p class="text-secondary m-0">Live operations snapshot & consolidated financial profitability for <strong><?= htmlspecialchars($company['name'] ?? 'Company') ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('company/sales-reports/export-batch-financials') ?>" class="btn btn-sm btn-outline-success fw-bold rounded-pill px-3 shadow-sm">
            <i class="fa-solid fa-file-excel me-1.5"></i> Export Batch Financials
        </a>
        <a href="<?= base_url('company/sales-reports/export-carton-analysis') ?>" class="btn btn-sm btn-success fw-bold rounded-pill px-3 shadow-sm">
            <i class="fa-solid fa-file-excel me-1.5"></i> Export Carton Analysis
        </a>
    </div>
</div>

<!-- DASHBOARD-MATCHING CONTROL & MULTI-FILTER HUB BAR -->
<div class="card p-3 mb-4 border-0 shadow-sm" style="border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0;">
    <form method="GET" action="<?= base_url('company/sales-reports') ?>" class="row g-2 align-items-end font-monospace" style="font-size: 12px;">
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-dark mb-1"><i class="fa-regular fa-calendar text-primary me-1"></i> Start Date</label>
            <input type="date" name="start_date" class="form-control form-control-sm text-dark bg-light rounded-3" value="<?= htmlspecialchars($filters['start_date']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-dark mb-1"><i class="fa-regular fa-calendar text-primary me-1"></i> End Date</label>
            <input type="date" name="end_date" class="form-control form-control-sm text-dark bg-light rounded-3" value="<?= htmlspecialchars($filters['end_date']) ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small fw-bold text-dark mb-1"><i class="fa-solid fa-user-tie text-primary me-1"></i> Buyer / Client</label>
            <select name="buyer_id" class="form-select form-select-sm text-dark bg-light rounded-3">
                <option value="">All Buyers & Clients</option>
                <?php foreach ($buyers as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $filters['buyer_id'] == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-dark mb-1"><i class="fa-solid fa-warehouse text-primary me-1"></i> Warehouse</label>
            <select name="warehouse_id" class="form-select form-select-sm text-dark bg-light rounded-3">
                <option value="">All Storage Locations</option>
                <?php foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= $filters['warehouse_id'] == $wh['id'] ? 'selected' : '' ?>><?= htmlspecialchars($wh['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-1">
            <label class="form-label small fw-bold text-dark mb-1"><i class="fa-solid fa-spinner text-primary me-1"></i> Status</label>
            <select name="status" class="form-select form-select-sm text-dark bg-light rounded-3">
                <option value="">All</option>
                <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="in_progress" <?= $filters['status'] === 'in_progress' ? 'selected' : '' ?>>WIP Running</option>
                <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Planned</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-1.5">
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold rounded-3 shadow-sm py-2">
                <i class="fa-solid fa-filter me-1"></i> Apply Filter
            </button>
            <a href="<?= base_url('company/sales-reports') ?>" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2">Reset</a>
        </div>
    </form>
</div>

<!-- EXECUTIVE 4 STAT WIDGETS GRID (MATCHING DASHBOARD STYLE) -->
<div class="row g-4 mb-5">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="stat-icon icon-success">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <div class="stat-number">₹<?= number_format($kpis['net_profit'], 0) ?></div>
            <div class="stat-label">NET PROFIT (MARGIN <?= number_format($kpis['profit_margin_pct'], 1) ?>%)</div>
            <div class="small text-muted mt-2 pt-2 border-top d-flex justify-content-between font-monospace" style="font-size: 11px;">
                <span>Total Sales: <strong>₹<?= number_format($kpis['total_sales_value'], 0) ?></strong></span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="stat-icon icon-primary">
                <i class="fa-solid fa-industry"></i>
            </div>
            <div class="stat-number"><?= number_format($kpis['total_batches']) ?></div>
            <div class="stat-label">TOTAL PRODUCTION BATCHES</div>
            <div class="small text-muted mt-2 pt-2 border-top d-flex justify-content-between font-monospace" style="font-size: 11px;">
                <span class="text-success"><i class="fa-solid fa-circle-check me-1"></i><?= $kpis['completed_batches'] ?> Done</span>
                <span class="text-primary"><i class="fa-solid fa-spinner fa-spin me-1"></i><?= $kpis['wip_batches'] ?> WIP</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="stat-icon icon-warning">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="stat-number">₹<?= number_format($kpis['outstanding_receivables'], 0) ?></div>
            <div class="stat-label">OUTSTANDING RECEIVABLES</div>
            <div class="small text-muted mt-2 pt-2 border-top d-flex justify-content-between font-monospace" style="font-size: 11px;">
                <span class="text-success">Received: <strong>₹<?= number_format($kpis['fully_received'] + $kpis['partially_received'], 0) ?></strong></span>
                <span class="text-danger">Pending: <strong>₹<?= number_format($kpis['pending_payments'], 0) ?></strong></span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="stat-icon icon-danger">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div class="stat-number">₹<?= number_format($kpis['warehouse_stock_value'], 0) ?></div>
            <div class="stat-label">FINISHED GOODS STOCK VALUATION</div>
            <div class="small text-muted mt-2 pt-2 border-top d-flex justify-content-between font-monospace" style="font-size: 11px;">
                <span>Ready Dispatch: <strong>₹<?= number_format($kpis['ready_dispatch_value'], 0) ?></strong></span>
            </div>
        </div>
    </div>
</div>

<!-- TABBED EXECUTIVE SECTION DATA HUB (MATCHING DASHBOARD CARD STYLING) -->
<div class="pepp-card border-0 shadow-sm overflow-hidden" style="border-radius: 16px; background: #ffffff;">
    <div class="pepp-card-header bg-light border-bottom p-2.5">
        <ul class="nav nav-pills card-header-pills font-monospace" id="salesReportsTab" role="tablist" style="font-size: 12.5px;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold py-2.5 px-4 rounded-3" id="batch-tab" data-bs-toggle="tab" data-bs-target="#batch-pane" type="button" role="tab">
                    <i class="fa-solid fa-file-invoice-dollar me-2"></i> Section 1: Production Batch Profitability Ledger
                    <span class="badge bg-primary text-white rounded-pill ms-2"><?= count($batchReportList) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold py-2.5 px-4 rounded-3 text-secondary" id="carton-tab" data-bs-toggle="tab" data-bs-target="#carton-pane" type="button" role="tab">
                    <i class="fa-solid fa-boxes-stacked me-2"></i> Section 2: Carton & Warehouse Sales Analysis
                    <span class="badge bg-secondary text-white rounded-pill ms-2"><?= count($cartonAnalysisList) ?></span>
                </button>
            </li>
        </ul>
    </div>

    <div class="pepp-card-body p-0">
        <div class="tab-content" id="salesReportsTabContent">
            
            <!-- TAB 1: PRODUCTION BATCH FINANCIALS -->
            <div class="tab-pane fade show active p-3.5" id="batch-pane" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 font-monospace">
                    <div>
                        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Production Batch Profitability Ledger</h5>
                        <p class="text-secondary small m-0 mt-0.5">Real-time target quantity, manufacturing costs, sales revenue, net profit, and payment receipt tracking</p>
                    </div>
                    <input type="text" id="batch-search-input" class="form-control form-control-sm font-monospace rounded-3" placeholder="Quick search batch, buyer, style..." style="width: 260px;">
                </div>

                <div class="table-responsive border-0">
                    <table class="table pepp-table table-hover align-middle mb-0 font-monospace" id="batch-table" style="font-size: 11.5px;">
                        <thead>
                            <tr>
                                <th class="ps-3">Batch No</th>
                                <th>Buyer / Client</th>
                                <th>Customer PO</th>
                                <th>Garment Style</th>
                                <th>Qty (Produced / Target)</th>
                                <th>Total Cost</th>
                                <th>Unit Price</th>
                                <th>Total Revenue</th>
                                <th>Net Profit</th>
                                <th>Margin %</th>
                                <th>Payment Status</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($batchReportList)): ?>
                                <?php foreach ($batchReportList as $b): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <strong class="text-primary font-monospace fs-6"><?= htmlspecialchars($b['batch_no']) ?></strong>
                                        </td>
                                        <td><span class="fw-semibold text-dark"><?= htmlspecialchars($b['buyer_name']) ?></span></td>
                                        <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($b['po_no']) ?></span></td>
                                        <td><small class="text-secondary"><?= htmlspecialchars($b['style_display']) ?></small></td>
                                        <td>
                                            <span class="fw-bold text-dark"><?= number_format($b['completed_qty']) ?></span> / <?= number_format($b['target_qty']) ?> pcs
                                        </td>
                                        <td><strong class="text-danger">₹<?= number_format($b['total_cost'], 0) ?></strong></td>
                                        <td class="text-secondary">₹<?= number_format($b['selling_price'], 2) ?></td>
                                        <td><strong class="text-dark">₹<?= number_format($b['total_sales_value'], 0) ?></strong></td>
                                        <td><strong class="<?= $b['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">₹<?= number_format($b['net_profit'], 0) ?></strong></td>
                                        <td>
                                            <span class="badge <?= $b['margin_pct'] >= 15 ? 'bg-success' : ($b['margin_pct'] >= 0 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                                <?= number_format($b['margin_pct'], 1) ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <!-- CLICKABLE / EDITABLE PAYMENT STATUS BADGE -->
                                            <?php if ($b['payment_status'] === 'Fully Received'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold cursor-pointer" onclick="openPaymentModal(<?= $b['batch_id'] ?>)" title="Click to view receipts & payment record">
                                                    <i class="fa-solid fa-circle-check me-1"></i> Fully Received
                                                </span>
                                            <?php elseif ($b['payment_status'] === 'Partially Received'): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1.5 fw-bold cursor-pointer" onclick="openPaymentModal(<?= $b['batch_id'] ?>)" title="Click to view receipts & record payment">
                                                    <i class="fa-solid fa-hourglass-half me-1"></i> Partially Received
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1.5 fw-bold cursor-pointer" onclick="openPaymentModal(<?= $b['batch_id'] ?>)" title="Click to view receipts & record payment">
                                                    <i class="fa-solid fa-clock me-1"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <div class="d-inline-flex gap-1">
                                                <!-- Action Button 1: View Receipt & Record Payment -->
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle shadow-sm" onclick="openPaymentModal(<?= $b['batch_id'] ?>)" title="View Receipt & Payment History" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                                    <i class="fa-solid fa-receipt"></i>
                                                </button>

                                                <!-- Action Button 2: View Stage Live Report -->
                                                <a href="<?= base_url('company/production/stage/' . $b['batch_id'] . '/live-report') ?>" class="btn btn-sm btn-outline-info rounded-circle shadow-sm" title="View Complete Production Stage Tracking" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                                    <i class="fa-solid fa-chart-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="12" class="text-center py-5 text-muted">No production batch financial records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: CARTON & WAREHOUSE SALES ANALYSIS -->
            <div class="tab-pane fade p-3.5" id="carton-pane" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 font-monospace">
                    <div>
                        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Carton & Warehouse Stock Sales Analysis</h5>
                        <p class="text-secondary small m-0 mt-0.5">Sealed carton box analysis, warehouse storage locations, estimated sales value, and expected profit margin</p>
                    </div>
                    <input type="text" id="carton-search-input" class="form-control form-control-sm font-monospace rounded-3" placeholder="Quick search carton, location..." style="width: 260px;">
                </div>

                <div class="table-responsive border-0">
                    <table class="table pepp-table table-hover align-middle mb-0 font-monospace" id="carton-table" style="font-size: 11.5px;">
                        <thead>
                            <tr>
                                <th class="ps-3">Carton ID</th>
                                <th>Shipment No</th>
                                <th>Garment Style</th>
                                <th>Qty (pcs)</th>
                                <th>Batch No</th>
                                <th>Warehouse Location</th>
                                <th>Dispatch Status</th>
                                <th>Total Mfg Cost</th>
                                <th>Estimated Value</th>
                                <th>Expected Profit</th>
                                <th>Expected Margin</th>
                                <th class="pe-3 text-end">Drill Down</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cartonAnalysisList)): ?>
                                <?php foreach ($cartonAnalysisList as $c): ?>
                                    <tr>
                                        <td class="ps-3"><strong class="text-primary font-monospace fs-6"><?= htmlspecialchars($c['carton_no']) ?></strong></td>
                                        <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($c['shipment_no']) ?></span></td>
                                        <td><small class="text-secondary"><?= htmlspecialchars($c['style_display']) ?></small></td>
                                        <td><span class="badge bg-success-subtle text-success border font-monospace px-2.5 py-1"><?= number_format($c['total_qty']) ?> pcs</span></td>
                                        <td><small class="font-monospace text-dark"><?= htmlspecialchars($c['batch_no']) ?></small></td>
                                        <td><span class="fw-semibold text-dark"><?= htmlspecialchars($c['location']) ?></span></td>
                                        <td>
                                            <?php if ($c['dispatch_status'] === 'Delivered'): ?>
                                                <span class="badge bg-success">Delivered</span>
                                            <?php elseif ($c['dispatch_status'] === 'Dispatched'): ?>
                                                <span class="badge bg-info text-dark">Dispatched</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">In Warehouse</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted">₹<?= number_format($c['total_mfg_cost'], 0) ?></td>
                                        <td><strong class="text-success">₹<?= number_format($c['estimated_sales_value'], 0) ?></strong></td>
                                        <td><strong class="<?= $c['expected_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">₹<?= number_format($c['expected_profit'], 0) ?></strong></td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= number_format($c['expected_margin_pct'], 1) ?>%</span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill btn-carton-drilldown px-3" data-carton-id="<?= $c['carton_id'] ?>">
                                                <i class="fa-solid fa-magnifying-glass me-1"></i> Contents
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="12" class="text-center py-5 text-muted">No carton analysis records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- PAYMENT DIALOG / RECEIPT MODAL -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content text-start" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <div class="modal-header bg-dark text-white py-3 px-4" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold font-outfit d-flex align-items-center" id="payment-modal-title">
                    <i class="fa-solid fa-receipt text-warning me-2"></i> Payment Receipts & Financial Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 font-monospace" id="payment-modal-body" style="font-size: 12.5px;">
                <div class="text-center py-5"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i></div>
            </div>
            <div class="modal-footer bg-light py-2.5 px-4" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close Window</button>
            </div>
        </div>
    </div>
</div>

<!-- ITEM DRILL DOWN MODAL FOR CARTON CONTENTS -->
<div class="modal fade" id="cartonDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content text-start" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <div class="modal-header bg-dark text-white py-3 px-4" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title font-outfit fw-bold d-flex align-items-center" id="carton-modal-title">
                    <i class="fa-solid fa-box-archive text-warning me-2"></i> Carton Contents & Batch Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 font-monospace" id="carton-modal-body" style="font-size: 12px;">
                <div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i></div>
            </div>
            <div class="modal-footer bg-light py-2.5 px-4" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close Window</button>
            </div>
        </div>
    </div>
</div>

<style>
.cursor-pointer { cursor: pointer !important; }
.cursor-pointer:hover { opacity: 0.85; transform: translateY(-1px); transition: all 0.2s ease; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab active text styling toggle
    const batchTabBtn = document.getElementById('batch-tab');
    const cartonTabBtn = document.getElementById('carton-tab');

    if (batchTabBtn && cartonTabBtn) {
        batchTabBtn.addEventListener('shown.bs.tab', function() {
            batchTabBtn.classList.remove('text-secondary');
            cartonTabBtn.classList.add('text-secondary');
        });

        cartonTabBtn.addEventListener('shown.bs.tab', function() {
            cartonTabBtn.classList.remove('text-secondary');
            batchTabBtn.classList.add('text-secondary');
        });
    }

    // Batch Table Search
    const batchSearchInput = document.getElementById('batch-search-input');
    const batchTableRows = document.querySelectorAll('#batch-table tbody tr');

    if (batchSearchInput) {
        batchSearchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            batchTableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Carton Table Search
    const cartonSearchInput = document.getElementById('carton-search-input');
    const cartonTableRows = document.querySelectorAll('#carton-table tbody tr');

    if (cartonSearchInput) {
        cartonSearchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            cartonTableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Carton Drill-Down Modal
    const cartonModalEl = document.getElementById('cartonDetailModal');
    const cartonModal = cartonModalEl ? new bootstrap.Modal(cartonModalEl) : null;
    const cartonModalTitle = document.getElementById('carton-modal-title');
    const cartonModalBody = document.getElementById('carton-modal-body');

    document.querySelectorAll('.btn-carton-drilldown').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartonId = this.getAttribute('data-carton-id');
            cartonModalTitle.innerHTML = '<i class="fa-solid fa-box-archive text-warning me-2"></i> Loading Carton Details...';
            cartonModalBody.innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i></div>';
            if (cartonModal) cartonModal.show();

            fetch('<?= base_url('company/sales-reports/api/carton-details/') ?>' + cartonId)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        cartonModalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }
                    const c = data.carton;
                    const items = data.items || [];

                    cartonModalTitle.innerHTML = `<i class="fa-solid fa-box-archive text-warning me-2"></i> Carton ID: ${c.carton_no} (Batch ${c.production_no || 'N/A'})`;

                    let itemsHtml = '';
                    if (items.length > 0) {
                        items.forEach((item, idx) => {
                            itemsHtml += `
                                <tr>
                                    <td>${idx + 1}</td>
                                    <td class="fw-bold text-primary">${item.product_qr_code || item.qr_code}</td>
                                    <td>${item.size || 'FREE'} / ${item.color || 'N/A'}</td>
                                    <td><span class="badge bg-success-subtle text-success border">1 pc</span></td>
                                    <td class="text-muted small">${item.assigned_at || item.created_at || 'N/A'}</td>
                                </tr>
                            `;
                        });
                    } else {
                        itemsHtml = `<tr><td colspan="5" class="text-center text-muted py-3">No individual product items logged inside this carton box.</td></tr>`;
                    }

                    cartonModalBody.innerHTML = `
                        <div class="row g-2 mb-3 bg-light p-3 rounded-3 border">
                            <div class="col-6 col-md-3"><small class="text-muted d-block">CARTON ID</small><strong>${c.carton_no}</strong></div>
                            <div class="col-6 col-md-3"><small class="text-muted d-block">BATCH NO</small><strong class="text-primary">${c.production_no || 'N/A'}</strong></div>
                            <div class="col-6 col-md-3"><small class="text-muted d-block">STYLE</small><strong>${c.style_no || 'N/A'} - ${c.style_name || ''}</strong></div>
                            <div class="col-6 col-md-3"><small class="text-muted d-block">LOCATION</small><strong class="text-dark">${c.warehouse_name || 'Factory Storage'}</strong></div>
                        </div>
                        <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-list me-1 text-primary"></i> Contained Products (${items.length} pcs)</h6>
                        <div class="table-responsive border rounded-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="bg-light text-uppercase small">
                                    <tr><th>#</th><th>Product QR Code</th><th>Size / Color</th><th>Quantity</th><th>Packing Date</th></tr>
                                </thead>
                                <tbody>${itemsHtml}</tbody>
                            </table>
                        </div>
                    `;
                })
                .catch(err => {
                    cartonModalBody.innerHTML = `<div class="alert alert-danger">Failed to load carton contents. Please try again.</div>`;
                });
        });
    });
});

// OPEN PAYMENT DIALOG / RECEIPT MODAL
function openPaymentModal(batchId) {
    const paymentModalEl = document.getElementById('paymentModal');
    const paymentModal = new bootstrap.Modal(paymentModalEl);
    const modalTitle = document.getElementById('payment-modal-title');
    const modalBody = document.getElementById('payment-modal-body');

    modalTitle.innerHTML = '<i class="fa-solid fa-receipt text-warning me-2"></i> Loading Payment History...';
    modalBody.innerHTML = '<div class="text-center py-5"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i></div>';
    paymentModal.show();

    fetch('<?= base_url('company/sales-reports/api/batch-payments/') ?>' + batchId)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }

            const b = data.batch;
            const payments = data.payments || [];
            const accounts = data.payment_accounts || [];
            const totalSales = parseFloat(data.total_sales_value) || 0;
            const totalReceived = parseFloat(data.total_received) || 0;
            const balanceDue = parseFloat(data.balance_due) || 0;
            const status = data.payment_status;

            modalTitle.innerHTML = `<i class="fa-solid fa-receipt text-warning me-2"></i> Financial Receipts: ${b.production_no}`;

            let statusBadge = `<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1.5 rounded-pill"><i class="fa-solid fa-clock me-1"></i> Pending</span>`;
            if (status === 'Fully Received') {
                statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> Fully Received</span>`;
            } else if (status === 'Partially Received') {
                statusBadge = `<span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1.5 rounded-pill"><i class="fa-solid fa-hourglass-half me-1"></i> Partially Received</span>`;
            }

            let historyRows = '';
            if (payments.length > 0) {
                payments.forEach((p, idx) => {
                    historyRows += `
                        <tr>
                            <td class="ps-3">${idx + 1}</td>
                            <td><strong>${p.paid_date}</strong></td>
                            <td class="text-success fw-bold">₹${parseFloat(p.amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td><span class="badge bg-light text-dark border">${p.payment_method || (p.account_name ? p.account_name + ' (' + p.account_type + ')' : 'Direct Payment')}</span></td>
                            <td class="text-secondary">${p.reference_no ? p.reference_no + (p.notes ? ' - ' + p.notes : '') : (p.notes || 'N/A')}</td>
                            <td class="pe-3 text-end"><span class="badge bg-success-subtle text-success border"><i class="fa-solid fa-check me-1"></i> Verified</span></td>
                        </tr>
                    `;
                });
            } else {
                historyRows = `<tr><td colspan="6" class="text-center text-muted py-3">No payments recorded yet for this production batch.</td></tr>`;
            }

            // Options for Payment Collected To Dropdown
            let accountOpts = '<option value="">-- Choose Account (from Settings) --</option>';
            if (accounts.length > 0) {
                accounts.forEach(acc => {
                    accountOpts += `<option value="${acc.id}">${acc.name} (${acc.type})</option>`;
                });
            } else {
                accountOpts += `
                    <option value="">Cash Account</option>
                    <option value="">Bank Transfer / Cheque</option>
                    <option value="">UPI / Digital Wallet</option>
                `;
            }

            const today = new Date().toISOString().split('T')[0];

            modalBody.innerHTML = `
                <!-- BATCH FINANCIAL SUMMARY HEADER -->
                <div class="p-3 mb-4 rounded-3 border bg-white shadow-sm">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-4">
                            <small class="text-muted d-block" style="font-size: 10px;">BATCH & BUYER CONTRACT</small>
                            <strong class="text-primary fs-6">${b.production_no}</strong>
                            <div class="text-dark small">${b.buyer_name || 'Direct Buyer'} (${b.po_no || 'N/A'})</div>
                        </div>
                        <div class="col-6 col-md-3 border-start ps-3">
                            <small class="text-muted d-block" style="font-size: 10px;">TOTAL ORDER REVENUE</small>
                            <strong class="text-dark fs-6 font-monospace">₹${totalSales.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                        </div>
                        <div class="col-6 col-md-3 border-start ps-3">
                            <small class="text-muted d-block" style="font-size: 10px;">CUMULATIVE RECEIVED</small>
                            <strong class="text-success fs-6 font-monospace">₹${totalReceived.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                        </div>
                        <div class="col-12 col-md-2 text-md-end border-start ps-3">
                            <small class="text-muted d-block mb-1" style="font-size: 10px;">PAYMENT STATUS</small>
                            ${statusBadge}
                        </div>
                    </div>
                </div>

                <!-- PAYMENT HISTORY & RECEIPTS TABLE -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-list-check text-primary me-1.5"></i> Payment History Receipts (${payments.length})</h6>
                    <small class="text-danger fw-bold">Balance Due: ₹${balanceDue.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</small>
                </div>
                <div class="table-responsive border rounded-3 mb-4">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                        <thead class="bg-light text-uppercase" style="font-size: 10px;">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Paid Date</th>
                                <th>Amount Received</th>
                                <th>Collected To</th>
                                <th>Reference / Remarks</th>
                                <th class="pe-3 text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>${historyRows}</tbody>
                    </table>
                </div>

                <!-- RECORD NEW PAYMENT FORM -->
                ${balanceDue > 0.01 ? `
                    <div class="card p-3 border shadow-sm rounded-3 bg-light">
                        <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-plus-circle text-success me-1.5"></i> Record New Payment Entry</h6>
                        <form id="record-payment-form" onsubmit="submitPaymentForm(event, ${b.batch_id}, ${totalSales}, ${totalReceived})">
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-bold text-dark mb-1">Received Amount (₹) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" id="pay_amount" class="form-control form-control-sm font-monospace fw-bold" max="${balanceDue.toFixed(2)}" placeholder="Max ₹${balanceDue.toFixed(2)}" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-bold text-dark mb-1">Payment Collected To <span class="text-danger">*</span></label>
                                    <select id="pay_account_id" class="form-select form-select-sm" required>
                                        ${accountOpts}
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-bold text-dark mb-1">Paid Date <span class="text-danger">*</span></label>
                                    <input type="date" id="pay_date" class="form-control form-control-sm" value="${today}" required>
                                </div>
                                <div class="col-12 col-md-9 mt-2">
                                    <input type="text" id="pay_reference" class="form-control form-control-sm" placeholder="Optional Payment Reference / UTR No / Remarks">
                                </div>
                                <div class="col-12 col-md-3 mt-2">
                                    <button type="submit" id="btn-save-payment" class="btn btn-success btn-sm w-100 fw-bold rounded-3">
                                        <i class="fa-solid fa-save me-1"></i> Save Payment
                                    </button>
                                </div>
                            </div>
                            <div id="payment-form-feedback" class="mt-2" style="font-size: 11.5px;"></div>
                        </form>
                    </div>
                ` : `
                    <div class="alert alert-success border-0 rounded-3 mb-0 d-flex align-items-center">
                        <i class="fa-solid fa-circle-check fa-2x me-3 text-success"></i>
                        <div>
                            <strong class="d-block">Order Payment Fully Received!</strong>
                            <span class="small">Cumulative received amount equals total order revenue. No further balance due for this production batch.</span>
                        </div>
                    </div>
                `}
            `;
        })
        .catch(err => {
            modalBody.innerHTML = `<div class="alert alert-danger">Failed to load payment receipts. Please try again.</div>`;
        });
}

// SUBMIT PAYMENT FORM AJAX
function submitPaymentForm(event, batchId, totalSales, currentReceived) {
    event.preventDefault();

    const amountInput = document.getElementById('pay_amount');
    const accountSelect = document.getElementById('pay_account_id');
    const dateInput = document.getElementById('pay_date');
    const refInput = document.getElementById('pay_reference');
    const feedbackEl = document.getElementById('payment-form-feedback');
    const saveBtn = document.getElementById('btn-save-payment');

    const amount = parseFloat(amountInput.value) || 0;
    const accountId = accountSelect.value;
    const paidDate = dateInput.value;
    const reference = refInput.value.trim();

    const balanceDue = max(0, totalSales - currentReceived);

    if (amount <= 0) {
        feedbackEl.innerHTML = `<div class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Please enter a valid payment amount > 0.</div>`;
        return;
    }

    if (amount > (totalSales - currentReceived + 0.01)) {
        feedbackEl.innerHTML = `<div class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Amount cannot exceed balance due (₹${balanceDue.toFixed(2)}).</div>`;
        return;
    }

    saveBtn.disabled = true;
    feedbackEl.innerHTML = `<div class="text-primary font-monospace"><i class="fa-solid fa-spinner fa-spin me-1"></i> Saving payment entry...</div>`;

    fetch('<?= base_url('company/sales-reports/api/record-payment') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            batch_id: batchId,
            amount: amount,
            payment_account_id: accountId,
            paid_date: paidDate,
            reference_no: reference
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            feedbackEl.innerHTML = `<div class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> ${data.error}</div>`;
            saveBtn.disabled = false;
        } else if (data.success) {
            feedbackEl.innerHTML = `<div class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> ${data.message} Reloading...</div>`;
            setTimeout(() => {
                openPaymentModal(batchId);
            }, 600);
        }
    })
    .catch(err => {
        feedbackEl.innerHTML = `<div class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Failed to save payment. Please try again.</div>`;
        saveBtn.disabled = false;
    });
}
</script>
