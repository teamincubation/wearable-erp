<div id="live-dashboard-container" class="container-fluid py-4 min-vh-100 px-3 px-md-4" data-theme="dark" style="font-family: 'Outfit', sans-serif; transition: background 0.3s ease, color 0.3s ease;">
    
    <!-- Theme Switcher & Full Dashboard CSS Overrides -->
    <style>
        /* Force html and body to inherit theme background to eliminate white gaps */
        html, body {
            background-color: #090d16 !important;
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100vh;
        }

        :root, [data-theme="dark"] {
            --bg-main: #090d16;
            --card-bg: #131a29;
            --card-border: rgba(255, 255, 255, 0.08);
            --card-hover-border: rgba(99, 102, 241, 0.5);
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --text-muted: #64748b;
            --input-bg: #0f172a;
            --input-border: #334155;
            --input-text: #ffffff;
            --input-placeholder: #64748b;
            --toolbar-bg: #111827;
            --toolbar-border: #1f2937;
            --table-header-bg: #0f172a;
            --table-row-border: rgba(255, 255, 255, 0.06);
            --table-hover-bg: rgba(255, 255, 255, 0.04);
            --color-primary: #3b82f6;
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-danger: #ef4444;
            --color-info: #06b6d4;
        }

        [data-theme="sepia"] {
            --bg-main: #231c17;
            --card-bg: #322923;
            --card-border: rgba(255, 248, 230, 0.12);
            --card-hover-border: rgba(224, 159, 103, 0.5);
            --text-main: #fceade;
            --text-sub: #d5c5b5;
            --text-muted: #a39281;
            --input-bg: #211914;
            --input-border: #4d3f35;
            --input-text: #ffffff;
            --input-placeholder: #a39281;
            --toolbar-bg: #1c1612;
            --toolbar-border: #3d3129;
            --table-header-bg: #271f1a;
            --table-row-border: rgba(255, 248, 230, 0.08);
            --table-hover-bg: rgba(255, 248, 230, 0.05);
            --color-primary: #e09f67;
            --color-success: #84cc16;
            --color-warning: #eab308;
            --color-danger: #f87171;
            --color-info: #38bdf8;
        }

        [data-theme="bright"] {
            --bg-main: #f1f5f9;
            --card-bg: #ffffff;
            --card-border: #cbd5e1;
            --card-hover-border: #3b82f6;
            --text-main: #0f172a;
            --text-sub: #475569;
            --text-muted: #64748b;
            --input-bg: #f8fafc;
            --input-border: #cbd5e1;
            --input-text: #0f172a;
            --input-placeholder: #94a3b8;
            --toolbar-bg: #ffffff;
            --toolbar-border: #cbd5e1;
            --table-header-bg: #f8fafc;
            --table-row-border: #e2e8f0;
            --table-hover-bg: rgba(0, 0, 0, 0.03);
            --color-primary: #2563eb;
            --color-success: #059669;
            --color-warning: #d97706;
            --color-danger: #dc2626;
            --color-info: #0284c7;
        }

        #live-dashboard-container {
            background-color: var(--bg-main) !important;
            color: var(--text-main) !important;
        }

        .live-card {
            background-color: var(--card-bg) !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: border-color 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
        }
        .live-card:hover {
            border-color: var(--card-hover-border) !important;
            transform: translateY(-2px);
            box-shadow: 0 14px 35px rgba(0, 0, 0, 0.22);
        }

        .text-dash-main { color: var(--text-main) !important; }
        .text-dash-sub { color: var(--text-sub) !important; }
        .text-dash-muted { color: var(--text-muted) !important; }

        /* Icon Badge Styles */
        .icon-badge {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            line-height: 1;
            background: transparent;
        }
        .icon-badge-primary { background: rgba(59, 130, 246, 0.15) !important; color: var(--color-primary) !important; }
        .icon-badge-success { background: rgba(16, 185, 129, 0.15) !important; color: var(--color-success) !important; }
        .icon-badge-warning { background: rgba(245, 158, 11, 0.15) !important; color: var(--color-warning) !important; }
        .icon-badge-danger { background: rgba(239, 68, 68, 0.15) !important; color: var(--color-danger) !important; }

        .animate-pulse-slow {
            animation: pulse-slow 2s infinite;
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .font-outfit { font-family: 'Outfit', sans-serif; }

        /* Theme Selector Buttons */
        .theme-btn-group .btn {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            transition: all 0.2s ease;
        }
        .theme-btn-group .btn.active {
            box-shadow: 0 0 12px rgba(59, 130, 246, 0.5);
        }

        /* Minimal QR Bar */
        .qr-lookup-bar {
            background-color: var(--card-bg) !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 16px;
        }
        .qr-lookup-input {
            background-color: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--input-text) !important;
            border-radius: 10px;
            font-size: 13.5px;
        }
        .qr-lookup-input::placeholder {
            color: var(--input-placeholder) !important;
            opacity: 0.8;
        }
        .qr-lookup-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3) !important;
            border-color: var(--color-primary) !important;
        }

        /* Fix Bootstrap Table Row Hover Background */
        .table-hover > tbody > tr:hover > * {
            background-color: var(--table-hover-bg) !important;
            color: var(--text-main) !important;
        }

        /* Custom Scrollbar for Real-Time Activity Feed */
        .custom-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: var(--input-border);
            border-radius: 10px;
        }

        .pulse-live-dot {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 1.6s infinite;
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>

    <!-- TOP NAVIGATION & HEADER BAR WITH CONTROLS -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-lg-7 col-12">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <a href="<?= base_url('company/production/orders') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 text-dash-sub" style="font-size: 11.5px; border-color: var(--card-border);">
                    <i class="fa-solid fa-arrow-left me-1"></i> Orders
                </a>
                <div class="bg-emerald-500 text-white rounded-pill px-3 py-1 fw-bold text-uppercase d-inline-flex align-items-center gap-2 shadow-sm" style="font-size: 0.68rem; letter-spacing: 0.05em; background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <span class="pulse-live-dot"></span> LIVE MONITOR
                </div>
                <span class="badge bg-primary bg-opacity-20 text-primary font-monospace fw-bold px-2.5 py-1.5 rounded-pill" style="font-size: 11px;">
                    <i class="fa-solid fa-layer-group me-1"></i> <?= htmlspecialchars($order['production_no']) ?>
                </span>
                <?php if (!empty($order['buyer_po_no'])): ?>
                    <span class="badge bg-secondary bg-opacity-20 text-dash-sub font-monospace px-2.5 py-1.5 rounded-pill" style="font-size: 11px;">
                        PO: <?= htmlspecialchars($order['buyer_po_no']) ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <h3 class="m-0 fw-bold font-outfit text-dash-main" style="letter-spacing: -0.02em;">
                <?= htmlspecialchars($order['style_name']) ?> 
                <span class="fs-5 text-dash-sub fw-normal font-monospace">(<?= htmlspecialchars($order['style_no']) ?>)</span>
            </h3>
            <p class="text-dash-sub small mt-1 mb-0" style="font-size: 12.5px;">
                Fabric: <strong class="text-dash-main"><?= htmlspecialchars($order['fabric_composition'] ?: 'Standard Garment Fabric') ?></strong> | 
                Category: <strong class="text-dash-main text-uppercase"><?= htmlspecialchars($order['style_category'] ?? 'Unisex') ?></strong>
            </p>
        </div>

        <div class="col-lg-5 col-12 text-lg-end text-start">
            <div class="d-inline-flex flex-wrap align-items-center gap-2 p-2 rounded-pill shadow-sm" style="background: var(--toolbar-bg); border: 1px solid var(--toolbar-border);">
                <!-- Theme Switcher Controls -->
                <div class="theme-btn-group btn-group" role="group" aria-label="Theme Selector">
                    <button type="button" class="btn btn-outline-primary active" id="theme-dark-btn" onclick="setDashboardTheme('dark')">
                        <i class="fa-solid fa-moon me-1"></i> Dark
                    </button>
                    <button type="button" class="btn btn-outline-warning" id="theme-sepia-btn" onclick="setDashboardTheme('sepia')">
                        <i class="fa-solid fa-book-open me-1"></i> Sepia
                    </button>
                    <button type="button" class="btn btn-outline-success" id="theme-bright-btn" onclick="setDashboardTheme('bright')">
                        <i class="fa-solid fa-sun me-1"></i> Bright
                    </button>
                </div>

                <div class="vr opacity-25 d-none d-sm-block" style="height: 18px; color: var(--text-sub);"></div>

                <!-- Clock & Auto Refresh Toggle -->
                <div class="d-inline-flex align-items-center gap-2 px-1" style="font-size: 12px;">
                    <button type="button" id="auto-refresh-toggle-btn" onclick="toggleAutoRefresh()" class="btn btn-sm btn-outline-success rounded-pill px-2.5 py-0.5 font-monospace" style="font-size: 11px;">
                        <i class="fa-solid fa-pause me-1"></i> <span id="refresh-countdown">30s</span>
                    </button>
                    <button onclick="window.location.reload();" class="btn btn-sm btn-link p-0 text-dash-sub text-decoration-none" title="Refresh Now">
                        <i class="fa-solid fa-arrows-rotate fs-6"></i>
                    </button>
                    <span class="fw-bold text-dash-main font-monospace ms-1" id="live-clock">00:00:00 AM</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN METRICS PANEL (4 CLEAN EQUAL CARDS) -->
    <?php
        $targetQty = (int)$order['target_qty'];
        
        $lastStage = end($stagesList);
        reset($stagesList);
        $finishedQty = isset($wip_summary[$lastStage]) ? (int)$wip_summary[$lastStage]['out'] : 0;
        $completionPct = $targetQty > 0 ? round(($finishedQty / $targetQty) * 100, 1) : 0;

        $totalWaste = 0;
        foreach ($stagesList as $stg) {
            $totalWaste += (isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['waste'] : 0);
        }
        $wastePct = $targetQty > 0 ? round(($totalWaste / $targetQty) * 100, 1) : 0;

        $latestLog = $recentLogs[0] ?? null;
        $tzStr = $tenantTimezone ?? 'Asia/Kolkata';
    ?>
    <div class="row g-3 mb-4">
        <!-- 1. Production Target Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3.5 h-100 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-dash-sub small fw-bold text-uppercase d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">Production Target</span>
                    <h3 class="m-0 fw-bold font-outfit text-dash-main"><?= number_format($targetQty) ?> <span class="fs-6 fw-normal text-dash-sub">pcs</span></h3>
                </div>
                <div class="icon-badge icon-badge-primary">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
            </div>
        </div>

        <!-- 2. Packaged / Completed Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3.5 h-100 d-flex align-items-center justify-content-between" style="border-left: 4px solid var(--color-success) !important;">
                <div>
                    <span class="text-dash-sub small fw-bold text-uppercase d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">Packaged / Completed</span>
                    <h3 class="m-0 fw-bold font-outfit" style="color: var(--color-success) !important;"><?= number_format($finishedQty) ?> <span class="fs-6 fw-normal text-dash-sub">(<?= $completionPct ?>%)</span></h3>
                </div>
                <div class="icon-badge icon-badge-success">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>

        <!-- 3. LATEST LIVE WIP UPDATE CARD -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3.5 h-100 d-flex flex-column justify-content-between" style="border-left: 4px solid var(--color-primary) !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-dash-sub small fw-bold text-uppercase d-block" style="font-size: 0.68rem; letter-spacing: 0.05em;">Latest Live Scan Update</span>
                        <?php if ($latestLog): ?>
                            <?php $timeAgoStr = \App\Helpers\TimezoneHelper::timeAgo($latestLog['created_at'] ?? 'now'); ?>
                            <span class="badge bg-primary bg-opacity-20 text-primary font-monospace" style="font-size: 9.5px;"><?= $timeAgoStr ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($latestLog): ?>
                        <h6 class="m-0 fw-bold font-outfit text-dash-main text-truncate" style="font-size: 14px;">
                            Stage: <span class="text-uppercase font-monospace" style="color: var(--color-primary) !important;"><?= str_replace('_', ' ', $latestLog['stage']) ?></span>
                        </h6>
                        <div class="text-dash-sub small mt-1" style="font-size: 11.5px; line-height: 1.35;">
                            By: <strong class="text-dash-main"><?= htmlspecialchars($latestLog['employee_name'] ?: 'Operator') ?></strong> | 
                            Out: <strong class="font-monospace" style="color: var(--color-success);"><?= (int)($latestLog['qty_out'] ?: 1) ?> pcs (<?= strtoupper($latestLog['status'] ?? 'PASS') ?>)</strong>
                        </div>
                    <?php else: ?>
                        <h6 class="m-0 fw-bold text-dash-sub font-outfit fs-6 py-1">Awaiting First Scan</h6>
                    <?php endif; ?>
                </div>
                <?php if ($latestLog): ?>
                    <?php 
                        $dtLog = new \DateTime($latestLog['created_at'] ?? 'now', new \DateTimeZone('UTC'));
                        try { $dtLog->setTimezone(new \DateTimeZone($tzStr)); } catch (\Exception $e) {}
                    ?>
                    <div class="pt-1 text-end border-top mt-1" style="border-color: var(--table-row-border) !important;">
                        <small class="text-dash-muted font-monospace" style="font-size: 10px;"><i class="fa-regular fa-clock me-1"></i><?= $dtLog->format('d M, h:i A') ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. Cumulative Waste Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3.5 h-100 d-flex align-items-center justify-content-between" style="border-left: 4px solid var(--color-danger) !important;">
                <div>
                    <span class="text-dash-sub small fw-bold text-uppercase d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">Cumulative Wastage</span>
                    <h3 class="m-0 fw-bold font-outfit" style="color: var(--color-danger) !important;"><?= number_format($totalWaste) ?> <span class="fs-6 fw-normal text-dash-sub">(<?= $wastePct ?>%)</span></h3>
                </div>
                <div class="icon-badge icon-badge-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- MINIMALISTIC & HIGH-ATTENTION QR CODE TRACKING BAR -->
    <div class="qr-lookup-bar p-3 mb-4">
        <form id="track-qr-unit-form" class="row align-items-center g-2">
            <div class="col-md-4 col-12">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="icon-badge icon-badge-primary" style="width: 38px; height: 38px; min-width: 38px; font-size: 1rem; border-radius: 10px;">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dash-main m-0 font-outfit" style="font-size: 13.5px;">Track Unit Status by QR Code</h6>
                        <span class="text-dash-sub small" style="font-size: 11px;">Instant unit lifecycle lookup for this batch</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-8">
                <div class="input-group input-group-sm">
                    <span class="input-group-text border-end-0 bg-transparent text-primary" style="border-color: var(--input-border); border-top-left-radius: 10px; border-bottom-left-radius: 10px;">
                        <i class="fa-solid fa-barcode"></i>
                    </span>
                    <input type="text" id="track-qr-unit-input" class="form-control qr-lookup-input font-monospace fw-bold ps-1" placeholder="Scan or type QR Code e.g. PO-TOCCO-2026-001-S-0001" required style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                </div>
            </div>
            <div class="col-md-2 col-4 text-end">
                <button type="submit" id="track-qr-unit-btn" class="btn btn-primary btn-sm w-100 fw-bold rounded-pill shadow-sm" style="font-size: 12px; padding: 7px 14px;">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Track Unit
                </button>
            </div>
        </form>
    </div>

    <!-- CHART & REAL-TIME ACTIVITY FEED ROW -->
    <div class="row g-4 mb-4">
        <!-- Live Chart -->
        <div class="col-lg-8 col-12">
            <div class="live-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="m-0 fw-bold font-outfit text-dash-main"><i class="fa-solid fa-chart-line text-primary me-2"></i> Stage Production Completion vs Target</h6>
                    <span class="small text-dash-sub" style="font-size: 11.5px;">All Active WIP Stages</span>
                </div>
                <div style="height: 320px;">
                    <canvas id="liveStageChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Real-Time Activity Feed -->
        <div class="col-lg-4 col-12">
            <div class="live-card p-4 h-100 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="m-0 fw-bold font-outfit text-dash-main"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i> Activity Feed</h6>
                    <?php if (!empty($recentLogs) && \App\Core\Auth::hasPermission('company.production.manage')): ?>
                        <button type="button" onclick="triggerSecurityDeleteModal('<?= base_url('company/production/stage/' . $order['id'] . '/clear-logs') ?>', 'Are you sure you want to CLEAR ALL activity feed logs for Batch #<?= htmlspecialchars($order['production_no']) ?>?')" class="btn btn-sm btn-outline-danger rounded-pill font-monospace" style="font-size: 10.5px; padding: 2px 10px;">
                            <i class="fa-solid fa-trash-can me-1"></i> Clear Feed
                        </button>
                    <?php endif; ?>
                </div>
                <div class="flex-grow-1 overflow-y-auto pe-1 custom-scroll" style="max-height: 310px;">
                    <?php if (!empty($recentLogs)): ?>
                        <div class="list-group list-group-flush bg-transparent">
                            <?php foreach ($recentLogs as $log): ?>
                                <?php 
                                    $isPass = ($log['qty_out'] > 0);
                                    $badgeClass = $isPass ? 'bg-success text-white' : 'bg-danger text-white';
                                    $statusText = $isPass ? 'PASS' : 'FAIL';
                                    $tagLabel = $log['machine_name'] ?: ($log['qr_code'] ?: 'Manual');
                                    $formattedLogTime = \App\Helpers\TimezoneHelper::formatTenantTime($log['created_at'] ?? 'now', $tzStr, 'h:i:s A');
                                ?>
                                <div class="list-group-item bg-transparent text-dash-main px-0 py-2.5" style="border-color: var(--table-row-border) !important;">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                        <span class="badge <?= $badgeClass ?> font-monospace fw-bold" style="font-size: 0.65rem; padding: 0.25em 0.6em;"><?= $statusText ?></span>
                                        <small class="text-dash-muted font-monospace" style="font-size: 10.5px;"><?= $formattedLogTime ?></small>
                                    </div>
                                    <?php 
                                        $rawLogStage = trim((string)($log['stage'] ?? ''));
                                        $displayStage = !empty($rawLogStage) ? str_replace('_', ' ', $rawLogStage) : 'Production Stage';
                                    ?>
                                    <p class="mb-0 small text-dash-main" style="font-size: 12.5px;">
                                        Stage <strong class="text-capitalize text-primary"><?= htmlspecialchars($displayStage) ?></strong>
                                    </p>
                                    <small class="text-dash-sub d-block font-monospace mt-0.5" style="font-size: 10.5px;">
                                        Tag: <span class="text-dash-main"><?= htmlspecialchars($tagLabel) ?></span> | 
                                        By: <span class="text-dash-main"><?= htmlspecialchars($log['employee_name'] ?: 'System') ?></span>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-dash-sub">
                            <i class="fa-solid fa-wave-square fs-3 mb-2 animate-pulse-slow"></i>
                            <p class="small m-0">Awaiting live barcode/RFID scans...</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- LIVE STAGE-WISE PIPELINE TABLE -->
    <div class="row">
        <div class="col-12">
            <div class="live-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="m-0 fw-bold font-outfit text-dash-main"><i class="fa-solid fa-list-check text-primary me-2"></i> Stage-Wise Pipeline Inventory Summary</h6>
                    <span class="badge bg-secondary bg-opacity-20 text-dash-sub font-monospace small px-3 py-1.5 rounded-pill">Total Stages: <?= count($stagesList) ?></span>
                </div>
                <div class="table-responsive border-0">
                    <table class="table table-hover mb-0 align-middle" style="color: var(--text-main); --bs-table-bg: transparent; --bs-table-border-color: var(--table-row-border);">
                        <thead>
                            <tr class="text-dash-sub" style="font-size: 0.72rem; letter-spacing: 0.05em; text-transform: uppercase;">
                                <th>Stage Name</th>
                                <th class="text-center">Qty In (Total)</th>
                                <th class="text-center">Qty Out (Completed)</th>
                                <th class="text-center">Wastage Count</th>
                                <th class="text-center">WIP Balance Stock</th>
                                <th class="text-end" style="width: 25%;">Stage Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stagesList as $stg): ?>
                                <?php 
                                    $in = isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['in'] : 0;
                                    $out = isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['out'] : 0;
                                    $waste = isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['waste'] : 0;
                                    $bal = isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['wip_balance'] : 0;
                                    $prog = $targetQty > 0 ? min(100, round(($out / $targetQty) * 100, 1)) : 0;
                                ?>
                                <tr style="border-bottom: 1px solid var(--table-row-border);">
                                    <td>
                                        <span class="fw-bold text-dash-main text-capitalize text-nowrap" style="font-size: 13.5px;"><?= str_replace('_', ' ', $stg) ?></span>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?php if ($in > 0): ?>
                                            <span class="badge bg-primary text-white font-monospace px-2.5 py-1 shadow-sm" style="font-size: 0.8rem;"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i><?= number_format($in) ?></span>
                                        <?php else: ?>
                                            <span class="text-dash-muted opacity-50 font-monospace">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?php if ($out > 0): ?>
                                            <span class="badge bg-success text-white font-monospace px-2.5 py-1 shadow-sm" style="font-size: 0.8rem;"><i class="fa-solid fa-check me-1"></i><?= number_format($out) ?></span>
                                        <?php else: ?>
                                            <span class="text-dash-muted opacity-50 font-monospace">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?php if ($waste > 0): ?>
                                            <span class="badge bg-danger text-white font-monospace px-2.5 py-1 shadow-sm" style="font-size: 0.8rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i><?= number_format($waste) ?></span>
                                        <?php else: ?>
                                            <span class="text-dash-muted opacity-50 font-monospace">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?php if ($bal > 0): ?>
                                            <span class="badge bg-warning text-dark font-monospace px-2.5 py-1 shadow-sm fw-bold" style="font-size: 0.8rem;"><i class="fa-solid fa-boxes-stacked me-1"></i><?= number_format($bal) ?></span>
                                        <?php else: ?>
                                            <span class="text-dash-muted opacity-50 font-monospace">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2.5">
                                            <div class="progress w-100" style="height: 7px; background-color: var(--input-bg); border-radius: 4px; overflow: hidden;">
                                                <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: <?= $prog ?>%;" aria-valuenow="<?= $prog ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="small font-monospace fw-bold text-dash-sub text-nowrap" style="font-size: 11.5px;"><?= $prog ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Track Unit Lifecycle History Modal -->
