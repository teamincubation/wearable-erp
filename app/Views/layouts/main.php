<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Wearable ERP' ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>

    <div class="app-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <a href="<?= base_url('company/dashboard') ?>" class="sidebar-brand">
                <i class="fa-solid fa-shirt"></i> Wearable ERP
            </a>
            
            <ul class="sidebar-menu">
                <?php 
                    $currentUri = $_SERVER['REQUEST_URI'];
                    $isActive = fn($uri) => (strpos($currentUri, $uri) !== false) ? 'active' : '';
                ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/dashboard') ?>" class="sidebar-link <?= ($currentUri === '/company/dashboard') ? 'active' : '' ?>">
                        <i class="fa-solid fa-chart-line"></i> Dashboard
                    </a>
                </li>
                
                <?php if (\App\Core\Auth::hasPermission('company.users.view')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/users') ?>" class="sidebar-link <?= $isActive('company/users') ?>">
                        <i class="fa-solid fa-users-gear"></i> Employees
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::hasPermission('company.roles.view')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/roles') ?>" class="sidebar-link <?= $isActive('company/roles') ?>">
                        <i class="fa-solid fa-shield-halved"></i> Roles & Privileges
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::hasPermission('company.styles.view')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/styles') ?>" class="sidebar-link <?= $isActive('company/styles') ?>">
                        <i class="fa-solid fa-shirt"></i> Style Master
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::hasPermission('company.styles.view')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/merchandising/costsheets') ?>" class="sidebar-link <?= $isActive('company/merchandising') ?>">
                        <i class="fa-solid fa-calculator"></i> Merchandising
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::hasPermission('company.styles.view')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/purchase/orders') ?>" class="sidebar-link <?= $isActive('company/purchase') ?>">
                        <i class="fa-solid fa-cart-shopping"></i> Procurement
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::hasPermission('company.inventory.view')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/inventory/ledger') ?>" class="sidebar-link <?= $isActive('company/inventory') ?>">
                        <i class="fa-solid fa-boxes-stacked"></i> Inventory Ledger
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::hasPermission('company.production.view')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/production/orders') ?>" class="sidebar-link <?= $isActive('company/production') ?>">
                        <i class="fa-solid fa-industry"></i> Production & Quality
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::hasPermission('company.users.view')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/hr/attendance') ?>" class="sidebar-link <?= $isActive('company/hr') ?>">
                        <i class="fa-solid fa-user-clock"></i> HR & Attendance
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::hasPermission('company.tally.export')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/tally/vouchers') ?>" class="sidebar-link <?= $isActive('company/tally') ?>">
                        <i class="fa-solid fa-file-excel"></i> Tally Integration
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::hasPermission('company.logs')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/logs') ?>" class="sidebar-link <?= $isActive('company/logs') ?>">
                        <i class="fa-solid fa-list-check"></i> Audit History
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::hasPermission('company.settings')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/settings') ?>" class="sidebar-link <?= $isActive('company/settings') ?>">
                        <i class="fa-solid fa-sliders"></i> ERP Settings
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="sidebar-footer">
                <a href="<?= base_url('logout') ?>" class="sidebar-link text-danger">
                    <i class="fa-solid fa-power-off"></i> Sign Out
                </a>
            </div>
        </aside>

        <!-- Main Window -->
        <main class="main-content">
            <!-- Top Navigation bar -->
            <header class="top-nav">
                <div>
                    <?php 
                        $tenant = \App\Core\Session::get('current_tenant');
                        if ($tenant):
                    ?>
                        <span class="badge badge-pepp badge-success me-2">
                            <i class="fa-solid fa-industry"></i> <?= htmlspecialchars($tenant['name']) ?>
                        </span>
                        <small class="text-secondary">Subdomain: <strong><?= htmlspecialchars($tenant['subdomain']) ?></strong></small>
                    <?php endif; ?>
                </div>

                <div class="dropdown">
                    <div class="user-profile-menu dropdown-toggle" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <?= strtoupper(substr(\App\Core\Session::get('user_name', 'U'), 0, 1)) ?>
                        </div>
                        <span class="d-none d-md-inline fw-semibold"><?= htmlspecialchars(\App\Core\Session::get('user_name', 'Employee')) ?></span>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenu">
                        <li><a class="dropdown-item" href="<?= base_url('company/settings') ?>"><i class="fa-solid fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="fa-solid fa-power-off me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </header>

            <!-- Alerts -->
            <?php if ($error = \App\Core\Session::getFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success = \App\Core\Session::getFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Page View Content -->
            <?= $content ?>
        </main>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
