<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Background Cron Jobs Logs</h3>
        <p class="text-secondary m-0">Diagnostics of automated system cron executions (billing renewals, cleanups, tally queues auto-sync)</p>
    </div>
</div>

<div class="pepp-card mb-4">
    <div class="pepp-card-header bg-light">
        <h5 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-gears text-primary me-2"></i> System Cron Runner Configurations</h5>
    </div>
    <div class="pepp-card-body small">
        <p class="mb-2">To automate the Wearable ERP platform SaaS billing and cleanups, hook the following shell runner to your Hostinger Cloud Cron Job Scheduler:</p>
        <div class="bg-dark text-white p-3 rounded font-monospace mb-2">
            * * * * * php /home/u361910773/public_html/cron_runner.php >> /home/u361910773/public_html/storage/logs/cron.log 2>&1
        </div>
        <div class="text-secondary"><i class="fa-solid fa-circle-info text-info"></i> The runner fires once per minute and scans pending tasks.</div>
    </div>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Cron Executions History</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table table-hover pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Cron Job / Task Name</th>
                        <th>Execution Timestamp</th>
                        <th>Runtime Duration</th>
                        <th>Trigger Status</th>
                        <th>Output Messages / Error Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cron_logs)): ?>
                        <?php foreach ($cron_logs as $log): ?>
                            <tr>
                                <td><span class="text-secondary small font-monospace">#<?= $log['id'] ?></span></td>
                                <td><strong class="text-dark font-monospace"><?= htmlspecialchars($log['job_name']) ?></strong></td>
                                <td><?= date('d M Y H:i:s', strtotime($log['executed_at'])) ?></td>
                                <td><span class="badge bg-light text-secondary"><?= number_format($log['runtime_seconds'], 2) ?>s</span></td>
                                <td>
                                    <span class="badge badge-pepp <?= $log['status'] === 'success' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= htmlspecialchars(strtoupper($log['status'])) ?>
                                    </span>
                                </td>
                                <td class="font-monospace text-secondary small" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($log['message']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-clock fs-1 mb-3 text-light"></i>
                                <p class="m-0">No background cron log entries found. Scheduler hasn't fired yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
