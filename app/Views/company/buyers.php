<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0 text-dark"><i class="fa-solid fa-user-tie text-primary me-2"></i> Buyers & Clients Master</h3>
        <p class="text-secondary small m-0 mt-1">Manage global export buyers, brand accounts, payment terms, and client profiles</p>
    </div>
    <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
        <button class="btn btn-pepp-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addBuyerModal">
            <i class="fa-solid fa-plus me-1"></i> Register New Buyer Client
        </button>
    <?php endif; ?>
</div>

<!-- Key Performance Indicators (Metrics Row) -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="pepp-card p-3 d-flex align-items-center">
            <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                <i class="fa-solid fa-building fs-3"></i>
            </div>
            <div>
                <div class="text-secondary small fw-semibold">Total Buyers Registered</div>
                <h4 class="fw-bold m-0 text-dark"><?= number_format($total_buyers) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="pepp-card p-3 d-flex align-items-center">
            <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                <i class="fa-solid fa-circle-check fs-3"></i>
            </div>
            <div>
                <div class="text-secondary small fw-semibold">Active Commercial Clients</div>
                <h4 class="fw-bold m-0 text-success"><?= number_format($active_buyers) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="pepp-card p-3 d-flex align-items-center">
            <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3 text-warning">
                <i class="fa-solid fa-pause-circle fs-3"></i>
            </div>
            <div>
                <div class="text-secondary small fw-semibold">Accounts On Hold / Inactive</div>
                <h4 class="fw-bold m-0 text-warning"><?= number_format($on_hold_buyers) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="pepp-card p-3 d-flex align-items-center">
            <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3 text-info">
                <i class="fa-solid fa-money-bill-transfer fs-3"></i>
            </div>
            <div>
                <div class="text-secondary small fw-semibold">Export Currencies</div>
                <h4 class="fw-bold m-0 text-info"><?= number_format($currencies_count) ?> Active</h4>
            </div>
        </div>
    </div>
</div>

