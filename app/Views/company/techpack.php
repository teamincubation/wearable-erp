<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= base_url('company/styles') ?>" class="btn btn-sm btn-light border mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Styles</a>
        <h3 class="fw-bold">Technical Specifications Pack</h3>
        <p class="text-secondary m-0">Bill of Materials (BOM) & Measurement Chart for <strong><?= htmlspecialchars($style['style_no']) ?></strong></p>
    </div>
    <div>
        <button type="button" class="btn btn-outline-secondary me-2" onclick="window.print();">
            <i class="fa-solid fa-print me-1"></i> Print / Export PDF
        </button>
    </div>
</div>

<form action="<?= base_url('company/styles/techpack/' . $techpack['id']) ?>" method="POST">
    <?= \App\Core\Session::csrfField() ?>

    <div class="row g-4">
        <!-- Style Overview Header Card -->
        <div class="col-12">
            <div class="pepp-card">
                <div class="pepp-card-header bg-light">
                    <h5 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-circle-info text-primary me-2"></i> Style Profile Overview</h5>
                </div>
                <div class="pepp-card-body">
                    <div class="row g-3">
                        <div class="col-md-2 col-6">
                            <small class="text-secondary d-block">Style Code</small>
                            <strong class="font-monospace text-primary"><?= htmlspecialchars($style['style_no']) ?></strong>
                        </div>
                        <div class="col-md-3 col-6">
                            <small class="text-secondary d-block">Style Name</small>
                            <strong><?= htmlspecialchars($style['name']) ?></strong>
                        </div>
                        <div class="col-md-2 col-6">
                            <small class="text-secondary d-block">Category</small>
                            <span class="badge bg-light text-secondary text-capitalize"><?= htmlspecialchars($style['category']) ?></span>
                        </div>
                        <div class="col-md-3 col-6">
                            <small class="text-secondary d-block">Fabric Composition</small>
                            <span><?= htmlspecialchars($style['composition'] ?: 'N/A') ?></span>
                        </div>
                        <div class="col-md-2 col-6">
                            <small class="text-secondary d-block">Brand / Client</small>
                            <span><?= htmlspecialchars($style['brand'] ?: 'N/A') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOM (Bill of Materials) Card -->
        <div class="col-12">
            <div class="pepp-card">
                <div class="pepp-card-header d-flex justify-content-between align-items-center">
                    <h5 class="pepp-card-title m-0"><i class="fa-solid fa-list-check text-primary me-2"></i> Bill of Materials (BOM)</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addBomRowBtn">
                        <i class="fa-solid fa-plus me-1"></i> Add Material Row
                    </button>
                </div>
                <div class="pepp-card-body p-0">
                    <div class="table-responsive border-0">
                        <table class="table pepp-table mb-0" id="bomTable">
                            <thead>
                                <tr>
                                    <th>Material / Item Name</th>
                                    <th>Category</th>
                                    <th>Color / Shade</th>
                                    <th>Unit (UOM)</th>
                                    <th>Consumption Qty per pc</th>
                                    <th class="text-center" style="width: 50px;">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($bom_list)): ?>
                                    <?php foreach ($bom_list as $index => $bom): ?>
                                        <tr>
                                            <td>
                                                <input type="text" name="bom_item_name[]" class="form-control form-control-sm" value="<?= htmlspecialchars($bom['item_name']) ?>" required>
                                            </td>
                                            <td>
                                                <select name="bom_item_type[]" class="form-select form-select-sm">
                                                    <option value="fabric" <?= $bom['item_type'] === 'fabric' ? 'selected' : '' ?>>Fabric</option>
                                                    <option value="yarn" <?= $bom['item_type'] === 'yarn' ? 'selected' : '' ?>>Yarn</option>
                                                    <option value="accessories" <?= $bom['item_type'] === 'accessories' ? 'selected' : '' ?>>Accessories (Buttons, Zips)</option>
                                                    <option value="chemical" <?= $bom['item_type'] === 'chemical' ? 'selected' : '' ?>>Chemicals / Dyes</option>
                                                    <option value="packing" <?= $bom['item_type'] === 'packing' ? 'selected' : '' ?>>Packing Materials</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="bom_color[]" class="form-control form-control-sm" value="<?= htmlspecialchars($bom['color']) ?>">
                                            </td>
                                            <td>
                                                <input type="text" name="bom_uom[]" class="form-control form-control-sm" value="<?= htmlspecialchars($bom['uom']) ?>" placeholder="e.g. kgs, meters, pcs">
                                            </td>
                                            <td>
                                                <input type="number" step="0.001" name="bom_qty[]" class="form-control form-control-sm" value="<?= htmlspecialchars($bom['qty']) ?>">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-link text-danger remove-row-btn p-0"><i class="fa-regular fa-trash-can"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Seed default empty rows for first load -->
                                    <tr class="empty-bom-indicator">
                                        <td colspan="6" class="text-center p-4 text-secondary">
                                            No materials configured in Bill of Materials. Click "Add Material Row" to start adding fabrics, yarns, or labels.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Size Measurement Chart -->
        <div class="col-12">
            <div class="pepp-card">
                <div class="pepp-card-header d-flex justify-content-between align-items-center">
                    <h5 class="pepp-card-title m-0"><i class="fa-solid fa-ruler-combined text-primary me-2"></i> Size Measurement Specifications (cms)</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addSizeRowBtn">
                        <i class="fa-solid fa-plus me-1"></i> Add Measurement Point
                    </button>
                </div>
                <div class="pepp-card-body p-0">
                    <div class="table-responsive border-0">
                        <table class="table pepp-table mb-0" id="sizeTable">
                            <thead>
                                <tr>
                                    <th>Measurement Parameter / Point of Measure</th>
                                    <th style="width: 100px;">Size S</th>
                                    <th style="width: 100px;">Size M</th>
                                    <th style="width: 100px;">Size L</th>
                                    <th style="width: 100px;">Size XL</th>
                                    <th style="width: 100px;">Size XXL</th>
                                    <th class="text-center" style="width: 50px;">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($size_guide)): ?>
                                    <?php foreach ($size_guide as $size): ?>
                                        <tr>
                                            <td>
                                                <input type="text" name="size_parameter[]" class="form-control form-control-sm" value="<?= htmlspecialchars($size['parameter']) ?>" placeholder="e.g. Chest Width, Total Length" required>
                                            </td>
                                            <td><input type="text" name="size_s[]" class="form-control form-control-sm text-center" value="<?= htmlspecialchars($size['s']) ?>"></td>
                                            <td><input type="text" name="size_m[]" class="form-control form-control-sm text-center" value="<?= htmlspecialchars($size['m']) ?>"></td>
                                            <td><input type="text" name="size_l[]" class="form-control form-control-sm text-center" value="<?= htmlspecialchars($size['l']) ?>"></td>
                                            <td><input type="text" name="size_xl[]" class="form-control form-control-sm text-center" value="<?= htmlspecialchars($size['xl']) ?>"></td>
                                            <td><input type="text" name="size_xxl[]" class="form-control form-control-sm text-center" value="<?= htmlspecialchars($size['xxl']) ?>"></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-link text-danger remove-row-btn p-0"><i class="fa-regular fa-trash-can"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="empty-size-indicator">
                                        <td colspan="7" class="text-center p-4 text-secondary">
                                            No dimensions configured in Measurement Guide. Click "Add Measurement Point" to configure sizing parameters.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Processing & Packing Specifications -->
        <div class="col-md-4">
            <div class="pepp-card h-100">
                <div class="pepp-card-header bg-light">
                    <h6 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-print text-primary me-2"></i> Print & Graphic Guidelines</h6>
                </div>
                <div class="pepp-card-body">
                    <textarea name="printing_specs" class="form-control" rows="6" placeholder="Configure ink type, chest prints placement, colors alignment specs..."><?= htmlspecialchars($techpack['printing_specs'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="pepp-card h-100">
                <div class="pepp-card-header bg-light">
                    <h6 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-scissors text-primary me-2"></i> Embroidery Specs</h6>
                </div>
                <div class="pepp-card-body">
                    <textarea name="embroidery_specs" class="form-control" rows="6" placeholder="Stitches density, backing fabric type, thread specifications..."><?= htmlspecialchars($techpack['embroidery_specs'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="pepp-card h-100">
                <div class="pepp-card-header bg-light">
                    <h6 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-box-open text-primary me-2"></i> Carton Packaging specs</h6>
                </div>
                <div class="pepp-card-body">
                    <textarea name="packing_specs" class="form-control" rows="6" placeholder="Hanger folding, single-polybag packaging rules, carton capacity matrix..."><?= htmlspecialchars($techpack['packing_specs'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="col-12 text-end mb-5">
            <a href="<?= base_url('company/styles') ?>" class="btn btn-light border me-2">Cancel</a>
            <button type="submit" class="btn btn-primary px-5 rounded-pill"><i class="fa-regular fa-floppy-disk me-1"></i> Save Tech Pack Details</button>
        </div>
    </div>
</form>

<!-- Javascript Row Injection Engine -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Add BOM Material Row
    const bomTable = document.getElementById('bomTable').querySelector('tbody');
    document.getElementById('addBomRowBtn').addEventListener('click', function() {
        // Remove empty placeholder row if present
        const emptyRow = bomTable.querySelector('.empty-bom-indicator');
        if (emptyRow) {
            emptyRow.remove();
        }

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <input type="text" name="bom_item_name[]" class="form-control form-control-sm" placeholder="e.g. Cotton Rib fabric, Brand Labels" required>
            </td>
            <td>
                <select name="bom_item_type[]" class="form-select form-select-sm">
                    <option value="fabric">Fabric</option>
                    <option value="yarn">Yarn</option>
                    <option value="accessories" selected>Accessories (Buttons, Zips)</option>
                    <option value="chemical">Chemicals / Dyes</option>
                    <option value="packing">Packing Materials</option>
                </select>
            </td>
            <td>
                <input type="text" name="bom_color[]" class="form-control form-control-sm" placeholder="Matching shade">
            </td>
            <td>
                <input type="text" name="bom_uom[]" class="form-control form-control-sm" placeholder="e.g. pcs, meters, kgs" value="pcs">
            </td>
            <td>
                <input type="number" step="0.001" name="bom_qty[]" class="form-control form-control-sm" value="1.000">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-link text-danger remove-row-btn p-0"><i class="fa-regular fa-trash-can"></i></button>
            </td>
        `;
        bomTable.appendChild(newRow);
        bindRemoveButtons();
    });

    // 2. Add Sizing Row
    const sizeTable = document.getElementById('sizeTable').querySelector('tbody');
    document.getElementById('addSizeRowBtn').addEventListener('click', function() {
        const emptyRow = sizeTable.querySelector('.empty-size-indicator');
        if (emptyRow) {
            emptyRow.remove();
        }

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <input type="text" name="size_parameter[]" class="form-control form-control-sm" placeholder="e.g. Chest circumference, Body length" required>
            </td>
            <td><input type="text" name="size_s[]" class="form-control form-control-sm text-center" value="0"></td>
            <td><input type="text" name="size_m[]" class="form-control form-control-sm text-center" value="0"></td>
            <td><input type="text" name="size_l[]" class="form-control form-control-sm text-center" value="0"></td>
            <td><input type="text" name="size_xl[]" class="form-control form-control-sm text-center" value="0"></td>
            <td><input type="text" name="size_xxl[]" class="form-control form-control-sm text-center" value="0"></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-link text-danger remove-row-btn p-0"><i class="fa-regular fa-trash-can"></i></button>
            </td>
        `;
        sizeTable.appendChild(newRow);
        bindRemoveButtons();
    });

    // 3. Remove row binding function
    function bindRemoveButtons() {
        document.querySelectorAll('.remove-row-btn').forEach(function(button) {
            button.onclick = function() {
                const tr = this.closest('tr');
                if (tr) tr.remove();
            };
        });
    }

    bindRemoveButtons();
});
</script>

<style>
@media print {
    .btn, header, aside, .form-text, .remove-row-btn, #addBomRowBtn, #addSizeRowBtn, .btn-primary {
        display: none !important;
    }
    body {
        background-color: #fff !important;
        padding: 0 !important;
    }
    .app-layout {
        display: block !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .pepp-card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        margin-bottom: 20px !important;
    }
    .pepp-card-header {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #ddd !important;
    }
    textarea {
        border: none !important;
        resize: none !important;
    }
}
</style>
