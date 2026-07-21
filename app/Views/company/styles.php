<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Style Master</h3>
        <p class="text-secondary m-0">Central catalog of apparel styles and production tech packs</p>
    </div>
    <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addStyleModal">
            <i class="fa-solid fa-plus me-1"></i> Add New Style
        </button>
    <?php endif; ?>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-shirt text-primary me-2"></i> Company Styles Catalog</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Style No</th>
                        <th>Style Name</th>
                        <th>Category</th>
                        <th>Fabric Composition</th>
                        <th>Brand</th>
                        <th>Sizes</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($styles)): ?>
                        <?php foreach ($styles as $s): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= htmlspecialchars($s['style_no']) ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong class="text-dark"><?= htmlspecialchars($s['name']) ?></strong>
                                        <?php if (!empty($s['description'])): ?>
                                            <div class="text-secondary small"><?= htmlspecialchars(substr($s['description'], 0, 60)) ?>...</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary text-capitalize"><?= htmlspecialchars($s['category']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($s['composition'] ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($s['brand'] ?: 'N/A') ?></td>
                                <td><span class="badge bg-light text-dark"><?= htmlspecialchars($s['size_range'] ?: 'N/A') ?></span></td>
                                <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                                <td class="text-end">
                                    <a href="<?= base_url('company/styles/techpack/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary me-1" title="Tech Pack & BOM">
                                        <i class="fa-solid fa-file-invoice"></i> Tech Pack
                                    </a>

                                    <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                        <button class="btn btn-sm btn-light border me-1" data-bs-toggle="modal" data-bs-target="#editStyleModal-<?= $s['id'] ?>" title="Edit Details">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Edit Style Modal -->
                            <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                <div class="modal fade" id="editStyleModal-<?= $s['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="<?= base_url('company/styles/edit/' . $s['id']) ?>" method="POST">
                                            <?= \App\Core\Session::csrfField() ?>
                                            <div class="modal-content" style="border-radius: 12px;">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Edit Style Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Style Number</label>
                                                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($s['style_no']) ?>" disabled>
                                                        <div class="form-text">Style numbers cannot be modified after registration.</div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Style Name</label>
                                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($s['name']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Category</label>
                                                        <select name="category" class="form-select">
                                                            <option value="men" <?= $s['category'] === 'men' ? 'selected' : '' ?>>Men</option>
                                                            <option value="women" <?= $s['category'] === 'women' ? 'selected' : '' ?>>Women</option>
                                                            <option value="kids" <?= $s['category'] === 'kids' ? 'selected' : '' ?>>Kids</option>
                                                            <option value="unisex" <?= $s['category'] === 'unisex' ? 'selected' : '' ?>>Unisex</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Fabric Composition</label>
                                                        <input type="text" name="composition" class="form-control" value="<?= htmlspecialchars($s['composition'] ?? '') ?>" placeholder="e.g. 100% Cotton, 80% Cotton 20% Polyester">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Brand</label>
                                                        <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($s['brand'] ?? '') ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Size Range</label>
                                                        <input type="text" name="size_range" class="form-control" value="<?= htmlspecialchars($s['size_range'] ?? '') ?>" placeholder="e.g. S, M, L, XL, XXL">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Description</label>
                                                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($s['description'] ?? '') ?></textarea>
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
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-shirt fs-1 mb-3 text-light"></i>
                                <p class="m-0">No garment styles registered in the database yet.</p>
                                <?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
                                    <button class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addStyleModal">
                                        <i class="fa-solid fa-plus me-1"></i> Register First Style
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

<!-- Add Style Modal -->
<?php if (\App\Core\Auth::hasPermission('company.styles.manage')): ?>
    <div class="modal fade" id="addStyleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/styles/create') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Register New Garment Style</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Style Number <span class="text-danger">*</span></label>
                            <input type="text" name="style_no" class="form-control" placeholder="e.g. STY-1001-TS" required>
                            <div class="form-text">Must be a unique style code (alphanumeric).</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Style Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Round Neck Cotton T-Shirt" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Category</label>
                            <select name="category" class="form-select">
                                <option value="men">Men</option>
                                <option value="women">Women</option>
                                <option value="kids">Kids</option>
                                <option value="unisex" selected>Unisex</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Fabric Composition</label>
                            <input type="text" name="composition" class="form-control" placeholder="e.g. 100% Combed Cotton, 180 GSM">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Brand</label>
                            <input type="text" name="brand" class="form-control" placeholder="e.g. TOCCO Exports">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Size Range</label>
                            <input type="text" name="size_range" class="form-control" placeholder="e.g. S, M, L, XL, XXL">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Optional comments on fit, packaging, or design requirements..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Register Style</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
