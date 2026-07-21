<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Platform Overview</h3>
        <p class="text-secondary m-0">Global statistics for Wearable ERP SaaS</p>
    </div>
    <span class="badge bg-secondary p-2.5 rounded-pill"><i class="fa-solid fa-code-commit me-1"></i> Version <?= htmlspecialchars($latest_version) ?></span>
</div>

<!-- Stats widgets -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon icon-primary">
                <i class="fa-solid fa-industry"></i>
            </div>
            <div class="stat-number"><?= $companies_count ?></div>
            <div class="stat-label">Onboarded Companies</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon icon-success">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-number"><?= $users_count ?></div>
            <div class="stat-label">Registered Tenant Users</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon icon-warning">
                <i class="fa-solid fa-credit-card"></i>
            </div>
            <div class="stat-number"><?= $plans_count ?></div>
            <div class="stat-label">Subscription Plans</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon icon-danger">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="stat-number">Active</div>
            <div class="stat-label">Security Shield</div>
        </div>
    </div>
</div>

<div class="row mb-5">
    <!-- Chart Widget -->
    <div class="col-md-8">
        <div class="pepp-card h-100">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-chart-line text-primary me-2"></i> Tenant Registration Trends (2026)</h5>
            </div>
            <div class="pepp-card-body">
                <canvas id="devGrowthChart" height="260"></canvas>
            </div>
        </div>
    </div>

    <!-- Onboarded list shortcut -->
    <div class="col-md-4">
        <div class="pepp-card h-100">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-list text-primary me-2"></i> Pilot Customer Shortcut</h5>
            </div>
            <div class="pepp-card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (!empty($companies)): ?>
                        <?php foreach ($companies as $c): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center p-3 border-0 border-bottom">
                                <div>
                                    <h6 class="m-0 fw-bold"><?= htmlspecialchars($c['name']) ?></h6>
                                    <small class="text-secondary"><?= htmlspecialchars($c['subdomain']) ?>.mywellgro.online</small>
                                </div>
                                <span class="badge badge-pepp badge-success"><?= htmlspecialchars($c['status']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-3 text-secondary">No companies onboarded.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('devGrowthChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            datasets: [{
                label: 'Onboarded Tenants',
                data: [1, 2, 4, 7, 10, 15, 23],
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.05)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
