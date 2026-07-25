<div class="mb-4">
    <h3 class="fw-bold">ERP Settings</h3>
    <p class="text-secondary m-0">Manage company details, billing parameters, and security protocols</p>
</div>

<div class="row g-4">
    <!-- Main Left Column -->
    <div class="col-lg-8">
        <!-- Company Profile & Preferences -->
        <div class="pepp-card mb-4">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title m-0"><i class="fa-solid fa-sliders text-primary me-2"></i> Company Profile & Preferences</h5>
            </div>
            <div class="pepp-card-body">
                <form action="<?= base_url('company/settings') ?>" method="POST">
                    <?= \App\Core\Session::csrfField() ?>
                    <input type="hidden" id="sidebar_menu_order_input" value="<?= htmlspecialchars($settings['sidebar_menu_order'] ?? '') ?>">

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-id-card me-1"></i> General Details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company Registered Name</label>
                            <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($company['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">GSTIN (Tax ID)</label>
                            <input type="text" name="gstin" class="form-control" value="<?= htmlspecialchars($company['gstin'] ?? '') ?>" placeholder="e.g. 33AAAAT1234A1Z1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Corporate Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($company['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contact Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($company['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Factory / Office Address</label>
                            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-globe me-1"></i> Regional Preferences</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Standard Timezone</label>
                            <select name="timezone" class="form-select">
                                <option value="Asia/Kolkata" <?= ($settings['timezone'] ?? 'Asia/Kolkata') === 'Asia/Kolkata' ? 'selected' : '' ?>>India (IST - Asia/Kolkata)</option>
                                <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>Coordinated Universal Time (UTC)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Base Currency Code</label>
                            <select name="currency" class="form-select">
                                <option value="INR" <?= ($settings['currency'] ?? 'INR') === 'INR' ? 'selected' : '' ?>>Indian Rupee (₹ - INR)</option>
                                <option value="USD" <?= ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>US Dollar ($ - USD)</option>
                                <option value="EUR" <?= ($settings['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>Euro (€ - EUR)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-pepp-primary">
                        <i class="fa-solid fa-circle-check me-1"></i> Save Company Profile
                    </button>
                </form>
            </div>
        </div>

        <!-- WIP Operational Stages Configuration -->
        <div class="pepp-card mb-4">
            <div class="pepp-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="pepp-card-title m-0"><i class="fa-solid fa-list-check text-primary me-2"></i> WIP Operational Stages Configuration</h5>
                    <small class="text-secondary">Manage company manufacturing stages and custom processes</small>
                </div>
                <button type="button" class="btn btn-sm btn-pepp-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addWipStageModal">
                    <i class="fa-solid fa-plus-circle me-1"></i> Add New WIP Stage
                </button>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table pepp-table mb-0 align-middle">
                        <thead>
                            <tr class="bg-light">
                                <th>Stage Display Name</th>
                                <th>System Key</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($companyWipStages)): ?>
                                <?php foreach ($companyWipStages as $stg): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark fs-6"><?= htmlspecialchars($stg['name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border font-monospace"><?= htmlspecialchars($stg['key']) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1" data-bs-toggle="modal" data-bs-target="#editWipStageModal-<?= htmlspecialchars($stg['key']) ?>">
                                                <i class="fa-solid fa-edit me-1"></i> Edit
                                            </button>
                                            <form action="<?= base_url('company/settings/wip-stages/delete/' . urlencode($stg['key'])) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete WIP stage \'<?= htmlspecialchars($stg['name']) ?>\'?');">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-secondary">No WIP operational stages configured.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Accounts Card -->
        <div class="pepp-card mb-4">
            <div class="pepp-card-header d-flex justify-content-between align-items-center">
                <h5 class="pepp-card-title m-0"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Payment Accounts (ERP Cashbooks)</h5>
                <button class="btn btn-sm btn-pepp-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                    <i class="fa-solid fa-plus-circle me-1"></i> Add Payment Account
                </button>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table table-hover pepp-table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Account Name</th>
                                <th>Account Type</th>
                                <th>GST Account</th>
                                <th>GST %</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($paymentAccounts)): ?>
                                <?php foreach ($paymentAccounts as $acc): ?>
                                    <tr>
                                        <td><strong class="text-dark"><?= htmlspecialchars($acc['name']) ?></strong></td>
                                        <td>
                                            <span class="badge bg-light text-dark font-monospace"><?= htmlspecialchars($acc['type']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $acc['gst_account'] === 'Yes' ? 'bg-success text-white' : 'bg-secondary text-white' ?>">
                                                <?= htmlspecialchars($acc['gst_account']) ?>
                                            </span>
                                        </td>
                                        <td class="font-monospace">
                                            <?= $acc['gst_account'] === 'Yes' ? number_format($acc['gst_percent'], 1) . '%' : '--' ?>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary me-1 rounded-pill" data-bs-toggle="modal" data-bs-target="#editAccountModal-<?= $acc['id'] ?>">
                                                <i class="fa-solid fa-edit"></i> Edit
                                            </button>
                                            <form action="<?= base_url('company/settings/payment-accounts/delete/' . $acc['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this payment account?');">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center p-5 text-secondary">
                                        <i class="fa-solid fa-building-columns fs-1 mb-3 text-light"></i>
                                        <p class="m-0">No payment accounts configured yet.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar Column -->
    <div class="col-lg-4">
        <div class="pepp-card mb-4">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title m-0"><i class="fa-solid fa-file-contract text-secondary me-2"></i> Subscription Summary</h5>
            </div>
            <div class="pepp-card-body" style="font-size: 14px;">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Billing Tier:</span>
                    <strong>Garment Lifetime</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Max Users:</span>
                    <strong>9,999 (Unlimited)</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Max Branches:</span>
                    <strong>99 (Unlimited)</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Expiry Date:</span>
                    <strong>21-Jul-2036</strong>
                </div>
            </div>
        </div>

        <div class="pepp-card bg-light border-dashed">
            <div class="pepp-card-body" style="font-size: 13.5px;">
                <h6 class="fw-bold"><i class="fa-solid fa-industry me-1 text-primary"></i> TOCCO Exports Instance</h6>
                <p class="text-secondary mt-2 mb-0">
                    This instance is optimized specifically for apparel manufacturing exports, with customized workflows and real-time WIP operations tracking.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODALS CONTAINER ===================== -->

<!-- Add WIP Stage Modal -->
<div class="modal fade" id="addWipStageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('company/settings/wip-stages/add') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content text-start" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle text-primary me-2"></i> Add New WIP Operational Stage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Stage Display Name <span class="text-danger">*</span></label>
                        <input type="text" name="stage_name" class="form-control text-dark" placeholder="e.g. Sublimation Printing" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">System Key (Optional - Auto-slugified)</label>
                        <input type="text" name="stage_key" class="form-control font-monospace" placeholder="e.g. sublimation_printing">
                        <div class="form-text">Unique system identifier key (lowercase, underscore).</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4">Add WIP Stage</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit WIP Stage Modals -->
<?php if (!empty($companyWipStages)): ?>
    <?php foreach ($companyWipStages as $stg): ?>
        <div class="modal fade" id="editWipStageModal-<?= htmlspecialchars($stg['key']) ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form action="<?= base_url('company/settings/wip-stages/edit') ?>" method="POST">
                    <?= \App\Core\Session::csrfField() ?>
                    <input type="hidden" name="original_key" value="<?= htmlspecialchars($stg['key']) ?>">
                    <div class="modal-content text-start" style="border-radius: 12px;">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold"><i class="fa-solid fa-edit text-primary me-2"></i> Edit WIP Stage</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-start">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Stage Display Name <span class="text-danger">*</span></label>
                                <input type="text" name="stage_name" class="form-control text-dark" value="<?= htmlspecialchars($stg['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">System Key</label>
                                <input type="text" class="form-control font-monospace bg-light" value="<?= htmlspecialchars($stg['key']) ?>" disabled>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary px-4">Update Stage</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Edit Payment Account Modals -->
<?php if (!empty($paymentAccounts)): ?>
    <?php foreach ($paymentAccounts as $acc): ?>
        <div class="modal fade" id="editAccountModal-<?= $acc['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form action="<?= base_url('company/settings/payment-accounts/edit/' . $acc['id']) ?>" method="POST">
                    <?= \App\Core\Session::csrfField() ?>
                    <div class="modal-content text-start" style="border-radius: 12px;">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold"><i class="fa-solid fa-edit text-primary me-2"></i> Edit Payment Account</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-start">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Account Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control text-dark" value="<?= htmlspecialchars($acc['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Account Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select text-dark" required>
                                    <option value="Bank" <?= $acc['type'] === 'Bank' ? 'selected' : '' ?>>Bank</option>
                                    <option value="Cash" <?= $acc['type'] === 'Cash' ? 'selected' : '' ?>>Cash</option>
                                    <option value="Digital Wallet" <?= $acc['type'] === 'Digital Wallet' ? 'selected' : '' ?>>Digital Wallet</option>
                                    <option value="Other" <?= $acc['type'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">GST Account <span class="text-danger">*</span></label>
                                <select name="gst_account" class="form-select text-dark edit-gst-trigger" data-id="<?= $acc['id'] ?>" required>
                                    <option value="No" <?= $acc['gst_account'] === 'No' ? 'selected' : '' ?>>No</option>
                                    <option value="Yes" <?= $acc['gst_account'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                </select>
                            </div>
                            <div class="mb-3 edit-gst-field-container-<?= $acc['id'] ?>" style="<?= $acc['gst_account'] === 'Yes' ? '' : 'display: none;' ?>">
                                <label class="form-label small fw-bold">GST %</label>
                                <input type="number" step="0.01" name="gst_percent" class="form-control text-dark" value="<?= htmlspecialchars($acc['gst_percent'] ?? '0.00') ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-pepp-primary px-4">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('company/settings/payment-accounts/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content text-start" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle text-primary me-2"></i> Add Payment Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Account Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control text-dark" placeholder="e.g. HDFC Bank Main A/c" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Account Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select text-dark" required>
                            <option value="Bank">Bank</option>
                            <option value="Cash">Cash</option>
                            <option value="Digital Wallet">Digital Wallet</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">GST Account <span class="text-danger">*</span></label>
                        <select name="gst_account" id="add_gst_account" class="form-select text-dark" required>
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div class="mb-3" id="add_gst_field_container" style="display: none;">
                        <label class="form-label small fw-bold">GST %</label>
                        <input type="number" step="0.01" name="gst_percent" class="form-control text-dark" value="18.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-pepp-primary px-4">Create Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add form GST toggle
    const addGstSelect = document.getElementById('add_gst_account');
    const addGstContainer = document.getElementById('add_gst_field_container');
    if (addGstSelect && addGstContainer) {
        addGstSelect.addEventListener('change', function() {
            addGstContainer.style.display = this.value === 'Yes' ? 'block' : 'none';
        });
    }

    // Edit form GST toggle
    document.querySelectorAll('.edit-gst-trigger').forEach(function(selectEl) {
        selectEl.addEventListener('change', function() {
            const accId = this.getAttribute('data-id');
            const container = document.querySelector('.edit-gst-field-container-' + accId);
            if (container) {
                container.style.display = this.value === 'Yes' ? 'block' : 'none';
            }
        });
    });
});
</script>
