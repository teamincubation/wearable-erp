<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<style>
    .mobile-pack-card {
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    }
    .scanner-viewport-box {
        width: 100%;
        background: #000000;
        border-radius: 20px;
        overflow: hidden;
        border: 3px solid #0f172a;
        min-height: 240px;
        position: relative;
    }
    #reader {
        width: 100% !important;
        height: 100% !important;
        border: none !important;
    }
    #reader video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }
    .nav-pills-mobile .nav-link {
        font-size: 14px;
        padding: 10px 16px;
    }
</style>

<div class="container-fluid py-3 px-2 px-md-4">
    <!-- Top Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-dark m-0 d-flex align-items-center">
                <i class="fa-solid fa-box-open text-primary me-2"></i> Carton Packing: <span class="font-monospace text-primary ms-1.5"><?= htmlspecialchars($carton['carton_no']) ?></span>
            </h4>
        </div>
        <div class="d-flex gap-1.5">
            <a href="<?= base_url('company/packing-qr') ?>" class="btn btn-light border btn-sm rounded-circle p-2" title="Back to Hub" style="width: 38px; height: 38px;">
                <i class="fa-solid fa-arrow-left fs-6"></i>
            </a>
            <a href="<?= base_url('company/packing-qr/traceability?query=' . urlencode($carton['carton_no'])) ?>" class="btn btn-outline-info btn-sm rounded-circle p-2" title="Carton Traceability" style="width: 38px; height: 38px;">
                <i class="fa-solid fa-route fs-6"></i>
            </a>
        </div>
    </div>

    <!-- Carton Details & Packed Units Header Card -->
    <div class="mobile-pack-card p-3 mb-3">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
            <div>
                <div class="d-flex align-items-center flex-wrap gap-1.5 mb-1">
                    <span class="badge bg-dark-subtle text-dark border font-monospace">
                        Batch: <?= htmlspecialchars($carton['production_no']) ?>
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary border font-monospace">
                        Style: <?= htmlspecialchars($carton['style_no'] ?: 'N/A') ?>
                    </span>
                </div>
                <div class="row g-2 font-monospace small text-muted mt-1" style="font-size: 11px;">
                    <div class="col-12 col-sm-6">
                        <i class="fa-solid fa-location-dot text-primary me-1"></i> <strong>Dest:</strong> 
                        <?= ($carton['destination_type'] === 'client') ? ($carton['client_name'] ?: 'Client Direct') : (($carton['destination_type'] === 'warehouse') ? ($carton['warehouse_name'] ?: 'Company Warehouse') : 'Unassigned') ?>
                    </div>
                    <div class="col-12 col-sm-6">
                        <i class="fa-solid fa-truck text-info me-1"></i> <strong>Shipment:</strong> 
                        <?= !empty($carton['shipment_no']) ? htmlspecialchars($carton['shipment_no']) : 'Unassigned' ?>
                    </div>
                </div>
            </div>

            <!-- Total Packed Count Badge -->
            <div class="bg-success-subtle text-success border border-success-subtle rounded-3 p-2.5 px-3.5 text-center w-100 w-sm-auto mt-2 mt-sm-0">
                <small class="text-uppercase fw-bold d-block font-monospace" style="font-size: 10px;">Total Packed Units</small>
                <h3 class="fw-bold m-0 font-monospace" id="header_packed_count"><?= number_format($assignedQty) ?> <small class="fs-6">pcs</small></h3>
            </div>
        </div>
    </div>

    <!-- Mobile-First Mode Selector Navigation Tabs -->
    <ul class="nav nav-pills nav-fill nav-pills-mobile bg-white p-1.5 rounded-4 shadow-sm mb-3 border" id="packingModeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-3 fw-bold" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual-mode" type="button" role="tab" onclick="switchAssignmentMode('manual')">
                <i class="fa-solid fa-list-check me-1.5"></i> Manual Select
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-bold" id="qr-tab" data-bs-toggle="tab" data-bs-target="#qr-mode" type="button" role="tab" onclick="switchAssignmentMode('qr')">
                <i class="fa-solid fa-camera me-1.5"></i> QR Scan Mode
            </button>
        </li>
    </ul>

    <div class="tab-content" id="packingModeTabsContent">
        <!-- ================= TAB 1: MANUAL MODE ================= -->
        <div class="tab-pane fade show active" id="manual-mode" role="tabpanel">
            <div class="mobile-pack-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="fw-bold text-dark m-0 font-monospace"><i class="fa-solid fa-hand text-primary me-1.5"></i> Batch Packed Goods</h6>
                        <small class="text-muted" style="font-size: 11px;">Select packed products from Batch <strong><?= htmlspecialchars($carton['production_no']) ?></strong> to link.</small>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle p-2" onclick="loadEligibleManualProducts()" title="Refresh List" style="width: 34px; height: 34px;">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                </div>

                <!-- Filters & Action Button -->
                <div class="row g-2 mb-2">
                    <div class="col-12 col-md-7">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="manual_search_input" class="form-control bg-light border-start-0 font-monospace" placeholder="Filter by Product QR Code or Style..." onkeyup="filterManualTable()">
                        </div>
                    </div>
                    <div class="col-12 col-md-5 text-end">
                        <button type="button" id="btn_assign_manual" class="btn btn-success btn-sm w-100 rounded-pill fw-bold py-2" onclick="submitManualAssignments()" disabled>
                            <i class="fa-solid fa-link me-1.5"></i> Assign Selected Items (<span id="manual_selected_count">0</span> pcs)
                        </button>
                    </div>
                </div>

                <!-- Carton Capacity Limit Alert Banner -->
                <div id="manual_capacity_warning"></div>

                <!-- Products List Table -->
                <div class="table-responsive border rounded-3" style="max-height: 420px;">
                    <table class="table table-hover align-middle mb-0" id="manual_products_table">
                        <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                            <tr>
                                <th width="40" class="ps-3"><input type="checkbox" id="manual_select_all" class="form-check-input" onchange="toggleSelectAllManual(this)"></th>
                                <th>Product QR Code</th>
                                <th>Batch No</th>
                                <th>Style & PO</th>
                                <th>Size / Color</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="manual_table_body">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted font-monospace">
                                    <i class="fa-solid fa-spinner fa-spin me-2"></i> Loading batch products...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= TAB 2: QR SCAN MODE ================= -->
        <div class="tab-pane fade" id="qr-mode" role="tabpanel">
            <div class="row g-3">
                <!-- Scanner Input & Controls -->
                <div class="col-12 col-lg-6">
                    <div class="mobile-pack-card p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark m-0 font-monospace"><i class="fa-solid fa-qrcode text-primary me-1.5"></i> Live Scanner</h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 font-monospace" onclick="toggleCameraStream()">
                                <i class="fa-solid fa-power-off me-1"></i> Toggle Camera
                            </button>
                        </div>

                        <!-- Real-Time Input Box for Handheld Barcode Guns -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Scan Product QR Code</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark text-white border-dark"><i class="fa-solid fa-barcode"></i></span>
                                <input type="text" id="qr_input_box" class="form-control font-monospace fw-bold text-dark bg-light" placeholder="Scan or type Product QR..." autofocus onkeydown="handleQrInputKeydown(event)">
                                <button class="btn btn-primary fw-bold px-3" type="button" onclick="triggerManualScanSubmit()">
                                    <i class="fa-solid fa-plus me-1"></i> Add
                                </button>
                            </div>
                        </div>

                        <!-- Camera Viewport Container -->
                        <div class="scanner-viewport-box mb-2">
                            <div id="reader"></div>
                        </div>

                        <!-- Live Scan Feedback Alert -->
                        <div id="scan_feedback_alert" class="alert alert-secondary py-2 px-3 rounded-3 small font-monospace d-none mb-0"></div>
                    </div>
                </div>

                <!-- Scanned Products Session Stream -->
                <div class="col-12 col-lg-6">
                    <div class="mobile-pack-card p-3 d-flex flex-column justify-content-between h-100">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark m-0 font-monospace"><i class="fa-solid fa-list-ol text-success me-1.5"></i> Scanned Items</h6>
                                <span class="badge bg-primary font-monospace fs-6 px-3 py-1" id="session_scanned_count_badge">0 Items</span>
                            </div>

                            <div class="table-responsive border rounded-3 mb-3" style="max-height: 320px;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                                        <tr>
                                            <th class="ps-3">#</th>
                                            <th>Product QR</th>
                                            <th>Batch No</th>
                                            <th>Scan Time</th>
                                            <th class="text-end pe-3">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody id="session_scanned_table_body">
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted font-monospace">
                                                <i class="fa-solid fa-qrcode fs-3 mb-1 opacity-50 text-secondary"></i>
                                                <p class="m-0">No items scanned in this session yet.</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Finalize & Submit Button -->
                        <div class="pt-2 border-top">
                            <button type="button" id="btn_finalize_qr_session" class="btn btn-success w-100 py-2.5 rounded-3 fw-bold shadow-sm" onclick="finalizeQrSession()" disabled>
                                <i class="fa-solid fa-circle-check me-1.5"></i> Finalise & Link (<span id="session_final_count">0</span> pcs)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Currently Linked Products Table -->
    <div class="mobile-pack-card mt-3">
        <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark m-0 font-monospace"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Currently Linked Items in Carton</h6>
            <span class="badge bg-light text-dark border font-monospace"><?= count($assignedItems) ?> Linked Items</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                        <tr>
                            <th class="ps-3">Product QR Code</th>
                            <th>Batch No</th>
                            <th>Size / Color</th>
                            <th>Assigned Date & Time</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assignedItems)): ?>
                            <?php foreach ($assignedItems as $ai): 
                                $itemQr = !empty($ai['product_qr_code']) ? (string)$ai['product_qr_code'] : ('ITEM-' . $ai['id']);
                                $displayItemQr = $itemQr;
                                if (!empty($carton['production_no']) && str_starts_with(strtoupper($displayItemQr), strtoupper($carton['production_no']) . '-')) {
                                    $displayItemQr = substr($displayItemQr, strlen($carton['production_no']) + 1);
                                }
                                $sizeVal = !empty($ai['size']) ? (string)$ai['size'] : 'FREE';
                                $colorVal = !empty($ai['color']) ? (string)$ai['color'] : 'N/A';
                                $assignedDate = !empty($ai['assigned_at']) ? date('d M Y, h:i A', strtotime($ai['assigned_at'])) : (!empty($ai['created_at']) ? date('d M Y, h:i A', strtotime($ai['created_at'])) : 'N/A');
                            ?>
                                <tr>
                                    <td class="ps-3">
                                        <strong class="font-monospace text-primary"><?= htmlspecialchars($displayItemQr) ?></strong>
                                    </td>
                                    <td><span class="font-monospace text-dark fw-bold"><?= htmlspecialchars($carton['production_no']) ?></span></td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($sizeVal) ?> / <?= htmlspecialchars($colorVal) ?></span></td>
                                    <td class="font-monospace small text-muted"><?= htmlspecialchars($assignedDate) ?></td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5" title="Remove / Unassign" onclick="removeProductFromCarton('<?= htmlspecialchars($itemQr) ?>', <?= (int)$ai['id'] ?>, '<?= htmlspecialchars($displayItemQr) ?>')">
                                            <i class="fa-solid fa-trash-can me-1"></i> Unassign
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted font-monospace">
                                    No products currently assigned to this carton.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const CARTON_ID = <?= (int)$carton['id'] ?>;
    const CARTON_NO = '<?= htmlspecialchars($carton['carton_no']) ?>';
    const MAX_CAPACITY = <?= (int)($carton['max_capacity_pcs'] ?: 50) ?>;
    let CURRENT_ASSIGNED = <?= (int)$assignedQty ?>;

    <?php 
    $dummyCount = 0;
    foreach ($assignedItems as $ai) {
        $qr = !empty($ai['product_qr_code']) ? (string)$ai['product_qr_code'] : (!empty($ai['qr_code']) ? (string)$ai['qr_code'] : '');
        if (empty($qr) || str_starts_with(strtoupper($qr), 'ITEM')) {
            $dummyCount++;
        }
    }
    ?>
    const DUMMY_ITEM_COUNT = <?= (int)$dummyCount ?>;
    let REAL_CURRENT_ASSIGNED = Math.max(0, CURRENT_ASSIGNED - DUMMY_ITEM_COUNT);

    let scannedSessionProducts = [];
    let html5QrScanner = null;
    let isCameraActive = false;

    function formatDisplayQr(qr, batchNo) {
        if (!qr) return '';
        const bNo = batchNo || '<?= htmlspecialchars($carton['production_no']) ?>';
        if (bNo && qr.toUpperCase().startsWith(bNo.toUpperCase() + '-')) {
            return qr.substring(bNo.length + 1);
        }
        return qr;
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadEligibleManualProducts();
    });

    function switchAssignmentMode(mode) {
        if (mode === 'qr') {
            setTimeout(() => {
                const inputEl = document.getElementById('qr_input_box');
                if (inputEl) inputEl.focus();
            }, 300);
        }
    }

    // ================= MANUAL MODE LOGIC =================
    function loadEligibleManualProducts() {
        const tbody = document.getElementById('manual_table_body');
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted font-monospace"><i class="fa-solid fa-spinner fa-spin me-2"></i> Fetching batch products...</td></tr>`;

        fetch(`<?= base_url('company/packing-qr/api/eligible-products') ?>?carton_id=${CARTON_ID}`)
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                return res.json();
            })
            .then(data => {
                if (data.success && data.products) {
                    renderManualProductsTable(data.products);
                } else {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger font-monospace"><i class="fa-solid fa-triangle-exclamation me-1"></i> ${data.message || 'Failed to load batch products.'}</td></tr>`;
                }
            })
            .catch(err => {
                console.error("Error fetching products:", err);
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger font-monospace"><i class="fa-solid fa-triangle-exclamation me-1"></i> Error loading products: ${err.message || 'Server connection error'}</td></tr>`;
            });
    }

    function renderManualProductsTable(products) {
        const tbody = document.getElementById('manual_table_body');
        if (!tbody) return;

        if (products.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted font-monospace">No products found for this batch.</td></tr>`;
            return;
        }

        let html = '';
        products.forEach(p => {
            const isAssignedToOther = p.is_assigned && !p.is_current_carton;
            const isCurrentCarton = p.is_current_carton;
            const isDisabled = isAssignedToOther || isCurrentCarton;
            const disabledAttr = isDisabled ? 'disabled data-originally-disabled="true"' : '';
            const displayQr = formatDisplayQr(p.qr_code, p.production_no);
            
            const statusBadge = isAssignedToOther ? 
                `<span class="badge bg-warning text-dark"><i class="fa-solid fa-lock me-1"></i> In ${p.existing_carton_no}</span>` :
                (isCurrentCarton ? `<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> In This Carton</span>` : `<span class="badge bg-primary">Ready to Pack</span>`);

            html += `
                <tr>
                    <td class="ps-3">
                        <input type="checkbox" value="${p.qr_code}" class="form-check-input manual-chk" ${disabledAttr} onchange="updateManualSelectionCount()">
                    </td>
                    <td><strong class="font-monospace text-dark">${displayQr}</strong></td>
                    <td><span class="font-monospace text-primary">${p.production_no}</span></td>
                    <td><div class="fw-bold" style="font-size: 12px;">${p.style_no}</div><small class="text-muted" style="font-size: 10px;">PO: ${p.buyer_po}</small></td>
                    <td><span class="badge bg-light text-dark border font-monospace">${p.size} / ${p.color}</span></td>
                    <td>${statusBadge}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        updateManualSelectionCount();
    }

    function toggleSelectAllManual(masterCb) {
        const checkboxes = document.querySelectorAll('.manual-chk:not([data-originally-disabled])');
        const maxNewAllowed = Math.max(0, MAX_CAPACITY - REAL_CURRENT_ASSIGNED);

        if (masterCb.checked) {
            let count = 0;
            checkboxes.forEach(cb => {
                if (count < maxNewAllowed) {
                    cb.checked = true;
                    count++;
                } else {
                    cb.checked = false;
                }
            });
        } else {
            checkboxes.forEach(cb => cb.checked = false);
        }
        updateManualSelectionCount();
    }

    function filterManualTable() {
        const query = document.getElementById('manual_search_input').value.toLowerCase();
        const rows = document.querySelectorAll('#manual_table_body tr');
        rows.forEach(tr => {
            const text = tr.textContent.toLowerCase();
            tr.style.display = text.includes(query) ? '' : 'none';
        });
    }

    function updateManualSelectionCount() {
        const checked = document.querySelectorAll('.manual-chk:checked');
        const unchecked = document.querySelectorAll('.manual-chk:not(:checked)');
        const countSpan = document.getElementById('manual_selected_count');
        const btn = document.getElementById('btn_assign_manual');
        const warningDiv = document.getElementById('manual_capacity_warning');
        const masterCb = document.getElementById('manual_select_all');

        const totalSelected = checked.length;
        const totalInCarton = REAL_CURRENT_ASSIGNED + totalSelected;
        const isCapacityReached = (totalInCarton >= MAX_CAPACITY);

        if (countSpan) countSpan.textContent = totalSelected;
        if (btn) btn.disabled = (totalSelected === 0);

        if (isCapacityReached) {
            unchecked.forEach(cb => {
                cb.disabled = true;
            });
            if (masterCb && !masterCb.checked) masterCb.disabled = true;

            if (warningDiv) {
                warningDiv.innerHTML = `
                    <div class="alert alert-warning py-2 px-3 rounded-3 font-monospace small mb-2 shadow-sm" style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.4); color: #b45309;">
                        <i class="fa-solid fa-triangle-exclamation me-1.5 text-warning"></i>
                        <strong>Selection Limit Reached:</strong> Unassign items below under "Currently Linked Items in Carton" to add new items.
                    </div>
                `;
            }
        } else {
            unchecked.forEach(cb => {
                if (!cb.hasAttribute('data-originally-disabled')) {
                    cb.disabled = false;
                }
            });
            if (masterCb) masterCb.disabled = false;

            if (warningDiv) {
                if (DUMMY_ITEM_COUNT > 0) {
                    warningDiv.innerHTML = `
                        <div class="alert alert-info py-2 px-3 rounded-3 font-monospace small mb-2" style="font-size: 11.5px;">
                            <i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i> ${DUMMY_ITEM_COUNT} placeholder ITEM entries will be auto-replaced on save.
                        </div>
                    `;
                } else {
                    warningDiv.innerHTML = '';
                }
            }
        }
    }

    function submitManualAssignments() {
        const checked = document.querySelectorAll('.manual-chk:checked');
        const qrs = Array.from(checked).map(cb => cb.value);

        if (qrs.length === 0) return;

        if (REAL_CURRENT_ASSIGNED + qrs.length > MAX_CAPACITY) {
            alert(`Selection Limit Reached: Unassign items below under "Currently Linked Items in Carton" to add items.`);
            return;
        }

        let confirmMsg = `Assign ${qrs.length} selected products to Carton ${CARTON_NO}?`;
        if (DUMMY_ITEM_COUNT > 0) {
            confirmMsg += `\n\nNote: ${DUMMY_ITEM_COUNT} placeholder item(s) starting with 'ITEM' will be automatically unassigned.`;
        }

        if (!confirm(confirmMsg)) return;

        fetch('<?= base_url('company/packing-qr/api/assign-bulk') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `carton_id=${CARTON_ID}&assignment_mode=manual&${qrs.map(q => `product_qrs[]=${encodeURIComponent(q)}`).join('&')}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Assignment Error: ' + data.message);
            }
        })
        .catch(err => alert('Network error submitting assignments.'));
    }

    // ================= QR SCAN MODE LOGIC =================
    function handleQrInputKeydown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            processProductScan(e.target.value.trim());
            e.target.value = '';
        }
    }

    function triggerManualScanSubmit() {
        const inputEl = document.getElementById('qr_input_box');
        if (inputEl && inputEl.value.trim()) {
            processProductScan(inputEl.value.trim());
            inputEl.value = '';
        }
    }

    function processProductScan(qrCode) {
        if (!qrCode) return;

        if (CURRENT_ASSIGNED + scannedSessionProducts.length >= MAX_CAPACITY) {
            showScanAlert('danger', `<i class="fa-solid fa-triangle-exclamation me-1"></i> SCAN REJECTED: Selection limit reached. Unassign items below under "Currently Linked Items in Carton" to add items.`);
            return;
        }

        fetch('<?= base_url('company/packing-qr/api/scan-product') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `carton_id=${CARTON_ID}&product_qr=${encodeURIComponent(qrCode)}&${scannedSessionProducts.map(p => `session_qrs[]=${encodeURIComponent(p.qr_code)}`).join('&')}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showScanAlert('success', `<i class="fa-solid fa-circle-check me-1"></i> VALID SCAN: Product QR '<strong>${qrCode}</strong>' added!`);
                scannedSessionProducts.unshift(data.product);
                updateQrSessionUI(data);
            } else {
                showScanAlert('danger', `<i class="fa-solid fa-triangle-exclamation me-1"></i> SCAN REJECTED: ${data.message}`);
            }
        })
        .catch(err => {
            showScanAlert('danger', '<i class="fa-solid fa-triangle-exclamation me-1"></i> Network error validating scan.');
        });
    }

    function showScanAlert(type, htmlMsg) {
        const alertEl = document.getElementById('scan_feedback_alert');
        if (!alertEl) return;
        alertEl.className = `alert alert-${type} py-2 px-3 rounded-3 small font-monospace mb-0 mt-2`;
        alertEl.innerHTML = htmlMsg;
        alertEl.classList.remove('d-none');
    }

    function updateQrSessionUI(data) {
        const tableBody = document.getElementById('session_scanned_table_body');
        const badgeCount = document.getElementById('session_scanned_count_badge');
        const finalCount = document.getElementById('session_final_count');
        const btnFinal = document.getElementById('btn_finalize_qr_session');
        const headerPackedCount = document.getElementById('header_packed_count');

        const count = scannedSessionProducts.length;
        if (badgeCount) badgeCount.textContent = `${count} Items`;
        if (finalCount) finalCount.textContent = count;
        if (btnFinal) btnFinal.disabled = (count === 0);
        if (headerPackedCount) headerPackedCount.innerHTML = `${CURRENT_ASSIGNED + count} <small class="fs-6">pcs</small>`;

        if (count === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted font-monospace"><i class="fa-solid fa-qrcode fs-3 mb-1 opacity-50 text-secondary"></i><p class="m-0">No items scanned in this session yet.</p></td></tr>`;
            return;
        }

        let html = '';
        scannedSessionProducts.forEach((p, idx) => {
            const displayQr = formatDisplayQr(p.qr_code, p.production_no);
            html += `
                <tr>
                    <td class="ps-3 font-monospace fw-bold">${count - idx}</td>
                    <td><strong class="font-monospace text-primary">${displayQr}</strong></td>
                    <td><span class="font-monospace text-dark">${p.production_no}</span></td>
                    <td class="font-monospace small text-muted">${p.scanned_at}</td>
                    <td class="text-end pe-3">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="removeSessionProduct('${p.qr_code}')"><i class="fa-solid fa-xmark"></i></button>
                    </td>
                </tr>
            `;
        });
        tableBody.innerHTML = html;
    }

    function removeSessionProduct(qrCode) {
        scannedSessionProducts = scannedSessionProducts.filter(p => p.qr_code !== qrCode);
        updateQrSessionUI();
    }

    function finalizeQrSession() {
        if (scannedSessionProducts.length === 0) return;

        const qrs = scannedSessionProducts.map(p => p.qr_code);

        if (!confirm(`Finalise and link ${qrs.length} scanned products to Carton ${CARTON_NO}?`)) return;

        fetch('<?= base_url('company/packing-qr/api/assign-bulk') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `carton_id=${CARTON_ID}&assignment_mode=qr_scan&${qrs.map(q => `product_qrs[]=${encodeURIComponent(q)}`).join('&')}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Finalisation Error: ' + data.message);
            }
        })
        .catch(err => alert('Network error finalizing session.'));
    }

    function removeProductFromCarton(qrCode, itemId, displayQr) {
        const showQr = displayQr || formatDisplayQr(qrCode);
        if (!confirm(`Authorised Reversal: Are you sure you want to unassign item '${showQr}' from Carton ${CARTON_NO}?`)) return;

        fetch('<?= base_url('company/packing-qr/api/remove-product') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `carton_id=${CARTON_ID}&item_id=${itemId || 0}&product_qr=${encodeURIComponent(qrCode || '')}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Removal Error: ' + data.message);
            }
        });
    }

    function toggleCameraStream() {
        if (isCameraActive) {
            if (html5QrScanner) {
                html5QrScanner.stop().then(() => {
                    isCameraActive = false;
                    document.getElementById('reader').innerHTML = '';
                });
            }
        } else {
            html5QrScanner = new Html5Qrcode("reader");
            html5QrScanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    processProductScan(decodedText.trim());
                },
                (errorMessage) => {}
            ).then(() => {
                isCameraActive = true;
            }).catch(err => {
                alert('Camera access denied or unavailable: ' + err);
            });
        }
    }
</script>
