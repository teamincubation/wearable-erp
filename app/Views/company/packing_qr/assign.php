<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<div class="container-fluid py-4">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 font-monospace small">
                    <li class="breadcrumb-item"><a href="<?= base_url('company/packing-qr') ?>" class="text-decoration-none">Packing QR Hub</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Carton #<?= htmlspecialchars($carton['carton_no']) ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark m-0 d-flex align-items-center">
                <i class="fa-solid fa-box-open text-primary me-2.5"></i> Carton Packing Assignment: <span class="font-monospace text-primary ms-1"><?= htmlspecialchars($carton['carton_no']) ?></span>
            </h3>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('company/packing-qr') ?>" class="btn btn-light border rounded-pill px-3 font-monospace">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Hub
            </a>
            <a href="<?= base_url('company/packing-qr/traceability?query=' . urlencode($carton['carton_no'])) ?>" class="btn btn-outline-info rounded-pill px-3 font-monospace">
                <i class="fa-solid fa-route me-1"></i> Carton Traceability
            </a>
        </div>
    </div>

    <!-- Carton Details & Capacity Bar Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <!-- Carton Info Details -->
                <div class="col-12 col-md-6 border-end-md">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-primary-subtle text-primary border font-monospace fs-6 px-3 py-1.5 me-2">
                            <i class="fa-solid fa-qrcode me-1"></i> <?= htmlspecialchars($carton['carton_no']) ?>
                        </span>
                        <span class="badge bg-light text-dark border font-monospace me-2">
                            Batch: <?= htmlspecialchars($carton['production_no']) ?>
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary border">
                            Style: <?= htmlspecialchars($carton['style_no'] ?: 'N/A') ?>
                        </span>
                    </div>
                    <div class="row g-2 font-monospace small text-muted mt-2">
                        <div class="col-6">
                            <i class="fa-solid fa-location-dot me-1 text-primary"></i> <strong>Destination:</strong> 
                            <?= ($carton['destination_type'] === 'client') ? ($carton['client_name'] ?: 'Client Direct') : (($carton['destination_type'] === 'warehouse') ? ($carton['warehouse_name'] ?: 'Company Warehouse') : 'Unassigned') ?>
                        </div>
                        <div class="col-6">
                            <i class="fa-solid fa-truck me-1 text-info"></i> <strong>Shipment:</strong> 
                            <?= !empty($carton['shipment_no']) ? htmlspecialchars($carton['shipment_no']) : 'Unassigned' ?>
                        </div>
                    </div>
                </div>

                <!-- Live Capacity Gauge -->
                <div class="col-12 col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-1.5 font-monospace">
                        <span class="fw-bold text-dark"><i class="fa-solid fa-scale-balanced text-primary me-1"></i> Carton Capacity Progress</span>
                        <span class="fw-bold text-primary fs-6" id="gauge_percent_display"><?= number_format($completionPercent, 1) ?>%</span>
                    </div>
                    <div class="progress mb-2" style="height: 12px; border-radius: 8px; background-color: #e2e8f0;">
                        <div id="gauge_progress_bar" class="progress-bar <?= $completionPercent >= 100 ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= min(100, $completionPercent) ?>%;"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center font-monospace small">
                        <span class="text-success fw-bold" id="gauge_assigned_display">
                            <i class="fa-solid fa-check me-1"></i> <span id="gauge_assigned_num"><?= number_format($assignedQty) ?></span> / <?= number_format($carton['max_capacity_pcs']) ?> pcs Assigned
                        </span>
                        <span class="<?= $remainingCapacity == 0 ? 'text-danger fw-bold' : 'text-muted' ?>" id="gauge_remaining_display">
                            Remaining: <strong id="gauge_remaining_num"><?= number_format($remainingCapacity) ?></strong> pcs
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mode Selector Navigation Tabs -->
    <ul class="nav nav-pills nav-fill bg-white p-2 rounded-4 shadow-sm mb-4 border" id="packingModeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-3 fw-bold py-2.5" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual-mode" type="button" role="tab" onclick="switchAssignmentMode('manual')">
                <i class="fa-solid fa-list-check me-2"></i> Manual Select Mode
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-bold py-2.5" id="qr-tab" data-bs-toggle="tab" data-bs-target="#qr-mode" type="button" role="tab" onclick="switchAssignmentMode('qr')">
                <i class="fa-solid fa-qrcode me-2"></i> QR Scan Mode (Camera & Scanner)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="packingModeTabsContent">
        <!-- ================= TAB 1: MANUAL MODE ================= -->
        <div class="tab-pane fade show active" id="manual-mode" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark m-0 font-monospace"><i class="fa-solid fa-hand me-2 text-primary"></i> Manual Packed Goods Assignment</h5>
                        <small class="text-muted">Select eligible packed products ready for dispatch in Batch <strong><?= htmlspecialchars($carton['production_no']) ?></strong>.</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm font-monospace px-3" onclick="loadEligibleManualProducts()">
                            <i class="fa-solid fa-rotate me-1"></i> Refresh List
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Filters & Search Bar -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="manual_search_input" class="form-control bg-light border-start-0 font-monospace" placeholder="Filter by Product QR Code or Style..." onkeyup="filterManualTable()">
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" id="btn_assign_manual" class="btn btn-success rounded-pill px-4 fw-bold" onclick="submitManualAssignments()" disabled>
                                <i class="fa-solid fa-link me-1.5"></i> Assign Selected Items (<span id="manual_selected_count">0</span> pcs)
                            </button>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="table-responsive border rounded-3" style="max-height: 380px;">
                        <table class="table table-hover align-middle mb-0" id="manual_products_table">
                            <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                                <tr>
                                    <th width="40" class="ps-3"><input type="checkbox" id="manual_select_all" class="form-check-input" onchange="toggleSelectAllManual(this)"></th>
                                    <th>Product QR Code</th>
                                    <th>Batch No</th>
                                    <th>Style & PO</th>
                                    <th>Size / Color</th>
                                    <th>Packing Status</th>
                                    <th>Assignment Status</th>
                                </tr>
                            </thead>
                            <tbody id="manual_table_body">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted font-monospace">
                                        <i class="fa-solid fa-spinner fa-spin me-2"></i> Loading eligible packed products...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= TAB 2: QR SCAN MODE ================= -->
        <div class="tab-pane fade" id="qr-mode" role="tabpanel">
            <div class="row g-4">
                <!-- Scanner Input & Controls -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark m-0 font-monospace"><i class="fa-solid fa-camera text-primary me-2"></i> Continuous QR Scanner</h5>
                            <span class="badge bg-success-subtle text-success border font-monospace"><i class="fa-solid fa-circle me-1"></i> Live Scanner Active</span>
                        </div>
                        <div class="card-body p-4">
                            <!-- Sealed Carton Auto Validation Bar -->
                            <div class="alert alert-primary py-2.5 px-3 rounded-3 small font-monospace mb-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="fa-solid fa-box-archive me-1.5"></i> <strong>Target Carton:</strong> <?= htmlspecialchars($carton['carton_no']) ?>
                                </div>
                                <span class="badge bg-light text-primary border fw-bold">Validated OK</span>
                            </div>

                            <!-- Real-Time Input Box (Keyboard Scanner Listener + Manual Override) -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Scan Product QR Code <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-dark text-white border-dark"><i class="fa-solid fa-qrcode"></i></span>
                                    <input type="text" id="qr_input_box" class="form-control font-monospace bg-light fw-bold text-dark" placeholder="Scan or paste Product QR..." autofocus onkeydown="handleQrInputKeydown(event)">
                                    <button class="btn btn-primary fw-bold" type="button" onclick="triggerManualScanSubmit()">
                                        <i class="fa-solid fa-plus me-1"></i> Add
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1 font-monospace" style="font-size: 11px;">
                                    <i class="fa-solid fa-keyboard me-1"></i> Supports hardware USB barcode guns, bluetooth scanners, and camera feed.
                                </small>
                            </div>

                            <!-- Camera Viewport (Html5Qrcode) -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="fw-bold text-dark font-monospace"><i class="fa-solid fa-video me-1 text-primary"></i> Camera Scanner Stream</small>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 font-monospace" onclick="toggleCameraStream()">
                                        <i class="fa-solid fa-power-off me-1"></i> Toggle Camera
                                    </button>
                                </div>
                                <div id="camera_container" class="rounded-3 overflow-hidden border bg-dark text-center py-4" style="min-height: 220px;">
                                    <div id="reader" style="width: 100%; min-height: 220px;"></div>
                                </div>
                            </div>

                            <!-- Live Scan Validation Status Toast / Alert -->
                            <div id="scan_feedback_alert" class="alert alert-secondary py-2.5 px-3 rounded-3 small font-monospace d-none"></div>
                        </div>
                    </div>
                </div>

                <!-- Scanned Products Session Stream -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark m-0 font-monospace"><i class="fa-solid fa-list-ol text-success me-2"></i> Current Session Scanned Items</h5>
                            <span class="badge bg-primary font-monospace fs-6 px-3 py-1" id="session_scanned_count_badge">0 Items</span>
                        </div>
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div class="table-responsive border rounded-3 mb-3" style="max-height: 360px;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                                        <tr>
                                            <th class="ps-3">#</th>
                                            <th>Product QR</th>
                                            <th>Batch & Style</th>
                                            <th>Scan Time</th>
                                            <th class="text-end pe-3">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody id="session_scanned_table_body">
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted font-monospace">
                                                <i class="fa-solid fa-qrcode fs-2 mb-2 opacity-50 text-secondary"></i>
                                                <p class="m-0">No products scanned in this session yet.</p>
                                                <small>Scan Product QR codes on the left to add them live.</small>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Finalize & Submit Button -->
                            <div class="pt-2 border-top">
                                <button type="button" id="btn_finalize_qr_session" class="btn btn-success w-100 py-3 rounded-3 fw-bold fs-6 shadow-sm" onclick="finalizeQrSession()" disabled>
                                    <i class="fa-solid fa-circle-check me-2"></i> Finalise & Link Scanned Products to Carton (<span id="session_final_count">0</span> pcs)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Currently Linked Products Section -->
    <div class="card border-0 shadow-sm rounded-4 mt-4 bg-white">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark m-0 font-monospace"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Currently Linked Items in Carton</h5>
            <span class="badge bg-light text-dark border font-monospace"><?= count($assignedItems) ?> Total Items</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                        <tr>
                            <th class="ps-4">Product QR Code</th>
                            <th>Batch No</th>
                            <th>Size / Color</th>
                            <th>Quantity</th>
                            <th>Assigned By</th>
                            <th>Assigned Date & Time</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assignedItems)): ?>
                            <?php foreach ($assignedItems as $ai): 
                                $itemQr = !empty($ai['product_qr_code']) ? (string)$ai['product_qr_code'] : ('ITEM-' . $ai['id']);
                                $assignedByName = !empty($ai['assigned_by_name']) ? (string)$ai['assigned_by_name'] : 'Admin';
                                $sizeVal = !empty($ai['size']) ? (string)$ai['size'] : 'FREE';
                                $colorVal = !empty($ai['color']) ? (string)$ai['color'] : 'N/A';
                                $assignedDate = !empty($ai['assigned_at']) ? date('d M Y, h:i A', strtotime($ai['assigned_at'])) : (!empty($ai['created_at']) ? date('d M Y, h:i A', strtotime($ai['created_at'])) : 'N/A');
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <strong class="font-monospace text-primary"><?= htmlspecialchars($itemQr) ?></strong>
                                    </td>
                                    <td><span class="font-monospace text-dark fw-bold"><?= htmlspecialchars($carton['production_no']) ?></span></td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($sizeVal) ?> / <?= htmlspecialchars($colorVal) ?></span></td>
                                    <td><span class="badge bg-success-subtle text-success border font-monospace"><?= number_format((int)($ai['qty'] ?: 1)) ?> pcs</span></td>
                                    <td class="font-monospace small"><?= htmlspecialchars($assignedByName) ?></td>
                                    <td class="font-monospace small text-muted"><?= htmlspecialchars($assignedDate) ?></td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Remove / Unassign from Carton" onclick="removeProductFromCarton('<?= htmlspecialchars($itemQr) ?>', <?= (int)$ai['id'] ?>)">
                                            <i class="fa-solid fa-trash-can me-1"></i> Unassign
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted font-monospace">
                                    No products currently assigned to this carton. Use Manual Mode or QR Scan Mode above to pack items.
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
    const MAX_CAPACITY = <?= (int)$carton['max_capacity_pcs'] ?>;
    let CURRENT_ASSIGNED = <?= (int)$assignedQty ?>;

    let scannedSessionProducts = [];
    let html5QrScanner = null;
    let isCameraActive = false;

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

        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted font-monospace"><i class="fa-solid fa-spinner fa-spin me-2"></i> Fetching eligible packed products for batch...</td></tr>`;

        fetch(`<?= base_url('company/packing-qr/api/eligible-products') ?>?carton_id=${CARTON_ID}`)
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                return res.json();
            })
            .then(data => {
                if (data.success && data.products) {
                    renderManualProductsTable(data.products);
                } else {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger font-monospace"><i class="fa-solid fa-triangle-exclamation me-1"></i> ${data.message || 'Failed to load batch products.'}</td></tr>`;
                }
            })
            .catch(err => {
                console.error("Error fetching products:", err);
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger font-monospace"><i class="fa-solid fa-triangle-exclamation me-1"></i> Error loading products: ${err.message || 'Server connection error'}</td></tr>`;
            });
    }

    function renderManualProductsTable(products) {
        const tbody = document.getElementById('manual_table_body');
        if (!tbody) return;

        if (products.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted font-monospace">No eligible packed products found ready for dispatch in this batch.</td></tr>`;
            return;
        }

        let html = '';
        products.forEach(p => {
            const isAssignedToOther = p.is_assigned && !p.is_current_carton;
            const disabledAttr = isAssignedToOther ? 'disabled' : '';
            const statusBadge = isAssignedToOther ? 
                `<span class="badge bg-warning text-dark"><i class="fa-solid fa-lock me-1"></i> Assigned (${p.existing_carton_no})</span>` :
                (p.is_current_carton ? `<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> In This Carton</span>` : `<span class="badge bg-primary">Ready to Pack</span>`);

            html += `
                <tr>
                    <td class="ps-3">
                        <input type="checkbox" value="${p.qr_code}" class="form-check-input manual-chk" ${disabledAttr} onchange="updateManualSelectionCount()">
                    </td>
                    <td><strong class="font-monospace text-dark">${p.qr_code}</strong></td>
                    <td><span class="font-monospace text-primary">${p.production_no}</span></td>
                    <td><div class="fw-bold">${p.style_no}</div><small class="text-muted">PO: ${p.buyer_po}</small></td>
                    <td><span class="badge bg-light text-dark border font-monospace">${p.size} / ${p.color}</span></td>
                    <td><span class="badge bg-success-subtle text-success border font-monospace">${p.stage}</span></td>
                    <td>${statusBadge}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        updateManualSelectionCount();
    }

    function toggleSelectAllManual(masterCb) {
        const checkboxes = document.querySelectorAll('.manual-chk:not([disabled])');
        checkboxes.forEach(cb => cb.checked = masterCb.checked);
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
        const countSpan = document.getElementById('manual_selected_count');
        const btn = document.getElementById('btn_assign_manual');
        if (countSpan) countSpan.textContent = checked.length;
        if (btn) btn.disabled = (checked.length === 0);
    }

    function submitManualAssignments() {
        const checked = document.querySelectorAll('.manual-chk:checked');
        const qrs = Array.from(checked).map(cb => cb.value);

        if (qrs.length === 0) return;

        const remaining = MAX_CAPACITY - CURRENT_ASSIGNED;
        if (qrs.length > remaining) {
            alert(`Capacity limit exceeded! You selected ${qrs.length} items, but carton only has ${remaining} pcs remaining capacity.`);
            return;
        }

        if (!confirm(`Assign ${qrs.length} selected products to Carton ${CARTON_NO}?`)) return;

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

        const alertEl = document.getElementById('scan_feedback_alert');

        fetch('<?= base_url('company/packing-qr/api/scan-product') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `carton_id=${CARTON_ID}&product_qr=${encodeURIComponent(qrCode)}&${scannedSessionProducts.map(p => `session_qrs[]=${encodeURIComponent(p.qr_code)}`).join('&')}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Play success chime or sound effect
                showScanAlert('success', `<i class="fa-solid fa-circle-check me-1"></i> VALID SCAN: Product QR '<strong>${qrCode}</strong>' added to session!`);
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
        alertEl.className = `alert alert-${type} py-2.5 px-3 rounded-3 small font-monospace mt-3`;
        alertEl.innerHTML = htmlMsg;
        alertEl.classList.remove('d-none');
    }

    function updateQrSessionUI(data) {
        const tableBody = document.getElementById('session_scanned_table_body');
        const badgeCount = document.getElementById('session_scanned_count_badge');
        const finalCount = document.getElementById('session_final_count');
        const btnFinal = document.getElementById('btn_finalize_qr_session');

        const gaugePercent = document.getElementById('gauge_percent_display');
        const progressBar = document.getElementById('gauge_progress_bar');
        const assignedNum = document.getElementById('gauge_assigned_num');
        const remainingNum = document.getElementById('gauge_remaining_num');

        // Update Gauge
        if (data) {
            if (gaugePercent) gaugePercent.textContent = `${data.completion_percent}%`;
            if (progressBar) {
                progressBar.style.width = `${Math.min(100, data.completion_percent)}%`;
                if (data.completion_percent >= 100) progressBar.className = 'progress-bar bg-success';
            }
            if (assignedNum) assignedNum.textContent = data.updated_assigned_qty;
            if (remainingNum) remainingNum.textContent = data.remaining_capacity;
        }

        const count = scannedSessionProducts.length;
        if (badgeCount) badgeCount.textContent = `${count} Items`;
        if (finalCount) finalCount.textContent = count;
        if (btnFinal) btnFinal.disabled = (count === 0);

        if (count === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted font-monospace"><i class="fa-solid fa-qrcode fs-2 mb-2 opacity-50 text-secondary"></i><p class="m-0">No products scanned in this session yet.</p></td></tr>`;
            return;
        }

        let html = '';
        scannedSessionProducts.forEach((p, idx) => {
            html += `
                <tr>
                    <td class="ps-3 font-monospace fw-bold">${count - idx}</td>
                    <td><strong class="font-monospace text-primary">${p.qr_code}</strong></td>
                    <td><span class="font-monospace text-dark">${p.production_no}</span><small class="d-block text-muted">${p.style_no}</small></td>
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
        const newTotal = CURRENT_ASSIGNED + scannedSessionProducts.length;
        const newRemaining = Math.max(0, MAX_CAPACITY - newTotal);
        const percent = Math.min(100, Math.round((newTotal / MAX_CAPACITY) * 100));
        updateQrSessionUI({
            completion_percent: percent,
            updated_assigned_qty: newTotal,
            remaining_capacity: newRemaining
        });
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

    function removeProductFromCarton(qrCode, itemId) {
        if (!confirm(`Authorised Reversal: Are you sure you want to unassign item '${qrCode}' from Carton ${CARTON_NO}?`)) return;

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
