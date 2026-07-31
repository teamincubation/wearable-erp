<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Garment ERP Dashboard</h3>
        <p class="text-secondary m-0">Live operations snapshot for <strong><?= htmlspecialchars($company['name']) ?></strong></p>
    </div>
    <div>
        <span class="badge bg-primary p-2.5 rounded-pill"><i class="fa-solid fa-key me-1"></i> License: Active</span>
    </div>
</div>

<!-- ENTERPRISE TRACE LIFECYCLE SEARCH SYSTEM -->
<div class="card p-3 mb-4 border-0 shadow-sm" style="border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0;">
    <form method="GET" action="<?= base_url('company/packing-qr/traceability') ?>" class="row g-2 align-items-center">
        <div class="col-12">
            <label class="form-label small fw-bold text-dark mb-1.5" style="font-size: 13px;">Enter Product QR Code OR Sealed Carton ID</label>
        </div>
        <div class="col-12 col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-primary text-white border-primary"><i class="fa-solid fa-qrcode"></i></span>
                <input type="text" name="query" class="form-control font-monospace fw-bold text-dark bg-light" placeholder="e.g. Scan or paste Product QR (XXL-0001) or Carton ID (CTN-2026-0001)..." required>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <button type="submit" class="btn btn-primary fw-bold w-100 py-2 shadow-sm rounded-3">
                <i class="fa-solid fa-magnifying-glass me-1.5"></i> Trace Lifecycle
            </button>
        </div>
    </form>
</div>

<!-- Stats widgets -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon icon-primary">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-number"><?= $users_count ?></div>
            <div class="stat-label">Employees (Users)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon icon-success">
                <i class="fa-solid fa-industry"></i>
            </div>
            <div class="stat-number"><?= $production_count ?></div>
            <div class="stat-label">Active Production Batches</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon icon-warning">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div class="stat-number"><?= $unique_stock_count ?></div>
            <div class="stat-label">Unique Stock Categories</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon icon-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div class="stat-number"><?= number_format($reject_rate, 1) ?>%</div>
            <div class="stat-label">Quality Rejection Rate (AQL)</div>
        </div>
    </div>
</div>

<!-- Active Manufacturing Lines & Live WIP Operations Hub (Request 8) -->
<?php if (!empty($active_batches)): ?>
    <div class="pepp-card border-0 shadow-sm mb-5" style="border-radius: 16px; background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
        <div class="pepp-card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold text-white m-0"><i class="fa-solid fa-industry text-warning me-2"></i> Active Production Lines Tracking Hub</h4>
                <p class="text-white-50 small m-0 mt-1">Direct access to Operations Stage Live Dashboards for all running garment batches.</p>
            </div>
            <a href="<?= base_url('company/production/orders') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3">View All Batches</a>
        </div>
        <div class="pepp-card-body p-4">
            <div class="row row-cols-1 row-cols-md-2 g-3">
                <?php foreach ($active_batches as $ab): ?>
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 14px; background: #ffffff;">
                            <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="badge bg-primary-subtle text-primary font-monospace fw-bold px-2.5 py-1 mb-1" style="font-size: 13px;"><?= htmlspecialchars($ab['production_no']) ?></span>
                                            <h6 class="fw-bold text-dark m-0"><?= htmlspecialchars($ab['style_no'] ?? '') ?> - <?= htmlspecialchars($ab['style_name'] ?? '') ?></h6>
                                        </div>
                                        <span class="badge bg-success text-white rounded-pill px-2.5 py-1" style="font-size: 11px;">
                                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Running
                                        </span>
                                    </div>
                                    <div class="text-secondary small border-top pt-2 mt-2" style="font-size: 12px;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Buyer PO Contract:</span>
                                            <strong class="text-dark font-monospace"><?= htmlspecialchars($ab['buyer_po_no'] ?? '') ?> (<?= htmlspecialchars($ab['buyer_name'] ?? '') ?>)</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Target Contract Qty:</span>
                                            <strong class="text-primary font-monospace"><?= number_format($ab['target_qty'] ?? 0) ?> pcs</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <a href="<?= base_url('company/production/stage/' . $ab['id'] . '/live-report') ?>" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm" style="letter-spacing: 0.3px;">
                                        Operations Stage Live Dashboard <i class="fa-solid fa-arrow-right-long ms-1.5"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Financial Value Banner -->
