<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Company Manager</h3>
        <p class="text-secondary m-0">View and onboard SaaS tenant companies</p>
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
                        <th>GSTIN</th>
                        <th>Subscription</th>
                        <th>Expires At</th>
                        <th>Status</th>
                        <th>Actions</th>
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
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td><?= htmlspecialchars($c['gstin'] ?: 'Not Provided') ?></td>
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
                                <td>
                                    <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editModal-<?= $c['id'] ?>">
                                        <i class="fa-regular fa-pen-to-square"></i> Edit
                                    </button>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal-<?= $c['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form action="<?= base_url('developer/companies/edit/' . $c['id']) ?>" method="POST">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <div class="modal-content" style="border-radius: var(--border-radius-lg);">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Update Tenant: <?= htmlspecialchars($c['name']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Subscription Plan</label>
                                                    <select name="subscription_plan_id" class="form-select">
                                                        <?php foreach ($plans as $p): ?>
                                                            <option value="<?= $p['id'] ?>" <?= ($p['id'] == $c['subscription_plan_id']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($p['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Account Status</label>
                                                    <select name="status" class="form-select">
                                                        <option value="active" <?= ($c['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                                        <option value="inactive" <?= ($c['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                                        <option value="suspended" <?= ($c['status'] === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-pepp-primary">Save Changes</button>
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
            <div class="modal-content shadow-lg" style="border-radius: var(--border-radius-lg);">
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

                    <!-- Step 2: Subscription Setting (displayed on same modal for simplicity) -->
                    <div id="step-2" class="mt-4 pt-4 border-top">
                        <h6 class="fw-bold text-primary mb-3"><i class="fa-regular fa-credit-card me-1"></i> Step 2: Service Plan & Setup</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Subscription Plan <span class="text-danger">*</span></label>
                                <select name="subscription_plan_id" class="form-select" required>
                                    <option value="" disabled selected>Select Plan</option>
                                    <?php foreach ($plans as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-light rounded" style="border: 1px dashed var(--border-color);">
                            <strong><i class="fa-solid fa-circle-check text-success"></i> Automations upon onboarding:</strong>
                            <ul class="m-0 ps-3 mt-1 text-secondary" style="font-size: 13px;">
                                <li>Create Company Admin User matching subdomain details.</li>
                                <li>Set default ERP roles: Admin, Production Manager.</li>
                                <li>Generate initial 1-Year license activation.</li>
                                <li>Seed feature flags: Inventory, Purchase, Production, HR, CRM.</li>
                            </ul>
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
