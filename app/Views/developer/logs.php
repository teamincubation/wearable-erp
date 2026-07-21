<div class="mb-4">
    <h3 class="fw-bold">Global Activity Logs</h3>
    <p class="text-secondary m-0">Review system activity, audits, and logins across the entire SaaS environment</p>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-bug-slash text-primary me-2"></i> Security Audit Logs</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table">
                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>User Account</th>
                        <th>Security Action</th>
                        <th>Description / Impact</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="text-nowrap"><?= date('d-M-Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <strong class="text-dark"><?= htmlspecialchars($log['user_name'] ?? 'System / Anonymous') ?></strong>
                                    <div class="text-secondary" style="font-size: 11px;">User ID: <?= $log['user_id'] ?: 'N/A' ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-pepp 
                                        <?php 
                                            if (strpos($log['action'], 'failed') !== false || strpos($log['action'], 'block') !== false) echo 'badge-danger';
                                            elseif (strpos($log['action'], 'create') !== false || strpos($log['action'], 'onboard') !== false) echo 'badge-success';
                                            elseif (strpos($log['action'], 'update') !== false) echo 'badge-warning';
                                            else echo 'badge-info';
                                        ?>">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </td>
                                <td><span style="font-size: 13px;"><?= htmlspecialchars($log['description'] ?? '') ?></span></td>
                                <td><code style="font-size: 12px;"><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></code></td>
                                <td class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($log['user_agent']) ?>">
                                    <small class="text-secondary"><?= htmlspecialchars($log['user_agent'] ?? 'N/A') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">No audit logs recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
