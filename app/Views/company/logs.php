<div class="mb-4">
    <h3 class="fw-bold">Audit History</h3>
    <p class="text-secondary m-0">Review actions, edits, and system changes conducted inside your company database</p>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-list-check text-primary me-2"></i> Company Audit Trails</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User Account</th>
                        <th>ERP Action</th>
                        <th>Description / Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="text-nowrap"><?= date('d-M-Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <strong class="text-dark"><?= htmlspecialchars($log['user_name'] ?? 'System Process') ?></strong>
                                    <div class="text-secondary" style="font-size: 11px;">User ID: <?= $log['user_id'] ?: 'System' ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td><span style="font-size: 13px;"><?= htmlspecialchars($log['description'] ?? 'Activity logs.') ?></span></td>
                                <td><code style="font-size: 12px;"><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">No audit logs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
