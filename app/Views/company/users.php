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
        <!-- Search and Filter Panel -->
        <div class="p-3 border-bottom bg-light">
            <form method="GET" action="<?= base_url('company/users') ?>" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-dark">Search Employees</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-search text-secondary"></i></span>
                        <input type="text" name="search" class="form-control text-dark" placeholder="Search by name, ID, phone..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-dark">Designation</label>
                    <select name="filter_designation" class="form-select form-select-sm text-dark">
                        <option value="">All Designations</option>
                        <?php foreach ($designations as $desgOpt): ?>
                            <option value="<?= htmlspecialchars($desgOpt['title']) ?>" <?= (($_GET['filter_designation'] ?? '') === $desgOpt['title']) ? 'selected' : '' ?>><?= htmlspecialchars($desgOpt['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-dark">Role Privilege</label>
                    <select name="filter_role" class="form-select form-select-sm text-dark">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $rOpt): ?>
                            <option value="<?= $rOpt['id'] ?>" <?= (($_GET['filter_role'] ?? '') == $rOpt['id']) ? 'selected' : '' ?>><?= htmlspecialchars($rOpt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-dark">Active Status</label>
                    <select name="filter_status" class="form-select form-select-sm text-dark">
                        <option value="">All Statuses</option>
                        <option value="active" <?= (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-sm btn-pepp-primary px-3 w-50"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="<?= base_url('company/users') ?>" class="btn btn-sm btn-light border px-3 w-50"><i class="fa-solid fa-rotate-left me-1"></i> Reset</a>
                </div>
            </form>
        </div>

        <div class="table-responsive border-0">
            <table class="table pepp-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Phone</th>
                        <th>Role Privileges</th>
                        <th>Salary Package</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($u['employee_code'] ?? 'N/A') ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar bg-light text-primary me-3 fw-bold">
                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong class="text-dark"><?= htmlspecialchars($u['name']) ?></strong>
                                            <div class="text-secondary" style="font-size: 11px;">ID: <?= htmlspecialchars($u['employee_code'] ?? 'N/A') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-secondary"><?= htmlspecialchars($u['designation'] ?: 'Staff') ?></span></td>
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
                                    <span class="fw-bold text-dark font-monospace"><?= get_currency_symbol() ?><?= number_format($u['base_salary'] ?? 0, 2) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-pepp <?= ($u['status'] === 'active') ? 'badge-success' : 'badge-danger' ?>">
                                        <?= htmlspecialchars(ucfirst($u['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#viewUserModal-<?= $u['id'] ?>" title="View Employee Details">
                                        <i class="fa-regular fa-eye text-primary"></i>
                                    </button>

                                    <?php if (\App\Core\Auth::hasPermission('company.users.edit')): ?>
                                        <?php if ((int)$u['id'] === (int)\App\Core\Session::get('user_id')): ?>
                                            <button class="btn btn-sm btn-light border me-1 opacity-50" disabled title="Signed user cannot edit their own account details">
                                                <i class="fa-regular fa-pen-to-square text-muted"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#editUserModal-<?= $u['id'] ?>" title="Edit Employee Details">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if (\App\Core\Auth::hasPermission('company.users.delete') && (int)$u['id'] !== (int)\App\Core\Session::get('user_id')): ?>
                                        <form action="<?= base_url('company/users/delete/' . $u['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this employee?');">
                                            <?= \App\Core\Session::csrfField() ?>
                                            <button type="submit" class="btn btn-sm btn-light border text-danger" title="Deactivate Employee Account">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- View User Modal -->
                            <div class="modal fade" id="viewUserModal-<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content text-start" style="border-radius: var(--border-radius-lg);">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold"><i class="fa-regular fa-id-badge text-primary me-2"></i> Employee Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4 text-dark">
                                            <div class="d-flex align-items-center mb-4">
                                                <div class="user-avatar bg-light text-primary me-3 fw-bold fs-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6;">
                                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($u['name']) ?></h5>
                                                    <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($u['employee_code'] ?? 'N/A') ?></span>
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-6 mb-2">
                                                    <label class="text-secondary small d-block">Designation</label>
                                                    <strong class="text-dark"><?= htmlspecialchars($u['designation'] ?: 'Staff') ?></strong>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="text-secondary small d-block">Assigned Role</label>
                                                    <strong class="text-dark"><?= htmlspecialchars($roleName) ?></strong>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="text-secondary small d-block">Email / Username</label>
                                                    <strong class="text-dark"><?= htmlspecialchars($u['email']) ?></strong>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="text-secondary small d-block">Contact Phone</label>
                                                    <strong class="text-dark"><?= htmlspecialchars($u['phone'] ?: 'N/A') ?></strong>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="text-secondary small d-block">Base Salary Package</label>
                                                    <strong class="text-success font-monospace"><?= get_currency_symbol() ?><?= number_format($u['base_salary'] ?? 0, 2) ?></strong>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="text-secondary small d-block">Account Status</label>
                                                    <span class="badge bg-light text-dark border px-2 py-1 text-capitalize fw-bold">
                                                        <?= htmlspecialchars(ucfirst($u['status'])) ?>
                                                    </span>
                                                </div>
                                                <div class="col-12 mb-2">
                                                    <label class="text-secondary small d-block">Verification Status</label>
                                                    <span class="badge badge-pepp <?= $u['email_verified_at'] ? 'badge-success' : 'badge-warning' ?>">
                                                        <?= $u['email_verified_at'] ? 'Verified' : 'Pending' ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <?php if ($u['status'] === 'inactive'): ?>
                                                <div class="mt-4 p-3 border border-danger rounded text-start" style="background-color: #fef2f2;">
                                                    <h6 class="fw-bold text-danger mb-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Inactivity Details</h6>
                                                    <div class="mb-2">
                                                        <span class="text-secondary small d-block">Reason / Separation Type</span>
                                                        <strong class="text-dark text-capitalize"><?= htmlspecialchars($u['inactive_reason'] ?: 'N/A') ?></strong>
                                                    </div>
                                                    <div class="mb-2">
                                                        <span class="text-secondary small d-block">Effective Date</span>
                                                        <strong class="text-dark"><?= $u['inactivity_date'] ? date('d M Y', strtotime($u['inactivity_date'])) : 'N/A' ?></strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-secondary small d-block">Admin Remarks</span>
                                                        <p class="text-dark mb-0 small"><?= nl2br(htmlspecialchars($u['inactivity_remarks'] ?: 'No remarks provided.')) ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer border-0">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

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
                                                    <input type="text" name="employee_code" class="form-control text-dark" value="<?= htmlspecialchars($u['employee_code'] ?? '') ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Employee Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control text-dark" value="<?= htmlspecialchars($u['name']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">
                                                        Email / Username <span class="text-danger">*</span>
                                                        <span class="email-status-badge" id="edit-emp-email-status-<?= $u['id'] ?>"></span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white"><i class="fa-solid fa-envelope text-primary"></i></span>
                                                        <input type="text" name="email" class="form-control text-dark check-uniqueness" data-exclude-user="<?= $u['id'] ?>" data-status-target="#edit-emp-email-status-<?= $u['id'] ?>" value="<?= htmlspecialchars($u['email']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Contact Phone</label>
                                                    <input type="text" name="phone" class="form-control text-dark" value="<?= htmlspecialchars($u['phone'] ?? '') ?>">
                                                </div>
                                                <div class="mb-3">
                                                     <label class="form-label fw-semibold">Assigned Role <span class="text-danger">*</span></label>
                                                     <select name="role_id" class="form-select text-dark" required>
                                                         <?php foreach ($roles as $role): ?>
                                                             <option value="<?= $role['id'] ?>" <?= ($role['id'] == $u['role_id']) ? 'selected' : '' ?>>
                                                                 <?= htmlspecialchars($role['name']) ?>
                                                             </option>
                                                         <?php endforeach; ?>
                                                     </select>
                                                 </div>
                                                  <div class="mb-3">
                                                      <label class="form-label fw-semibold">Salary Package (Base Monthly Salary - <?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                                                      <input type="number" step="0.01" name="base_salary" class="form-control text-dark" value="<?= htmlspecialchars($u['base_salary'] ?? '0.00') ?>" required>
                                                  </div>
                                                  <div class="mb-3">
                                                      <label class="form-label fw-semibold">Designation / Role Title <span class="text-danger">*</span></label>
                                                      <select name="designation" class="form-select text-dark" required>
                                                          <option value="" disabled>-- Select Designation --</option>
                                                          <?php if (!empty($designations)): ?>
                                                              <?php foreach ($designations as $d): ?>
                                                                  <option value="<?= htmlspecialchars($d['title']) ?>" <?= (($u['designation'] ?? '') === $d['title']) ? 'selected' : '' ?>>
                                                                      <?= htmlspecialchars($d['title']) ?>
                                                                  </option>
                                                              <?php endforeach; ?>
                                                          <?php else: ?>
                                                              <option value="Staff" selected>Staff</option>
                                                          <?php endif; ?>
                                                      </select>
                                                  </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Reset Password (Leave blank to keep current)</label>
                                                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Account Status</label>
                                                    <select name="status" class="form-select text-dark" onchange="toggleInactivitySection(this, '<?= $u['id'] ?>')">
                                                        <option value="active" <?= ($u['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                                        <option value="inactive" <?= ($u['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                                    </select>
                                                </div>

                                                <!-- Inactivity details section (only shown if status is set to inactive) -->
                                                <div id="inactivitySection-<?= $u['id'] ?>" style="display: <?= ($u['status'] === 'inactive') ? 'block' : 'none' ?>; background-color: #fef2f2; border: 1px solid #fca5a5; padding: 15px; border-radius: 8px;" class="mb-3 text-start">
                                                    <h6 class="fw-bold text-danger mb-3"><i class="fa-solid fa-triangle-exclamation me-1"></i> Inactivity Details</h6>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold text-dark text-start d-block">Inactive Type <span class="text-danger">*</span></label>
                                                        <select name="inactive_reason" id="inactive_reason-<?= $u['id'] ?>" class="form-select text-dark" <?= ($u['status'] === 'inactive') ? 'required' : '' ?>>
                                                            <option value="" disabled <?= empty($u['inactive_reason']) ? 'selected' : '' ?>>-- Select Type --</option>
                                                            <option value="resigned" <?= ($u['inactive_reason'] === 'resigned') ? 'selected' : '' ?>>Resigned</option>
                                                            <option value="terminated" <?= ($u['inactive_reason'] === 'terminated') ? 'selected' : '' ?>>Terminated</option>
                                                            <option value="contract expired" <?= ($u['inactive_reason'] === 'contract expired') ? 'selected' : '' ?>>Contract Expired</option>
                                                            <option value="deceased" <?= ($u['inactive_reason'] === 'deceased') ? 'selected' : '' ?>>Deceased</option>
                                                            <option value="retirement" <?= ($u['inactive_reason'] === 'retirement') ? 'selected' : '' ?>>Retirement</option>
                                                            <option value="disability or medical separation" <?= ($u['inactive_reason'] === 'disability or medical separation') ? 'selected' : '' ?>>Disability or Medical Separation</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold text-dark text-start d-block">Date of Inactivity Affected From <span class="text-danger">*</span></label>
                                                        <input type="date" name="inactivity_date" id="inactivity_date-<?= $u['id'] ?>" class="form-control text-dark" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($u['inactivity_date'] ?? '') ?>" <?= ($u['status'] === 'inactive') ? 'required' : '' ?>>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold text-dark text-start d-block">Note / Remarks</label>
                                                        <textarea name="inactivity_remarks" class="form-control text-dark" rows="2" placeholder="Provide description..."><?= htmlspecialchars($u['inactivity_remarks'] ?? '') ?></textarea>
                                                    </div>
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
                        <label class="form-label fw-semibold">
                            Email / Username <span class="text-danger">*</span>
                            <span class="email-status-badge" id="add-emp-email-status"></span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-envelope text-primary"></i></span>
                            <input type="text" name="email" class="form-control check-uniqueness" data-status-target="#add-emp-email-status" placeholder="ramesh or ramesh@company.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g. +91 99887 76655">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-key text-primary"></i></span>
                            <input type="password" name="password" class="form-control password-field" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary toggle-pwd-btn" type="button"><i class="fa-solid fa-eye"></i></button>
                        </div>
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
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Salary Package (Base Monthly Salary - <?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="base_salary" class="form-control" placeholder="e.g. 20000.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Designation / Role Title <span class="text-danger">*</span></label>
                        <select name="designation" class="form-select text-dark" required>
                            <option value="" disabled selected>-- Select Designation --</option>
                            <?php if (!empty($designations)): ?>
                                <?php foreach ($designations as $d): ?>
                                    <option value="<?= htmlspecialchars($d['title']) ?>">
                                        <?= htmlspecialchars($d['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="Staff">Staff (Configure master designations to add more)</option>
                            <?php endif; ?>
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

<script>
function toggleInactivitySection(selectEl, userId) {
    const section = document.getElementById('inactivitySection-' + userId);
    const reasonInput = document.getElementById('inactive_reason-' + userId);
    const dateInput = document.getElementById('inactivity_date-' + userId);
    
    if (selectEl.value === 'inactive') {
        section.style.display = 'block';
        reasonInput.setAttribute('required', 'required');
        dateInput.setAttribute('required', 'required');
    } else {
        section.style.display = 'none';
        reasonInput.removeAttribute('required');
        dateInput.removeAttribute('required');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Real-time AJAX Uniqueness Validation for Email / Username Inputs
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('check-uniqueness')) {
            const inputEl = e.target;
            const val = inputEl.value.trim();
            const targetBadge = document.querySelector(inputEl.dataset.statusTarget);
            if (!targetBadge) return;

            if (val.length < 3) {
                targetBadge.innerHTML = '';
                return;
            }

            const excludeUserId = inputEl.dataset.excludeUser || 0;
            const excludeCompanyId = inputEl.dataset.excludeCompany || 0;

            fetch('<?= base_url("api/check-identifier-uniqueness") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    identifier: val,
                    exclude_user_id: excludeUserId,
                    exclude_company_id: excludeCompanyId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.available) {
                    targetBadge.innerHTML = '<span class="badge bg-success-subtle text-success border border-success font-monospace ms-2" style="font-size:10px;"><i class="fa-solid fa-circle-check me-1"></i> Available</span>';
                } else {
                    targetBadge.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger font-monospace ms-2" style="font-size:10px;"><i class="fa-solid fa-triangle-exclamation me-1"></i> ' + (data.message || 'Already in use') + '</span>';
                }
            })
            .catch(err => {
                targetBadge.innerHTML = '';
            });
        }
    });

    // Password Visibility Toggle Button
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.toggle-pwd-btn');
        if (btn) {
            const input = btn.previousElementSibling;
            if (input && input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
            } else if (input) {
                input.type = 'password';
                btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
            }
        }
    });
});
</script>
