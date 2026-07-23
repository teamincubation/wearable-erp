<?php
$currentPagePermission = \App\Core\Session::get('current_page_permission');
$isExpired = false;
$isCompanyExpired = false;
$daysLeft = 9999;
$whatsappUrl = '#';
$lockMessage = '';

$company = \App\Core\Session::get('current_tenant');
$subdomain = $company ? $company['subdomain'] : '';

// 1. Check Company-Wide Subscription Expiry
if ($company) {
    $db = \App\Core\Database::getInstance();
    $stmtComp = $db->prepare("
        SELECT c.subscription_expires_at, p.billing_cycle 
        FROM companies c 
        LEFT JOIN subscription_plans p ON c.subscription_plan_id = p.id 
        WHERE c.id = ? AND c.deleted_at IS NULL
    ");
    $stmtComp->execute([$company['id']]);
    $compInfo = $stmtComp->fetch();
    if ($compInfo && $compInfo['billing_cycle'] !== 'lifetime' && !empty($compInfo['subscription_expires_at'])) {
        $tzComp = new \DateTimeZone('Asia/Kolkata');
        $nowComp = new \DateTime('now', $tzComp);
        $expiryComp = new \DateTime($compInfo['subscription_expires_at'], $tzComp);
        $nowComp->setTime(0, 0, 0);
        $expiryComp->setTime(0, 0, 0);
        if ($nowComp > $expiryComp) {
            $isCompanyExpired = true;
            $isExpired = true;
            $lockMessage = "Your entire Wearable ERP subscription has expired! Access to all modules, pages, and sections has been restricted. Please contact WellGro Developers to renew your enterprise subscription.";
            
            $whatsappMsg = "Hello WellGro Developers, our Wearable ERP subscription on tenant subdomain '{$subdomain}' has expired. We want to renew our entire subscription plan. Please guide us on the renewal steps.";
            $whatsappUrl = "https://wa.me/" . \App\Core\Auth::getDeveloperWhatsapp() . "?text=" . urlencode($whatsappMsg);
        }
    }
}

// 2. Check Individual Feature Expiry (if company-wide is not expired)
if (!$isCompanyExpired && $currentPagePermission) {
    $validity = \App\Core\Auth::getFeatureValidity($currentPagePermission);
    if (isset($validity['expired']) && $validity['expired']) {
        $isExpired = true;
        $featureLabel = ucwords(str_replace(['company.', '.', 'view', 'manage'], [' ', ' ', '', ''], $currentPagePermission));
        $lockMessage = "The validity period for access to the <strong>" . htmlspecialchars(trim($featureLabel)) . "</strong> section has ended. Please contact WellGro Developers to renew your module access.";
        
        $whatsappMsg = "Hello WellGro Developers, I want to renew the subscription for the module: '" . trim($featureLabel) . "' on tenant subdomain '{$subdomain}' of Wearable ERP. Please share the renewal steps.";
        $whatsappUrl = "https://wa.me/" . \App\Core\Auth::getDeveloperWhatsapp() . "?text=" . urlencode($whatsappMsg);
    }
}

// Load custom sidebar menu order from system_settings
$savedOrderRaw = null;
if ($company) {
    $db = \App\Core\Database::getInstance();

    // ── One-time migration: enforce new default sidebar order (v2) ──
    $currentVersion = 2;
    $stmtVer = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = 'sidebar_menu_order_version' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
    $stmtVer->execute([$company['id']]);
    $dbVersion = (int)($stmtVer->fetchColumn() ?: 0);

    if ($dbVersion < $currentVersion) {
        $newDefaultOrder = json_encode(['dashboard','hr','production','merchandising','styles','buyers','inventory','procurement','masterdata','users','roles','tally','logs','settings','rfid_tracking']);

        // Upsert the sidebar order
        $stmtChk = $db->prepare("SELECT id FROM system_settings WHERE company_id = ? AND setting_key = 'sidebar_menu_order' AND deleted_at IS NULL LIMIT 1");
        $stmtChk->execute([$company['id']]);
        $existingId = $stmtChk->fetchColumn();
        if ($existingId) {
            $db->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE id = ?")->execute([$newDefaultOrder, $existingId]);
        } else {
            $db->prepare("INSERT INTO system_settings (company_id, setting_key, setting_value) VALUES (?, 'sidebar_menu_order', ?)")->execute([$company['id'], $newDefaultOrder]);
        }

        // Upsert the version flag
        $stmtVerChk = $db->prepare("SELECT id FROM system_settings WHERE company_id = ? AND setting_key = 'sidebar_menu_order_version' AND deleted_at IS NULL LIMIT 1");
        $stmtVerChk->execute([$company['id']]);
        $existingVerId = $stmtVerChk->fetchColumn();
        if ($existingVerId) {
            $db->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE id = ?")->execute([(string)$currentVersion, $existingVerId]);
        } else {
            $db->prepare("INSERT INTO system_settings (company_id, setting_key, setting_value) VALUES (?, 'sidebar_menu_order_version', ?)")->execute([$company['id'], (string)$currentVersion]);
        }

        $savedOrderRaw = $newDefaultOrder;
    } else {
        $stmtMenu = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = 'sidebar_menu_order' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
        $stmtMenu->execute([$company['id']]);
        $savedOrderRaw = $stmtMenu->fetchColumn();
    }
}
$savedOrder = [];
if ($savedOrderRaw) {
    $decodedRaw = html_entity_decode($savedOrderRaw, ENT_QUOTES, 'UTF-8');
    $parsed = json_decode($decodedRaw, true);
    if (is_array($parsed)) {
        $savedOrder = $parsed;
    }
}

$defaultMenu = [
    'dashboard' => ['name' => 'Dashboard', 'icon' => 'fa-solid fa-chart-line', 'url' => 'company/dashboard', 'permission' => 'company.dashboard', 'active_check' => '/company/dashboard', 'is_exact' => true],
    'hr' => ['name' => 'HR & Attendance', 'icon' => 'fa-solid fa-user-clock', 'url' => 'company/hr/attendance', 'permission' => 'company.users.view', 'active_check' => 'company/hr'],
    'production' => ['name' => 'Production & Quality', 'icon' => 'fa-solid fa-industry', 'url' => 'company/production/orders', 'permission' => 'company.production.view', 'active_check' => 'company/production'],
    'merchandising' => ['name' => 'Merchandising', 'icon' => 'fa-solid fa-calculator', 'url' => 'company/merchandising/costsheets', 'permission' => 'company.styles.view', 'active_check' => 'company/merchandising'],
    'styles' => ['name' => 'Style Master', 'icon' => 'fa-solid fa-shirt', 'url' => 'company/styles', 'permission' => 'company.styles.view', 'active_check' => 'company/styles'],
    'buyers' => ['name' => 'Buyers / Clients', 'icon' => 'fa-solid fa-user-tie', 'url' => 'company/buyers', 'permission' => 'company.styles.view', 'active_check' => 'company/buyers'],
    'inventory' => ['name' => 'Inventory Ledger', 'icon' => 'fa-solid fa-boxes-stacked', 'url' => 'company/inventory/balances', 'permission' => 'company.inventory.view', 'active_check' => 'company/inventory'],
    'procurement' => ['name' => 'Procurement', 'icon' => 'fa-solid fa-cart-shopping', 'url' => 'company/purchase/orders', 'permission' => 'company.styles.view', 'active_check' => 'company/purchase'],
    'masterdata' => ['name' => 'Master Data Hub', 'icon' => 'fa-solid fa-database', 'url' => 'company/masterdata', 'permission' => 'company.styles.view', 'active_check' => 'company/masterdata'],
    'users' => ['name' => 'Employees', 'icon' => 'fa-solid fa-users-gear', 'url' => 'company/users', 'permission' => 'company.users.view', 'active_check' => 'company/users'],
    'roles' => ['name' => 'Roles & Privileges', 'icon' => 'fa-solid fa-shield-halved', 'url' => 'company/roles', 'permission' => 'company.roles.view', 'active_check' => 'company/roles'],
    'tally' => ['name' => 'Tally Integration', 'icon' => 'fa-solid fa-file-excel', 'url' => 'company/tally/vouchers', 'permission' => 'company.tally.export', 'active_check' => 'company/tally'],
    'logs' => ['name' => 'Audit History', 'icon' => 'fa-solid fa-list-check', 'url' => 'company/logs', 'permission' => 'company.logs', 'active_check' => 'company/logs'],
    'settings' => ['name' => 'ERP Settings', 'icon' => 'fa-solid fa-sliders', 'url' => 'company/settings', 'permission' => 'company.settings', 'active_check' => 'company/settings'],
    'rfid_tracking' => ['name' => 'QR Code Scanner', 'icon' => 'fa-solid fa-mobile-screen-button', 'url' => 'company/production/qr-tracking', 'permission' => 'company.production.rfid_tracking', 'active_check' => 'company/production/qr-tracking']
];

$orderedMenuKeys = array_merge($savedOrder, array_diff(array_keys($defaultMenu), $savedOrder));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Wearable ERP' ?></title>
    <link rel="icon" type="image/png" href="<?= ($company && !empty($company['logo'])) ? base_url($company['logo']) : base_url('assets/images/favicon.ico') ?>">
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
        <aside class="sidebar" id="app-sidebar">
            <!-- Sidebar Drag-to-Resize Handle -->
            <div class="sidebar-resize-handle" id="sidebar-resize-handle"></div>

            <div class="sidebar-brand-container d-flex align-items-center justify-content-between">
                <a href="<?= base_url('company/dashboard') ?>" class="sidebar-brand d-flex align-items-center text-decoration-none">
                    <?php if ($company && !empty($company['logo'])): ?>
                        <img src="<?= base_url($company['logo']) ?>" alt="Logo" class="rounded me-2" style="max-height: 28px; width: auto; object-fit: contain; background: white; padding: 2px;">
                    <?php else: ?>
                        <i class="fa-solid fa-shirt me-2"></i>
                    <?php endif; ?>
                    <span class="text-truncate" style="max-width: 120px; font-weight: 700; color: white;"><?= $company ? htmlspecialchars($company['name']) : 'Wearable ERP' ?></span>
                </a>
                <button id="sidebar-toggle-btn" class="btn text-white p-0 border-0 ms-2" style="box-shadow: none; background: transparent;" type="button" title="Toggle Sidebar">
                    <i class="fa-solid fa-circle-chevron-left fs-5" id="sidebar-toggle-icon"></i>
                </button>
            </div>
            
            <ul class="sidebar-menu">
                <?php 
                    $currentUri = $_SERVER['REQUEST_URI'] ?? '';
                    foreach ($orderedMenuKeys as $key):
                        if (!isset($defaultMenu[$key])) continue;
                        $item = $defaultMenu[$key];
                        if (!\App\Core\Auth::hasPermission($item['permission'])) continue;
                        
                        $activeClass = '';
                        if (!empty($item['is_exact'])) {
                            if ($currentUri === $item['active_check']) {
                                $activeClass = 'active';
                            }
                        } else {
                            if ($currentUri !== null && !empty($item['active_check']) && strpos($currentUri, $item['active_check']) !== false) {
                                $activeClass = 'active';
                            }
                        }
                ?>
                    <li class="sidebar-item">
                        <a href="<?= base_url($item['url']) ?>" class="sidebar-link <?= $activeClass ?>">
                            <i class="<?= $item['icon'] ?>"></i> 
                            <span class="sidebar-text"><?= htmlspecialchars($item['name']) ?></span> 
                            <span class="sidebar-badge-container"><?= \App\Core\Auth::getFeatureLabelBadge($item['permission']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="sidebar-footer">
                <a href="<?= base_url('logout') ?>" class="sidebar-link text-danger">
                    <i class="fa-solid fa-power-off"></i> <span class="sidebar-text">Sign Out</span>
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
                $whatsappWarningMsg = "Hello WellGro Developers, our subscription validity for the module: '" . trim($featureLabel) . "' on tenant subdomain '{$subdomain}' is expiring in {$expiringFeature['days_left']} days. We want to renew it. Please share the renewal steps.";
                $whatsappWarningUrl = "https://wa.me/" . \App\Core\Auth::getDeveloperWhatsapp() . "?text=" . urlencode($whatsappWarningMsg);
            ?>
                <div class="bg-warning text-dark text-center py-2 fw-semibold border-bottom d-flex align-items-center justify-content-center" style="font-size: 14px; gap: 10px;">
                    <span>
                        <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> 
                        Subscription Warning: The access to <strong><?= htmlspecialchars(trim($featureLabel)) ?></strong> is expiring in <strong><?= $expiringFeature['days_left'] ?> day<?= $expiringFeature['days_left'] == 1 ? '' : 's' ?></strong>.
                    </span>
                    <a href="<?= $whatsappWarningUrl ?>" target="_blank" class="btn btn-sm btn-dark rounded-pill px-3 py-1 fw-bold text-white border-0" style="background-color: #0b1528;">
                        <i class="fa-brands fa-whatsapp text-success me-1"></i> Renew Now
                    </a>
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
                        <small class="text-secondary me-2">Subdomain: <strong><?= htmlspecialchars($tenant['subdomain']) ?></strong></small>
                        <?php if (!empty($currentPagePermission)): ?>
                            <?= \App\Core\Auth::getFeatureLabelBadge($currentPagePermission) ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Global timezone-aware realtime clock -->
                <?php
                    $tenantTimezone = 'Asia/Kolkata';
                    if (!empty($tenant) && !empty($tenant['timezone'])) {
                        $tenantTimezone = $tenant['timezone'];
                    }
                ?>
                <div class="d-none d-md-flex align-items-center me-3 px-3 py-1.5 rounded-pill shadow-sm" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #3b82f6; gap: 8px; font-size: 13px; font-weight: 600;">
                    <i class="fa-regular fa-clock text-primary fa-pulse"></i>
                    <span id="erp-global-clock" class="text-dark font-monospace">Loading...</span>
                    <span class="badge bg-primary text-white font-monospace" style="font-size: 10px;"><?= htmlspecialchars($tenantTimezone) ?></span>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const timezone = "<?= $tenantTimezone ?>";
                    let serverTimeMs = <?= time() * 1000 ?>;
                    const clockElem = document.getElementById('erp-global-clock');

                    function updateClock() {
                        serverTimeMs += 1000;
                        const dateObj = new Date(serverTimeMs);
                        try {
                            const formatter = new Intl.DateTimeFormat('en-US', {
                                timeZone: timezone,
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: true
                            });

                            const parts = formatter.formatToParts(dateObj);
                            let day = '', month = '', year = '', hour = '', minute = '', second = '', dayPeriod = '';
                            for (const part of parts) {
                                if (part.type === 'day') day = part.value;
                                else if (part.type === 'month') month = part.value;
                                else if (part.type === 'year') year = part.value;
                                else if (part.type === 'hour') hour = part.value;
                                else if (part.type === 'minute') minute = part.value;
                                else if (part.type === 'second') second = part.value;
                                else if (part.type === 'dayPeriod') dayPeriod = part.value.toUpperCase();
                            }

                            // Pad hour/minute/second if single digit
                            if (hour.length === 1) hour = '0' + hour;
                            if (minute.length === 1) minute = '0' + minute;
                            if (second.length === 1) second = '0' + second;

                            const clockStr = `${day} ${month} ${year}, ${hour}:${minute}:${second} ${dayPeriod}`;
                            if (clockElem) clockElem.innerText = clockStr;
                        } catch (e) {
                            if (clockElem) clockElem.innerText = dateObj.toUTCString();
                        }
                    }

                    setInterval(updateClock, 1000);
                    updateClock();
                });
                </script>

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
                            <h4 class="fw-bold text-dark mb-2">Subscription Expired</h4>
                            <p class="text-secondary mb-4">
                                <?= $lockMessage ?>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('app-sidebar');
        const resizeHandle = document.getElementById('sidebar-resize-handle');
        const toggleBtn = document.getElementById('sidebar-toggle-btn');
        const toggleIcon = document.getElementById('sidebar-toggle-icon');

        // 1. Initial State Loading from LocalStorage
        const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        if (isCollapsed) {
            document.body.classList.add('sidebar-collapsed');
            document.documentElement.style.setProperty('--sidebar-width', '70px');
            if (toggleIcon) {
                toggleIcon.classList.remove('fa-circle-chevron-left');
                toggleIcon.classList.add('fa-circle-chevron-right');
            }
        } else {
            const savedWidth = localStorage.getItem('sidebar-width') || '260px';
            document.documentElement.style.setProperty('--sidebar-width', savedWidth);
        }

        // 2. Collapse / Expand Action
        function toggleSidebar() {
            const currentlyCollapsed = document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', currentlyCollapsed ? 'true' : 'false');
            
            if (currentlyCollapsed) {
                document.documentElement.style.setProperty('--sidebar-width', '70px');
            } else {
                const savedWidth = localStorage.getItem('sidebar-width') || '260px';
                document.documentElement.style.setProperty('--sidebar-width', savedWidth);
            }
            
            if (toggleIcon) {
                if (currentlyCollapsed) {
                    toggleIcon.classList.remove('fa-circle-chevron-left');
                    toggleIcon.classList.add('fa-circle-chevron-right');
                } else {
                    toggleIcon.classList.remove('fa-circle-chevron-right');
                    toggleIcon.classList.add('fa-circle-chevron-left');
                }
            }
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });
        }

        const sidebarBrandContainer = document.querySelector('.sidebar-brand-container');
        if (sidebarBrandContainer) {
            sidebarBrandContainer.addEventListener('click', function(e) {
                if (document.body.classList.contains('sidebar-collapsed')) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                }
            });
        }

        // 3. Drag to Resize Logic
        if (resizeHandle && sidebar) {
            let isResizing = false;

            resizeHandle.addEventListener('mousedown', function(e) {
                e.preventDefault();
                isResizing = true;
                resizeHandle.classList.add('active');
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';
            });

            document.addEventListener('mousemove', function(e) {
                if (!isResizing) return;
                
                let newWidth = e.clientX;
                // Constraints
                if (newWidth < 180) newWidth = 180;
                if (newWidth > 450) newWidth = 450;

                document.documentElement.style.setProperty('--sidebar-width', newWidth + 'px');
                localStorage.setItem('sidebar-width', newWidth + 'px');
                
                // If dragged to resize, ensure it is not collapsed
                if (document.body.classList.contains('sidebar-collapsed')) {
                    document.body.classList.remove('sidebar-collapsed');
                    localStorage.setItem('sidebar-collapsed', 'false');
                    if (toggleIcon) {
                        toggleIcon.classList.remove('fa-circle-chevron-right');
                        toggleIcon.classList.add('fa-circle-chevron-left');
                    }
                }
            });

            document.addEventListener('mouseup', function() {
                if (isResizing) {
                    isResizing = false;
                    resizeHandle.classList.remove('active');
                    document.body.style.cursor = '';
                    document.body.style.userSelect = '';
                }
            });
        }
    });
    </script>
</body>
</html>
