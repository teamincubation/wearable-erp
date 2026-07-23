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
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-4 py-2.5" id="shifts-tab" data-bs-toggle="tab" data-bs-target="#shifts-pane" type="button" role="tab" aria-selected="false">
            <i class="fa-solid fa-clock me-2 text-info"></i> Shifts & Hours
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-4 py-2.5" id="hrpolicies-tab" data-bs-toggle="tab" data-bs-target="#hrpolicies-pane" type="button" role="tab" aria-selected="false">
            <i class="fa-solid fa-calendar-days me-2 text-danger"></i> HR Policies & Calendar
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-4 py-2.5" id="designations-tab" data-bs-toggle="tab" data-bs-target="#designations-pane" type="button" role="tab" aria-selected="false">
            <i class="fa-solid fa-id-card-clip me-2" style="color: #a855f7;"></i> Designations
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-4 py-2.5" id="style_vars-tab" data-bs-toggle="tab" data-bs-target="#style_vars-pane" type="button" role="tab" aria-selected="false">
            <i class="fa-solid fa-sliders me-2 text-primary"></i> Style Variables
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
                                            <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                                <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#editBomCatModal-<?= $cat['id'] ?>"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                                                <form action="<?= base_url('company/masterdata/bomcategories/delete/' . $cat['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this BOM category?');">
                                                    <?= \App\Core\Session::csrfField() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Edit BOM Category Modal -->
                                    <div class="modal fade" id="editBomCatModal-<?= $cat['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <form action="<?= base_url('company/masterdata/bomcategories/edit/' . $cat['id']) ?>" method="POST">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <div class="modal-content text-start" style="border-radius: 12px;">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Edit BOM Category</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Category Name <span class="text-danger">*</span></label>
                                                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($cat['name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Category Code <span class="text-danger">*</span></label>
                                                            <input type="text" name="code" class="form-control font-monospace" value="<?= htmlspecialchars($cat['code']) ?>" required>
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

    <!-- 4. Shifts & Hours Pane -->
    <div class="tab-pane fade" id="shifts-pane" role="tabpanel" tabindex="0">
        <div class="row g-4">
            
            <!-- General Working Hours Card -->
            <div class="col-md-4">
                <div class="pepp-card">
                    <div class="pepp-card-header">
                        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-hourglass-half text-info me-2"></i> General Working Hours</h5>
                    </div>
                    <div class="pepp-card-body">
                        <form action="<?= base_url('company/masterdata/generalhours') ?>" method="POST">
                            <?= \App\Core\Session::csrfField() ?>
                            <p class="text-secondary small mb-3">Define the default standard working duration in hours for employees. This value is used to compute standard attendance shifts and overtime hours dynamically.</p>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Standard Work Hours / Day <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="general_working_hours" class="form-control text-dark" min="1" max="24" value="<?= htmlspecialchars($general_working_hours) ?>" required>
                                    <span class="input-group-text bg-light text-secondary">Hrs</span>
                                </div>
                            </div>
                            <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                <button type="submit" class="btn btn-info text-white w-100"><i class="fa-solid fa-save me-1"></i> Save Working Hours</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Shift Schedules List -->
            <div class="col-md-8">
                <div class="pepp-card">
                    <div class="pepp-card-header d-flex justify-content-between align-items-center">
                        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-business-time text-info me-2"></i> Shift Schedules</h5>
                        <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                            <button class="btn btn-sm btn-info text-white rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                                <i class="fa-solid fa-plus-circle me-1"></i> Add Shift Schedule
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="pepp-card-body p-0">
                        <div class="table-responsive border-0">
                            <table class="table pepp-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Shift Title</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($shifts)): ?>
                                        <?php foreach ($shifts as $sh): ?>
                                            <tr>
                                                <td><strong class="text-dark"><?= htmlspecialchars($sh['name']) ?></strong></td>
                                                <td><span class="badge bg-light text-secondary font-monospace"><?= date('h:i A', strtotime($sh['start_time'])) ?></span></td>
                                                <td><span class="badge bg-light text-secondary font-monospace"><?= date('h:i A', strtotime($sh['end_time'])) ?></span></td>
                                                <td class="text-end">
                                                    <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                                        <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#editShiftModal-<?= $sh['id'] ?>"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                                                        <form action="<?= base_url('company/masterdata/shifts/delete/' . $sh['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this shift schedule?');">
                                                            <?= \App\Core\Session::csrfField() ?>
                                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <!-- Modal: Edit Shift -->
                                            <div class="modal fade" id="editShiftModal-<?= $sh['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <form action="<?= base_url('company/masterdata/shifts/edit/' . $sh['id']) ?>" method="POST">
                                                        <?= \App\Core\Session::csrfField() ?>
                                                        <div class="modal-content text-start" style="border-radius: 12px;">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold">Edit Shift Schedule</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-bold">Shift Title <span class="text-danger">*</span></label>
                                                                    <input type="text" name="name" class="form-control text-dark" value="<?= htmlspecialchars($sh['name']) ?>" required>
                                                                </div>
                                                                <div class="row g-3">
                                                                    <div class="col-6 mb-3">
                                                                        <label class="form-label small fw-bold">Start Time <span class="text-danger">*</span></label>
                                                                        <input type="time" name="start_time" class="form-control text-dark" value="<?= htmlspecialchars($sh['start_time']) ?>" required>
                                                                    </div>
                                                                    <div class="col-6 mb-3">
                                                                        <label class="form-label small fw-bold">End Time <span class="text-danger">*</span></label>
                                                                        <input type="time" name="end_time" class="form-control text-dark" value="<?= htmlspecialchars($sh['end_time']) ?>" required>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-info text-white px-4">Save Changes</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center p-5 text-secondary">
                                                <i class="fa-solid fa-business-time fs-1 mb-3 text-light"></i>
                                                <p class="m-0">No customized shift schedules defined yet.</p>
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

    <!-- 5. HR Policies & Calendar Pane -->
    <div class="tab-pane fade" id="hrpolicies-pane" role="tabpanel" tabindex="0">
        <div class="row g-4">
            
            <!-- Leaves & Cut Policies -->
            <div class="col-md-5">
                <div class="pepp-card">
                    <div class="pepp-card-header">
                        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-calculator text-danger me-2"></i> Leave Allocation & Salary Cut Policies</h5>
                    </div>
                    <div class="pepp-card-body">
                        <form action="<?= base_url('company/masterdata/hrpolicies') ?>" method="POST">
                            <?= \App\Core\Session::csrfField() ?>
                            
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-leaf text-success me-1"></i> Annual Leave Allowances</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-4">
                                    <label class="form-label small fw-bold">Casual Leave (CL)</label>
                                    <input type="number" name="leave_allocation_cl" class="form-control text-dark" value="<?= htmlspecialchars($policySettings['leave_allocation_cl'] ?? '12') ?>" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold">Sick Leave (SL)</label>
                                    <input type="number" name="leave_allocation_sl" class="form-control text-dark" value="<?= htmlspecialchars($policySettings['leave_allocation_sl'] ?? '10') ?>" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold">Privilege Leave (EL)</label>
                                    <input type="number" name="leave_allocation_el" class="form-control text-dark" value="<?= htmlspecialchars($policySettings['leave_allocation_el'] ?? '15') ?>" required>
                                </div>
                            </div>

                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-percent text-danger me-1"></i> Non-Paid Leave Salary Cuts</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Unexcused Absence Cut (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="cut_policy_absent" class="form-control text-dark" value="<?= htmlspecialchars($policySettings['cut_policy_absent'] ?? '100') ?>" required>
                                    <span class="input-group-text bg-light text-secondary">%</span>
                                </div>
                                <div class="form-text small">Deduction rate of the standard daily pay for an unexcused absent day (default 100%).</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Loss of Pay (LOP) Cut (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="cut_policy_lop" class="form-control text-dark" value="<?= htmlspecialchars($policySettings['cut_policy_lop'] ?? '100') ?>" required>
                                    <span class="input-group-text bg-light text-secondary">%</span>
                                </div>
                                <div class="form-text small">Deduction rate of standard daily pay for extra non-paid leave days (default 100%).</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Half Day Work Cut (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="cut_policy_halfday" class="form-control text-dark" value="<?= htmlspecialchars($policySettings['cut_policy_halfday'] ?? '50') ?>" required>
                                    <span class="input-group-text bg-light text-secondary">%</span>
                                </div>
                                <div class="form-text small">Deduction rate of standard daily pay for a half-day working session (default 50%).</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Overtime Pay/Hour Charge (<?= get_currency_symbol() ?>)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-secondary"><?= get_currency_symbol() ?></span>
                                    <input type="number" step="0.01" name="overtime_pay_hour_charge" class="form-control text-dark" value="<?= htmlspecialchars($policySettings['overtime_pay_hour_charge'] ?? '150.00') ?>" required>
                                </div>
                                <div class="form-text small">Standard rate paid per hour of overtime worked (default <?= get_currency_symbol() ?>150.00).</div>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 mt-2"><i class="fa-solid fa-save me-1"></i> Save Policies & Allocations</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Holidays & Weekends Planner -->
            <div class="col-md-7">
                <div class="pepp-card mb-4">
                    <div class="pepp-card-header d-flex justify-content-between align-items-center">
                        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-calendar text-primary me-2"></i> Holiday & Weekend Calendar</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                                <i class="fa-solid fa-plus-circle me-1"></i> Add Date
                            </button>
                            <button class="btn btn-sm btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#generateWeekendsModal">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto Weekends
                            </button>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cloneHolidaysModal">
                                <i class="fa-solid fa-clone me-1"></i> Clone Year
                            </button>
                        </div>
                    </div>
                    <div class="pepp-card-body p-0">
                        <div class="table-responsive border-0" style="max-height: 520px; overflow-y: auto;">
                            <table class="table pepp-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Occasion Name</th>
                                        <th>Category Type</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($holidays)): ?>
                                        <?php foreach ($holidays as $h): ?>
                                            <tr>
                                                <td><strong class="text-dark font-monospace"><?= date('d-M-Y (l)', strtotime($h['date'])) ?></strong></td>
                                                <td><span class="fw-semibold text-dark"><?= htmlspecialchars($h['name']) ?></span></td>
                                                <td>
                                                    <span class="badge rounded-pill <?= $h['type'] === 'weekend' ? 'bg-light text-secondary' : 'bg-danger text-white' ?>" style="font-size: 11px;">
                                                        <?= ucfirst($h['type']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <form action="<?= base_url('company/masterdata/holidays/delete/' . $h['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Remove this date from Holiday & Weekend Calendar?');">
                                                        <?= \App\Core\Session::csrfField() ?>
                                                        <button type="submit" class="btn btn-sm btn-link text-danger border-0"><i class="fa-solid fa-trash-can"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center p-5 text-secondary">
                                                <i class="fa-solid fa-calendar-xmark fs-1 mb-3 text-light"></i>
                                                <p class="m-0">No holidays or weekends defined. Mark them to block attendance dates.</p>
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

    <!-- 6. Designations Pane -->
    <div class="tab-pane fade" id="designations-pane" role="tabpanel" tabindex="0">
        <div class="pepp-card">
            <div class="pepp-card-header d-flex justify-content-between align-items-center">
                <h5 class="pepp-card-title m-0"><i class="fa-solid fa-id-card-clip text-primary me-2"></i> Employee Designations Master</h5>
                <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                    <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addDesignationModal">
                        <i class="fa-solid fa-plus-circle me-1"></i> Add Designation
                    </button>
                <?php endif; ?>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table table-hover pepp-table mb-0">
                        <thead>
                            <tr>
                                <th>Designation Title</th>
                                <th>Description / Responsibilities</th>
                                <th>Created Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($designations)): ?>
                                <?php foreach ($designations as $desig): ?>
                                    <tr>
                                        <td><strong class="text-dark"><?= htmlspecialchars($desig['title']) ?></strong></td>
                                        <td><span class="text-secondary small"><?= htmlspecialchars($desig['description'] ?: 'No description provided') ?></span></td>
                                        <td><?= date('d M Y', strtotime($desig['created_at'])) ?></td>
                                        <td class="text-end">
                                            <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                                <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#editDesignationModal-<?= $desig['id'] ?>"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                                                <form action="<?= base_url('company/masterdata/designations/delete/' . $desig['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this Designation?');">
                                                    <?= \App\Core\Session::csrfField() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Edit Designation Modal -->
                                    <div class="modal fade" id="editDesignationModal-<?= $desig['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <form action="<?= base_url('company/masterdata/designations/edit/' . $desig['id']) ?>" method="POST">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <div class="modal-content text-start" style="border-radius: 12px;">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Edit Designation</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Designation Title <span class="text-danger">*</span></label>
                                                            <input type="text" name="title" class="form-control text-dark" value="<?= htmlspecialchars($desig['title']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Description / Responsibilities</label>
                                                            <textarea name="description" class="form-control text-dark" rows="3" placeholder="Define role details..."><?= htmlspecialchars($desig['description'] ?? '') ?></textarea>
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
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center p-5 text-secondary">
                                        <i class="fa-solid fa-id-card-clip fs-1 mb-3 text-light"></i>
                                        <p class="m-0">No custom designations configured yet.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Add Designation -->
    <div class="modal fade" id="addDesignationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/masterdata/designations/create') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content text-start" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-id-card-clip text-primary me-2"></i> Register Designation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Designation Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control text-dark" placeholder="e.g. Production Supervisor, Cost Manager" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Description / Responsibilities</label>
                            <textarea name="description" class="form-control text-dark" rows="3" placeholder="Define role details..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Register Role</button>
                    </div>
                </div>
            </form>
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

<!-- Modal: Add Shift Schedule -->
<div class="modal fade" id="addShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('company/masterdata/shifts/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle text-info me-2"></i> Add Shift Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Shift Title <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control text-dark" placeholder="e.g. Night Shift, Evening Shift" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control text-dark" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control text-dark" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-info text-white px-4">Create Shift</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Holiday -->
<div class="modal fade" id="addHolidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('company/masterdata/holidays/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-calendar-plus text-primary me-2"></i> Register Holiday/Weekend</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control text-dark" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Occasion Title / Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control text-dark" placeholder="e.g. Independence Day, New Year" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Day Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select text-dark" required>
                            <option value="holiday" selected>Official Holiday</option>
                            <option value="weekend">Weekly Weekend</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4">Register Date</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Generate Weekends -->
<div class="modal fade" id="generateWeekendsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('company/masterdata/holidays/generate') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-wand-magic-sparkles text-success me-2"></i> Auto-Generate Weekly Weekends</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="text-secondary small">This tool automatically generates and inserts all Saturdays and Sundays for the specified year as weekends in your calendar.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Calendar Year <span class="text-danger">*</span></label>
                        <input type="number" name="year" class="form-control text-dark" value="<?= date('Y') ?>" min="2000" max="2100" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success text-white px-4">Generate Weekends</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Clone Holidays -->
<div class="modal fade" id="cloneHolidaysModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('company/masterdata/holidays/clone') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-clone text-secondary me-2"></i> Clone Holidays to Upcoming Term</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="text-secondary small">Copy all official holidays (excluding weekly weekends) from a source fiscal term/year directly into a new target calendar year.</p>
                    <div class="row g-3">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Source Year <span class="text-danger">*</span></label>
                            <input type="number" name="source_year" class="form-control text-dark" value="<?= date('Y') - 1 ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Target Year <span class="text-danger">*</span></label>
                            <input type="number" name="target_year" class="form-control text-dark" value="<?= date('Y') ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-secondary px-4">Clone Calendar</button>
                </div>
            </div>
        </form>
    </div>
</div>

    <!-- 7. Style Variables Pane -->
    <div class="tab-pane fade" id="style_vars-pane" role="tabpanel" tabindex="0">
        <?php
        $varsGrouped = [
            'category' => ['title' => 'Categories', 'icon' => 'fa-solid fa-list-check', 'placeholder' => 'e.g. Unisex, Men, Kids, Women', 'items' => []],
            'gsm' => ['title' => 'GSM Options', 'icon' => 'fa-solid fa-weight-scale', 'placeholder' => 'e.g. 160, 180, 200, 220', 'items' => []],
            'color' => ['title' => 'Color Shades', 'icon' => 'fa-solid fa-palette', 'placeholder' => 'e.g. Red, Blue, Navy, Melange', 'items' => []],
            'brand' => ['title' => 'Brands', 'icon' => 'fa-solid fa-copyright', 'placeholder' => 'e.g. Wearable, Wellgro, Pepp', 'items' => []],
            'size_range' => ['title' => 'Size Ranges', 'icon' => 'fa-solid fa-ruler-horizontal', 'placeholder' => 'e.g. S,M,L,XL,XXL or XS,S,M,L', 'items' => []]
        ];
        foreach ($styleVariables ?? [] as $v) {
            if (isset($varsGrouped[$v['type']])) {
                $varsGrouped[$v['type']]['items'][] = $v;
            }
        }
        ?>
        <div class="row g-4">
            <?php foreach ($varsGrouped as $type => $group): ?>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="pepp-card h-100">
                        <div class="pepp-card-header d-flex justify-content-between align-items-center bg-light">
                            <h6 class="pepp-card-title m-0 text-dark"><i class="<?= $group['icon'] ?> text-primary me-2"></i> <?= htmlspecialchars($group['title']) ?></h6>
                            <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                <button class="btn btn-sm btn-outline-primary py-0.5 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#addStyleVarModal-<?= $type ?>">
                                    <i class="fa-solid fa-plus small"></i> Add
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="pepp-card-body p-0" style="max-height: 400px; overflow-y: auto;">
                            <ul class="list-group list-group-flush mb-0">
                                <?php if (!empty($group['items'])): ?>
                                    <?php foreach ($group['items'] as $item): ?>
                                        <li class="list-group-item d-flex align-items-center justify-content-between py-2.5 px-3">
                                            <span class="text-dark fw-semibold"><?= htmlspecialchars($item['value']) ?></span>
                                            <div>
                                                <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                                    <button class="btn btn-sm btn-link text-secondary p-0 me-2 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#editStyleVarModal-<?= $item['id'] ?>"><i class="fa-regular fa-edit"></i></button>
                                                    <form action="<?= base_url('company/masterdata/stylevariables/delete/' . $item['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this variable option?');">
                                                        <?= \App\Core\Session::csrfField() ?>
                                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0 bg-transparent"><i class="fa-solid fa-trash-can"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </li>

                                        <!-- Edit Style Variable Modal -->
                                        <div class="modal fade" id="editStyleVarModal-<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                                <form action="<?= base_url('company/masterdata/stylevariables/edit/' . $item['id']) ?>" method="POST">
                                                    <?= \App\Core\Session::csrfField() ?>
                                                    <div class="modal-content text-start text-dark" style="border-radius: 12px;">
                                                        <div class="modal-header py-2.5">
                                                            <h6 class="modal-title fw-bold">Edit <?= htmlspecialchars(rtrim($group['title'], 's')) ?></h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body py-3">
                                                            <label class="form-label small fw-bold">Value <span class="text-danger">*</span></label>
                                                            <input type="text" name="value" class="form-control text-dark" value="<?= htmlspecialchars($item['value']) ?>" required>
                                                        </div>
                                                        <div class="modal-footer py-2">
                                                            <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-sm btn-primary px-3">Save</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center py-4 text-secondary small">
                                        No <?= strtolower($group['title']) ?> configured yet.
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Add Style Variable Modal -->
                <div class="modal fade" id="addStyleVarModal-<?= $type ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <form action="<?= base_url('company/masterdata/stylevariables/create') ?>" method="POST">
                            <?= \App\Core\Session::csrfField() ?>
                            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                            <div class="modal-content text-start text-dark" style="border-radius: 12px;">
                                <div class="modal-header py-2.5">
                                    <h6 class="modal-title fw-bold">Add New <?= htmlspecialchars(rtrim($group['title'], 's')) ?></h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body py-3">
                                    <label class="form-label small fw-bold">Option Value <span class="text-danger">*</span></label>
                                    <input type="text" name="value" class="form-control text-dark" placeholder="<?= htmlspecialchars($group['placeholder']) ?>" required>
                                </div>
                                <div class="modal-footer py-2">
                                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-sm btn-primary px-3">Add Option</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab) {
        const tabButton = document.getElementById(activeTab + '-tab');
        if (tabButton) {
            // Remove active state from other buttons
            document.querySelectorAll('#masterDataTabs .nav-link').forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            // Hide other panes
            document.querySelectorAll('#masterDataTabsContent .tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Set clicked button to active
            tabButton.classList.add('active');
            tabButton.setAttribute('aria-selected', 'true');
            
            // Show target tab pane
            const targetPaneId = tabButton.getAttribute('data-bs-target');
            if (targetPaneId) {
                const targetPane = document.querySelector(targetPaneId);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                }
            }
        }
    }
});
</script>