<div class="pepp-card bg-light-subtle mb-5 border border-primary">
    <div class="pepp-card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-wallet text-primary me-2"></i> Active Buyer Order Book value</h5>
            <p class="text-secondary small m-0">Sum of all approved production contract values booked this cycle</p>
        </div>
        <div class="text-end">
            <h3 class="fw-bold text-success font-monospace m-0">₹<?= number_format($contracts_value, 2) ?></h3>
        </div>
    </div>
</div>

<div class="row mb-5">
    <!-- Chart Widget -->
    <div class="col-md-12">
        <div class="pepp-card h-100">
            <div class="pepp-card-header d-flex justify-content-between align-items-center">
                <h5 class="pepp-card-title m-0"><i class="fa-solid fa-chart-simple text-primary me-2"></i> Garment Production Outputs (pcs)</h5>
                <select id="productionChartFilter" class="form-select form-select-sm bg-dark text-white border-secondary" style="width: auto;">
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>
            <div class="pepp-card-body">
                <canvas id="productionChart" height="260"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent activity table -->
<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-history text-primary me-2"></i> Recent ERP Activity</h5>
        <a href="<?= base_url('company/logs') ?>" class="btn btn-sm btn-light border">View All</a>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User Name</th>
                        <th>Action</th>
                        <th>IP Address</th>
                        <th>Activity Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_logs)): ?>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?= date('d-M-Y H:i', strtotime($log['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($log['user_name'] ?? 'User') ?></strong></td>
                                <td>
                                    <span class="badge badge-pepp bg-light text-primary"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td><code><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></code></td>
                                <td><?= htmlspecialchars($log['description'] ?? 'System audit recorded.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">No recent activity recorded.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('productionChart').getContext('2d');
    let productionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    const filterSelect = document.getElementById('productionChartFilter');
    
    function fetchChartData(filter) {
        fetch(`<?= base_url('company/api/dashboard-chart') ?>?filter=${filter}`)
            .then(res => res.json())
            .then(res => {
                if(res.success && res.data) {
                    productionChart.data.labels = res.data.labels;
                    productionChart.data.datasets = res.data.datasets;
                    productionChart.update();
                }
            })
            .catch(err => console.error("Failed to fetch chart data", err));
    }

    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            fetchChartData(this.value);
        });
    }

    // Initial fetch
    fetchChartData('weekly');

    // Track QR Unit Form Event on Main Dashboard
    const trackForm = document.getElementById('track-qr-unit-form');
    const trackInput = document.getElementById('track-qr-unit-input');
    const trackModalEl = document.getElementById('trackQrUnitModal');
    const trackModalBody = document.getElementById('track-qr-modal-body');

    if (trackForm && trackInput) {
        trackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const qrCode = trackInput.value.trim();
            if (!qrCode) return;

            const modal = new bootstrap.Modal(trackModalEl);
            trackModalBody.innerHTML = `
                <div class="text-center py-4">
                    <span class="spinner-border text-primary" role="status"></span>
                    <p class="mt-2 text-white-50 small font-monospace">Fetching complete lifecycle history for <strong>${qrCode}</strong>...</p>
                </div>
            `;
            modal.show();

            fetch(`<?= base_url('company/production/track-qr-unit') ?>?qr_code=${encodeURIComponent(qrCode)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.logs && data.logs.length > 0) {
                        let cartonCardHtml = '';
                        if (data.carton_info) {
                            const c = data.carton_info;
                            let statusBadge = `<span class="badge bg-primary text-white px-3 py-1.5 rounded-pill"><i class="fa-solid fa-boxes-packing me-1"></i> ${c.status_label}</span>`;
                            if (c.status === 'delivered') {
                                statusBadge = `<span class="badge bg-success text-white px-3 py-1.5 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> Delivered</span>`;
                            } else if (c.status === 'dispatched') {
                                statusBadge = `<span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill"><i class="fa-solid fa-truck-fast me-1"></i> Dispatched ${c.shipment_no ? '(' + c.shipment_no + ')' : ''}</span>`;
                            }

                            cartonCardHtml = `
                                <div class="p-3 rounded-3 mb-3 font-monospace" style="background: #1e293b; border: 1px solid #334155; color: #f8fafc;">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-2 border-bottom" style="border-color: rgba(255,255,255,0.1) !important;">
                                        <div>
                                            <span class="badge bg-primary text-white font-monospace fs-6 px-2.5 py-1 me-2" style="background: #2563eb !important;">
                                                <i class="fa-solid fa-box-archive me-1"></i> ${c.carton_no}
                                            </span>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle font-monospace" style="font-size: 11px;">
                                                <i class="fa-solid fa-location-dot me-1"></i> Dest: ${c.destination}
                                            </span>
                                        </div>
                                        <div>${statusBadge}</div>
                                    </div>
                                    <div class="row g-2" style="font-size: 11.5px; color: #cbd5e1;">
                                        <div class="col-12 col-md-6">
                                            <i class="fa-solid fa-calendar-check text-primary me-1"></i> <strong>Carton Packed:</strong> ${c.packed_at_formatted || 'N/A'}
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <i class="fa-solid fa-truck text-warning me-1"></i> <strong>Shipment:</strong> ${c.shipment_no || 'Pending Shipment'}
                                        </div>
                                        ${c.courier_details ? `
                                            <div class="col-12 col-md-6">
                                                <i class="fa-solid fa-route text-info me-1"></i> <strong>Courier / Vehicle:</strong> ${c.courier_details}
                                            </div>
                                        ` : ''}
                                        ${c.tracking_no ? `
                                            <div class="col-12 col-md-6">
                                                <i class="fa-solid fa-barcode text-success me-1"></i> <strong>Tracking ID:</strong> ${c.tracking_no}
                                            </div>
                                        ` : ''}
                                        ${c.dispatched_at_formatted ? `
                                            <div class="col-12 col-md-6">
                                                <i class="fa-solid fa-clock text-muted me-1"></i> <strong>Dispatch Date:</strong> ${c.dispatched_at_formatted}
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        } else {
                            cartonCardHtml = `
                                <div class="p-2.5 rounded-3 mb-3 font-monospace small" style="background: rgba(51, 65, 85, 0.4); border: 1px solid #334155; color: #94a3b8;">
                                    <i class="fa-solid fa-box-open text-warning me-1.5"></i> <strong>Carton Assignment:</strong> Not yet linked to any sealed carton box.
                                </div>
                            `;
                        }

                        let html = `
                            <div class="p-3 rounded-3 mb-3" style="background: #0f172a; border: 1px solid #1e293b;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary text-white font-monospace fw-bold me-2 px-2.5 py-1" style="font-size: 11px; background: #2563eb !important; color: #ffffff !important;">QR / CODE</span>
                                        <strong class="font-monospace fs-5 text-white fw-bold" style="color: #ffffff !important; letter-spacing: 0.05em;">${data.qr_code}</strong>
                                    </div>
                                    <span class="badge bg-success text-white px-3 py-1.5 rounded-pill fw-bold" style="font-size: 12px; background: #10b981 !important; color: #ffffff !important;">
                                        <i class="fa-solid fa-check-double me-1"></i> ${data.total_stages} Stages Tracked
                                    </span>
                                </div>
                            </div>
                            ${cartonCardHtml}
                            <div class="table-responsive border-0" style="background: #0f172a; border-radius: 12px;">
                                <table class="table table-hover align-middle mb-0" style="font-size: 12.5px; background-color: #0f172a !important; color: #f8fafc !important; --bs-table-bg: #0f172a; --bs-table-color: #f8fafc;">
                                    <thead>
                                        <tr style="background-color: #1e293b !important; color: #94a3b8 !important;">
                                            <th style="background-color: #1e293b !important; color: #94a3b8 !important; padding: 12px 14px;">WIP STAGE</th>
                                            <th style="background-color: #1e293b !important; color: #94a3b8 !important; padding: 12px 14px;">STATUS</th>
                                            <th style="background-color: #1e293b !important; color: #94a3b8 !important; padding: 12px 14px;">UPDATED BY (OPERATOR)</th>
                                            <th style="background-color: #1e293b !important; color: #94a3b8 !important; padding: 12px 14px;">LOGGED DATE & TIME</th>
                                            <th style="background-color: #1e293b !important; color: #94a3b8 !important; padding: 12px 14px;">DURATION</th>
                                        </tr>
                                    </thead>
                                    <tbody style="background-color: #0f172a !important;">
                        `;

                        data.logs.forEach(l => {
                            const badge = l.status === 'PASS' ? 'bg-success' : 'bg-danger';
                            let editNotice = '';
                            if (l.edited_by_name && l.edited_at_formatted) {
                                editNotice = `
                                    <div class="mt-1.5 p-1.5 rounded font-monospace" style="background: rgba(234, 179, 8, 0.15); border: 1px solid rgba(234, 179, 8, 0.35); color: #facc15 !important; font-size: 11px; line-height: 1.3;">
                                        <i class="fa-solid fa-pen-to-square me-1 text-warning"></i> <strong>Edited</strong> by <span class="text-white">${l.edited_by_name}</span> on ${l.edited_at_formatted}${l.edit_remarks ? ' - "' + l.edit_remarks + '"' : ''}
                                    </div>
                                `;
                            }
                            html += `
                                <tr>
                                    <td style="background-color: #0f172a !important; color: #38bdf8 !important; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                        <strong class="font-monospace text-uppercase" style="color: #38bdf8 !important; font-weight: 700;">${l.stage}</strong>
                                        ${editNotice}
                                    </td>
                                    <td style="background-color: #0f172a !important; border-bottom: 1px solid rgba(255,255,255,0.06);"><span class="badge ${badge} text-white font-monospace px-2.5 py-1" style="color: #ffffff !important; font-weight: 700;">${l.status}</span></td>
                                    <td style="background-color: #0f172a !important; color: #ffffff !important; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                        <div class="fw-bold text-white" style="color: #ffffff !important;">${l.operator_name}</div>
                                        <small style="color: #94a3b8 !important; font-size: 11px;">${l.operator_role}</small>
                                    </td>
                                    <td style="background-color: #0f172a !important; color: #ffffff !important; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                        <div class="fw-bold font-monospace text-white" style="color: #ffffff !important;">${l.updated_at}</div>
                                        <small style="color: #94a3b8 !important; font-size: 11px;">${l.time_ago}</small>
                                    </td>
                                    <td style="background-color: #0f172a !important; border-bottom: 1px solid rgba(255,255,255,0.06);"><span class="badge text-white font-monospace" style="color: #ffffff !important; background: #334155 !important;">${l.duration}</span></td>
                                </tr>
                            `;
                        });

                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                        trackModalBody.innerHTML = html;
                    } else {
                        trackModalBody.innerHTML = `
                            <div class="alert alert-warning text-center py-4 my-2" style="background: rgba(245, 158, 11, 0.15); border-color: rgba(245, 158, 11, 0.3); color: #f59e0b;">
                                <i class="fa-solid fa-circle-exclamation fs-2 mb-2 text-warning"></i>
                                <h6 class="fw-bold text-white">No Stage History Logs Found</h6>
                                <p class="small text-dash-sub mb-0">No operational logs recorded yet for item tag <strong>${qrCode}</strong>.</p>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    console.error(err);
                    trackModalBody.innerHTML = `
                        <div class="alert alert-danger text-center py-3 my-2">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Failed to communicate with production tracking server.
                        </div>
                    `;
                });
        });
    }
});
</script>