<div class="modal fade" id="trackQrUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content text-start" style="border-radius: 16px; background: var(--card-bg) !important; color: var(--text-main) !important; border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dash-main"><i class="fa-solid fa-qrcode text-primary me-2"></i> Complete Unit Stage Lifecycle History</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-start" id="track-qr-modal-body">
                <div class="text-center py-4">
                    <span class="spinner-border text-primary" role="status"></span>
                    <p class="mt-2 text-dash-sub">Fetching stage lifecycle logs...</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // 1. Theme Switcher Logic (Enforces body & html background color match)
    function setDashboardTheme(themeName) {
        const container = document.getElementById('live-dashboard-container');
        if (!container) return;
        container.setAttribute('data-theme', themeName);
        localStorage.setItem('wearable_dashboard_theme', themeName);

        // Dynamically get active background color from CSS variable
        const computedBg = getComputedStyle(container).getPropertyValue('--bg-main').trim();
        document.body.style.backgroundColor = computedBg;
        document.body.style.background = computedBg;
        document.documentElement.style.backgroundColor = computedBg;

        // Update button active state
        document.querySelectorAll('.theme-btn-group .btn').forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.getElementById(`theme-${themeName}-btn`);
        if (activeBtn) activeBtn.classList.add('active');

        // Update Chart Colors
        if (window.liveChartInstance) {
            const isDark = (themeName === 'dark' || themeName === 'sepia');
            const textColor = isDark ? '#cbd5e1' : '#475569';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)';

            window.liveChartInstance.options.plugins.legend.labels.color = textColor;
            window.liveChartInstance.options.scales.x.ticks.color = textColor;
            window.liveChartInstance.options.scales.x.grid.color = gridColor;
            window.liveChartInstance.options.scales.y.ticks.color = textColor;
            window.liveChartInstance.options.scales.y.grid.color = gridColor;
            window.liveChartInstance.update();
        }
    }

    // Load saved theme on load
    document.addEventListener("DOMContentLoaded", function() {
        const savedTheme = localStorage.getItem('wearable_dashboard_theme') || 'dark';
        setDashboardTheme(savedTheme);
    });

    // 2. Live Clock Timer
    function updateClock() {
        const now = new Date();
        let hours = now.getHours();
        let minutes = now.getMinutes();
        let seconds = now.getSeconds();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;
        
        const strTime = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
        const clockEl = document.getElementById('live-clock');
        if (clockEl) clockEl.innerText = strTime;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 3. Auto Reload countdown & pause/play controls
    let isAutoRefreshActive = true;
    let refreshSeconds = 30;
    const countdownEl = document.getElementById('refresh-countdown');

    function toggleAutoRefresh() {
        isAutoRefreshActive = !isAutoRefreshActive;
        const toggleBtn = document.getElementById('auto-refresh-toggle-btn');
        if (toggleBtn) {
            if (isAutoRefreshActive) {
                toggleBtn.className = "btn btn-sm btn-outline-success rounded-pill px-2.5 py-0.5 font-monospace";
                toggleBtn.innerHTML = `<i class="fa-solid fa-pause me-1"></i> <span id="refresh-countdown">${refreshSeconds}s</span>`;
            } else {
                toggleBtn.className = "btn btn-sm btn-outline-warning rounded-pill px-2.5 py-0.5 font-monospace";
                toggleBtn.innerHTML = `<i class="fa-solid fa-play me-1"></i> Paused`;
            }
        }
    }

    setInterval(function() {
        if (!isAutoRefreshActive) return;
        refreshSeconds--;
        if (refreshSeconds <= 0) {
            if (countdownEl) countdownEl.innerText = "Reloading...";
            window.location.reload();
        } else {
            const cdEl = document.getElementById('refresh-countdown');
            if (cdEl) cdEl.innerText = `${refreshSeconds}s`;
        }
    }, 1000);

    // 4. Track Unit Search Form Submission via AJAX Modal
    document.addEventListener("DOMContentLoaded", function() {
        const trackForm = document.getElementById('track-qr-unit-form');
        if (trackForm) {
            trackForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const qrCode = document.getElementById('track-qr-unit-input').value.trim();
                if (!qrCode) return;

                const modalBody = document.getElementById('track-qr-modal-body');
                modalBody.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-dash-sub">Searching unit lifecycle for <strong>${qrCode}</strong>...</p>
                    </div>
                `;

                const modal = new bootstrap.Modal(document.getElementById('trackQrUnitModal'));
                modal.show();

                fetch('<?= base_url('company/production/verify-qr') ?>?qr_code=' + encodeURIComponent(qrCode) + '&batch_id=<?= $order['id'] ?>')
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            modalBody.innerHTML = `
                                <div class="alert alert-warning border-0 rounded-3 p-3">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> ${data.message || 'No scan logs found for this QR code tag.'}
                                </div>
                            `;
                            return;
                        }

                        let logsHtml = '';
                        if (data.logs && data.logs.length > 0) {
                            logsHtml = data.logs.map(log => `
                                <div class="d-flex align-items-center justify-content-between p-3 mb-2 rounded-3" style="background: var(--input-bg); border: 1px solid var(--input-border);">
                                    <div>
                                        <h6 class="m-0 fw-bold text-primary font-monospace">${log.stage}</h6>
                                        <small class="text-dash-sub">Operator: <strong>${log.operator_name}</strong> (${log.operator_role})</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge ${log.status === 'PASS' ? 'bg-success' : 'bg-danger'} font-monospace px-2.5 py-1 mb-1">${log.status}</span>
                                        <div class="small text-dash-muted font-monospace">${log.updated_at}</div>
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            logsHtml = `<div class="text-center py-3 text-dash-sub">No recorded stage movement yet for ${qrCode}.</div>`;
                        }

                        modalBody.innerHTML = `
                            <div class="mb-3 pb-2 border-bottom" style="border-color: var(--table-row-border) !important;">
                                <h6 class="fw-bold m-0 font-monospace text-dash-main">Tag: ${data.qr_code}</h6>
                                <span class="small text-dash-sub">Total Stages Passed: <strong>${data.total_stages || 0}</strong></span>
                            </div>
                            <div class="custom-scroll" style="max-height: 380px; overflow-y: auto;">
                                ${logsHtml}
                            </div>
                        `;
                    })
                    .catch(err => {
                        modalBody.innerHTML = `<div class="alert alert-danger">Error retrieving unit log history.</div>`;
                    });
            });
        }
    });

    // 5. Render Chart.js line graph
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('liveStageChart').getContext('2d');
        
        const stagesLabels = [
            <?php foreach ($stagesList as $stg): ?>
                "<?= str_replace('_', ' ', ucfirst($stg)) ?>",
            <?php endforeach; ?>
        ];

        const completedData = [
            <?php foreach ($stagesList as $stg): ?>
                <?= isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['out'] : 0 ?>,
            <?php endforeach; ?>
        ];

        const targetData = Array(stagesLabels.length).fill(<?= $targetQty ?>);

        window.liveChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: stagesLabels,
                datasets: [
                    {
                        label: 'Completed Output (pcs)',
                        data: completedData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.15)',
                        borderWidth: 3,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Batch Target (pcs)',
                        data: targetData,
                        borderColor: 'rgba(148, 163, 184, 0.4)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#94a3b8',
                            font: { family: 'Outfit', size: 12, weight: '600' },
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#ffffff',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.08)' },
                        ticks: { color: '#94a3b8', font: { family: 'Outfit', size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.08)' },
                        ticks: { color: '#94a3b8', font: { family: 'Outfit', size: 11 } }
                    }
                }
            }
        });
    });
