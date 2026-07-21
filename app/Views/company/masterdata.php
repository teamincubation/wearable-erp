<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Master Data Hub</h3>
        <p class="text-secondary m-0">Central repository to manage apparel specifications, vendor/client contacts, branches, and warehouses</p>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4 border-bottom" id="masterDataTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold px-4 py-2.5" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories-pane" type="button" role="tab" aria-selected="true">
            <i class="fa-solid fa-list-check me-2 text-primary"></i> BOM Categories
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-4 py-2.5" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts-pane" type="button" role="tab" aria-selected="false">
            <i class="fa-solid fa-truck-field me-2 text-success"></i> Vendors & Logistics
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-4 py-2.5" id="locations-tab" data-bs-toggle="tab" data-bs-target="#locations-pane" type="button" role="tab" aria-selected="false">
            <i class="fa-solid fa-warehouse me-2 text-warning"></i> Warehouses & Branches
        </button>
    </li>
</ul>

<!-- Tabs Content -->
<div class="tab-content" id="masterDataTabsContent">
    
    <!-- 1. BOM Categories Pane -->
    <div class="tab-pane fade show active" id="categories-pane" role="tabpanel" tabindex="0">
        <div class="pepp-card">
            <div class="pepp-card-header d-flex justify-content-between align-items-center">
                <h5 class="pepp-card-title m-0"><i class="fa-solid fa-tags text-primary me-2"></i> Bill of Materials (BOM) Categories</h5>
                <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                    <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addBomCategoryModal">
                        <i class="fa-solid fa-plus-circle me-1"></i> Add BOM Category
                    </button>
                <?php endif; ?>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table pepp-table mb-0">
                        <thead>
                            <tr>
                                <th>Category Code</th>
                                <th>Category Name</th>
                                <th>Created Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><strong class="text-primary font-monospace"><?= htmlspecialchars($cat['code']) ?></strong></td>
                                        <td><strong class="text-dark"><?= htmlspecialchars($cat['name']) ?></strong></td>
                                        <td><?= date('d M Y H:i', strtotime($cat['created_at'])) ?></td>
                                        <td class="text-end">
                                            <form action="<?= base_url('company/masterdata/bomcategories/delete/' . $cat['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this BOM category?');">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center p-5 text-secondary">
                                        <i class="fa-solid fa-tags fs-1 mb-3 text-light"></i>
                                        <p class="m-0">No BOM categories added yet. Seed some to organize your Tech Pack items.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Contacts Pane -->
    <div class="tab-pane fade" id="contacts-pane" role="tabpanel" tabindex="0">
        <div class="pepp-card">
            <div class="pepp-card-header d-flex justify-content-between align-items-center">
                <h5 class="pepp-card-title m-0"><i class="fa-solid fa-truck-ramp-box text-success me-2"></i> Vendors & Logistics Ledger</h5>
                <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                    <button class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addContactModal">
                        <i class="fa-solid fa-user-plus me-1"></i> Register Vendor / Transporter
                    </button>
                <?php endif; ?>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table pepp-table mb-0">
                        <thead>
                            <tr>
                                <th>Party Code</th>
                                <th>Party Name</th>
                                <th>Contact Type</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>GSTIN</th>
                                <th>Address</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($contacts)): ?>
                                <?php foreach ($contacts as $con): ?>
                                    <tr>
                                        <td><strong class="text-success font-monospace"><?= htmlspecialchars($con['code']) ?></strong></td>
                                        <td><strong class="text-dark"><?= htmlspecialchars($con['name']) ?></strong></td>
                                        <td>
                                            <span class="badge badge-pepp text-capitalize
                                                <?php 
                                                    if ($con['type'] === 'supplier') echo 'bg-info text-dark';
                                                    elseif ($con['type'] === 'buyer') echo 'bg-primary';
                                                    else echo 'bg-light text-dark';
                                                ?>">
                                                <?= htmlspecialchars($con['type']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($con['email'] ?: '--') ?></td>
                                        <td><?= htmlspecialchars($con['phone'] ?: '--') ?></td>
                                        <td><span class="font-monospace text-secondary small"><?= htmlspecialchars($con['gstin'] ?: '--') ?></span></td>
                                        <td><span class="small text-secondary"><?= htmlspecialchars($con['address'] ?: '--') ?></span></td>
                                        <td class="text-end">
                                            <form action="<?= base_url('company/masterdata/contacts/delete/' . $con['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete party contact record?');">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center p-5 text-secondary">
                                        <i class="fa-solid fa-address-book fs-1 mb-3 text-light"></i>
                                        <p class="m-0">No buyer or vendor contacts registered yet.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Locations Pane -->
    <div class="tab-pane fade" id="locations-pane" role="tabpanel" tabindex="0">
        <div class="row g-4">
            
            <!-- Branches List -->
            <div class="col-md-6">
                <div class="pepp-card h-100">
                    <div class="pepp-card-header d-flex justify-content-between align-items-center">
                        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-code-branch text-warning me-2"></i> Company Branches</h5>
                        <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                            <button class="btn btn-sm btn-outline-warning rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                                <i class="fa-solid fa-plus-circle me-1"></i> Add Branch
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="pepp-card-body p-0">
                        <div class="table-responsive border-0">
                            <table class="table pepp-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Branch Code</th>
                                        <th>Branch Name</th>
                                        <th>Address Info</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($branches)): ?>
                                        <?php foreach ($branches as $br): ?>
                                            <tr>
                                                <td><strong class="text-warning font-monospace"><?= htmlspecialchars($br['code']) ?></strong></td>
                                                <td><strong class="text-dark"><?= htmlspecialchars($br['name']) ?></strong></td>
                                                <td><span class="small text-secondary"><?= htmlspecialchars($br['address'] ?: '--') ?></span></td>
                                                <td class="text-end">
                                                    <form action="<?= base_url('company/masterdata/branches/delete/' . $br['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete branch office?');">
                                                        <?= \App\Core\Session::csrfField() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center p-5 text-secondary">
                                                <p class="m-0">No branch locations configured.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warehouses List -->
            <div class="col-md-6">
                <div class="pepp-card h-100">
                    <div class="pepp-card-header d-flex justify-content-between align-items-center">
                        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-warehouse text-warning me-2"></i> Warehouse Stores</h5>
                        <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                            <button class="btn btn-sm btn-outline-warning rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addWarehouseModal">
                                <i class="fa-solid fa-plus-circle me-1"></i> Add Warehouse
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="pepp-card-body p-0">
                        <div class="table-responsive border-0">
                            <table class="table pepp-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Store Code</th>
                                        <th>Store Name</th>
                                        <th>Category Type</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($warehouses)): ?>
                                        <?php foreach ($warehouses as $wh): ?>
                                            <tr>
                                                <td><strong class="text-warning font-monospace"><?= htmlspecialchars($wh['code']) ?></strong></td>
                                                <td><strong class="text-dark"><?= htmlspecialchars($wh['name']) ?></strong></td>
                                                <td><span class="badge bg-light text-secondary text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $wh['type'])) ?></span></td>
                                                <td class="text-end">
                                                    <form action="<?= base_url('company/masterdata/warehouses/delete/' . $wh['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete warehouse store?');">
                                                        <?= \App\Core\Session::csrfField() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center p-5 text-secondary">
                                                <p class="m-0">No warehouse configurations registered.</p>
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
    </div>

