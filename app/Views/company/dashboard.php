<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Garment ERP Dashboard</h3>
        <p class="text-secondary m-0">Live operations snapshot for <strong><?= htmlspecialchars($company['name']) ?></strong></p>
    </div>
    <div>
        <span class="badge bg-primary p-2.5 rounded-pill"><i class="fa-solid fa-key me-1"></i> License: Active</span>
    </div>
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
                <span class="badge bg-success bg-opacity-20 text-success border border-success px-3 py-1.5 rounded-pill font-monospace fw-bold mb-2">
                    <span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true"></span> LIVE WIP OPERATIONS ACTIVE
                </span>
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
                                            <h6 class="fw-bold text-dark m-0"><?= htmlspecialchars($ab['style_no']) ?> - <?= htmlspecialchars($ab['style_name']) ?></h6>
                                        </div>
                                        <span class="badge bg-success text-white rounded-pill px-2.5 py-1" style="font-size: 11px;">
                                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Running
                                        </span>
                                    </div>
                                    <div class="text-secondary small border-top pt-2 mt-2" style="font-size: 12px;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Buyer PO Contract:</span>
                                            <strong class="text-dark font-monospace"><?= htmlspecialchars($ab['buyer_po_no']) ?> (<?= htmlspecialchars($ab['buyer_name']) ?>)</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Target Contract Qty:</span>
                                            <strong class="text-primary font-monospace"><?= number_format($ab['target_qty']) ?> pcs</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <a href="<?= base_url('company/production/stage/' . $ab['id']) ?>" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm" style="letter-spacing: 0.3px;">
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
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-chart-simple text-primary me-2"></i> Weekly Garment Production Outputs (pcs)</h5>
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
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            datasets: [
                {
                    label: 'Knitting',
                    data: [1500, 1800, 1600, 1900, 1750, 1200],
                    backgroundColor: '#4f46e5'
                },
                {
                    label: 'Sewing',
                    data: [1300, 1500, 1400, 1650, 1500, 1100],
                    backgroundColor: '#10b981'
                },
                {
                    label: 'Packing',
                    data: [1200, 1400, 1350, 1500, 1450, 1000],
                    backgroundColor: '#f59e0b'
                }
            ]
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
});
</script>
