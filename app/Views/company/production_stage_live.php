<div class="container-fluid py-4 min-vh-100 text-light px-3 px-md-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); font-family: 'Outfit', sans-serif;">
    
    <!-- Custom CSS for Dark Dashboard -->
    <style>
        body {
            background-color: #0f172a !important;
        }
        .live-card {
            background: #1e293b !important;
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            transition: transform 0.3s ease, border-color 0.3s ease;
        }
        .live-card:hover {
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-2px);
        }
        /* Specific overrides for live report table background & text contrast */
        .live-card .table-responsive {
            background-color: transparent !important;
            border: none !important;
        }
        .live-card table {
            --bs-table-bg: transparent !important;
            color: #cbd5e1 !important;
        }
        .live-card table th {
            color: #94a3b8 !important;
            font-weight: 600 !important;
        }
        .live-card table td {
            color: #e2e8f0 !important;
        }
        .live-card table tr:hover {
            background-color: rgba(255, 255, 255, 0.03) !important;
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .animate-pulse-slow {
            animation: pulse-slow 3s infinite;
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .glow-green {
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.2);
            border-color: rgba(34, 197, 94, 0.4) !important;
        }
        .glow-indigo {
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.4) !important;
        }
        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }
        .text-neon-cyan {
            color: #22d3ee;
            text-shadow: 0 0 10px rgba(34, 211, 238, 0.3);
        }
        .text-neon-green {
            color: #4ade80;
            text-shadow: 0 0 10px rgba(74, 222, 128, 0.3);
        }
    </style>

    <!-- TOP HEADER -->
    <div class="row align-items-center mb-4">
        <div class="col-md-7 col-12 mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-pill px-3 py-1.5 fw-bold text-uppercase d-inline-flex align-items-center gap-1.5 animate-pulse-slow" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                    <span class="d-inline-block rounded-circle bg-white" style="width: 8px; height: 8px;"></span> Live Monitoring
                </div>
                <h2 class="m-0 fw-bold font-outfit" style="letter-spacing: -0.02em;">Operations Stage Live Dashboard</h2>
            </div>
            <p class="text-secondary small mt-1 mb-0">
                Batch: <strong class="text-white"><?= htmlspecialchars($order['production_no']) ?></strong> | 
                Style: <strong class="text-white"><?= htmlspecialchars($order['style_no']) ?> - <?= htmlspecialchars($order['style_name']) ?></strong> | 
                Fabric: <strong class="text-white"><?= htmlspecialchars($order['fabric_composition']) ?></strong>
            </p>
        </div>
        <div class="col-md-5 col-12 text-md-end text-start">
            <div class="d-inline-flex align-items-center gap-3 bg-slate-900 border border-slate-800 rounded-pill px-3 py-1.5 shadow-sm">
                <span class="small fw-semibold text-secondary font-monospace" id="refresh-countdown">Reloading in 30s...</span>
                <button onclick="window.location.reload();" class="btn btn-sm btn-dark rounded-circle p-1.5 text-secondary hover:text-white" title="Refresh Now">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
                <div class="vr bg-secondary opacity-25" style="height: 18px;"></div>
                <span class="small fw-bold text-white font-monospace" id="live-clock">00:00:00 AM</span>
            </div>
        </div>
    </div>

    <!-- MAIN METRICS PANEL -->
    <?php
        // Calculate key metrics
        $targetQty = (int)$order['target_qty'];
        
        // Cumulative output of last active stage
        $lastStage = end($stagesList);
        $finishedQty = isset($wip_summary[$lastStage]) ? (int)$wip_summary[$lastStage]['out'] : 0;
        $completionPct = $targetQty > 0 ? round(($finishedQty / $targetQty) * 100, 1) : 0;

        // Sum of all balance counts currently active on the WIP floor
        $totalActiveWip = 0;
        $totalWaste = 0;
        foreach ($stagesList as $stg) {
            $totalActiveWip += (isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['wip_balance'] : 0);
            $totalWaste += (isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['waste'] : 0);
        }
        $wastePct = $targetQty > 0 ? round(($totalWaste / $targetQty) * 100, 1) : 0;
    ?>
    <div class="row g-3 mb-4">
        <!-- Target Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Production Target</span>
                    <h3 class="m-0 fw-bold font-outfit text-white"><?= number_format($targetQty) ?> <span class="fs-6 fw-normal text-secondary">pcs</span></h3>
                </div>
                <div class="stat-icon bg-indigo-900 bg-opacity-40 text-indigo-400">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
            </div>
        </div>
        <!-- Completed Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3 d-flex align-items-center justify-content-between glow-green">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Packaged / Completed</span>
                    <h3 class="m-0 fw-bold font-outfit text-neon-green"><?= number_format($finishedQty) ?> <span class="fs-6 fw-normal text-secondary">(<?= $completionPct ?>%)</span></h3>
                </div>
                <div class="stat-icon bg-green-950 bg-opacity-40 text-green-400">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>
        <!-- Active WIP Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3 d-flex align-items-center justify-content-between glow-indigo">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Active WIP Stock</span>
                    <h3 class="m-0 fw-bold font-outfit text-neon-cyan"><?= number_format($totalActiveWip) ?> <span class="fs-6 fw-normal text-secondary">pcs</span></h3>
                </div>
                <div class="stat-icon bg-cyan-950 bg-opacity-40 text-cyan-400">
                    <i class="fa-solid fa-spinner"></i>
                </div>
            </div>
        </div>
        <!-- Wastage Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="live-card p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Cumulative Waste</span>
                    <h3 class="m-0 fw-bold font-outfit text-danger"><?= number_format($totalWaste) ?> <span class="fs-6 fw-normal text-secondary">(<?= $wastePct ?>%)</span></h3>
                </div>
                <div class="stat-icon bg-red-950 bg-opacity-40 text-red-400">
                    <i class="fa-solid fa-dumpster-fire"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- CHART & STAGE PROGRESS ROW -->
    <div class="row g-4 mb-4">
        <!-- Live Chart -->
        <div class="col-lg-8 col-12">
            <div class="live-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="m-0 fw-bold font-outfit"><i class="fa-solid fa-chart-line text-indigo-400 me-2"></i> Stage Production Completion vs Target</h5>
                    <span class="small text-secondary">All Active WIP Stages</span>
                </div>
                <div style="height: 320px;">
                    <canvas id="liveStageChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Live Ticker / Recent Activity Logs -->
        <div class="col-lg-4 col-12">
            <div class="live-card p-4 h-100 d-flex flex-column">
                <h5 class="mb-3 fw-bold font-outfit"><i class="fa-solid fa-clock-rotate-left text-cyan-400 me-2"></i> Real-Time Activity Feed</h5>
                <div class="flex-grow-1 overflow-y-auto pe-1" style="max-height: 310px;">
                    <?php if (!empty($recentLogs)): ?>
                        <div class="list-group list-group-flush bg-transparent">
                            <?php foreach ($recentLogs as $log): ?>
                                <?php 
                                    $isPass = ($log['qty_out'] > 0);
                                    $badgeClass = $isPass ? 'bg-success' : 'bg-danger';
                                    $statusText = $isPass ? 'PASS' : 'FAIL';
                                    $tagLabel = $log['machine_name'] ?: ($log['qr_code'] ?: 'Manual');
                                ?>
                                <div class="list-group-item bg-transparent text-light border-slate-800 px-0 py-2.5">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                        <span class="badge <?= $badgeClass ?> font-monospace fw-bold" style="font-size: 0.65rem; padding: 0.25em 0.5em;"><?= $statusText ?></span>
                                        <small class="text-secondary font-monospace"><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                                    </div>
                                    <p class="mb-0 text-white small">
                                        Stage <strong class="text-capitalize text-indigo-300"><?= str_replace('_', ' ', $log['stage']) ?></strong>
                                    </p>
                                    <small class="text-secondary d-block font-monospace" style="font-size: 0.75rem;">
                                        Tag: <span class="text-slate-300"><?= htmlspecialchars($tagLabel) ?></span> | 
                                        By: <span class="text-slate-300"><?= htmlspecialchars($log['employee_name'] ?: 'System') ?></span>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-secondary">
                            <i class="fa-solid fa-wave-square fs-3 mb-2 animate-pulse-slow"></i>
                            <p class="small m-0">Awaiting live barcode/RFID scans...</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- LIVE STAGE-WISE STATS GRID TABLE -->
    <div class="row">
        <div class="col-12">
            <div class="live-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="m-0 fw-bold font-outfit"><i class="fa-solid fa-list-check text-cyan-400 me-2"></i> Stage-Wise Pipeline Inventory Summary</h5>
                    <span class="badge bg-slate-900 border border-slate-800 text-secondary font-monospace small px-3.5 py-1.5 rounded-pill">Total Active Stages: <?= count($stagesList) ?></span>
                </div>
                <div class="table-responsive border-0">
                    <table class="table table-dark table-hover mb-0 align-middle" style="--bs-table-bg: transparent; --bs-table-border-color: rgba(255,255,255,0.05);">
                        <thead>
                            <tr class="text-secondary" style="font-size: 0.75rem; letter-spacing: 0.05em; text-transform: uppercase;">
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
                                <tr class="border-bottom border-slate-900">
                                    <td>
                                        <span class="fw-bold text-white text-capitalize text-nowrap"><?= str_replace('_', ' ', $stg) ?></span>
                                    </td>
                                    <td class="text-center font-monospace"><?= number_format($in) ?></td>
                                    <td class="text-center font-monospace text-neon-green fw-bold"><?= number_format($out) ?></td>
                                    <td class="text-center font-monospace text-danger"><?= number_format($waste) ?></td>
                                    <td class="text-center font-monospace text-neon-cyan fw-semibold"><?= number_format($bal) ?></td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2.5">
                                            <div class="progress w-100" style="height: 6px; background-color: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden;">
                                                <div class="progress-bar bg-indigo-500 rounded-pill" role="progressbar" style="width: <?= $prog ?>%;" aria-valuenow="<?= $prog ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="small font-monospace fw-bold text-secondary text-nowrap"><?= $prog ?>%</span>
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

