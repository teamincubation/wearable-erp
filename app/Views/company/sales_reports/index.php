<!-- EXECUTIVE SALES & FINANCIAL REPORTS HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold font-outfit m-0 text-dark d-flex align-items-center">
            <i class="fa-solid fa-chart-pie text-primary me-2.5"></i> Executive Sales & Financial Reports
        </h3>
        <p class="text-secondary small m-0 mt-0.5">Real-time executive dashboard for order profitability, manufacturing cost, and warehouse sales</p>
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

<!-- SLEEK MULTI-FILTER CONTROL BAR -->
<div class="card p-3 mb-4 border-0 shadow-sm rounded-4 bg-white" style="border: 1px solid #e2e8f0;">
    <form method="GET" action="<?= base_url('company/sales-reports') ?>" class="row g-2 align-items-end font-monospace" style="font-size: 12px;">
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1"><i class="fa-regular fa-calendar me-1"></i> Start Date</label>
            <input type="date" name="start_date" class="form-control form-control-sm text-dark bg-light rounded-3" value="<?= htmlspecialchars($filters['start_date']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1"><i class="fa-regular fa-calendar me-1"></i> End Date</label>
            <input type="date" name="end_date" class="form-control form-control-sm text-dark bg-light rounded-3" value="<?= htmlspecialchars($filters['end_date']) ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-user-tie me-1"></i> Buyer / Client</label>
            <select name="buyer_id" class="form-select form-select-sm text-dark bg-light rounded-3">
                <option value="">All Buyers & Clients</option>
                <?php foreach ($buyers as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $filters['buyer_id'] == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-warehouse me-1"></i> Warehouse</label>
            <select name="warehouse_id" class="form-select form-select-sm text-dark bg-light rounded-3">
                <option value="">All Storage Locations</option>
                <?php foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= $filters['warehouse_id'] == $wh['id'] ? 'selected' : '' ?>><?= htmlspecialchars($wh['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-1">
            <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-spinner me-1"></i> Status</label>
            <select name="status" class="form-select form-select-sm text-dark bg-light rounded-3">
                <option value="">All</option>
                <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="in_progress" <?= $filters['status'] === 'in_progress' ? 'selected' : '' ?>>WIP Running</option>
                <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Planned</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-1.5">
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold rounded-3 shadow-sm">
                <i class="fa-solid fa-filter me-1"></i> Filter
            </button>
            <a href="<?= base_url('company/sales-reports') ?>" class="btn btn-outline-secondary btn-sm rounded-3 px-3">Reset</a>
        </div>
    </form>
</div>

<!-- EXECUTIVE CONSOLIDATED 4-PILLAR KPI SUMMARY CARDS -->
<div class="row g-3 mb-4 font-monospace">
    <!-- Pillar 1: Net Revenue & Profitability -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-3 border-0 shadow-sm bg-white rounded-4 h-100 border-start border-success border-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">NET PROFIT & REVENUE</span>
                <div class="bg-success-subtle text-success d-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <h4 class="fw-bold text-success m-0 mb-1">₹<?= number_format($kpis['net_profit'], 0) ?></h4>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2" style="font-size: 11px;">
                <span class="text-secondary">Gross Sales: <strong>₹<?= number_format($kpis['total_sales_value'], 0) ?></strong></span>
                <span class="badge bg-success-subtle text-success border"><?= number_format($kpis['profit_margin_pct'], 1) ?>% Margin</span>
            </div>
        </div>
    </div>

    <!-- Pillar 2: Manufacturing & Production Performance -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-3 border-0 shadow-sm bg-white rounded-4 h-100 border-start border-primary border-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">MANUFACTURING OUTPUT</span>
                <div class="bg-primary-subtle text-primary d-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-industry"></i>
                </div>
            </div>
            <h4 class="fw-bold text-dark m-0 mb-1"><?= number_format($kpis['total_batches']) ?> <span class="fs-6 text-muted font-monospace fw-normal">Batches</span></h4>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2" style="font-size: 11px;">
                <span class="text-success fw-semibold"><i class="fa-solid fa-circle-check me-1"></i><?= $kpis['completed_batches'] ?> Done</span>
                <span class="text-primary fw-semibold"><i class="fa-solid fa-spinner fa-spin me-1"></i><?= $kpis['wip_batches'] ?> WIP</span>
                <span class="badge bg-info-subtle text-info border"><?= number_format($kpis['overall_efficiency_pct'], 0) ?>% Efficiency</span>
            </div>
        </div>
    </div>

    <!-- Pillar 3: Cash Flow & Financial Receivables -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-3 border-0 shadow-sm bg-white rounded-4 h-100 border-start border-warning border-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">OUTSTANDING RECEIVABLES</span>
                <div class="bg-warning-subtle text-warning d-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <h4 class="fw-bold text-warning m-0 mb-1">₹<?= number_format($kpis['outstanding_receivables'], 0) ?></h4>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2" style="font-size: 11px;">
                <span class="text-success">Received: <strong>₹<?= number_format($kpis['fully_received'], 0) ?></strong></span>
                <span class="text-danger">Pending: <strong>₹<?= number_format($kpis['pending_payments'], 0) ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Pillar 4: Warehouse Stock Valuation -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-3 border-0 shadow-sm bg-white rounded-4 h-100 border-start border-info border-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">FINISHED GOODS IN STOCK</span>
                <div class="bg-info-subtle text-info d-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-warehouse"></i>
                </div>
            </div>
            <h4 class="fw-bold text-dark m-0 mb-1">₹<?= number_format($kpis['warehouse_stock_value'], 0) ?></h4>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2" style="font-size: 11px;">
                <span class="text-secondary">Ready Dispatch: <strong>₹<?= number_format($kpis['ready_dispatch_value'], 0) ?></strong></span>
                <span class="badge bg-light text-dark border"><?= $kpis['delivered_orders_count'] ?> Delivered</span>
            </div>
        </div>
    </div>
</div>

<!-- TABBED EXECUTIVE SECTION DATA HUB -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden" style="border: 1px solid #e2e8f0;">
    <div class="card-header bg-light border-bottom p-2">
        <ul class="nav nav-pills card-header-pills font-monospace" id="salesReportsTab" role="tablist" style="font-size: 12.5px;">
            <li class="nav-item" role="presentation">
                <button class="nav-item-btn nav-link active fw-bold py-2 px-3.5 rounded-3" id="batch-tab" data-bs-toggle="tab" data-bs-target="#batch-pane" type="button" role="tab">
                    <i class="fa-solid fa-file-invoice-dollar me-2"></i> Section 1: Production Batch Financials
                    <span class="badge bg-primary text-white rounded-pill ms-2"><?= count($batchReportList) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-item-btn nav-link fw-bold py-2 px-3.5 rounded-3 text-secondary" id="carton-tab" data-bs-toggle="tab" data-bs-target="#carton-pane" type="button" role="tab">
                    <i class="fa-solid fa-boxes-stacked me-2"></i> Section 2: Carton & Warehouse Sales Analysis
                    <span class="badge bg-secondary text-white rounded-pill ms-2"><?= count($cartonAnalysisList) ?></span>
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="salesReportsTabContent">
            
            <!-- TAB 1: PRODUCTION BATCH FINANCIALS -->
            <div class="tab-pane fade show active p-3" id="batch-pane" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 font-monospace">
                    <div>
                        <h6 class="fw-bold text-dark m-0">Production Batch Profitability Ledger</h6>
                        <small class="text-muted">Breakdown of target quantity, total cost, unit pricing, gross revenue, net profit, and payment status</small>
                    </div>
                    <input type="text" id="batch-search-input" class="form-control form-control-sm font-monospace" placeholder="Quick search batch, buyer, style..." style="width: 240px;">
                </div>

                <div class="table-responsive border rounded-3" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-hover table-striped align-middle mb-0 font-monospace" id="batch-table" style="font-size: 11.5px;">
                        <thead class="bg-dark text-white text-uppercase" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th class="ps-3 py-2.5">Batch No</th>
                                <th>Buyer / Client</th>
                                <th>Customer PO</th>
                                <th>Garment Style</th>
                                <th>Qty (Produced / Target)</th>
                                <th>Status</th>
                                <th>Total Cost (₹)</th>
                                <th>Unit Price (₹)</th>
                                <th>Total Revenue (₹)</th>
                                <th>Net Profit (₹)</th>
                                <th>Margin %</th>
                                <th>Payment</th>
                                <th class="pe-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($batchReportList)): ?>
                                <?php foreach ($batchReportList as $b): ?>
                                    <tr>
                                        <td class="ps-3"><strong class="text-primary font-monospace"><?= htmlspecialchars($b['batch_no']) ?></strong></td>
                                        <td><span class="fw-semibold text-dark"><?= htmlspecialchars($b['buyer_name']) ?></span></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($b['po_no']) ?></span></td>
                                        <td><small class="text-secondary"><?= htmlspecialchars($b['style_display']) ?></small></td>
                                        <td>
                                            <span class="fw-bold text-dark"><?= number_format($b['completed_qty']) ?></span> / <?= number_format($b['target_qty']) ?> pcs
                                        </td>
                                        <td>
                                            <?php if ($b['production_status'] === 'completed'): ?>
                                                <span class="badge bg-success-subtle text-success border">Completed</span>
                                            <?php elseif ($b['production_status'] === 'in_progress'): ?>
                                                <span class="badge bg-primary-subtle text-primary border">WIP Running</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border">Planned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong class="text-danger">₹<?= number_format($b['total_cost'], 0) ?></strong></td>
                                        <td class="text-success">₹<?= number_format($b['selling_price'], 2) ?></td>
                                        <td><strong class="text-success">₹<?= number_format($b['total_sales_value'], 0) ?></strong></td>
                                        <td><strong class="<?= $b['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">₹<?= number_format($b['net_profit'], 0) ?></strong></td>
                                        <td>
                                            <span class="badge <?= $b['margin_pct'] >= 15 ? 'bg-success' : ($b['margin_pct'] >= 0 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                                <?= number_format($b['margin_pct'], 1) ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= $b['payment_status'] ?></span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <a href="<?= base_url('company/production/stage/' . $b['batch_id'] . '/live-report') ?>" class="btn btn-xs btn-outline-primary rounded-circle" title="View Stage Live Report">
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="13" class="text-center py-4 text-muted">No production batch financial records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: CARTON & WAREHOUSE SALES ANALYSIS -->
            <div class="tab-pane fade p-3" id="carton-pane" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 font-monospace">
                    <div>
                        <h6 class="fw-bold text-dark m-0">Carton & Warehouse Stock Valuation</h6>
                        <small class="text-muted">Sealed carton box analysis, warehouse locations, estimated selling value, and expected profit margin</small>
                    </div>
                    <input type="text" id="carton-search-input" class="form-control form-control-sm font-monospace" placeholder="Quick search carton, location..." style="width: 240px;">
                </div>

                <div class="table-responsive border rounded-3" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-hover table-striped align-middle mb-0 font-monospace" id="carton-table" style="font-size: 11.5px;">
                        <thead class="bg-dark text-white text-uppercase" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th class="ps-3 py-2.5">Carton ID</th>
                                <th>Shipment No</th>
                                <th>Garment Style</th>
                                <th>Qty (pcs)</th>
                                <th>Batch No</th>
                                <th>Warehouse Location</th>
                                <th>Dispatch Status</th>
                                <th>Total Mfg Cost (₹)</th>
                                <th>Estimated Value (₹)</th>
                                <th>Expected Profit (₹)</th>
                                <th>Expected Margin</th>
                                <th class="pe-3 text-end">Drill Down</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cartonAnalysisList)): ?>
                                <?php foreach ($cartonAnalysisList as $c): ?>
                                    <tr>
                                        <td class="ps-3"><strong class="text-primary font-monospace"><?= htmlspecialchars($c['carton_no']) ?></strong></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['shipment_no']) ?></span></td>
                                        <td><small class="text-secondary"><?= htmlspecialchars($c['style_display']) ?></small></td>
                                        <td><span class="badge bg-success-subtle text-success border"><?= number_format($c['total_qty']) ?> pcs</span></td>
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
                                            <button type="button" class="btn btn-xs btn-outline-primary rounded-pill btn-carton-drilldown" data-carton-id="<?= $c['carton_id'] ?>">
                                                <i class="fa-solid fa-magnifying-glass me-1"></i> Contents
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="12" class="text-center py-4 text-muted">No carton analysis records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ITEMDRILL DOWN MODAL FOR CARTON CONTENTS -->
<div class="modal fade" id="cartonDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h6 class="modal-title font-outfit fw-bold d-flex align-items-center" id="carton-modal-title">
                    <i class="fa-solid fa-box-archive text-warning me-2"></i> Carton Contents & Batch Details
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 font-monospace" id="carton-modal-body" style="font-size: 12px;">
                <div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i></div>
            </div>
            <div class="modal-footer bg-light py-2 px-4">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab active text styling toggle
    const batchTabBtn = document.getElementById('batch-tab');
    const cartonTabBtn = document.getElementById('carton-tab');

    batchTabBtn.addEventListener('shown.bs.tab', function() {
        batchTabBtn.classList.remove('text-secondary');
        cartonTabBtn.classList.add('text-secondary');
    });

    cartonTabBtn.addEventListener('shown.bs.tab', function() {
        cartonTabBtn.classList.remove('text-secondary');
        batchTabBtn.classList.add('text-secondary');
    });

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
    const cartonModal = new bootstrap.Modal(document.getElementById('cartonDetailModal'));
    const cartonModalTitle = document.getElementById('carton-modal-title');
    const cartonModalBody = document.getElementById('carton-modal-body');

    document.querySelectorAll('.btn-carton-drilldown').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartonId = this.getAttribute('data-carton-id');
            cartonModalTitle.innerHTML = '<i class="fa-solid fa-box-archive text-warning me-2"></i> Loading Carton Details...';
            cartonModalBody.innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i></div>';
            cartonModal.show();

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
</script>
