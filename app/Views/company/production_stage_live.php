<div id="live-dashboard-container" class="container-fluid py-4 min-vh-100 px-3 px-md-4" style="font-family: 'Outfit', sans-serif; background-color: #090d16 !important; color: #f8fafc !important;">
    
    <!-- Full Dashboard CSS Overrides for Dark Permanent Theme & High Contrast -->
    <style>
        html, body {
            background-color: #090d16 !important;
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100vh;
            color: #f8fafc !important;
        }

        :root {
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
            --input-placeholder: #94a3b8;
            --toolbar-bg: #111827;
            --toolbar-border: #1f2937;
            --table-header-bg: #0f172a;
            --table-row-border: rgba(255, 255, 255, 0.08);
            --table-hover-bg: rgba(255, 255, 255, 0.04);
            --color-primary: #3b82f6;
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-danger: #ef4444;
            --color-info: #06b6d4;
        }

        .live-card {
            background-color: #131a29 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            transition: border-color 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
        }
        .live-card:hover {
            border-color: rgba(99, 102, 241, 0.5) !important;
            transform: translateY(-2px);
            box-shadow: 0 14px 35px rgba(0, 0, 0, 0.35);
        }

        .text-dash-main { color: #f8fafc !important; }
        .text-dash-sub { color: #94a3b8 !important; }
        .text-dash-muted { color: #64748b !important; }

        /* Icon Badge Styles */
        .icon-badge {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            line-height: 1;
            background: transparent;
        }
        .icon-badge-primary { background: rgba(59, 130, 246, 0.18) !important; color: #3b82f6 !important; }
        .icon-badge-success { background: rgba(16, 185, 129, 0.18) !important; color: #10b981 !important; }
        .icon-badge-warning { background: rgba(245, 158, 11, 0.18) !important; color: #f59e0b !important; }
        .icon-badge-danger { background: rgba(239, 68, 68, 0.18) !important; color: #ef4444 !important; }

        .font-outfit { font-family: 'Outfit', sans-serif; }

        /* Minimal QR Bar */
        .qr-lookup-bar {
            background-color: #131a29 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 16px;
        }
        .qr-lookup-input {
            background-color: #0f172a !important;
            border: 1px solid #334155 !important;
            color: #ffffff !important;
            border-radius: 10px;
            font-size: 13.5px;
        }
        .qr-lookup-input::placeholder {
            color: #94a3b8 !important;
            opacity: 0.9;
        }
        .qr-lookup-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3) !important;
            border-color: #3b82f6 !important;
        }

        /* Fix Table Background & Row Colors for Permanent Dark Theme */
        .table-dark-custom {
            background-color: #131a29 !important;
            color: #f8fafc !important;
        }
        .table-dark-custom thead th {
            background-color: #0f172a !important;
            color: #94a3b8 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        .table-dark-custom tbody td, .table-dark-custom tbody tr {
            background-color: #131a29 !important;
            color: #f8fafc !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
        }
        .table-dark-custom tbody tr:hover td {
            background-color: rgba(255, 255, 255, 0.04) !important;
        }

        /* Custom Scrollbar for Real-Time Activity Feed */
        .custom-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 10px;
        }

        .pulse-live-dot {
            width: 8px;
            height: 8px;
            background-color: #ffffff;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
            animation: pulse-white 1.6s infinite;
        }
        @keyframes pulse-white {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(255, 255, 255, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
        }
    </style>

    <!-- TOP NAVIGATION & HEADER BAR -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-lg-7 col-12">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <a href="<?= base_url('company/production/orders') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 text-white fw-semibold" style="font-size: 11.5px; border-color: rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.05);">
                    <i class="fa-solid fa-arrow-left me-1"></i> Orders
                </a>
                
                <!-- B) Crisp White Text on Top Header Badges -->
                <div class="badge bg-primary text-white rounded-pill px-3 py-1.5 fw-bold text-uppercase d-inline-flex align-items-center gap-2 shadow-sm" style="font-size: 0.68rem; letter-spacing: 0.05em; background: #2563eb !important; color: #ffffff !important;">
                    <span class="pulse-live-dot"></span> LIVE MONITORING
                </div>
                
                <span class="badge text-white font-monospace fw-bold px-3 py-1.5 rounded-pill shadow-sm" style="font-size: 11px; background: #3b82f6 !important; color: #ffffff !important;">
                    <i class="fa-solid fa-layer-group me-1"></i> <?= htmlspecialchars($order['production_no']) ?>
                </span>
                
                <?php if (!empty($order['buyer_po_no'])): ?>
                    <span class="badge text-white font-monospace fw-bold px-3 py-1.5 rounded-pill shadow-sm" style="font-size: 11px; background: #334155 !important; color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.15);">
                        PO: <?= htmlspecialchars($order['buyer_po_no']) ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <h3 class="m-0 fw-bold font-outfit text-white" style="letter-spacing: -0.02em;">
                <?= htmlspecialchars($order['style_name']) ?> 
                <span class="fs-5 text-dash-sub fw-normal font-monospace">(<?= htmlspecialchars($order['style_no']) ?>)</span>
            </h3>
            <p class="text-dash-sub small mt-1 mb-0" style="font-size: 12.5px;">
                Fabric: <strong class="text-white"><?= htmlspecialchars($order['fabric_composition'] ?: 'Standard Garment Fabric') ?></strong> | 
                Category: <strong class="text-white text-uppercase"><?= htmlspecialchars($order['style_category'] ?? 'Unisex') ?></strong>
            </p>
        </div>

        <!-- Right Side Controls: Instant Sync Indicator & Live Clock -->
        <div class="col-lg-5 col-12 text-lg-end text-start">
            <div class="d-inline-flex align-items-center gap-3 p-2.5 rounded-pill shadow-sm" style="background: #111827; border: 1px solid #1f2937;">
                <div class="d-inline-flex align-items-center gap-2 px-2">
                    <span class="badge bg-success text-white px-2.5 py-1 rounded-pill fw-bold font-monospace" style="font-size: 11px;">
                        <i class="fa-solid fa-bolt me-1"></i> Instant Sync (Live)
                    </span>
                    <span class="fw-bold text-white font-monospace" id="live-clock">00:00:00 AM</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN METRICS PANEL (4 CLEAN EQUAL CARDS WITH PROPER LEFT PADDING ALIGNMENT) -->
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
        <!-- 1. Production Target Card (Point A: ps-4 padding to move text right) -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3.5 h-100 d-flex align-items-center justify-content-between" style="border-left: 4px solid var(--color-primary) !important;">
                <div class="ps-3">
                    <span class="text-dash-sub small fw-bold text-uppercase d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">PRODUCTION TARGET</span>
                    <h3 class="m-0 fw-bold font-outfit text-white" id="stat-target-qty"><?= number_format($targetQty) ?> <span class="fs-6 fw-normal text-dash-sub">pcs</span></h3>
                </div>
                <div class="icon-badge icon-badge-primary me-2">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
            </div>
        </div>

        <!-- 2. Packaged / Completed Card (Point A: ps-4 padding to move text right) -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3.5 h-100 d-flex align-items-center justify-content-between" style="border-left: 4px solid var(--color-success) !important;">
                <div class="ps-3">
                    <span class="text-dash-sub small fw-bold text-uppercase d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">PACKAGED / COMPLETED</span>
                    <h3 class="m-0 fw-bold font-outfit" id="stat-completed-qty" style="color: var(--color-success) !important;"><?= number_format($finishedQty) ?> <span class="fs-6 fw-normal text-dash-sub">(<?= $completionPct ?>%)</span></h3>
                </div>
                <div class="icon-badge icon-badge-success me-2">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>

        <!-- 3. LATEST LIVE SCAN UPDATE CARD (Point A: ps-4 padding to move text right) -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3.5 h-100 d-flex flex-column justify-content-between" style="border-left: 4px solid var(--color-primary) !important;">
                <div class="ps-3 pe-2" id="stat-latest-scan-container">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-dash-sub small fw-bold text-uppercase d-block" style="font-size: 0.68rem; letter-spacing: 0.05em;">LATEST LIVE SCAN UPDATE</span>
                        <?php if ($latestLog): ?>
                            <?php $timeAgoStr = \App\Helpers\TimezoneHelper::timeAgo($latestLog['created_at'] ?? 'now'); ?>
                            <span class="badge bg-primary bg-opacity-20 text-primary font-monospace" style="font-size: 9.5px;"><?= $timeAgoStr ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($latestLog): ?>
                        <h6 class="m-0 fw-bold font-outfit text-white text-truncate" style="font-size: 14px;">
                            Stage: <span class="text-uppercase font-monospace text-primary"><?= str_replace('_', ' ', $latestLog['stage']) ?></span>
                        </h6>
                        <div class="text-dash-sub small mt-1" style="font-size: 11.5px; line-height: 1.35;">
                            By: <strong class="text-white"><?= htmlspecialchars($latestLog['employee_name'] ?: 'Operator') ?></strong> | 
                            Out: <strong class="font-monospace text-success"><?= (int)($latestLog['qty_out'] ?: 1) ?> pcs (<?= strtoupper($latestLog['status'] ?? 'PASS') ?>)</strong>
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
                    <div class="pt-1 text-end border-top mt-1 ps-3 pe-2" style="border-color: var(--table-row-border) !important;">
                        <small class="text-dash-muted font-monospace" style="font-size: 10px;"><i class="fa-regular fa-clock me-1"></i><?= $dtLog->format('d M, h:i A') ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. Cumulative Wastage Card (Point A: ps-4 padding to move text right) -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3.5 h-100 d-flex align-items-center justify-content-between" style="border-left: 4px solid var(--color-danger) !important;">
                <div class="ps-3">
                    <span class="text-dash-sub small fw-bold text-uppercase d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">CUMULATIVE WASTAGE</span>
                    <h3 class="m-0 fw-bold font-outfit" id="stat-waste-qty" style="color: var(--color-danger) !important;"><?= number_format($totalWaste) ?> <span class="fs-6 fw-normal text-dash-sub">(<?= $wastePct ?>%)</span></h3>
                </div>
                <div class="icon-badge icon-badge-danger me-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- MINIMALISTIC & HIGH-ATTENTION QR CODE TRACKING BAR -->
    <div class="qr-lookup-bar p-3 mb-4">
        <form id="track-qr-unit-form" class="row align-items-center g-2">
            <div class="col-md-4 col-12">
                <div class="d-flex align-items-center gap-2.5 ps-2">
                    <div class="icon-badge icon-badge-primary" style="width: 36px; height: 36px; min-width: 36px; font-size: 1rem; border-radius: 10px;">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-white m-0 font-outfit" style="font-size: 13.5px;">Track Unit Status by QR Code</h6>
                        <span class="text-dash-sub small" style="font-size: 11px;">Instant unit lifecycle lookup for this batch</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-8">
                <div class="input-group input-group-sm">
                    <span class="input-group-text border-end-0 bg-transparent text-primary" style="border-color: #334155; border-top-left-radius: 10px; border-bottom-left-radius: 10px;">
                        <i class="fa-solid fa-barcode"></i>
                    </span>
                    <input type="text" id="track-qr-unit-input" class="form-control qr-lookup-input font-monospace fw-bold ps-1" placeholder="Scan or type QR Code e.g. PO-TOCCO-2026-001-S-0001" required style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                </div>
            </div>
            <div class="col-md-2 col-4 text-end pe-3">
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
                    <h6 class="m-0 fw-bold font-outfit text-white"><i class="fa-solid fa-chart-line text-primary me-2"></i> Stage Production Completion vs Target</h6>
                    <span class="small text-dash-sub" style="font-size: 11.5px;">All Active WIP Stages</span>
                </div>
                <div style="height: 310px;">
                    <canvas id="liveStageChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Real-Time Activity Feed -->
        <div class="col-lg-4 col-12">
            <div class="live-card p-4 h-100 d-flex flex-column">
                <h6 class="mb-3 fw-bold font-outfit text-white"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i> Activity Feed</h6>
                <div class="flex-grow-1 overflow-y-auto pe-1 custom-scroll" id="activity-feed-container" style="max-height: 300px;">
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
                                <div class="list-group-item bg-transparent text-dash-main px-0 py-2" style="border-color: var(--table-row-border) !important;">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                        <span class="badge <?= $badgeClass ?> font-monospace fw-bold" style="font-size: 0.65rem; padding: 0.2em 0.5em;"><?= $statusText ?></span>
                                        <small class="text-dash-muted font-monospace" style="font-size: 10.5px;"><?= $formattedLogTime ?></small>
                                    </div>
                                    <p class="mb-0 small text-white" style="font-size: 12.5px;">
                                        Stage <strong class="text-capitalize text-primary"><?= str_replace('_', ' ', $log['stage']) ?></strong>
                                    </p>
                                    <small class="text-dash-sub d-block font-monospace mt-0.5" style="font-size: 10.5px;">
                                        Tag: <span class="text-white"><?= htmlspecialchars($tagLabel) ?></span> | 
                                        By: <span class="text-white"><?= htmlspecialchars($log['employee_name'] ?: 'System') ?></span>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-dash-sub">
                            <i class="fa-solid fa-wave-square fs-3 mb-2 opacity-50"></i>
                            <p class="small m-0">Awaiting live barcode/RFID scans...</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Point D: LIVE STAGE-WISE PIPELINE TABLE IN PERMANENT DARK BACKGROUND -->
    <div class="row">
        <div class="col-12">
            <div class="live-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="m-0 fw-bold font-outfit text-white"><i class="fa-solid fa-list-check text-primary me-2"></i> Stage-Wise Pipeline Inventory Summary</h6>
                    <span class="badge text-dash-sub font-monospace small px-3 py-1 rounded-pill" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">Total Stages: <?= count($stagesList) ?></span>
                </div>
                <div class="table-responsive border-0" style="background-color: #131a29; border-radius: 12px;">
                    <table class="table table-hover table-dark-custom mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>STAGE NAME</th>
                                <th class="text-center">QTY IN (TOTAL)</th>
                                <th class="text-center">QTY OUT (COMPLETED)</th>
                                <th class="text-center">WASTAGE COUNT</th>
                                <th class="text-center">WIP BALANCE STOCK</th>
                                <th class="text-end" style="width: 25%;">STAGE PROGRESS</th>
                            </tr>
                        </thead>
                        <tbody id="pipelineTableBody">
                            <?php foreach ($stagesList as $stg): ?>
                                <?php 
                                    $in = isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['in'] : 0;
                                    $out = isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['out'] : 0;
                                    $waste = isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['waste'] : 0;
                                    $bal = isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['wip_balance'] : 0;
                                    $prog = $targetQty > 0 ? min(100, round(($out / $targetQty) * 100, 1)) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-white text-capitalize text-nowrap" style="font-size: 13px;"><?= str_replace('_', ' ', $stg) ?></span>
                                    </td>
                                    <td class="text-center font-monospace" id="stg-in-<?= $stg ?>">
                                        <?php if ($in > 0): ?>
                                            <span class="badge bg-primary text-white font-monospace px-2.5 py-1 shadow-sm" style="font-size: 0.8rem;"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i><?= number_format($in) ?></span>
                                        <?php else: ?>
                                            <span class="text-dash-muted opacity-50 font-monospace">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-monospace" id="stg-out-<?= $stg ?>">
                                        <?php if ($out > 0): ?>
                                            <span class="badge bg-success text-white font-monospace px-2.5 py-1 shadow-sm" style="font-size: 0.8rem;"><i class="fa-solid fa-check me-1"></i><?= number_format($out) ?></span>
                                        <?php else: ?>
                                            <span class="text-dash-muted opacity-50 font-monospace">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-monospace" id="stg-waste-<?= $stg ?>">
                                        <?php if ($waste > 0): ?>
                                            <span class="badge bg-danger text-white font-monospace px-2.5 py-1 shadow-sm" style="font-size: 0.8rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i><?= number_format($waste) ?></span>
                                        <?php else: ?>
                                            <span class="text-dash-muted opacity-50 font-monospace">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-monospace" id="stg-bal-<?= $stg ?>">
                                        <?php if ($bal > 0): ?>
                                            <span class="badge bg-warning text-dark font-monospace px-2.5 py-1 shadow-sm fw-bold" style="font-size: 0.8rem;"><i class="fa-solid fa-boxes-stacked me-1"></i><?= number_format($bal) ?></span>
                                        <?php else: ?>
                                            <span class="text-dash-muted opacity-50 font-monospace">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2.5">
                                            <div class="progress w-100" style="height: 7px; background-color: #0f172a; border-radius: 4px; overflow: hidden;">
                                                <div class="progress-bar bg-primary rounded-pill" id="stg-bar-<?= $stg ?>" role="progressbar" style="width: <?= $prog ?>%;" aria-valuenow="<?= $prog ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="small font-monospace fw-bold text-dash-sub text-nowrap" id="stg-pct-<?= $stg ?>" style="font-size: 11.5px;"><?= $prog ?>%</span>
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
        <div class="modal-content text-start" style="border-radius: 16px; background: #131a29 !important; color: #f8fafc !important; border: 1px solid rgba(255,255,255,0.1) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-qrcode text-primary me-2"></i> Complete Unit Stage Lifecycle History</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-start" id="track-qr-modal-body">
                <div class="text-center py-4">
                    <span class="spinner-border text-primary" role="status"></span>
                    <p class="mt-2 text-dash-sub">Fetching stage lifecycle logs...</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 text-white" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Live Clock Timer
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

    // Chart.js Setup
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
                        borderColor: '#ef4444',
                        borderWidth: 2,
                        borderDash: [6, 4],
                        pointRadius: 0,
                        fill: false,
                        tension: 0
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
                            color: '#cbd5e1',
                            font: { family: 'Outfit', size: 11, weight: 'bold' }
                        }
                    },
                    tooltip: {
                        padding: 10,
                        backgroundColor: '#0f172a',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: '#334155',
                        borderWidth: 1,
                        titleFont: { family: 'Outfit', weight: 'bold' },
                        bodyFont: { family: 'Outfit' }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.08)' },
                        ticks: { color: '#cbd5e1', font: { family: 'Outfit', size: 10.5, weight: 'bold' } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.08)' },
                        ticks: { color: '#cbd5e1', font: { family: 'Outfit', size: 10.5, weight: 'bold' } },
                        beginAtZero: true
                    }
                }
            }
        });

        // Point E: INSTANT REAL-TIME AJAX LIVE DATA POLLING ENGINE (Every 3 seconds)
        function fetchLiveDashboardData() {
            fetch("<?= base_url('company/production/stage/' . $order['id'] . '/live-api') ?>")
                .then(res => res.json())
                .then(data => {
                    if (!data || !data.success) return;

                    // 1. Update Metric Cards
                    const targetEl = document.getElementById('stat-target-qty');
                    if (targetEl) targetEl.innerHTML = `${data.target_qty} <span class="fs-6 fw-normal text-dash-sub">pcs</span>`;

                    const completedEl = document.getElementById('stat-completed-qty');
                    if (completedEl) completedEl.innerHTML = `${data.finished_qty} <span class="fs-6 fw-normal text-dash-sub">(${data.completion_pct}%)</span>`;

                    const wasteEl = document.getElementById('stat-waste-qty');
                    if (wasteEl) wasteEl.innerHTML = `${data.total_waste} <span class="fs-6 fw-normal text-dash-sub">(${data.waste_pct}%)</span>`;

                    // 2. Update Latest Scan Update Card
                    const latestScanContainer = document.getElementById('stat-latest-scan-container');
                    if (latestScanContainer) {
                        if (data.latest_log) {
                            const log = data.latest_log;
                            const statusClass = (log.qty_out > 0) ? 'text-success' : 'text-danger';
                            const statusText = (log.qty_out > 0) ? 'PASS' : 'FAIL';
                            latestScanContainer.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-dash-sub small fw-bold text-uppercase d-block" style="font-size: 0.68rem; letter-spacing: 0.05em;">LATEST LIVE SCAN UPDATE</span>
                                    <span class="badge bg-primary bg-opacity-20 text-primary font-monospace" style="font-size: 9.5px;">${log.time_ago}</span>
                                </div>
                                <h6 class="m-0 fw-bold font-outfit text-white text-truncate" style="font-size: 14px;">
                                    Stage: <span class="text-uppercase font-monospace text-primary">${log.stage_clean}</span>
                                </h6>
                                <div class="text-dash-sub small mt-1" style="font-size: 11.5px; line-height: 1.35;">
                                    By: <strong class="text-white">${log.employee_name || 'Operator'}</strong> | 
                                    Out: <strong class="font-monospace ${statusClass}">${log.qty_out || 1} pcs (${statusText})</strong>
                                </div>
                            `;
                        }
                    }

                    // 3. Update Chart.js
                    if (window.liveChartInstance && data.stages_list && data.wip_summary) {
                        const updatedCompletedData = data.stages_list.map(stg => (data.wip_summary[stg] ? parseInt(data.wip_summary[stg].out) || 0 : 0));
                        window.liveChartInstance.data.datasets[0].data = updatedCompletedData;
                        window.liveChartInstance.update('none'); // smooth update without full chart redraw
                    }

                    // 4. Update Activity Feed List
                    const activityFeedContainer = document.getElementById('activity-feed-container');
                    if (activityFeedContainer && data.recent_logs) {
                        if (data.recent_logs.length > 0) {
                            let feedHtml = '<div class="list-group list-group-flush bg-transparent">';
                            data.recent_logs.forEach(l => {
                                const isPass = (l.qty_out > 0);
                                const badgeClass = isPass ? 'bg-success text-white' : 'bg-danger text-white';
                                const statusText = isPass ? 'PASS' : 'FAIL';
                                const tagLabel = l.machine_name || (l.qr_code || 'Manual');
                                const isEdited = l.edited_by_name && l.edited_at_formatted;
                                let editBadgeHtml = '';
                                if (isEdited) {
                                    editBadgeHtml = `
                                        <small class="d-block font-monospace mt-1" style="color: #facc15 !important; font-size: 10px; line-height: 1.2;">
                                            <i class="fa-solid fa-pen-to-square me-1 text-warning"></i> Edited by <strong class="text-white">${l.edited_by_name}</strong> on ${l.edited_at_formatted}${l.edit_remarks ? ' - "' + l.edit_remarks + '"' : ''}
                                        </small>
                                    `;
                                }
                                feedHtml += `
                                    <div class="list-group-item bg-transparent text-dash-main px-0 py-2" style="border-color: var(--table-row-border) !important;">
                                        <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                            <span class="badge ${badgeClass} font-monospace fw-bold" style="font-size: 0.65rem; padding: 0.2em 0.5em;">${statusText}</span>
                                            <small class="text-dash-muted font-monospace" style="font-size: 10.5px;">${l.formatted_time}</small>
                                        </div>
                                        <p class="mb-0 small text-white" style="font-size: 12.5px;">
                                            Stage <strong class="text-capitalize text-primary">${l.stage_clean}</strong>
                                        </p>
                                        <small class="text-dash-sub d-block font-monospace mt-0.5" style="font-size: 10.5px;">
                                            Tag: <span class="text-white">${tagLabel}</span> | 
                                            By: <span class="text-white">${l.employee_name || 'System'}</span>
                                        </small>
                                        ${editBadgeHtml}
                                    </div>
                                `;
                            });
                            feedHtml += '</div>';
                            activityFeedContainer.innerHTML = feedHtml;
                        }
                    }

                    // 5. Update Pipeline Inventory Table
                    if (data.stages_list && data.wip_summary) {
                        const targetNum = parseInt(data.target_qty.replace(/,/g, '')) || 0;
                        data.stages_list.forEach(stg => {
                            const inVal = data.wip_summary[stg] ? parseInt(data.wip_summary[stg].in) || 0 : 0;
                            const outVal = data.wip_summary[stg] ? parseInt(data.wip_summary[stg].out) || 0 : 0;
                            const wasteVal = data.wip_summary[stg] ? parseInt(data.wip_summary[stg].waste) || 0 : 0;
                            const balVal = data.wip_summary[stg] ? parseInt(data.wip_summary[stg].wip_balance) || 0 : 0;
                            const progVal = targetNum > 0 ? Math.min(100, Math.round((outVal / targetNum) * 1000) / 10) : 0;

                            const inEl = document.getElementById(`stg-in-${stg}`);
                            if (inEl) inEl.innerHTML = inVal > 0 ? `<span class="badge bg-primary text-white font-monospace px-2.5 py-1 shadow-sm" style="font-size: 0.8rem;"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i>${inVal.toLocaleString()}</span>` : `<span class="text-dash-muted opacity-50 font-monospace">0</span>`;

                            const outEl = document.getElementById(`stg-out-${stg}`);
                            if (outEl) outEl.innerHTML = outVal > 0 ? `<span class="badge bg-success text-white font-monospace px-2.5 py-1 shadow-sm" style="font-size: 0.8rem;"><i class="fa-solid fa-check me-1"></i>${outVal.toLocaleString()}</span>` : `<span class="text-dash-muted opacity-50 font-monospace">0</span>`;

                            const wasteElRow = document.getElementById(`stg-waste-${stg}`);
                            if (wasteElRow) wasteElRow.innerHTML = wasteVal > 0 ? `<span class="badge bg-danger text-white font-monospace px-2.5 py-1 shadow-sm" style="font-size: 0.8rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i>${wasteVal.toLocaleString()}</span>` : `<span class="text-dash-muted opacity-50 font-monospace">0</span>`;

                            const balEl = document.getElementById(`stg-bal-${stg}`);
                            if (balEl) balEl.innerHTML = balVal > 0 ? `<span class="badge bg-warning text-dark font-monospace px-2.5 py-1 shadow-sm fw-bold" style="font-size: 0.8rem;"><i class="fa-solid fa-boxes-stacked me-1"></i>${balVal.toLocaleString()}</span>` : `<span class="text-dash-muted opacity-50 font-monospace">0</span>`;

                            const barEl = document.getElementById(`stg-bar-${stg}`);
                            if (barEl) barEl.style.width = `${progVal}%`;

                            const pctEl = document.getElementById(`stg-pct-${stg}`);
                            if (pctEl) pctEl.innerText = `${progVal}%`;
                        });
                    }
                })
                .catch(err => console.error("Live Polling Error:", err));
        }

        // Poll every 3 seconds for instant real-time sync
        setInterval(fetchLiveDashboardData, 3000);

        // 6. Track QR Code Form Event
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
                        <p class="mt-2 text-dash-sub small font-monospace">Fetching complete lifecycle history for <strong>${qrCode}</strong>...</p>
                    </div>
                `;
                modal.show();

                fetch(`<?= base_url('company/production/track-qr-unit') ?>?qr_code=${encodeURIComponent(qrCode)}&batch_id=<?= $order['id'] ?>`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.logs && data.logs.length > 0) {
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
                                <div class="table-responsive border-0" style="background: #0f172a; border-radius: 12px;">
                                    <table class="table table-hover table-dark-custom align-middle mb-0" style="font-size: 12.5px; background-color: #0f172a !important; color: #f8fafc !important; --bs-table-bg: #0f172a; --bs-table-color: #f8fafc; --bs-table-border-color: rgba(255,255,255,0.08);">
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
                                    <p class="small text-dash-sub mb-0">No operational logs recorded yet for item tag <strong>${qrCode}</strong> in this batch.</p>
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