<!-- Buyers Directory Table -->
<div class="pepp-card">
    <div class="pepp-card-header d-flex justify-content-between align-items-center">
        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-address-book text-primary me-2"></i> Client Directory Ledger</h5>
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="buyerSearchInput" class="form-control form-control-sm" placeholder="Search buyer or brand..." style="width: 240px;">
        </div>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0" id="buyersTable">
                <thead>
                    <tr>
                        <th>Buyer Code & Brand</th>
                        <th>Client / Company Name</th>
                        <th>Contact Person & Info</th>
                        <th>Country & Currency</th>
                        <th>Payment Terms</th>
                        <th>GSTIN / Tax ID</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($buyers)): ?>
                        <?php foreach ($buyers as $b): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= htmlspecialchars($b['code']) ?></strong>
                                    <?php if (!empty($b['brand_name'])): ?>
                                        <div class="small text-secondary fw-semibold"><i class="fa-solid fa-tag me-1"></i><?= htmlspecialchars($b['brand_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-dark fs-6"><?= htmlspecialchars($b['name']) ?></strong>
                                    <?php if (!empty($b['address'])): ?>
                                        <div class="small text-secondary text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($b['address']) ?>">
                                            <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($b['address']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($b['contact_person'] ?: 'N/A') ?></div>
                                    <div class="small text-secondary">
                                        <?php if ($b['email']): ?><span><i class="fa-solid fa-envelope me-1"></i><?= htmlspecialchars($b['email']) ?></span><br><?php endif; ?>
                                        <?php if ($b['phone']): ?><span><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($b['phone']) ?></span><?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark font-monospace"><i class="fa-solid fa-globe me-1"></i><?= htmlspecialchars($b['country'] ?: 'India') ?></span>
                                    <div class="mt-1"><span class="badge bg-primary bg-opacity-10 text-primary font-monospace"><?= htmlspecialchars($b['currency'] ?: 'INR') ?></span></div>
                                </td>
                                <td>
                                    <span class="small text-dark fw-semibold"><?= htmlspecialchars($b['payment_terms'] ?: 'Standard Terms') ?></span>
                                </td>
                                <td>
                                    <span class="font-monospace text-secondary small"><?= htmlspecialchars($b['gstin'] ?: 'N/A') ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-pepp 
                                        <?php 
                                            if ($b['status'] === 'active') echo 'badge-success';
                                            elseif ($b['status'] === 'on_hold') echo 'badge-warning';
                                            else echo 'badge-danger';
                                        ?>">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $b['status']))) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                        <!-- Quick Status Toggle Dropdown -->
                                        <div class="btn-group me-1">
                                            <button type="button" class="btn btn-sm btn-light border dropdown-toggle" data-bs-toggle="dropdown">
                                                Status
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <form action="<?= base_url('company/buyers/status/' . $b['id']) ?>" method="POST">
                                                        <?= \App\Core\Session::csrfField() ?>
                                                        <input type="hidden" name="status" value="active">
                                                        <button type="submit" class="dropdown-item text-success"><i class="fa-solid fa-check me-2"></i> Set Active</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="<?= base_url('company/buyers/status/' . $b['id']) ?>" method="POST">
                                                        <?= \App\Core\Session::csrfField() ?>
                                                        <input type="hidden" name="status" value="on_hold">
                                                        <button type="submit" class="dropdown-item text-warning"><i class="fa-solid fa-pause me-2"></i> Set On Hold</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="<?= base_url('company/buyers/status/' . $b['id']) ?>" method="POST">
                                                        <?= \App\Core\Session::csrfField() ?>
                                                        <input type="hidden" name="status" value="inactive">
                                                        <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-ban me-2"></i> Set Inactive</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Edit Modal Trigger -->
                                        <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#editBuyerModal-<?= $b['id'] ?>" title="Edit Details">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>

                                        <!-- Delete Form -->
                                        <form action="<?= base_url('company/buyers/delete/' . $b['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this buyer client?');">
                                            <?= \App\Core\Session::csrfField() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Delete Buyer"><i class="fa-solid fa-trash-can"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Edit Buyer Modal -->
                            <div class="modal fade" id="editBuyerModal-<?= $b['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <form action="<?= base_url('company/buyers/edit/' . $b['id']) ?>" method="POST">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <div class="modal-content text-start" style="border-radius: var(--border-radius-lg);">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen text-primary me-2"></i> Edit Buyer Client: <?= htmlspecialchars($b['name']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Buyer Code <span class="text-danger">*</span></label>
                                                        <input type="text" name="code" class="form-control font-monospace" value="<?= htmlspecialchars($b['code']) ?>" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Company / Client Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($b['name']) ?>" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Brand / Division Name</label>
                                                        <input type="text" name="brand_name" class="form-control" value="<?= htmlspecialchars($b['brand_name'] ?? '') ?>" placeholder="e.g. Zara Woman">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Contact Person Name</label>
                                                        <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($b['contact_person'] ?? '') ?>" placeholder="e.g. Alexander Wright">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Contact Email</label>
                                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($b['email'] ?? '') ?>" placeholder="buyer@client.com">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Contact Phone</label>
                                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($b['phone'] ?? '') ?>" placeholder="+1 555-0192">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-semibold">Country / Region</label>
                                                        <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($b['country'] ?: 'India') ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-semibold">Billing Currency</label>
                                                        <select name="currency" class="form-select">
                                                            <option value="INR" <?= ($b['currency'] === 'INR') ? 'selected' : '' ?>>INR (₹)</option>
                                                            <option value="USD" <?= ($b['currency'] === 'USD') ? 'selected' : '' ?>>USD ($)</option>
                                                            <option value="EUR" <?= ($b['currency'] === 'EUR') ? 'selected' : '' ?>>EUR (€)</option>
                                                            <option value="GBP" <?= ($b['currency'] === 'GBP') ? 'selected' : '' ?>>GBP (£)</option>
                                                            <option value="AED" <?= ($b['currency'] === 'AED') ? 'selected' : '' ?>>AED (د.إ)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-semibold">Account Status</label>
                                                        <select name="status" class="form-select">
                                                            <option value="active" <?= ($b['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                                            <option value="on_hold" <?= ($b['status'] === 'on_hold') ? 'selected' : '' ?>>On Hold</option>
                                                            <option value="inactive" <?= ($b['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Payment Terms</label>
                                                        <input type="text" name="payment_terms" class="form-control" value="<?= htmlspecialchars($b['payment_terms'] ?? '') ?>" placeholder="e.g. LC 60 Days / Net 30 Days">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">GSTIN / Tax Registration ID</label>
                                                        <input type="text" name="gstin" class="form-control font-monospace" value="<?= htmlspecialchars($b['gstin'] ?? '') ?>" placeholder="33AAAAT1234A1Z1">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Billing Address</label>
                                                        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($b['address'] ?? '') ?></textarea>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Shipping / Destination Address</label>
                                                        <textarea name="shipping_address" class="form-control" rows="3"><?= htmlspecialchars($b['shipping_address'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-pepp-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Save Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-user-tie fs-1 mb-3 text-light"></i>
                                <p class="m-0">No buyer / client accounts registered yet. Register your first buyer to get started.</p>
                                <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                    <button class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addBuyerModal">
                                        <i class="fa-solid fa-plus me-1"></i> Register First Buyer Client
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Buyer Modal -->
<div class="modal fade" id="addBuyerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="<?= base_url('company/buyers/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content text-start" style="border-radius: var(--border-radius-lg);">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus text-primary me-2"></i> Register New Buyer Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company / Client Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Inditex / Zara Group" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Buyer Code</label>
                            <input type="text" name="code" class="form-control font-monospace" placeholder="Auto-generated if blank (e.g. BUY-1001)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Brand / Division Name</label>
                            <input type="text" name="brand_name" class="form-control" placeholder="e.g. Zara Woman / Massimo Dutti">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contact Person Name</label>
                            <input type="text" name="contact_person" class="form-control" placeholder="e.g. Alexander Wright">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contact Email</label>
                            <input type="email" name="email" class="form-control" placeholder="buyer@client.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contact Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="+1 555-0192">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Country / Region</label>
                            <input type="text" name="country" class="form-control" value="Spain" placeholder="e.g. Spain / USA / UK">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Billing Currency</label>
                            <select name="currency" class="form-select">
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="INR">INR (₹)</option>
                                <option value="GBP">GBP (£)</option>
                                <option value="AED">AED (د.إ)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Account Status</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="on_hold">On Hold</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Terms</label>
                            <input type="text" name="payment_terms" class="form-control" placeholder="e.g. LC 60 Days / Net 30 Days">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">GSTIN / Tax Registration ID</label>
                            <input type="text" name="gstin" class="form-control font-monospace" placeholder="e.g. 33AAAAT1234A1Z1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Billing Address</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Headquarters billing address..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Shipping / Destination Address</label>
                            <textarea name="shipping_address" class="form-control" rows="3" placeholder="Port of discharge / warehouse shipping destination..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-pepp-primary"><i class="fa-solid fa-circle-check me-1"></i> Register Buyer Client</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('buyerSearchInput');
    const table = document.getElementById('buyersTable');
    if (searchInput && table) {
        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase();
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            for (let row of rows) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            }
        });
    }
});
</script>
