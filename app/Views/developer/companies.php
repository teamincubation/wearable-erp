<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Company Manager</h3>
        <p class="text-secondary m-0">View, onboarding, and hard delete SaaS tenant companies</p>
    </div>
    <button class="btn btn-pepp-primary" data-bs-toggle="modal" data-bs-target="#onboardModal">
        <i class="fa-solid fa-plus me-1"></i> Onboard New Tenant
    </button>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-list-check text-primary me-2"></i> Onboarded Tenants</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Subdomain</th>
                        <th>Admin Email</th>
                        <th>T&C & Payment Slip Info</th>
                        <th>Subscription</th>
                        <th>Expires At</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($companies)): ?>
                        <?php foreach ($companies as $c): ?>
                            <tr>
                                <td>
                                    <strong class="text-dark"><?= htmlspecialchars($c['name']) ?></strong>
                                    <div class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($c['city'] ?? 'Tiruppur') ?>, <?= htmlspecialchars($c['state'] ?? 'Tamil Nadu') ?></div>
                                </td>
                                <td><span class="badge bg-light text-primary"><?= htmlspecialchars($c['subdomain']) ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['admin_email'] ?? $c['email']) ?></div>
                                    <?php if (!empty($c['admin_email']) && $c['admin_email'] !== $c['email']): ?>
                                        <div class="text-secondary small">Contact: <?= htmlspecialchars($c['email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small text-secondary">
                                        <strong>T&C:</strong> <?= $c['tc_agreement'] ? 'Assigned' : 'Pending' ?><br>
                                        <strong>Slip:</strong> <?= $c['payment_slip'] ? htmlspecialchars($c['payment_slip']) : 'Pending' ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-pepp badge-info text-capitalize"><?= htmlspecialchars($c['subscription_status']) ?></span>
                                </td>
                                <td>
                                    <?= $c['subscription_expires_at'] ? date('d-M-Y', strtotime($c['subscription_expires_at'])) : 'Lifetime' ?>
                                </td>
                                <td>
                                    <span class="badge badge-pepp <?= ($c['status'] === 'active') ? 'badge-success' : 'badge-danger' ?>">
                                        <?= htmlspecialchars(ucfirst($c['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#editModal-<?= $c['id'] ?>">
                                        <i class="fa-regular fa-pen-to-square"></i> Edit
                                    </button>
                                    <form action="<?= base_url('developer/companies/delete/' . $c['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('WARNING: Are you absolutely sure you want to HARD DELETE the tenant \'<?= htmlspecialchars($c['name']) ?>\'? This will permanently erase all data, tables association, production orders, inventory ledger logs, and users. This action is irreversible!');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3">
                                            <i class="fa-solid fa-trash-can"></i> Hard Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal-<?= $c['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <form action="<?= base_url('developer/companies/edit/' . $c['id']) ?>" method="POST">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <div class="modal-content text-start" style="border-radius: var(--border-radius-lg);">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold"><i class="fa-solid fa-building-user text-primary me-2"></i> Update Tenant Details & Credentials: <?= htmlspecialchars($c['name']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <!-- Section 1: Company Profile -->
                                                <h6 class="fw-bold text-primary mb-3"><i class="fa-regular fa-id-card me-1"></i> Company Profile Details</h6>
                                                <div class="row g-3 mb-4">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Company Name</label>
                                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($c['name']) ?>" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Subdomain</label>
                                                        <div class="input-group">
                                                            <input type="text" name="subdomain" class="form-control" value="<?= htmlspecialchars($c['subdomain']) ?>" required>
                                                            <span class="input-group-text">.mywellgro.online</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Company Email</label>
                                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($c['email']) ?>" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Company Phone</label>
                                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($c['phone'] ?? '') ?>">
                                                    </div>
                                                </div>

                                                <!-- Section 2: Tenant Super Admin Credentials -->
                                                <h6 class="fw-bold text-primary mb-3 pt-3 border-top"><i class="fa-solid fa-user-shield me-1"></i> Tenant Super Admin Credentials</h6>
                                                <div class="row g-3 mb-4">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Super Admin Name</label>
                                                        <input type="text" name="admin_name" class="form-control" value="<?= htmlspecialchars($c['admin_name'] ?? ($c['name'] . ' Admin')) ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Super Admin Email (Login)</label>
                                                        <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($c['admin_email'] ?? $c['email']) ?>" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Super Admin Phone</label>
                                                        <input type="text" name="admin_phone" class="form-control" value="<?= htmlspecialchars($c['admin_phone'] ?? $c['phone'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Reset Super Admin Password</label>
                                                        <input type="password" name="admin_password" class="form-control" placeholder="Leave blank to keep unchanged">
                                                        <div class="form-text">Input new password to reset tenant super admin credentials.</div>
                                                    </div>
                                                </div>

                                                <!-- Section 3: Subscription & Billing Terms -->
                                                <h6 class="fw-bold text-primary mb-3 pt-3 border-top"><i class="fa-solid fa-file-contract me-1"></i> Subscription & Billing Terms</h6>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Subscription Plan</label>
                                                        <select name="subscription_plan_id" class="form-select text-dark">
                                                            <?php foreach ($plans as $p): ?>
                                                                <option value="<?= $p['id'] ?>" <?= ($p['id'] == $c['subscription_plan_id']) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($p['name']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Account Status</label>
                                                        <select name="status" class="form-select text-dark">
                                                            <option value="active" <?= ($c['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                                            <option value="inactive" <?= ($c['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                                            <option value="suspended" <?= ($c['status'] === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">Terms & Conditions Agreement (Optional)</label>
                                                        <textarea name="tc_agreement" class="form-control" rows="3" placeholder="Input specific Terms & Conditions agreement text here..."><?= htmlspecialchars($c['tc_agreement'] ?? '') ?></textarea>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">Payment Slip Info / Link / Code (Optional)</label>
                                                        <input type="text" name="payment_slip" class="form-control" value="<?= htmlspecialchars($c['payment_slip'] ?? '') ?>" placeholder="e.g. SLIP-TOCCO-901 / Payment Link ID">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-pepp-primary">
                                                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">No companies registered in the platform yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Onboard Modal with Wizard Form -->
<div class="modal fade" id="onboardModal" tabindex="-1" aria-labelledby="onboardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="<?= base_url('developer/companies/create') ?>" method="POST" id="onboardWizardForm">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content shadow-lg text-start" style="border-radius: var(--border-radius-lg);">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="onboardModalLabel"><i class="fa-solid fa-briefcase text-primary me-2"></i> Onboard New Apparel Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Step 1: Company Profile -->
                    <div id="step-1">
                        <h6 class="fw-bold text-primary mb-3"><i class="fa-regular fa-id-card me-1"></i> Step 1: Profile Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. TOCCO Exports" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Subdomain Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="subdomain" class="form-control" placeholder="e.g. tocco" required>
                                    <span class="input-group-text">.mywellgro.online</span>
                                </div>
                                <div class="form-text">Alphabets, digits and hyphens only.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contact Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="info@company.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contact Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="e.g. +91 98765 43210">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Tenant Super Admin Credentials -->
                    <div id="step-admin" class="mt-4 pt-4 border-top">
                        <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-user-shield me-1"></i> Step 2: Tenant Super Admin Account</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Admin Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="admin_email" class="form-control" placeholder="admin@company.com" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Admin Initial Password <span class="text-danger">*</span></label>
                                <input type="password" name="admin_password" class="form-control" placeholder="Choose Password" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Admin Contact Phone</label>
                                <input type="text" name="admin_phone" class="form-control" placeholder="e.g. +91 98765 43210">
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Subscription & Licensing Documents -->
                    <div id="step-documents" class="mt-4 pt-4 border-top">
                        <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-file-contract me-1"></i> Step 3: Billing & Licensing Terms</h6>
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold">Subscription Plan <span class="text-danger">*</span></label>
                                <select name="subscription_plan_id" class="form-select text-dark" required>
                                    <option value="" disabled selected>Select Plan</option>
                                    <?php foreach ($plans as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold">Terms & Conditions Agreement (Optional)</label>
                                <textarea name="tc_agreement" class="form-control" rows="3" placeholder="Input specific Terms & Conditions agreement text here..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Payment Slip Info / Link / Code (Optional)</label>
                                <input type="text" name="payment_slip" class="form-control" placeholder="e.g. SLIP-TOCCO-901">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-pepp-primary">
                        <i class="fa-solid fa-circle-check me-1"></i> Complete Onboarding
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
