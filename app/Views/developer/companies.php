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
                                                        <select name="subscription_plan_id" class="form-select text-dark plan-select" data-company-id="<?= $c['id'] ?>">
                                                            <?php foreach ($plans as $p): ?>
                                                                <option value="<?= $p['id'] ?>" data-cycle="<?= $p['billing_cycle'] ?>" <?= ($p['id'] == $c['subscription_plan_id']) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['billing_cycle']) ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 expiry-main-container">
                                                        <label class="form-label fw-semibold">Subscription Expiry Date</label>
                                                        <input type="date" name="subscription_expires_at" class="form-control text-dark subscription-expiry-date-main" value="<?= $c['subscription_expires_at'] ? date('Y-m-d', strtotime($c['subscription_expires_at'])) : '' ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Account Status</label>
                                                        <select name="status" class="form-select text-dark">
                                                            <option value="active" <?= ($c['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                                            <option value="inactive" <?= ($c['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                                            <option value="suspended" <?= ($c['status'] === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Timezone <span class="text-danger">*</span></label>
                                                        <select name="timezone" class="form-select text-dark" required>
                                                            <option value="Asia/Kolkata" <?= ($c['timezone'] ?? 'Asia/Kolkata') === 'Asia/Kolkata' ? 'selected' : '' ?>>IST Kolkata (Asia/Kolkata)</option>
                                                            <option value="UTC" <?= ($c['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC (GMT+0)</option>
                                                            <option value="America/New_York" <?= ($c['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>EST New York (America/New_York)</option>
                                                            <option value="Europe/London" <?= ($c['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>>GMT London (Europe/London)</option>
                                                            <option value="Asia/Singapore" <?= ($c['timezone'] ?? '') === 'Asia/Singapore' ? 'selected' : '' ?>>SGT Singapore (Asia/Singapore)</option>
                                                            <option value="Asia/Dubai" <?= ($c['timezone'] ?? '') === 'Asia/Dubai' ? 'selected' : '' ?>>GST Dubai (Asia/Dubai)</option>
                                                            <option value="Australia/Sydney" <?= ($c['timezone'] ?? '') === 'Australia/Sydney' ? 'selected' : '' ?>>AEST Sydney (Australia/Sydney)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Currency <span class="text-danger">*</span></label>
                                                        <select name="currency" class="form-select text-dark" required>
                                                            <option value="INR" <?= ($c['currency'] ?? 'INR') === 'INR' ? 'selected' : '' ?>>₹INR (Indian Rupee)</option>
                                                            <option value="USD" <?= ($c['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>$USD (US Dollar)</option>
                                                            <option value="EUR" <?= ($c['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>€EUR (Euro)</option>
                                                            <option value="GBP" <?= ($c['currency'] ?? '') === 'GBP' ? 'selected' : '' ?>>£GBP (British Pound)</option>
                                                            <option value="AED" <?= ($c['currency'] ?? '') === 'AED' ? 'selected' : '' ?>>AED (UAE Dirham)</option>
                                                            <option value="SGD" <?= ($c['currency'] ?? '') === 'SGD' ? 'selected' : '' ?>>S$SGD (Singapore Dollar)</option>
                                                            <option value="AUD" <?= ($c['currency'] ?? '') === 'AUD' ? 'selected' : '' ?>>A$AUD (Australian Dollar)</option>
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

                                                <!-- Section 4: Assign Modules & Pages validity -->
                                                <h6 class="fw-bold text-primary mb-3 pt-3 border-top"><i class="fa-solid fa-list-check me-1"></i> Assign ERP Modules & Page Validity</h6>
                                                <p class="text-secondary small mb-3">Check each module/page to grant access, and set their individual expiration dates. Date fields are automatically disabled for Lifetime subscription plans.</p>

                                                <div class="row g-3">
                                                    <?php if (!empty($allPermissions)): ?>
                                                        <?php foreach ($allPermissions as $perm): 
                                                            $hasFlag = isset($companyFlags[$c['id']][$perm['name']]);
                                                            $expiryVal = $hasFlag ? $companyFlags[$c['id']][$perm['name']]['expiry_date'] : '';
                                                            $cleanLabel = ucwords(str_replace(['company.', '.', 'view', 'manage'], [' ', ' ', ' (View)', ' (Manage)'], $perm['name']));
                                                            
                                                            $moduleName = 'Other';
                                                            $bgColor = '#6c757d';
                                                            $textColor = '#ffffff';

                                                            if (strpos($perm['name'], 'company.users') !== false || strpos($perm['name'], 'company.payroll') !== false) {
                                                                $moduleName = 'HR/Users';
                                                                $bgColor = '#6f42c1';
                                                            } elseif (strpos($perm['name'], 'company.roles') !== false) {
                                                                $moduleName = 'Security';
                                                                $bgColor = '#343a40';
                                                            } elseif (strpos($perm['name'], 'company.styles') !== false) {
                                                                $moduleName = 'Styles/Merch';
                                                                $bgColor = '#198754';
                                                            } elseif (strpos($perm['name'], 'company.purchase') !== false) {
                                                                $moduleName = 'Procure';
                                                                $bgColor = '#ffc107';
                                                                $textColor = '#212529';
                                                            } elseif (strpos($perm['name'], 'company.inventory') !== false) {
                                                                $moduleName = 'Inventory';
                                                                $bgColor = '#0dcaf0';
                                                                $textColor = '#212529';
                                                            } elseif (strpos($perm['name'], 'company.production') !== false) {
                                                                $moduleName = 'Production';
                                                                $bgColor = '#0d6efd';
                                                            } elseif (strpos($perm['name'], 'company.tally') !== false) {
                                                                $moduleName = 'Tally';
                                                                $bgColor = '#6610f2';
                                                            } elseif (strpos($perm['name'], 'company.logs') !== false) {
                                                                $moduleName = 'Audit Logs';
                                                                $bgColor = '#dc3545';
                                                            } elseif (strpos($perm['name'], 'company.settings') !== false) {
                                                                $moduleName = 'Settings';
                                                                $bgColor = '#495057';
                                                            } elseif (strpos($perm['name'], 'company.dashboard') !== false) {
                                                                $moduleName = 'Core';
                                                                $bgColor = '#20c997';
                                                            }
                                                        ?>
                                                            <div class="col-md-6 border-bottom pb-2">
                                                                <div class="form-check">
                                                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="<?= htmlspecialchars($perm['name']) ?>" id="perm_<?= $c['id'] ?>_<?= $perm['id'] ?>" <?= $hasFlag ? 'checked' : '' ?>>
                                                                    <label class="form-check-label fw-semibold text-dark d-inline-flex align-items-center" for="perm_<?= $c['id'] ?>_<?= $perm['id'] ?>">
                                                                        <span class="badge rounded-pill me-2" style="font-size: 10px; background-color: <?= $bgColor ?>; color: <?= $textColor ?>; padding: 3px 8px;"><?= $moduleName ?></span>
                                                                        <?= htmlspecialchars($cleanLabel) ?>
                                                                    </label>
                                                                </div>
                                                                <div class="mt-1 ps-4 expiry-input-container">
                                                                    <label class="small text-muted d-block mb-1">Access Expiration Date</label>
                                                                    <input type="date" name="expiry_date[<?= htmlspecialchars($perm['name']) ?>]" class="form-control form-control-sm expiry-date-input" value="<?= htmlspecialchars($expiryVal) ?>" style="max-width: 180px;">
                                                                </div>
                                                                <div class="mt-2 ps-4 label-input-container">
                                                                     <label class="small text-muted d-block mb-1">Release Label</label>
                                                                     <?php $labelVal = $hasFlag ? ($companyFlags[$c['id']][$perm['name']]['label'] ?? 'no_label') : 'no_label'; ?>
                                                                     <select name="labels[<?= htmlspecialchars($perm['name']) ?>]" class="form-select form-select-sm release-label-select fw-semibold" style="max-width: 150px;">
                                                                         <option value="no_label" <?= $labelVal === 'no_label' ? 'selected' : '' ?>>No Label</option>
                                                                         <option value="draft" style="color: #664d03; background-color: #fff3cd;" <?= $labelVal === 'draft' ? 'selected' : '' ?>>⚠️ Draft</option>
                                                                         <option value="beta" style="color: #087990; background-color: #cff4fc;" <?= $labelVal === 'beta' ? 'selected' : '' ?>>🧪 Beta</option>
                                                                         <option value="new" style="color: #842029; background-color: #f8d7da;" <?= $labelVal === 'new' ? 'selected' : '' ?>>🔥 New</option>
                                                                     </select>
                                                                 </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
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
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Subscription Plan <span class="text-danger">*</span></label>
                                <select name="subscription_plan_id" class="form-select text-dark plan-select" data-company-id="onboard" required>
                                    <option value="" disabled selected>Select Plan</option>
                                    <?php foreach ($plans as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-cycle="<?= $p['billing_cycle'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['billing_cycle']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 expiry-main-container">
                                <label class="form-label fw-semibold">Subscription Expiry Date</label>
                                <input type="date" name="subscription_expires_at" class="form-control text-dark subscription-expiry-date-main" data-target-company="onboard">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Timezone <span class="text-danger">*</span></label>
                                <select name="timezone" class="form-select text-dark" required>
                                    <option value="Asia/Kolkata" selected>IST Kolkata (Asia/Kolkata)</option>
                                    <option value="UTC">UTC (GMT+0)</option>
                                    <option value="America/New_York">EST New York (America/New_York)</option>
                                    <option value="Europe/London">GMT London (Europe/London)</option>
                                    <option value="Asia/Singapore">SGT Singapore (Asia/Singapore)</option>
                                    <option value="Asia/Dubai">GST Dubai (Asia/Dubai)</option>
                                    <option value="Australia/Sydney">AEST Sydney (Australia/Sydney)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Currency <span class="text-danger">*</span></label>
                                <select name="currency" class="form-select text-dark" required>
                                    <option value="INR" selected>₹INR (Indian Rupee)</option>
                                    <option value="USD">$USD (US Dollar)</option>
                                    <option value="EUR">€EUR (Euro)</option>
                                    <option value="GBP">£GBP (British Pound)</option>
                                    <option value="AED">AED (UAE Dirham)</option>
                                    <option value="SGD">S$SGD (Singapore Dollar)</option>
                                    <option value="AUD">A$AUD (Australian Dollar)</option>
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

                    <!-- Step 4: Assign Modules & Pages validity -->
                    <div id="step-modules" class="mt-4 pt-4 border-top">
                        <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-list-check me-1"></i> Step 4: Assign ERP Modules & Page Validity</h6>
                        <p class="text-secondary small mb-3">Check each module/page to grant access, and set their individual expiration dates. Date fields are automatically disabled for Lifetime subscription plans.</p>

                        <div class="row g-3">
                            <?php if (!empty($allPermissions)): ?>
                                <?php foreach ($allPermissions as $perm): 
                                    $cleanLabel = ucwords(str_replace(['company.', '.', 'view', 'manage'], [' ', ' ', ' (View)', ' (Manage)'], $perm['name']));
                                    
                                    $moduleName = 'Other';
                                    $bgColor = '#6c757d';
                                    $textColor = '#ffffff';

                                    if (strpos($perm['name'], 'company.users') !== false || strpos($perm['name'], 'company.payroll') !== false) {
                                        $moduleName = 'HR/Users';
                                        $bgColor = '#6f42c1';
                                    } elseif (strpos($perm['name'], 'company.roles') !== false) {
                                        $moduleName = 'Security';
                                        $bgColor = '#343a40';
                                    } elseif (strpos($perm['name'], 'company.styles') !== false) {
                                        $moduleName = 'Styles/Merch';
                                        $bgColor = '#198754';
                                    } elseif (strpos($perm['name'], 'company.purchase') !== false) {
                                        $moduleName = 'Procure';
                                        $bgColor = '#ffc107';
                                        $textColor = '#212529';
                                    } elseif (strpos($perm['name'], 'company.inventory') !== false) {
                                        $moduleName = 'Inventory';
                                        $bgColor = '#0dcaf0';
                                        $textColor = '#212529';
                                    } elseif (strpos($perm['name'], 'company.production') !== false) {
                                        $moduleName = 'Production';
                                        $bgColor = '#0d6efd';
                                    } elseif (strpos($perm['name'], 'company.tally') !== false) {
                                        $moduleName = 'Tally';
                                        $bgColor = '#6610f2';
                                    } elseif (strpos($perm['name'], 'company.logs') !== false) {
                                        $moduleName = 'Audit Logs';
                                        $bgColor = '#dc3545';
                                    } elseif (strpos($perm['name'], 'company.settings') !== false) {
                                        $moduleName = 'Settings';
                                        $bgColor = '#495057';
                                    } elseif (strpos($perm['name'], 'company.dashboard') !== false) {
                                        $moduleName = 'Core';
                                        $bgColor = '#20c997';
                                    }
                                ?>
                                    <div class="col-md-6 border-bottom pb-2">
                                        <div class="form-check">
                                            <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="<?= htmlspecialchars($perm['name']) ?>" id="perm_onboard_<?= $perm['id'] ?>" checked>
                                            <label class="form-check-label fw-semibold text-dark d-inline-flex align-items-center" for="perm_onboard_<?= $perm['id'] ?>">
                                                <span class="badge rounded-pill me-2" style="font-size: 10px; background-color: <?= $bgColor ?>; color: <?= $textColor ?>; padding: 3px 8px;"><?= $moduleName ?></span>
                                                <?= htmlspecialchars($cleanLabel) ?>
                                            </label>
                                        </div>
                                        <div class="mt-1 ps-4 expiry-input-container">
                                            <label class="small text-muted d-block mb-1">Access Expiration Date</label>
                                            <input type="date" name="expiry_date[<?= htmlspecialchars($perm['name']) ?>]" class="form-control form-control-sm expiry-date-input" style="max-width: 180px;">
                                        </div>
                                        <div class="mt-2 ps-4 label-input-container">
                                             <label class="small text-muted d-block mb-1">Release Label</label>
                                             <select name="labels[<?= htmlspecialchars($perm['name']) ?>]" class="form-select form-select-sm release-label-select fw-semibold" style="max-width: 150px;">
                                                 <option value="no_label" selected>No Label</option>
                                                 <option value="draft" style="color: #664d03; background-color: #fff3cd;">⚠️ Draft</option>
                                                 <option value="beta" style="color: #087990; background-color: #cff4fc;">🧪 Beta</option>
                                                 <option value="new" style="color: #842029; background-color: #f8d7da;">🔥 New</option>
                                             </select>
                                         </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    function checkValidityInputs(selectEl) {
        const selectedOption = selectEl.options[selectEl.selectedIndex];
        const cycle = selectedOption ? selectedOption.getAttribute('data-cycle') : '';
        const modal = selectEl.closest('.modal-body') || selectEl.closest('.modal-content');
        if (!modal) return;

        const mainExpiryInput = modal.querySelector('.subscription-expiry-date-main');
        const mainExpiryContainer = modal.querySelector('.expiry-main-container');
        const dateInputs = modal.querySelectorAll('.expiry-date-input');
        const containers = modal.querySelectorAll('.expiry-input-container');

        if (cycle === 'lifetime') {
            if (mainExpiryInput) {
                mainExpiryInput.value = '';
                mainExpiryInput.disabled = true;
            }
            if (mainExpiryContainer) {
                mainExpiryContainer.style.opacity = '0.5';
                mainExpiryContainer.style.pointerEvents = 'none';
            }

            dateInputs.forEach(function(input) {
                input.value = '';
                input.disabled = true;
            });
            containers.forEach(function(container) {
                container.style.opacity = '0.5';
                container.style.pointerEvents = 'none';
            });
        } else {
            if (mainExpiryInput) {
                mainExpiryInput.disabled = false;
            }
            if (mainExpiryContainer) {
                mainExpiryContainer.style.opacity = '1';
                mainExpiryContainer.style.pointerEvents = 'auto';
            }

            dateInputs.forEach(function(input) {
                input.disabled = false;
            });
            containers.forEach(function(container) {
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            });
        }
    }

    document.querySelectorAll('.plan-select').forEach(function(selectEl) {
        selectEl.addEventListener('change', function() {
            checkValidityInputs(this);
        });
        // Run initially
        checkValidityInputs(selectEl);
    });

    document.querySelectorAll('.subscription-expiry-date-main').forEach(function(mainInput) {
        mainInput.addEventListener('change', function() {
            const chosenDate = this.value;
            const modal = this.closest('.modal-body') || this.closest('.modal-content');
            if (modal && chosenDate) {
                modal.querySelectorAll('.expiry-date-input').forEach(function(featureInput) {
                    if (!featureInput.disabled) {
                        featureInput.value = chosenDate;
                    }
                });
            }
        });
    });

    function updateLabelSelectStyle(selectEl) {
        const val = selectEl.value;
        if (val === 'draft') {
            selectEl.style.backgroundColor = '#fff3cd';
            selectEl.style.color = '#664d03';
            selectEl.style.borderColor = '#ffc107';
        } else if (val === 'beta') {
            selectEl.style.backgroundColor = '#cff4fc';
            selectEl.style.color = '#087990';
            selectEl.style.borderColor = '#0dcaf0';
        } else if (val === 'new') {
            selectEl.style.backgroundColor = '#f8d7da';
            selectEl.style.color = '#842029';
            selectEl.style.borderColor = '#dc3545';
        } else {
            selectEl.style.backgroundColor = '';
            selectEl.style.color = '';
            selectEl.style.borderColor = '';
        }
    }

    document.querySelectorAll('.release-label-select').forEach(function(selectEl) {
        selectEl.addEventListener('change', function() {
            updateLabelSelectStyle(this);
        });
        updateLabelSelectStyle(selectEl);
    });
});
</script>
