<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Employee Manager</h3>
        <p class="text-secondary m-0">Manage employee accounts and role mappings</p>
    </div>
    <?php if (\App\Core\Auth::hasPermission('company.users.create')): ?>
        <button class="btn btn-pepp-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fa-solid fa-plus me-1"></i> Add Employee
        </button>
    <?php endif; ?>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-users text-primary me-2"></i> Company Employees</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email / Username</th>
                        <th>Phone</th>
                        <th>Role Privileges</th>
                        <th>Verification</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($u['employee_code'] ?? 'N/A') ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar bg-light text-primary me-3 fw-bold">
                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong class="text-dark"><?= htmlspecialchars($u['name']) ?></strong>
                                            <div class="text-secondary" style="font-size: 11px;">User ID: <?= $u['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['phone'] ?: 'N/A') ?></td>
                                <td>
                                    <?php 
                                        // Find role name
                                        $roleName = 'No Role';
                                        foreach ($roles as $r) {
                                            if ($r['id'] == $u['role_id']) {
                                                $roleName = $r['name'];
                                                break;
                                            }
                                        }
                                    ?>
                                    <span class="badge bg-light text-secondary"><?= htmlspecialchars($roleName) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-pepp <?= $u['email_verified_at'] ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $u['email_verified_at'] ? 'Verified' : 'Pending' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-pepp <?= ($u['status'] === 'active') ? 'badge-success' : 'badge-danger' ?>">
                                        <?= htmlspecialchars(ucfirst($u['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (\App\Core\Auth::hasPermission('company.users.edit')): ?>
                                        <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#editUserModal-<?= $u['id'] ?>">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if (\App\Core\Auth::hasPermission('company.users.delete')): ?>
                                        <form action="<?= base_url('company/users/delete/' . $u['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this employee?');">
                                            <?= \App\Core\Session::csrfField() ?>
                                            <button type="submit" class="btn btn-sm btn-light border text-danger">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Edit User Modal -->
                            <div class="modal fade" id="editUserModal-<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form action="<?= base_url('company/users/edit/' . $u['id']) ?>" method="POST">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <div class="modal-content" style="border-radius: var(--border-radius-lg);">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Update Employee Context</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Employee ID <span class="text-danger">*</span></label>
                                                    <input type="text" name="employee_code" class="form-control" value="<?= htmlspecialchars($u['employee_code'] ?? '') ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Employee Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($u['name']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Email / Username <span class="text-danger">*</span></label>
                                                    <input type="text" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Contact Phone</label>
                                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($u['phone'] ?? '') ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Assigned Role <span class="text-danger">*</span></label>
                                                    <select name="role_id" class="form-select" required>
                                                        <?php foreach ($roles as $role): ?>
                                                            <option value="<?= $role['id'] ?>" <?= ($role['id'] == $u['role_id']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($role['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Reset Password (Leave blank to keep current)</label>
                                                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Account Status</label>
                                                    <select name="status" class="form-select">
                                                        <option value="active" <?= ($u['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                                        <option value="inactive" <?= ($u['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-pepp-primary">Save Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">No employees registered.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('company/users/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content" style="border-radius: var(--border-radius-lg);">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus text-primary me-2"></i> Register Employee User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" name="employee_code" class="form-control" placeholder="e.g. EMP001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Ramesh Kumar" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email / Username <span class="text-danger">*</span></label>
                        <input type="text" name="email" class="form-control" placeholder="ramesh or ramesh@toccoexports.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g. +91 99887 76655">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ERP Security Role <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-select" required>
                            <option value="" disabled selected>Choose Role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-pepp-primary">Register Account</button>
                </div>
            </div>
        </form>
    </div>
</div>