</div>

<!-- Modal 1: Add BOM Category -->
<div class="modal fade" id="addBomCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('company/masterdata/bomcategories/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Register BOM Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Buttons, Threads, Sewing Yarn" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control font-monospace" placeholder="e.g. CAT-BTN" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4">Save Category</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Add Party Contact -->
<div class="modal fade" id="addContactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="<?= base_url('company/masterdata/contacts/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Register Vendor or Transporter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Contact Classification <span class="text-danger">*</span></label>
                            <select name="type" class="form-select text-dark" required>
                                <option value="supplier">Supplier / Material Vendor</option>
                                <option value="transporter">Logistics Transporter</option>
                                <option value="agent">Broker Agent</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Client / Vendor Reference Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control font-monospace" placeholder="e.g. VND-TRP-01" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label small fw-bold">Full Company / Person Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Tiruppur Dyeing Labs Pvt Ltd" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Business Email</label>
                            <input type="email" name="email" class="form-control" placeholder="billing@vendor.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Contact Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. +91 99000 88000">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label small fw-bold">GSTIN Registration ID</label>
                            <input type="text" name="gstin" class="form-control font-monospace" placeholder="33AAAAA1111A1Z1">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label small fw-bold">Physical Billing Address</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Vendor factory address, dispatch center, etc."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success px-4">Register Contact</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Add Branch -->
<div class="modal fade" id="addBranchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('company/masterdata/branches/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Register Branch Office</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Main Sewing Unit, Dyeing Lab" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Branch Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control font-monospace" placeholder="e.g. BR-MAIN" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Branch Address</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Address..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4">Save Branch</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal 4: Add Warehouse -->
<div class="modal fade" id="addWarehouseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('company/masterdata/warehouses/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Warehouse Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Warehouse Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Central Fabric Store" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Store Reference Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control font-monospace" placeholder="e.g. WH-FAB" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Link Branch Office</label>
                        <select name="branch_id" class="form-select text-dark">
                            <option value="">-- No Branch Mapping --</option>
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= $br['id'] ?>"><?= htmlspecialchars($br['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Storage Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select text-dark" required>
                            <option value="raw_material">Raw Materials</option>
                            <option value="yarn">Yarn Storage</option>
                            <option value="fabric" selected>Fabric Store</option>
                            <option value="accessories">Accessories/Trims</option>
                            <option value="chemical">Chemicals & Dyes</option>
                            <option value="packing">Packing Store</option>
                            <option value="wip">WIP Floor Stock</option>
                            <option value="finished_goods">Finished Goods Warehouse</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4">Create Warehouse</button>
                </div>
            </div>
        </form>
    </div>
</div>
