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
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="stat-number"><?= $roles_count ?></div>
            <div class="stat-label">Active User Roles</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon icon-warning">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <div class="stat-number">12,500m</div>
            <div class="stat-label">Fabric Inventory (WIP)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon icon-danger">
                <i class="fa-solid fa-truck-ramp-box"></i>
            </div>
            <div class="stat-number">3,200 pcs</div>
            <div class="stat-label">T-Shirts Ready to Ship</div>
        </div>
    </div>
</div>

<div class="row mb-5">
    <!-- Chart Widget -->
    <div class="col-md-8">
        <div class="pepp-card h-100">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-chart-simple text-primary me-2"></i> Weekly Garment Production Outputs (pcs)</h5>
            </div>
            <div class="pepp-card-body">
                <canvas id="productionChart" height="260"></canvas>
            </div>
        </div>
    </div>

    <!-- Active features status -->
    <div class="col-md-4">
        <div class="pepp-card h-100">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-toggle-on text-primary me-2"></i> Feature License Modules</h5>
            </div>
            <div class="pepp-card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (!empty($features)): ?>
                        <?php foreach ($features as $f): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center p-3 border-0 border-bottom">
                                <span class="fw-semibold text-dark text-capitalize"><i class="fa-solid fa-circle-check text-success me-2"></i> <?= htmlspecialchars($f['feature_key']) ?></span>
                                <span class="badge badge-pepp 
                                    <?php 
                                        if ($f['status'] === 'enabled') echo 'badge-success';
                                        elseif ($f['status'] === 'trial') echo 'badge-warning';
                                        elseif ($f['status'] === 'beta') echo 'badge-info';
                                        else echo 'badge-danger';
                                    ?>">
                                    <?= htmlspecialchars(ucfirst($f['status'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-3 text-secondary text-center">No features allocated to this subscription.</div>
                    <?php endif; ?>
                </div>
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