<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // 1. Live Clock Timer
    function updateClock() {
        const now = new Date();
        let hours = now.getHours();
        let minutes = now.getMinutes();
        let seconds = now.getSeconds();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;
        
        const strTime = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
        document.getElementById('live-clock').innerText = strTime;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 2. Auto Reload countdown timer
    let refreshSeconds = 30;
    const countdownEl = document.getElementById('refresh-countdown');
    setInterval(function() {
        refreshSeconds--;
        if (refreshSeconds <= 0) {
            countdownEl.innerText = "Reloading stage updates...";
            window.location.reload();
        } else {
            countdownEl.innerText = `Reloading in ${refreshSeconds}s...`;
        }
    }, 1000);

    // 3. Render Chart.js line graph
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('liveStageChart').getContext('2d');
        
        // Labels (WIP stages)
        const stagesLabels = [
            <?php foreach ($stagesList as $stg): ?>
                "<?= str_replace('_', ' ', ucfirst($stg)) ?>",
            <?php endforeach; ?>
        ];

        // Completed dataset (qty_out at each stage)
        const completedData = [
            <?php foreach ($stagesList as $stg): ?>
                <?= isset($wip_summary[$stg]) ? (int)$wip_summary[$stg]['out'] : 0 ?>,
            <?php endforeach; ?>
        ];

        // Target dataset (flat target quantity)
        const targetData = Array(stagesLabels.length).fill(<?= $targetQty ?>);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: stagesLabels,
                datasets: [
                    {
                        label: 'Completed Output (pcs)',
                        data: completedData,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#ffffff',
                        pointHoverRadius: 7,
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
                        pointHoverRadius: 0,
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
                            font: {
                                family: 'Outfit',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        padding: 12,
                        backgroundColor: '#1e293b',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: '#475569',
                        borderWidth: 1,
                        titleFont: { family: 'Outfit', weight: 'bold' },
                        bodyFont: { family: 'Outfit' }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.08)',
                        },
                        ticks: {
                            color: '#cbd5e1',
                            font: { family: 'Outfit', size: 11, weight: 'bold' }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.08)',
                        },
                        ticks: {
                            color: '#cbd5e1',
                            font: { family: 'Outfit', size: 11, weight: 'bold' }
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