</script>

<!-- Security Confirmation DELETE Prompt Modal -->
<div class="modal fade text-start" id="securityDeleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg text-main" style="border-radius: 16px; background: var(--card-bg); color: var(--text-main); border: 1px solid var(--card-border);">
            <form id="securityDeleteConfirmForm" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Security Confirmation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <p class="mb-2 text-dash-main fw-semibold" id="securityDeleteModalTargetText">Are you sure you want to delete this record?</p>
                    <div class="alert alert-warning border-0 rounded-3 p-3 text-secondary small mb-3" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> This action cannot be undone. To proceed, please type <strong class="text-danger font-monospace">DELETE</strong> in the box below.
                    </div>
                    <label class="form-label fw-semibold small text-dash-sub">Confirmation Phrase:</label>
                    <input type="text" id="securityDeleteConfirmInput" name="confirm_code" class="form-control form-control-lg font-monospace text-center fw-bold qr-lookup-input" placeholder="Type DELETE to confirm" autocomplete="off" required style="letter-spacing: 2px;">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="securityDeleteSubmitBtn" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold" disabled>
                        <i class="fa-solid fa-trash me-1"></i> Confirm Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function triggerSecurityDeleteModal(actionUrl, targetMessage) {
        const form = document.getElementById('securityDeleteConfirmForm');
        const textEl = document.getElementById('securityDeleteModalTargetText');
        const inputEl = document.getElementById('securityDeleteConfirmInput');
        const btnEl = document.getElementById('securityDeleteSubmitBtn');

        if (form && inputEl && btnEl) {
            form.action = actionUrl;
            if (textEl) textEl.innerText = targetMessage || 'Are you sure you want to proceed with deletion?';
            inputEl.value = '';
            btnEl.disabled = true;

            const modal = new bootstrap.Modal(document.getElementById('securityDeleteConfirmModal'));
            modal.show();

            inputEl.oninput = function() {
                btnEl.disabled = (inputEl.value.trim() !== 'DELETE');
            };
        }
    }
</script>
