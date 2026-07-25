<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= base_url('company/styles') ?>" class="btn btn-sm btn-light border mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Styles</a>
        <h3 class="fw-bold m-0">Technical Specifications Pack</h3>
        <p class="text-secondary m-0">Bill of Materials (BOM) & Measurement Chart for <strong class="text-primary font-monospace"><?= htmlspecialchars($style['style_no']) ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print();">
            <i class="fa-solid fa-print me-1"></i> Print / Export PDF
        </button>
        <button type="submit" form="techpackForm" class="btn btn-primary px-4">
            <i class="fa-regular fa-floppy-disk me-1"></i> Save Changes
        </button>
    </div>
</div>

<?php 
// Parse size ranges configured by admin when creating/editing Garment Style
$rawSizes = !empty($style['size_range']) ? explode(',', $style['size_range']) : [];
$sizes = array_values(array_filter(array_map('trim', $rawSizes)));

// Receive categories & stock items directly from controller (fetched from Current Stock Levels in company/inventory/balances)
$stockCategories = $stock_categories ?? [];
$stockItemsByCategory = $stock_items_by_category ?? [];
?>

<form id="techpackForm" action="<?= base_url('company/styles/techpack/' . $techpack['id']) ?>" method="POST">
    <?= \App\Core\Session::csrfField() ?>

    <div class="row g-4">
        <!-- Style Profile Overview Card -->
        <div class="col-12">
            <div class="pepp-card">
                <div class="pepp-card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-circle-info text-primary me-2"></i> Style Profile Overview</h5>
                    <span class="badge bg-white text-secondary border">Tech Pack ID: #<?= $techpack['id'] ?></span>
                </div>
                <div class="pepp-card-body">
                    <div class="row g-3">
                        <div class="col-md-2 col-6">
                            <small class="text-secondary d-block">Style Code</small>
                            <strong class="font-monospace text-primary fs-6"><?= htmlspecialchars($style['style_no']) ?></strong>
                        </div>
                        <div class="col-md-3 col-6">
                            <small class="text-secondary d-block">Style Name</small>
                            <strong class="text-dark fs-6"><?= htmlspecialchars($style['name']) ?></strong>
                        </div>
                        <div class="col-md-2 col-6">
                            <small class="text-secondary d-block">Garment Category</small>
                            <span class="badge bg-light text-secondary text-capitalize border"><?= htmlspecialchars($style['category']) ?></span>
                        </div>
                        <div class="col-md-3 col-6">
                            <small class="text-secondary d-block">Fabric & GSM Specs</small>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($style['composition'] ?: 'N/A') ?></span>
                            <?php if (!empty($style['gsm'])): ?>
                                <span class="badge bg-light text-dark font-monospace border ms-1"><?= htmlspecialchars($style['gsm']) ?> GSM</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2 col-6">
                            <small class="text-secondary d-block">Brand / Client</small>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($style['brand'] ?: 'N/A') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mandatory Production Workflow Stages Card -->
        <div class="col-12">
            <div class="pepp-card border-primary border-opacity-25">
                <div class="pepp-card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="pepp-card-title m-0 text-primary"><i class="fa-solid fa-route text-primary me-2"></i> Mandatory Production Workflow Stages & Order Sequence <span class="text-danger">*</span></h5>
                        <small class="text-secondary">Select required operational stages for this style and assign their strict execution order numbers</small>
                    </div>
                    <span class="badge bg-primary text-white">Style Workflow Compliance</span>
                </div>
                <div class="pepp-card-body">
                    <p class="text-secondary small mb-3">
                        <i class="fa-solid fa-circle-info text-info me-1"></i>
                        The selected stages and sequence numbers will be strictly enforced in Production Tracking & QR Code Scanners. Out-of-order scans will be blocked until preceding stages are completed.
                    </p>
                    
                    <?php 
                    $activeStyleStageKeys = !empty($style_stages) ? array_column($style_stages, 'key') : [];
                    $styleStageOrderMap = [];
                    if (!empty($style_stages)) {
                        foreach ($style_stages as $stgItem) {
                            $styleStageOrderMap[$stgItem['key']] = $stgItem['order'];
                        }
                    }
                    ?>
                    
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                        <?php if (!empty($company_wip_stages)): ?>
                            <?php foreach ($company_wip_stages as $cStg): ?>
                                <?php 
                                $isSel = in_array($cStg['key'], $activeStyleStageKeys) || empty($style_stages);
                                $orderVal = $styleStageOrderMap[$cStg['key']] ?? $cStg['order'];
                                ?>
                                <div class="col">
                                    <div class="p-3 border rounded h-100 <?= $isSel ? 'bg-light border-primary' : 'bg-white' ?>">
                                        <div class="form-check d-flex align-items-center justify-content-between mb-2">
                                            <div>
                                                <input class="form-check-input stage-checkbox me-2" type="checkbox" name="selected_stages[]" value="<?= htmlspecialchars($cStg['key']) ?>" id="tech-stage-<?= htmlspecialchars($cStg['key']) ?>" <?= $isSel ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-bold text-dark" for="tech-stage-<?= htmlspecialchars($cStg['key']) ?>">
                                                    <?= htmlspecialchars($cStg['name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center mt-2">
                                            <span class="small text-secondary me-2">Order #:</span>
                                            <input type="number" name="stage_order[<?= htmlspecialchars($cStg['key']) ?>]" class="form-control form-control-sm font-monospace text-center w-50" value="<?= (int)$orderVal ?>" min="1">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                        <table class="table pepp-table mb-0 align-middle" id="bomTable">
                            <thead>
                                <tr class="bg-light">
                                    <th style="width: 25%;">Category / Type (From Stock)</th>
                                    <th style="width: 30%;">Material / Item Description</th>
                                    <th style="width: 15%;">Color / Shade</th>
                                    <th style="width: 12%;">Unit (UOM)</th>
                                    <th style="width: 13%;">Consumption Qty per pc <span class="text-danger">*</span></th>
                                    <th class="text-center" style="width: 5%;">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($bom_list)): ?>
                                    <?php foreach ($bom_list as $index => $bom): ?>
                                        <?php 
                                            $currCat = $bom['item_type'] ?? reset($stockCategories);
                                            $currItems = $stockItemsByCategory[$currCat] ?? (reset($stockItemsByCategory) ?: []);
                                        ?>
                                        <tr>
                                            <td>
                                                <select name="bom_item_type[]" class="form-select form-select-sm bom-cat-select" required>
                                                    <?php if (!empty($stockCategories)): ?>
                                                        <?php foreach ($stockCategories as $cat): ?>
                                                            <option value="<?= htmlspecialchars($cat) ?>" <?= (strcasecmp($cat, $bom['item_type'] ?? '') === 0) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                    <option value="__add_custom_category__">+ Add Custom Category / Type...</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="bom_item_name[]" class="form-control form-control-sm bom-name-input" value="<?= htmlspecialchars($bom['item_name'] ?? '') ?>" placeholder="Select from stock or type material name" required>
                                            </td>
                                            <td>
                                                <input type="text" name="bom_color[]" class="form-control form-control-sm" value="<?= htmlspecialchars($bom['color'] ?? '') ?>" placeholder="Color shade">
                                            </td>
                                            <td>
                                                <input type="text" name="bom_uom[]" class="form-control form-control-sm" value="<?= htmlspecialchars($bom['uom'] ?? 'pcs') ?>" placeholder="e.g. kgs, meters, pcs">
                                            </td>
                                            <td>
                                                <input type="number" step="0.0001" name="bom_qty[]" class="form-control form-control-sm text-primary fw-bold" value="<?= htmlspecialchars($bom['qty'] ?? '1.0000') ?>" required>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-link text-danger remove-row-btn p-0"><i class="fa-regular fa-trash-can"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="empty-bom-indicator">
                                        <td colspan="6" class="text-center p-4 text-secondary">
                                            No materials configured in Bill of Materials. Click "Add Material Row" to select materials from Current Stock Levels.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Size Measurement Specifications Chart -->
        <div class="col-12">
            <div class="pepp-card">
                <div class="pepp-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-ruler-combined text-primary me-2"></i> Size Measurement Specifications (cms)</h5>
                        <small class="text-secondary">Admin configured sizes for this style: <strong><?= !empty($sizes) ? htmlspecialchars(implode(', ', $sizes)) : 'None Assigned' ?></strong></small>
                    </div>
                    <?php if (!empty($sizes)): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addSizeRowBtn">
                            <i class="fa-solid fa-plus me-1"></i> Add Measurement Point
                        </button>
                    <?php endif; ?>
                </div>
                <div class="pepp-card-body p-0">
                    <?php if (!empty($sizes)): ?>
                        <div class="table-responsive border-0">
                            <table class="table pepp-table mb-0 align-middle" id="sizeTable">
                                <thead>
                                    <tr class="bg-light">
                                        <th style="min-width: 240px;">Measurement Parameter / Point of Measure</th>
                                        <?php foreach ($sizes as $sz): ?>
                                            <th class="text-center" style="min-width: 100px;">Size <?= htmlspecialchars($sz) ?></th>
                                        <?php endforeach; ?>
                                        <th class="text-center" style="width: 50px;">Remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($size_guide)): ?>
                                        <?php foreach ($size_guide as $sg): ?>
                                            <tr>
                                                <td>
                                                    <input type="text" name="size_parameter[]" class="form-control form-control-sm" value="<?= htmlspecialchars($sg['parameter'] ?? '') ?>" required>
                                                </td>
                                                <?php foreach ($sizes as $sz): ?>
                                                    <?php $key = strtolower(trim($sz)); ?>
                                                    <td>
                                                        <input type="text" name="sizes[<?= htmlspecialchars($sz) ?>][]" class="form-control form-control-sm text-center font-monospace" value="<?= htmlspecialchars($sg[$key] ?? '0') ?>">
                                                    </td>
                                                <?php endforeach; ?>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-link text-danger remove-row-btn p-0"><i class="fa-regular fa-trash-can"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="empty-size-indicator">
                                            <td colspan="<?= count($sizes) + 2 ?>" class="text-center p-4 text-secondary">
                                                No dimensions configured in Measurement Guide. Click "Add Measurement Point" to configure sizing parameters.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4 text-secondary">
                            <i class="fa-solid fa-circle-exclamation text-warning me-1"></i> No size range assigned to this Garment Style yet. Edit Garment Style Master to add size ranges (e.g. S, M, L, XL).
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Printing & Embroidery Specs Card -->
        <div class="col-md-4 col-12">
            <div class="pepp-card h-100">
                <div class="pepp-card-header bg-light">
                    <h5 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-print text-primary me-2"></i> Print & Graphic Guidelines</h5>
                </div>
                <div class="pepp-card-body">
                    <textarea name="printing_specs" class="form-control" rows="4" placeholder="Specify print placement, screen print colors, ink type, artwork dimensions..."><?= htmlspecialchars($techpack['printing_specs'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-12">
            <div class="pepp-card h-100">
                <div class="pepp-card-header bg-light">
                    <h5 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-scissors text-primary me-2"></i> Embroidery Specs</h5>
                </div>
                <div class="pepp-card-body">
                    <textarea name="embroidery_specs" class="form-control" rows="4" placeholder="Stitch count, thread colors, backing material, applique details..."><?= htmlspecialchars($techpack['embroidery_specs'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-12">
            <div class="pepp-card h-100">
                <div class="pepp-card-header bg-light">
                    <h5 class="pepp-card-title m-0 text-dark"><i class="fa-solid fa-box-archive text-primary me-2"></i> Carton Packaging Specs</h5>
                </div>
                <div class="pepp-card-body">
                    <textarea name="packing_specs" class="form-control" rows="4" placeholder="Fold type, polybag specs, carton dimensions, pcs per carton, barcode sticker..."><?= htmlspecialchars($techpack['packing_specs'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Floating Sticky Bottom Save Bar -->
        <div class="col-12 text-end mb-5">
            <a href="<?= base_url('company/styles') ?>" class="btn btn-light border me-2 px-4">Cancel</a>
            <button type="submit" form="techpackForm" class="btn btn-primary px-5 rounded-pill shadow-sm"><i class="fa-regular fa-floppy-disk me-1"></i> Save Tech Pack Details</button>
        </div>
    </div>
</form>

<!-- Javascript Row Injection Engine -->
<script>
const stockItemsMap = <?= json_encode($stockItemsByCategory ?: []) ?>;
const stockCatList = <?= json_encode($stockCategories ?: []) ?>;
const activeSizesList = <?= json_encode($sizes ?: []) ?>;

document.addEventListener('DOMContentLoaded', function() {
    function populateRowDatalist(catSelect, nameInput) {
        if (!catSelect || !nameInput) return;
        const selectedCat = catSelect.value;
        const items = stockItemsMap[selectedCat] || [];
        
        let listId = nameInput.getAttribute('list');
        let dataListEl = listId ? document.getElementById(listId) : null;
        
        if (!dataListEl) {
            listId = 'dl-' + Math.random().toString(36).substr(2, 9);
            dataListEl = document.createElement('datalist');
            dataListEl.id = listId;
            document.body.appendChild(dataListEl);
            nameInput.setAttribute('list', listId);
        }
        
        dataListEl.innerHTML = '';
        if (items.length > 0) {
            items.forEach(itm => {
                const opt = document.createElement('option');
                opt.value = itm;
                opt.textContent = itm + ' (Current Stock Level)';
                dataListEl.appendChild(opt);
            });
        }
    }

    function handleCustomCategorySelection(catSel, nameInput) {
        if (catSel.value === '__add_custom_category__') {
            const customCat = prompt("Enter custom Category / Material Type name:");
            if (customCat && customCat.trim()) {
                const trimmedCat = customCat.trim();
                const newOpt = document.createElement('option');
                newOpt.value = trimmedCat;
                newOpt.textContent = trimmedCat;
                newOpt.selected = true;
                
                const customOptionEl = catSel.querySelector('option[value="__add_custom_category__"]');
                if (customOptionEl) {
                    catSel.insertBefore(newOpt, customOptionEl);
                } else {
                    catSel.appendChild(newOpt);
                }
                
                if (!stockCatList.includes(trimmedCat)) {
                    stockCatList.push(trimmedCat);
                }
                populateRowDatalist(catSel, nameInput);
            } else {
                catSel.selectedIndex = 0;
                populateRowDatalist(catSel, nameInput);
            }
        } else {
            populateRowDatalist(catSel, nameInput);
        }
    }

    function bindCategoryCascades() {
        document.querySelectorAll('#bomTable tbody tr').forEach(row => {
            const catSel = row.querySelector('.bom-cat-select');
            const nameInput = row.querySelector('.bom-name-input');
            if (catSel && nameInput) {
                populateRowDatalist(catSel, nameInput);
                if (!catSel.dataset.bound) {
                    catSel.dataset.bound = 'true';
                    catSel.addEventListener('change', function() {
                        handleCustomCategorySelection(catSel, nameInput);
                    });
                }
            }
        });
    }
    bindCategoryCascades();

    // 1. Add BOM Material Row
    const bomTable = document.getElementById('bomTable') ? document.getElementById('bomTable').querySelector('tbody') : null;
    const addBomRowBtn = document.getElementById('addBomRowBtn');
    
    if (addBomRowBtn && bomTable) {
        addBomRowBtn.addEventListener('click', function() {
            const emptyRow = bomTable.querySelector('.empty-bom-indicator');
            if (emptyRow) {
                emptyRow.remove();
            }

            let catOptionsHtml = '';
            stockCatList.forEach(c => {
                catOptionsHtml += `<option value="${c}">${c}</option>`;
            });
            catOptionsHtml += `<option value="__add_custom_category__">+ Add Custom Category / Type...</option>`;

            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <select name="bom_item_type[]" class="form-select form-select-sm bom-cat-select" required>
                        ${catOptionsHtml}
                    </select>
                </td>
                <td>
                    <input type="text" name="bom_item_name[]" class="form-control form-control-sm bom-name-input" placeholder="Select from stock or type description" required>
                </td>
                <td>
                    <input type="text" name="bom_color[]" class="form-control form-control-sm" placeholder="Matching shade">
                </td>
                <td>
                    <input type="text" name="bom_uom[]" class="form-control form-control-sm" placeholder="e.g. pcs, meters, kgs" value="pcs">
                </td>
                <td>
                    <input type="number" step="0.0001" name="bom_qty[]" class="form-control form-control-sm text-primary fw-bold" value="1.0000" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger remove-row-btn p-0"><i class="fa-regular fa-trash-can"></i></button>
                </td>
            `;
            bomTable.appendChild(newRow);
            bindRemoveButtons();
            bindCategoryCascades();
        });
    }

    // 2. Add Sizing Row
    const sizeTable = document.getElementById('sizeTable') ? document.getElementById('sizeTable').querySelector('tbody') : null;
    const addSizeRowBtn = document.getElementById('addSizeRowBtn');
    
    if (addSizeRowBtn && sizeTable) {
        addSizeRowBtn.addEventListener('click', function() {
            const emptyRow = sizeTable.querySelector('.empty-size-indicator');
            if (emptyRow) {
                emptyRow.remove();
            }

            let sizeColsHtml = '';
            activeSizesList.forEach(sz => {
                sizeColsHtml += `<td><input type="text" name="sizes[${sz}][]" class="form-control form-control-sm text-center font-monospace" value="0"></td>`;
            });

            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <input type="text" name="size_parameter[]" class="form-control form-control-sm" placeholder="e.g. Chest circumference, Body length" required>
                </td>
                ${sizeColsHtml}
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger remove-row-btn p-0"><i class="fa-regular fa-trash-can"></i></button>
                </td>
            `;
            sizeTable.appendChild(newRow);
            bindRemoveButtons();
        });
    }

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
