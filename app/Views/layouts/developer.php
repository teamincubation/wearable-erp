<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Developer Portal' ?></title>
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
        <aside class="sidebar" style="background-color: #0f172a;">
            <a href="<?= base_url('developer/dashboard') ?>" class="sidebar-brand text-info">
                <i class="fa-solid fa-code"></i> Developer Portal
            </a>
            
            <ul class="sidebar-menu">
                <?php 
                    $currentUri = $_SERVER['REQUEST_URI'];
                    $isActive = fn($uri) => (strpos($currentUri, $uri) !== false) ? 'active' : '';
                ?>
                <li class="sidebar-item">
                    <a href="<?= base_url('developer/dashboard') ?>" class="sidebar-link <?= ($currentUri === '/developer/dashboard') ? 'active' : '' ?>">
                        <i class="fa-solid fa-cubes"></i> Overview Dashboard
                    </a>
                </li>
                
                <li class="sidebar-item">
                    <a href="<?= base_url('developer/companies') ?>" class="sidebar-link <?= $isActive('developer/companies') ?>">
                        <i class="fa-solid fa-industry"></i> Companies (Tenants)
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="<?= base_url('developer/subscriptions') ?>" class="sidebar-link <?= $isActive('developer/subscriptions') ?>">
                        <i class="fa-solid fa-credit-card"></i> Subscription Plans
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="<?= base_url('developer/versions') ?>" class="sidebar-link <?= $isActive('developer/versions') ?>">
                        <i class="fa-solid fa-code-fork"></i> Release Versioning
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="<?= base_url('developer/logs') ?>" class="sidebar-link <?= $isActive('developer/logs') ?>">
                        <i class="fa-solid fa-bug-slash"></i> Global Activity Logs
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="<?= base_url('developer/settings') ?>" class="sidebar-link <?= $isActive('developer/settings') ?>">
                        <i class="fa-solid fa-gears"></i> Platform Settings
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="<?= base_url('logout') ?>" class="sidebar-link text-danger">
                    <i class="fa-solid fa-power-off"></i> Exit Admin
                </a>
            </div>
        </aside>

        <!-- Main Window -->
        <main class="main-content">
            <!-- Top Navigation bar -->
            <header class="top-nav">
                <div>
                    <span class="badge badge-pepp badge-info me-2">
                        <i class="fa-solid fa-crown"></i> Platform Developer Portal
                    </span>
                    <small class="text-secondary">Environment: <strong><?= strtoupper(APP_ENV) ?></strong></small>
                </div>

                <div class="dropdown">
                    <div class="user-profile-menu dropdown-toggle" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar" style="background-color: #06b6d4;">
                            D
                        </div>
                        <span class="d-none d-md-inline fw-semibold">SaaS Developer Admin</span>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenu">
                        <li><a class="dropdown-item" href="<?= base_url('developer/settings') ?>"><i class="fa-solid fa-gears me-2"></i> Settings</a></li>
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
