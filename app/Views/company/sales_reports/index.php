<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold font-outfit m-0 text-dark">Executive Sales & Financial Reports</h3>
        <p class="text-secondary small m-0">Real-time consolidated profitability, manufacturing performance & warehouse sales analysis</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('company/sales-reports/export-batch-financials') ?>" class="btn btn-sm btn-outline-success fw-bold rounded-pill shadow-sm">
            <i class="fa-solid fa-file-excel me-1.5"></i> Export Batch Financials
        </a>
        <a href="<?= base_url('company/sales-reports/export-carton-analysis') ?>" class="btn btn-sm btn-success fw-bold rounded-pill shadow-sm">
            <i class="fa-solid fa-file-excel me-1.5"></i> Export Carton Analysis
        </a>
    </div>
</div>

<!-- MULTI-FILTER CONTROL HUB BAR -->
<div class="card p-3 mb-4 border-0 shadow-sm rounded-4 bg-white" style="border: 1px solid #e2e8f0;">
    <form method="GET" action="<?= base_url('company/sales-reports') ?>" class="row g-2 align-items-end font-monospace" style="font-size: 12px;">
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1">Start Date</label>
            <input type="date" name="start_date" class="form-control form-control-sm text-dark bg-light" value="<?= htmlspecialchars($filters['start_date']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1">End Date</label>
            <input type="date" name="end_date" class="form-control form-control-sm text-dark bg-light" value="<?= htmlspecialchars($filters['end_date']) ?>">
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1">Buyer / Client</label>
            <select name="buyer_id" class="form-select form-select-sm text-dark bg-light">
                <option value="">All Buyers</option>
                <?php foreach ($buyers as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $filters['buyer_id'] == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1">Warehouse</label>
            <select name="warehouse_id" class="form-select form-select-sm text-dark bg-light">
                <option value="">All Warehouses</option>
                <?php foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= $filters['warehouse_id'] == $wh['id'] ? 'selected' : '' ?>><?= htmlspecialchars($wh['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm text-dark bg-light">
                <option value="">All Statuses</option>
                <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="in_progress" <?= $filters['status'] === 'in_progress' ? 'selected' : '' ?>>Work in Progress (WIP)</option>
                <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Planned / Draft</option>
                <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold rounded-3">
                <i class="fa-solid fa-filter me-1"></i> Apply
            </button>
            <a href="<?= base_url('company/sales-reports') ?>" class="btn btn-outline-secondary btn-sm rounded-3">Reset</a>
        </div>
    </form>
</div>

<!-- EXECUTIVE CONSOLIDATED KPI CARDS GRID -->
<div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-2 mb-4 font-monospace">
    <div class="col">
        <div class="card p-2.5 border-0 shadow-sm bg-white rounded-3 border-start border-primary border-4 h-100">
            <small class="text-muted d-block" style="font-size: 9.5px;">TOTAL BUYER ORDERS</small>
            <strong class="text-primary h5 fw-bold mb-0"><?= number_format($kpis['total_buyer_orders']) ?></strong>
            <small class="text-secondary d-block" style="font-size: 9px;"><?= number_format($kpis['delivered_orders_count']) ?> Delivered</small>
        </div>
    </div>
    <div class="col">
        <div class="card p-2.5 border-0 shadow-sm bg-white rounded-3 border-start border-info border-4 h-100">
            <small class="text-muted d-block" style="font-size: 9.5px;">PRODUCTION BATCHES</small>
            <strong class="text-dark h5 fw-bold mb-0"><?= number_format($kpis['total_batches']) ?></strong>
            <small class="text-success d-block" style="font-size: 9px;"><?= $kpis['completed_batches'] ?> Done | <?= $kpis['wip_batches'] ?> WIP</small>
        </div>
    </div>
    <div class="col">
        <div class="card p-2.5 border-0 shadow-sm bg-white rounded-3 border-start border-warning border-4 h-100">
            <small class="text-muted d-block" style="font-size: 9.5px;">PROCUREMENT COST</small>
            <strong class="text-dark h5 fw-bold mb-0">₹<?= number_format($kpis['procurement_cost'], 0) ?></strong>
            <small class="text-muted d-block" style="font-size: 9px;">Raw Materials Used</small>
        </div>
    </div>
    <div class="col">
        <div class="card p-2.5 border-0 shadow-sm bg-white rounded-3 border-start border-danger border-4 h-100">
            <small class="text-muted d-block" style="font-size: 9.5px;">MANUFACTURING COST</small>
            <strong class="text-danger h5 fw-bold mb-0">₹<?= number_format($kpis['mfg_cost'], 0) ?></strong>
            <small class="text-muted d-block" style="font-size: 9px;">Processing & Labor</small>
        </div>
    </div>
    <div class="col">
        <div class="card p-2.5 border-0 shadow-sm bg-white rounded-3 border-start border-success border-4 h-100">
            <small class="text-muted d-block" style="font-size: 9.5px;">TOTAL SALES VALUE</small>
            <strong class="text-success h5 fw-bold mb-0">₹<?= number_format($kpis['total_sales_value'], 0) ?></strong>
            <small class="text-success d-block" style="font-size: 9px;">Gross Order Revenue</small>
        </div>
    </div>
    <div class="col">
        <div class="card p-2.5 border-0 shadow-sm bg-white rounded-3 border-start border-secondary border-4 h-100">
            <small class="text-muted d-block" style="font-size: 9.5px;">NET PROFIT & MARGIN</small>
            <strong class="text-success h5 fw-bold mb-0">₹<?= number_format($kpis['net_profit'], 0) ?></strong>
            <small class="badge bg-success-subtle text-success border" style="font-size: 9px;"><?= number_format($kpis['profit_margin_pct'], 1) ?>% Margin</small>
        </div>
    </div>
</div>

<!-- SECONDARY FINANCIAL & WAREHOUSE KPI STRIP -->
<div class="row row-cols-2 row-cols-md-4 g-2 mb-4 font-monospace">
    <div class="col">
        <div class="card p-2.5 border-0 shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted d-block" style="font-size: 9.5px;">PENDING / OUTSTANDING RECEIVABLES</small>
                    <strong class="text-warning h6 fw-bold mb-0">₹<?= number_format($kpis['outstanding_receivables'], 0) ?></strong>
                </div>
                <span class="badge bg-warning-subtle text-warning p-2 rounded-circle"><i class="fa-solid fa-clock-rotate-left"></i></span>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card p-2.5 border-0 shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted d-block" style="font-size: 9.5px;">FULLY RECEIVED PAYMENTS</small>
                    <strong class="text-success h6 fw-bold mb-0">₹<?= number_format($kpis['fully_received'], 0) ?></strong>
                </div>
                <span class="badge bg-success-subtle text-success p-2 rounded-circle"><i class="fa-solid fa-circle-check"></i></span>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card p-2.5 border-0 shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted d-block" style="font-size: 9.5px;">WAREHOUSE STOCK VALUE</small>
                    <strong class="text-primary h6 fw-bold mb-0">₹<?= number_format($kpis['warehouse_stock_value'], 0) ?></strong>
                </div>
                <span class="badge bg-primary-subtle text-primary p-2 rounded-circle"><i class="fa-solid fa-warehouse"></i></span>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card p-2.5 border-0 shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted d-block" style="font-size: 9.5px;">OVERALL MFG EFFICIENCY</small>
                    <strong class="text-dark h6 fw-bold mb-0"><?= number_format($kpis['overall_efficiency_pct'], 1) ?>%</strong>
                </div>
                <span class="badge bg-info-subtle text-info p-2 rounded-circle"><i class="fa-solid fa-gauge-high"></i></span>
            </div>
        </div>
    </div>
</div>

<!-- ================= SECTION 1: PRODUCTION BATCH FINANCIALS & PROFITABILITY ================= -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white" style="border: 1px solid #e2e8f0;">
    <div class="card-header bg-transparent border-bottom p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold font-outfit m-0 text-dark"><i class="fa-solid fa-industry text-primary me-2"></i> Section 1: Production Batch Financials & Profitability Ledger</h5>
            <small class="text-secondary">Comprehensive order cost sheets, manufacturing expenses, revenue, and net profit margins</small>
        </div>
        <div class="d-flex gap-2">
            <input type="text" id="batch-search-input" class="form-control form-control-sm font-monospace" placeholder="Search batch, buyer, style, PO..." style="width: 220px;">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
            <table class="table table-hover table-striped align-middle mb-0 font-monospace" id="batch-table" style="font-size: 11.5px;">
                <thead class="bg-dark text-white text-uppercase" style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th class="ps-3">Batch No</th>
                        <th>Buyer / Client</th>
                        <th>PO No</th>
                        <th>Garment Style</th>
                        <th>Target / Produced</th>
                        <th>Status</th>
                        <th>Procurement (₹)</th>
                        <th>Mfg Cost (₹)</th>
                        <th>Total Cost (₹)</th>
                        <th>Unit Price (₹)</th>
                        <th>Sales Value (₹)</th>
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
                                <td class="text-muted">₹<?= number_format($b['procurement_cost'], 0) ?></td>
                                <td class="text-muted">₹<?= number_format($b['mfg_cost'], 0) ?></td>
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
                        <tr><td colspan="15" class="text-center py-4 text-muted">No production batches found for selected filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= SECTION 2: CARTON & WAREHOUSE SALES ANALYSIS ================= -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4" style="border: 1px solid #e2e8f0;">
    <div class="card-header bg-transparent border-bottom p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold font-outfit m-0 text-dark"><i class="fa-solid fa-boxes-stacked text-warning me-2"></i> Section 2: Carton & Warehouse Sales Analysis</h5>
            <small class="text-secondary">Sealed carton box inventory, warehouse storage, estimated sales value & expected profitability</small>
        </div>
        <div class="d-flex gap-2">
            <input type="text" id="carton-search-input" class="form-control form-control-sm font-monospace" placeholder="Search carton, shipment, warehouse..." style="width: 220px;">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
            <table class="table table-hover table-striped align-middle mb-0 font-monospace" id="carton-table" style="font-size: 11.5px;">
                <thead class="bg-dark text-white text-uppercase" style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th class="ps-3">Carton ID</th>
                        <th>Shipment No</th>
                        <th>Garment Style</th>
                        <th>Qty (pcs)</th>
                        <th>Batch No</th>
                        <th>Warehouse / Location</th>
                        <th>Dispatch Status</th>
                        <th>Total Mfg Cost (₹)</th>
                        <th>Sales Value (₹)</th>
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
                                        <span class="badge bg-warning text-dark">In Warehouse Stock</span>
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
                        <tr><td colspan="12" class="text-center py-4 text-muted">No carton records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DRILL DOWN MODAL FOR CARTON CONTENTS -->
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
    // 1. Batch Table Search Filter
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

    // 2. Carton Table Search Filter
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

    // 3. Drill-down AJAX Modal for Carton Contents
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
