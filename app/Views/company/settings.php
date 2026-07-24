<div class="mb-4">
    <h3 class="fw-bold">ERP settings</h3>
    <p class="text-secondary m-0">Manage company details, billing parameters, and security protocols</p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="pepp-card">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-sliders text-primary me-2"></i> Company Profile & Preferences</h5>
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

        <div class="pepp-card mt-4">
            <div class="pepp-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="pepp-card-title m-0"><i class="fa-solid fa-list-check text-primary me-2"></i> WIP Operational Stages Configuration</h5>
                    <small class="text-secondary">Manage company manufacturing stages, execution sequence numbers, and custom processes</small>
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
                                <th>Order #</th>
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
                                            <span class="badge bg-primary text-white font-monospace fs-6 px-2.5 py-1">#<?= (int)$stg['order'] ?></span>
                                        </td>
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

                                    <!-- Edit WIP Stage Modal -->
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
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Execution Sequence Order # <span class="text-danger">*</span></label>
                                                            <input type="number" name="stage_order" class="form-control text-dark font-monospace" value="<?= (int)$stg['order'] ?>" min="1" required>
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
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-secondary">No WIP operational stages configured.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Execution Sequence Order # <span class="text-danger">*</span></label>
                            <input type="number" name="stage_order" class="form-control text-dark font-monospace" value="<?= count($companyWipStages ?? []) + 1 ?>" min="1" required>
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
    </div>

    <!-- Sidebar context cards -->
    <div class="col-md-4">
        <div class="pepp-card mb-4">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-file-contract text-secondary me-2"></i> Subscription Summary</h5>
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
                <h6 class="fw-bold"><i class="fa-solid fa-industry"></i> TOCCO Exports Pilot</h6>
                <p class="text-secondary mt-2">
                    This instance is optimized specifically for apparel manufacturing exports, with customized workflows for Tiruppur, India.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Payment Accounts Card -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="pepp-card">
            <div class="pepp-card-header d-flex justify-content-between align-items-center">
                <h5 class="pepp-card-title m-0"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Payment Accounts (ERP Cashbooks)</h5>
                <button class="btn btn-sm btn-pepp-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                    <i class="fa-solid fa-plus-circle me-1"></i> Add Payment Account
                </button>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table table-hover pepp-table mb-0">
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

                                    <!-- Edit Account Modal -->
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
</div>

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
    const editGstSelects = document.querySelectorAll('.edit-gst-trigger');
    editGstSelects.forEach(select => {
        select.addEventListener('change', function() {
            const accId = this.getAttribute('data-id');
            const editGstContainer = document.querySelector('.edit-gst-field-container-' + accId);
            if (editGstContainer) {
                editGstContainer.style.display = this.value === 'Yes' ? 'block' : 'none';
            }
        });
    });

    // ── Multi-Modal Sidebar Menu Reordering (Drag & Drop, Up/Down Buttons, Position Inputs) ──
    const menuList = document.getElementById('draggable-menu-list');
    const hiddenInput = document.getElementById('sidebar_menu_order_input');
    const saveBtn = document.getElementById('save-menu-order-btn');
    const resetBtn = document.getElementById('reset-default-order-btn');
    const statusText = document.getElementById('menu-order-status');

    if (menuList && hiddenInput) {
        // Sync position numbers, hidden input JSON, and UI status
        function syncOrderState(isSaved = false) {
            const items = menuList.querySelectorAll('.draggable-menu-item');
            const keys = [];
            
            items.forEach((item, index) => {
                const key = item.dataset.key;
                keys.push(key);
                
                const posInput = item.querySelector('.pos-input');
                if (posInput) {
                    posInput.value = index + 1;
                }
            });

            hiddenInput.value = JSON.stringify(keys);

            if (statusText) {
                if (isSaved) {
                    statusText.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i> Order saved & active';
                } else {
                    statusText.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-warning me-1"></i> Unsaved changes pending';
                }
            }
        }

        // 1. Move Up / Move Down Button Listeners
        menuList.addEventListener('click', function(e) {
            const moveUpBtn = e.target.closest('.move-up-btn');
            const moveDownBtn = e.target.closest('.move-down-btn');
            
            if (moveUpBtn) {
                const item = moveUpBtn.closest('.draggable-menu-item');
                const prev = item.previousElementSibling;
                if (prev) {
                    menuList.insertBefore(item, prev);
                    syncOrderState(false);
                }
            } else if (moveDownBtn) {
                const item = moveDownBtn.closest('.draggable-menu-item');
                const next = item.nextElementSibling;
                if (next) {
                    menuList.insertBefore(item, next.nextElementSibling);
                    syncOrderState(false);
                }
            }
        });

        // 2. Position Number Input Change Listener
        menuList.addEventListener('change', function(e) {
            if (!e.target.classList.contains('pos-input')) return;

            const input = e.target;
            const item = input.closest('.draggable-menu-item');
            const totalItems = menuList.querySelectorAll('.draggable-menu-item').length;
            let targetPos = parseInt(input.value, 10);

            if (isNaN(targetPos) || targetPos < 1) targetPos = 1;
            if (targetPos > totalItems) targetPos = totalItems;

            const items = Array.from(menuList.querySelectorAll('.draggable-menu-item'));
            const currentIndex = items.indexOf(item);
            const targetIndex = targetPos - 1;

            if (currentIndex !== targetIndex) {
                if (targetIndex >= items.length - 1) {
                    menuList.appendChild(item);
                } else if (targetIndex > currentIndex) {
                    menuList.insertBefore(item, items[targetIndex].nextElementSibling);
                } else {
                    menuList.insertBefore(item, items[targetIndex]);
                }
            }

            syncOrderState(false);
        });

        // 3. HTML5 Drag and Drop Handlers
        let draggedItem = null;

        menuList.addEventListener('dragstart', function(e) {
            draggedItem = e.target.closest('.draggable-menu-item');
            if (!draggedItem) return;

            draggedItem.classList.add('opacity-50', 'bg-light');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', draggedItem.dataset.key);
        });

        menuList.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            const targetItem = e.target.closest('.draggable-menu-item');
            if (!targetItem || targetItem === draggedItem) return;

            const rect = targetItem.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;

            if (e.clientY < midpoint) {
                menuList.insertBefore(draggedItem, targetItem);
            } else {
                menuList.insertBefore(draggedItem, targetItem.nextElementSibling);
            }
        });

        menuList.addEventListener('drop', function(e) {
            e.preventDefault();
        });

        menuList.addEventListener('dragend', function(e) {
            if (draggedItem) {
                draggedItem.classList.remove('opacity-50', 'bg-light');
                draggedItem = null;
            }
            syncOrderState(false);
        });

        // 4. Reset Default Order Button
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                const defaultOrder = ['dashboard','hr','production','merchandising','styles','buyers','inventory','procurement','masterdata','users','roles','tally','logs','settings','rfid_tracking'];
                const itemMap = {};
                
                menuList.querySelectorAll('.draggable-menu-item').forEach(item => {
                    itemMap[item.dataset.key] = item;
                });

                defaultOrder.forEach(key => {
                    if (itemMap[key]) {
                        menuList.appendChild(itemMap[key]);
                    }
                });

                syncOrderState(false);
            });
        }

        // 5. Save Button AJAX Action
        if (saveBtn) {
            saveBtn.addEventListener('click', function() {
                if (!hiddenInput.value) return;

                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...';

                const formData = new FormData();
                formData.append('sidebar_menu_order', hiddenInput.value);
                formData.append('csrf_token', '<?= \App\Core\Session::csrfToken() ?>');

                fetch('<?= base_url("company/settings/menu-order") ?>', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '<?= \App\Core\Session::csrfToken() ?>'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        syncOrderState(true);
                        window.location.reload();
                    } else {
                        alert(data.error || 'Failed to save sidebar menu order.');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Save Sidebar Navigation Order';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred while saving.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Save Sidebar Navigation Order';
                });
            });
        }
    }
});
</script>
