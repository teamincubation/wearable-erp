<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Roles & Privileges</h3>
        <p class="text-secondary m-0">Define organizational access tiers and assign feature permissions</p>
    </div>
    <?php if (\App\Core\Auth::hasPermission('company.roles.manage')): ?>
        <button class="btn btn-pepp-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
            <i class="fa-solid fa-plus me-1"></i> Add Custom Role
        </button>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Role listing & details -->
    <div class="col-md-8">
        <div class="pepp-card">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-shield-halved text-primary me-2"></i> Current Access Roles</h5>
            </div>
            <div class="pepp-card-body">
                <div class="row g-4">
                    <?php foreach ($roles as $role): ?>
                        <div class="col-md-6">
                            <div class="card h-100 p-3 shadow-sm border" style="border-radius: var(--border-radius-md);">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($role['name']) ?></h5>
                                        <p class="text-secondary" style="font-size: 13px;"><?= htmlspecialchars($role['description'] ?: 'No description provided') ?></p>
                                    </div>
                                    <?php if ($role['is_system']): ?>
                                        <span class="badge bg-light text-secondary"><i class="fa-solid fa-lock me-1"></i> System</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <h6 class="fw-bold text-primary mb-2" style="font-size: 12px; text-transform: uppercase;">Permissions Granted:</h6>
                                    <div class="d-flex flex-wrap gap-1" style="max-height: 120px; overflow-y: auto;">
                                        <?php 
                                            $rolePermIds = $role_permissions[$role['id']] ?? [];
                                            if (!empty($rolePermIds)):
                                                foreach ($permissions as $p):
                                                    if (in_array($p['id'], $rolePermIds)):
                                        ?>
                                                        <span class="badge bg-light text-dark" style="font-size: 11px;"><?= htmlspecialchars($p['name']) ?></span>
                                        <?php 
                                                    endif;
                                                endforeach;
                                            else:
                                        ?>
                                                <span class="text-secondary" style="font-size: 12px;">No privileges mapped.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!$role['is_system'] && \App\Core\Auth::hasPermission('company.roles.manage')): ?>
                                    <div class="mt-auto border-top pt-2 text-end">
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editRoleModal-<?= $role['id'] ?>">
                                            <i class="fa-regular fa-pen-to-square"></i> Configure Role
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Edit Role Modal -->
                        <div class="modal fade" id="editRoleModal-<?= $role['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <form action="<?= base_url('company/roles/edit/' . $role['id']) ?>" method="POST">
                                    <?= \App\Core\Session::csrfField() ?>
                                    <div class="modal-content" style="border-radius: var(--border-radius-lg);">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Configure Role: <?= htmlspecialchars($role['name']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Role Name</label>
                                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($role['name']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Description</label>
                                                <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($role['description'] ?? '') ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold d-block mb-3">Toggles Permissions Privileges</label>
                                                <div class="row g-3">
                                                    <?php foreach ($permissions as $p): ?>
                                                        <div class="col-md-6">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" 
                                                                       class="form-check-input" id="switch-edit-<?= $role['id'] ?>-<?= $p['id'] ?>"
                                                                       <?= in_array($p['id'], $rolePermIds) ? 'checked' : '' ?>>
                                                                <label class="form-check-label" for="switch-edit-<?= $role['id'] ?>-<?= $p['id'] ?>">
                                                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                                                    <div class="text-secondary" style="font-size: 11px;"><?= htmlspecialchars($p['description']) ?></div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-pepp-primary">Save Role Configuration</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructions context card -->
    <div class="col-md-4">
        <div class="pepp-card bg-light border-dashed">
            <div class="pepp-card-body">
                <h6 class="fw-bold"><i class="fa-solid fa-circle-info text-primary me-2"></i> Security Best Practices</h6>
                <hr>
                <p class="text-secondary" style="font-size: 13.5px; line-height: 1.5;">
                    Every role maps to security tokens parsed by the <strong>PermissionMiddleware</strong>. 
                </p>
                <p class="text-secondary" style="font-size: 13.5px; line-height: 1.5;">
                    Changes to role privilege maps execute instantly. Affected active employees will inherit updated boundaries immediately upon reloading their pages.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="<?= base_url('company/roles/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content" style="border-radius: var(--border-radius-lg);">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle text-primary me-2"></i> Add Custom Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Quality Inspector" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Brief details about role responsibilities...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block mb-3">Map Permissions Privileges</label>
                        <div class="row g-3">
                            <?php foreach ($permissions as $p): ?>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" class="form-check-input" id="switch-add-<?= $p['id'] ?>">
                                        <label class="form-check-label" for="switch-add-<?= $p['id'] ?>">
                                            <strong><?= htmlspecialchars($p['name']) ?></strong>
                                            <div class="text-secondary" style="font-size: 11px;"><?= htmlspecialchars($p['description']) ?></div>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-pepp-primary">Register Role</button>
                </div>
            </div>
        </form>
    </div>
</div>
