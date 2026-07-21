<?php
$currentPagePermission = \App\Core\Session::get('current_page_permission');
$isExpired = false;
$daysLeft = 9999;
$whatsappUrl = '#';

if ($currentPagePermission) {
    $validity = \App\Core\Auth::getFeatureValidity($currentPagePermission);
    if (isset($validity['expired']) && $validity['expired']) {
        $isExpired = true;
        
        $company = \App\Core\Session::get('current_tenant');
        $subdomain = $company ? $company['subdomain'] : '';
        $featureLabel = ucwords(str_replace(['company.', '.', 'view', 'manage'], [' ', ' ', '', ''], $currentPagePermission));
        
        $whatsappMsg = "Hello WellGro Developers, I want to renew the subscription for the module: '" . trim($featureLabel) . "' on tenant subdomain '{$subdomain}' of Wearable ERP. Please share the renewal steps.";
        $whatsappUrl = "https://wa.me/" . \App\Core\Auth::getDeveloperWhatsapp() . "?text=" . urlencode($whatsappMsg);
    }
}
?>
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
                    <a href="<?= base_url('company/masterdata') ?>" class="sidebar-link <?= $isActive('company/masterdata') ?>">
                        <i class="fa-solid fa-database"></i> Master Data Hub
                    </a>
                </li>
                <?php endif; ?>

                <?php if (\App\Core\Auth::hasPermission('company.styles.view')): ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('company/buyers') ?>" class="sidebar-link <?= $isActive('company/buyers') ?>">
                        <i class="fa-solid fa-user-tie"></i> Buyers / Clients
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
            <!-- Expiry Warning Banner -->
            <?php
            $expiringFeature = \App\Core\Auth::getClosestExpiringFeature();
            if ($expiringFeature):
                $featureLabel = ucwords(str_replace(['company.', '.', 'view', 'manage'], [' ', ' ', '', ''], $expiringFeature['feature_key']));
            ?>
                <div class="bg-warning text-dark text-center py-2 fw-semibold border-bottom" style="font-size: 14px;">
                    <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> 
                    Subscription Warning: The access to <strong><?= htmlspecialchars(trim($featureLabel)) ?></strong> is expiring in <strong><?= $expiringFeature['days_left'] ?> day<?= $expiringFeature['days_left'] == 1 ? '' : 's' ?></strong>. Please renew now to avoid access lock.
                </div>
            <?php endif; ?>

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
            <?php if ($isExpired): ?>
                <style>
                .expired-feature-lock {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    min-height: 500px;
                    background: rgba(255, 255, 255, 0.4);
                    backdrop-filter: blur(10px);
                    -webkit-backdrop-filter: blur(10px);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 1050;
                    border-radius: 12px;
                }
                .expired-lock-card {
                    background: #ffffff;
                    border: 1px solid rgba(0, 0, 0, 0.1);
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                    border-radius: 16px;
                    padding: 2.5rem;
                    text-align: center;
                    max-width: 480px;
                    animation: fadeInUp 0.4s ease-out;
                }
                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                </style>
                <div class="position-relative">
                    <!-- Overlay Lock -->
                    <div class="expired-feature-lock">
                        <div class="expired-lock-card">
                            <div class="mb-4 text-danger">
                                <i class="fa-solid fa-lock fs-1" style="font-size: 4rem !important;"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-2">Feature Subscription Expired</h4>
                            <p class="text-secondary mb-4">
                                The validity period for access to the <strong><?= htmlspecialchars(ucwords(str_replace(['company.', '.', 'view', 'manage'], [' ', ' ', '', ''], $currentPagePermission))) ?></strong> section has ended. 
                                Please contact WellGro Developers to renew your module access.
                            </p>
                            <a href="<?= $whatsappUrl ?>" target="_blank" class="btn btn-success btn-lg rounded-pill px-5">
                                <i class="fa-brands fa-whatsapp me-2"></i> Renew via WhatsApp
                            </a>
                        </div>
                    </div>
                    
                    <!-- Original View (Blurred & Disabled behind overlay) -->
                    <div style="filter: blur(5px); pointer-events: none; user-select: none;">
                        <?= $content ?>
                    </div>
                </div>
            <?php else: ?>
                <?= $content ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
