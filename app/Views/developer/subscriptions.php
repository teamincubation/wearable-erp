<div class="mb-4">
    <h3 class="fw-bold">Subscription Plans</h3>
    <p class="text-secondary m-0">Define SaaS tier packages and resource limits</p>
</div>

<div class="row">
    <!-- Plans list -->
    <div class="col-md-8">
        <div class="pepp-card">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-tags text-primary me-2"></i> Current SaaS Packages</h5>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table pepp-table">
                        <thead>
                            <tr>
                                <th>Plan Name</th>
                                <th>Billing Cycle</th>
                                <th>Price</th>
                                <th>Limits</th>
                                <th>API Access</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $p): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark"><?= htmlspecialchars($p['name']) ?></strong>
                                        <div class="text-secondary" style="font-size: 11px;">Code: <?= htmlspecialchars($p['code']) ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-primary text-uppercase"><?= htmlspecialchars($p['billing_cycle']) ?></span></td>
                                    <td><strong>₹<?= number_format($p['price'], 2) ?></strong></td>
                                    <td>
                                        <div style="font-size: 13px;">
                                            <i class="fa-solid fa-user-tag text-secondary"></i> Users: <strong><?= $p['max_users'] ?></strong><br>
                                            <i class="fa-solid fa-sitemap text-secondary"></i> Branches: <strong><?= $p['max_branches'] ?></strong><br>
                                            <i class="fa-solid fa-hard-drive text-secondary"></i> Storage: <strong><?= $p['max_storage_mb'] >= 1024 ? ($p['max_storage_mb']/1024) . ' GB' : $p['max_storage_mb'] . ' MB' ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-pepp <?= $p['api_access'] ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $p['api_access'] ? 'Yes' : 'No' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-pepp <?= ($p['status'] === 'active') ? 'badge-success' : 'badge-danger' ?>">
                                            <?= htmlspecialchars(ucfirst($p['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary border-0" data-bs-toggle="modal" data-bs-target="#editPlanModal-<?= $p['id'] ?>">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                    </td>
                                </tr>

                                <!-- Edit Plan Modal -->
                                <div class="modal fade" id="editPlanModal-<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="<?= base_url('developer/subscriptions/edit/' . $p['id']) ?>" method="POST">
                                            <?= \App\Core\Session::csrfField() ?>
                                            <div class="modal-content text-start" style="border-radius: 12px;">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Edit Subscription Plan: <?= htmlspecialchars($p['name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Plan Name</label>
                                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Unique Plan Code</label>
                                                        <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($p['code']) ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Billing Cycle</label>
                                                        <select name="billing_cycle" class="form-select" required>
                                                            <option value="trial" <?= ($p['billing_cycle'] === 'trial') ? 'selected' : '' ?>>Trial (No Cost)</option>
                                                            <option value="monthly" <?= ($p['billing_cycle'] === 'monthly') ? 'selected' : '' ?>>Monthly</option>
                                                            <option value="quarterly" <?= ($p['billing_cycle'] === 'quarterly') ? 'selected' : '' ?>>Quarterly</option>
                                                            <option value="yearly" <?= ($p['billing_cycle'] === 'yearly') ? 'selected' : '' ?>>Yearly</option>
                                                            <option value="lifetime" <?= ($p['billing_cycle'] === 'lifetime') ? 'selected' : '' ?>>Lifetime</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Price (INR)</label>
                                                        <input type="number" name="price" step="0.01" class="form-control" value="<?= htmlspecialchars($p['price']) ?>" required>
                                                    </div>

                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Max Users</label>
                                                            <input type="number" name="max_users" class="form-control" value="<?= htmlspecialchars($p['max_users']) ?>" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold">Max Branches</label>
                                                            <input type="number" name="max_branches" class="form-control" value="<?= htmlspecialchars($p['max_branches']) ?>" required>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Max Storage (MB)</label>
                                                        <input type="number" name="max_storage_mb" class="form-control" value="<?= htmlspecialchars($p['max_storage_mb']) ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Status</label>
                                                        <select name="status" class="form-select">
                                                            <option value="active" <?= ($p['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                                            <option value="inactive" <?= ($p['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3 form-check">
                                                        <input type="checkbox" name="api_access" id="api_access-<?= $p['id'] ?>" class="form-check-input" value="1" <?= $p['api_access'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label fw-semibold" for="api_access-<?= $p['id'] ?>">Allow API Integration Access</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create form -->
    <div class="col-md-4">
        <div class="pepp-card">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-plus text-primary me-2"></i> Add Subscription Tier</h5>
            </div>
            <div class="pepp-card-body">
                <form action="<?= base_url('developer/subscriptions/create') ?>" method="POST">
                    <?= \App\Core\Session::csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Plan Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Starter Pack" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Unique Plan Code</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. starter_monthly" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Billing Cycle</label>
                        <select name="billing_cycle" class="form-select" required>
                            <option value="trial">Trial (No Cost)</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                            <option value="lifetime">Lifetime</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Price (INR)</label>
                        <input type="number" name="price" step="0.01" class="form-control" placeholder="e.g. 2999" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Max Users</label>
                            <input type="number" name="max_users" class="form-control" value="5" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Max Branches</label>
                            <input type="number" name="max_branches" class="form-control" value="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Max Storage (MB)</label>
                        <input type="number" name="max_storage_mb" class="form-control" value="1024" required>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="api_access" id="api_access" class="form-check-input" value="1">
                        <label class="form-check-label fw-semibold" for="api_access">Allow API Integration Access</label>
                    </div>

                    <button type="submit" class="btn btn-pepp-primary w-100">
                        <i class="fa-solid fa-plus me-1"></i> Register Package
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
