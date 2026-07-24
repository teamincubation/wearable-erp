<div id="live-dashboard-container" class="container-fluid py-4 min-vh-100 px-3 px-md-4" data-theme="dark" style="font-family: 'Outfit', sans-serif; transition: background 0.4s ease, color 0.4s ease;">
    
    <!-- Theme Switcher & Dashboard CSS Custom Properties -->
    <style>
        :root, [data-theme="dark"] {
            --bg-main: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --body-bg: #0f172a;
            --card-bg: #1e293b;
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --text-muted: #64748b;
            --input-bg: #0f172a;
            --input-border: #334155;
            --input-text: #f8fafc;
            --toolbar-bg: #090d16;
            --toolbar-border: #1e293b;
            --table-header-bg: #0f172a;
            --table-row-border: rgba(255, 255, 255, 0.05);
            --neon-green: #4ade80;
            --neon-cyan: #22d3ee;
            --neon-amber: #fbbf24;
        }

        [data-theme="sepia"] {
            --bg-main: linear-gradient(135deg, #2d241e 0%, #43352b 100%);
            --body-bg: #2d241e;
            --card-bg: #3c3027;
            --card-border: rgba(255, 248, 230, 0.12);
            --text-main: #f5ebe0;
            --text-sub: #d5c5b5;
            --text-muted: #aa9785;
            --input-bg: #2b221b;
            --input-border: #574639;
            --input-text: #f5ebe0;
            --toolbar-bg: #1e1713;
            --toolbar-border: #43352b;
            --table-header-bg: #2b221b;
            --table-row-border: rgba(255, 248, 230, 0.08);
            --neon-green: #86efac;
            --neon-cyan: #67e8f9;
            --neon-amber: #fde047;
        }

        [data-theme="bright"] {
            --bg-main: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --body-bg: #f8fafc;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --text-main: #0f172a;
            --text-sub: #475569;
            --text-muted: #64748b;
            --input-bg: #f1f5f9;
            --input-border: #cbd5e1;
            --input-text: #0f172a;
            --toolbar-bg: #ffffff;
            --toolbar-border: #cbd5e1;
            --table-header-bg: #f1f5f9;
            --table-row-border: #e2e8f0;
            --neon-green: #16a34a;
            --neon-cyan: #0284c7;
            --neon-amber: #d97706;
        }

        #live-dashboard-container {
            background: var(--bg-main) !important;
            color: var(--text-main) !important;
        }

        .live-card {
            background: var(--card-bg) !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        .live-card:hover {
            border-color: rgba(99, 102, 241, 0.4) !important;
            transform: translateY(-2px);
        }

        .text-dash-main { color: var(--text-main) !important; }
        .text-dash-sub { color: var(--text-sub) !important; }
        .text-dash-muted { color: var(--text-muted) !important; }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        .animate-pulse-slow {
            animation: pulse-slow 3s infinite;
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .font-outfit { font-family: 'Outfit', sans-serif; }

        /* Theme Selector Buttons */
        .theme-btn-group .btn {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            transition: all 0.2s ease;
        }
        .theme-btn-group .btn.active {
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.4);
        }

        /* Minimal QR Bar */
        .qr-lookup-bar {
            background: var(--card-bg);
            border: 1.5px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .qr-lookup-input {
            background: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--input-text) !important;
            border-radius: 10px;
            font-size: 13.5px;
        }
        .qr-lookup-input:focus {
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
            border-color: #3b82f6 !important;
        }
    </style>

    <!-- TOP HEADER BAR WITH THEME SWITCHER -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-lg-6 col-12">
            <div class="d-flex align-items-center gap-2.5">
                <div class="bg-primary text-white rounded-pill px-3 py-1 fw-bold text-uppercase d-inline-flex align-items-center gap-1.5 animate-pulse-slow" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                    <span class="d-inline-block rounded-circle bg-white" style="width: 7px; height: 7px;"></span> Live Monitoring
                </div>
                <h3 class="m-0 fw-bold font-outfit text-dash-main" style="letter-spacing: -0.02em;">Operations Stage Live Dashboard</h3>
            </div>
            <p class="text-dash-sub small mt-1 mb-0" style="font-size: 12.5px;">
                Batch: <strong class="text-dash-main font-monospace"><?= htmlspecialchars($order['production_no']) ?></strong> | 
                Style: <strong class="text-dash-main"><?= htmlspecialchars($order['style_no']) ?> (<?= htmlspecialchars($order['style_name']) ?>)</strong> | 
                Fabric: <strong class="text-dash-main"><?= htmlspecialchars($order['fabric_composition'] ?: 'Standard Export Fabric') ?></strong>
            </p>
        </div>

        <div class="col-lg-6 col-12 text-lg-end text-start">
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

                <!-- Clock & Refresh Timer -->
                <div class="d-inline-flex align-items-center gap-2 px-2" style="font-size: 12px;">
                    <span class="fw-semibold text-dash-sub font-monospace" id="refresh-countdown">30s</span>
                    <button onclick="window.location.reload();" class="btn btn-sm btn-outline-secondary rounded-circle p-1 text-dash-sub border-0" title="Refresh Now">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                    <span class="fw-bold text-dash-main font-monospace ms-1" id="live-clock">00:00:00 AM</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN METRICS PANEL (4 CLEAN EQUAL CARDS) -->
    <?php
        // Calculate key metrics
        $targetQty = (int)$order['target_qty'];
        
        // Cumulative output of last active stage
        $lastStage = end($stagesList);
        reset($stagesList);
        $finishedQty = isset($wip_summary[$lastStage]) ? (int)$wip_summary[$lastStage]['out'] : 0;
        $completionPct = $targetQty > 0 ? round(($finishedQty / $targetQty) * 100, 1) : 0;

        // Sum of all waste counts
        $totalWaste = 0;
        foreach ($stagesList as $stg) {
            $totalWaste += (isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['waste'] : 0);
        }
        $wastePct = $targetQty > 0 ? round(($totalWaste / $targetQty) * 100, 1) : 0;

        // Latest Live WIP Activity
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
                <div class="stat-icon bg-primary bg-opacity-15 text-primary">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
            </div>
        </div>

        <!-- 2. Packaged / Completed Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3.5 h-100 d-flex align-items-center justify-content-between" style="border-left: 4px solid #10b981 !important;">
                <div>
                    <span class="text-dash-sub small fw-bold text-uppercase d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">Packaged / Completed</span>
                    <h3 class="m-0 fw-bold font-outfit text-success"><?= number_format($finishedQty) ?> <span class="fs-6 fw-normal text-dash-sub">(<?= $completionPct ?>%)</span></h3>
                </div>
                <div class="stat-icon bg-success bg-opacity-15 text-success">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>

        <!-- 3. LATEST LIVE WIP UPDATE CARD (REPLACES ACTIVE WIP STOCK CARD) -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3.5 h-100 d-flex flex-column justify-content-between" style="border-left: 4px solid #3b82f6 !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-dash-sub small fw-bold text-uppercase d-block" style="font-size: 0.68rem; letter-spacing: 0.05em;">Latest Live WIP Update</span>
                        <?php if ($latestLog): ?>
                            <?php $timeAgoStr = \App\Helpers\TimezoneHelper::timeAgo($latestLog['created_at'] ?? 'now'); ?>
                            <span class="badge bg-primary bg-opacity-20 text-primary font-monospace" style="font-size: 9.5px;"><?= $timeAgoStr ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($latestLog): ?>
                        <h6 class="m-0 fw-bold font-outfit text-dash-main text-truncate" style="font-size: 14px;">
                            Stage: <span class="text-primary text-uppercase font-monospace"><?= str_replace('_', ' ', $latestLog['stage']) ?></span>
                        </h6>
                        <div class="text-dash-sub small mt-1" style="font-size: 11.5px; line-height: 1.35;">
                            By: <strong class="text-dash-main"><?= htmlspecialchars($latestLog['employee_name'] ?: 'Operator') ?></strong> | 
                            Out: <strong class="text-success font-monospace"><?= (int)($latestLog['qty_out'] ?: 1) ?> pcs (<?= strtoupper($latestLog['status'] ?? 'PASS') ?>)</strong>
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
            <div class="live-card p-3.5 h-100 d-flex align-items-center justify-content-between" style="border-left: 4px solid #ef4444 !important;">
                <div>
                    <span class="text-dash-sub small fw-bold text-uppercase d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">Cumulative Waste</span>
                    <h3 class="m-0 fw-bold font-outfit text-danger"><?= number_format($totalWaste) ?> <span class="fs-6 fw-normal text-dash-sub">(<?= $wastePct ?>%)</span></h3>
                </div>
                <div class="stat-icon bg-danger bg-opacity-15 text-danger">
                    <i class="fa-solid fa-dumpster-fire"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- MINIMALISTIC & HIGH-ATTENTION QR CODE TRACKING BAR -->
    <div class="qr-lookup-bar p-3 mb-4">
        <form id="track-qr-unit-form" class="row align-items-center g-2">
            <div class="col-md-4 col-12">
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon bg-primary text-white" style="width: 38px; height: 38px; font-size: 1.1rem; border-radius: 10px;">
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
                    <input type="text" id="track-qr-unit-input" class="form-control qr-lookup-input font-monospace fw-bold ps-1" placeholder="Type or scan QR Code / Serial No e.g. BATCH-03-S-0001" required style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                </div>
            </div>
            <div class="col-md-2 col-4 text-end">
                <button type="submit" id="track-qr-unit-btn" class="btn btn-primary btn-sm w-100 fw-bold rounded-pill shadow-sm" style="font-size: 12px; padding: 6px 14px;">
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
                <div style="height: 310px;">
                    <canvas id="liveStageChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Real-Time Activity Feed -->
        <div class="col-lg-4 col-12">
            <div class="live-card p-4 h-100 d-flex flex-column">
                <h6 class="mb-3 fw-bold font-outfit text-dash-main"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i> Real-Time Activity Feed</h6>
                <div class="flex-grow-1 overflow-y-auto pe-1" style="max-height: 300px;">
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
                                    <p class="mb-0 small text-dash-main" style="font-size: 12.5px;">
                                        Stage <strong class="text-capitalize text-primary"><?= str_replace('_', ' ', $log['stage']) ?></strong>
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
                    <span class="badge bg-light text-dark font-monospace small px-3 py-1 rounded-pill">Total Active Stages: <?= count($stagesList) ?></span>
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
                                        <span class="fw-bold text-dash-main text-capitalize text-nowrap" style="font-size: 13px;"><?= str_replace('_', ' ', $stg) ?></span>
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
                                            <div class="progress w-100" style="height: 6px; background-color: var(--input-bg); border-radius: 4px; overflow: hidden;">
                                                <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: <?= $prog ?>%;" aria-valuenow="<?= $prog ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="small font-monospace fw-bold text-dash-sub text-nowrap" style="font-size: 11px;"><?= $prog ?>%</span>
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
        <div class="modal-content text-start" style="border-radius: 16px; background: var(--card-bg); color: var(--text-main); border: 1px solid var(--card-border);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dash-main"><i class="fa-solid fa-qrcode text-primary me-2"></i> Complete Unit Stage Lifecycle History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
    // 1. Theme Switcher Logic
    function setDashboardTheme(themeName) {
        const container = document.getElementById('live-dashboard-container');
        if (!container) return;
        container.setAttribute('data-theme', themeName);
        localStorage.setItem('wearable_dashboard_theme', themeName);

        // Update button active state
        document.querySelectorAll('.theme-btn-group .btn').forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.getElementById(`theme-${themeName}-btn`);
        if (activeBtn) activeBtn.classList.add('active');

        // Update ChartColors
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

    // 3. Auto Reload countdown timer
    let refreshSeconds = 30;
    const countdownEl = document.getElementById('refresh-countdown');
    setInterval(function() {
        refreshSeconds--;
        if (refreshSeconds <= 0) {
            if (countdownEl) countdownEl.innerText = "Reloading...";
            window.location.reload();
        } else {
            if (countdownEl) countdownEl.innerText = `${refreshSeconds}s`;
        }
    }, 1000);

    // 4. Render Chart.js line graph
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
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.12)',
                        borderWidth: 3,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#ffffff',
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Total Target (pcs)',
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

        // 5. Track QR Code Form Event
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
                                <div class="p-3 bg-primary bg-opacity-15 border border-primary rounded-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-primary font-monospace fw-bold me-1">QR / CODE</span>
                                            <strong class="font-monospace text-primary fs-5">${data.qr_code}</strong>
                                        </div>
                                        <span class="badge bg-success text-white px-3 py-1.5 rounded-pill fw-bold">
                                            <i class="fa-solid fa-check-double me-1"></i> ${data.total_stages} Stages Tracked
                                        </span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
                                        <thead>
                                            <tr>
                                                <th>WIP Stage</th>
                                                <th>Status</th>
                                                <th>Updated By (Operator)</th>
                                                <th>Logged Date & Time</th>
                                                <th>Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;

                            data.logs.forEach(l => {
                                const badge = l.status === 'PASS' ? 'bg-success' : 'bg-danger';
                                html += `
                                    <tr>
                                        <td><strong class="font-monospace">${l.stage}</strong></td>
                                        <td><span class="badge ${badge} text-white font-monospace">${l.status}</span></td>
                                        <td>
                                            <div class="fw-bold">${l.operator_name}</div>
                                            <small class="text-secondary opacity-75">${l.operator_role}</small>
                                        </td>
                                        <td>
                                            <div class="fw-bold font-monospace">${l.updated_at}</div>
                                            <small class="text-secondary font-monospace">${l.time_ago}</small>
                                        </td>
                                        <td><span class="badge bg-light text-dark font-monospace">${l.duration}</span></td>
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
                                <div class="alert alert-warning text-center py-4 my-2">
                                    <i class="fa-solid fa-circle-exclamation fs-2 mb-2 text-warning"></i>
                                    <h6 class="fw-bold text-dark">No Stage History Logs Found</h6>
                                    <p class="small text-secondary mb-0">No operational logs recorded yet for item tag <strong>${qrCode}</strong> in this batch.</p>
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
