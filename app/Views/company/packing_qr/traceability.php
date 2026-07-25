<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 font-monospace small">
                    <li class="breadcrumb-item"><a href="<?= base_url('company/packing-qr') ?>" class="text-decoration-none">Packing QR Hub</a></li>
                    <li class="breadcrumb-item active" aria-current="page">2-Way Traceability</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark m-0 d-flex align-items-center">
                <i class="fa-solid fa-route text-primary me-2.5"></i> Enterprise 2-Way QR Lifecycle Traceability
            </h3>
        </div>
        <div>
            <a href="<?= base_url('company/packing-qr') ?>" class="btn btn-light border rounded-pill px-3 font-monospace">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Hub
            </a>
        </div>
    </div>

    <!-- Search Input Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4">
            <form method="GET" action="<?= base_url('company/packing-qr/traceability') ?>" class="row g-2 align-items-center">
                <div class="col-12 col-md-9">
                    <label class="form-label small fw-bold text-dark">Enter Product QR Code OR Sealed Carton ID / QR Code</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-primary text-white border-primary"><i class="fa-solid fa-qrcode fs-5"></i></span>
                        <input type="text" name="query" class="form-control font-monospace fw-bold text-dark bg-light" placeholder="e.g. Scan or paste Product QR (PROD-...) or Carton ID (CTN-2026-0001)..." value="<?= htmlspecialchars($query ?? '') ?>" autofocus required>
                    </div>
                </div>
                <div class="col-12 col-md-3 mt-md-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                        <i class="fa-solid fa-magnifying-glass me-1.5"></i> Trace Lifecycle
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($query)): ?>
        <?php if ($searchType === 'carton' && !empty($searchResult)): ?>
            <!-- ================= CARTON 2-WAY TRACEABILITY RESULT ================= -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary font-monospace me-2">CARTON CONTAINER</span>
                        <h5 class="fw-bold text-dark d-inline font-monospace">Carton ID: <?= htmlspecialchars($searchResult['carton_no']) ?></h5>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark border font-monospace fs-6 px-3 py-1.5">
                            Batch: <?= htmlspecialchars($searchResult['production_no']) ?> (<?= htmlspecialchars($searchResult['style_no']) ?>)
                        </span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Carton Lifecycle Stepper -->
                    <div class="row text-center font-monospace mb-4 g-2">
                        <div class="col">
                            <div class="p-3 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 10px;">STAGE 1</small>
                                <strong class="text-primary fs-6"><i class="fa-solid fa-box-archive me-1"></i> Sealed Carton</strong>
                                <small class="d-block text-success fw-bold mt-1"><?= date('d M Y, h:i A', strtotime($searchResult['created_at'])) ?></small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 10px;">STAGE 2</small>
                                <strong class="text-info fs-6"><i class="fa-solid fa-location-dot me-1"></i> Destination</strong>
                                <small class="d-block text-dark fw-bold mt-1">
                                    <?= ($searchResult['destination_type'] === 'client') ? ($searchResult['client_name'] ?: 'Client Direct') : (($searchResult['destination_type'] === 'warehouse') ? ($searchResult['warehouse_name'] ?: 'Company Warehouse') : 'Unassigned') ?>
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 10px;">STAGE 3</small>
                                <strong class="text-warning fs-6"><i class="fa-solid fa-truck me-1"></i> Shipment</strong>
                                <small class="d-block text-dark fw-bold mt-1">
                                    <?= !empty($searchResult['shipment_no']) ? htmlspecialchars($searchResult['shipment_no']) : 'Pending Shipment' ?>
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 10px;">STAGE 4</small>
                                <strong class="text-success fs-6"><i class="fa-solid fa-flag-checkered me-1"></i> Status</strong>
                                <small class="d-block text-uppercase fw-bold text-success mt-1"><?= htmlspecialchars($searchResult['status']) ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Contained Items Breakdown Table -->
                    <h6 class="fw-bold text-dark font-monospace mb-3"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Contained Products Inside This Carton (<?= count($searchResult['items']) ?> pcs)</h6>
                    <div class="table-responsive border rounded-3">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                                <tr>
                                    <th class="ps-4">Product QR Code</th>
                                    <th>Size / Color</th>
                                    <th>Quantity</th>
                                    <th>Carton Assignment Date</th>
                                    <th class="text-end pe-4">Trace Product</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($searchResult['items'])): ?>
                                    <?php foreach ($searchResult['items'] as $item): ?>
                                        <tr>
                                            <td class="ps-4"><strong class="font-monospace text-primary"><?= htmlspecialchars($item['product_qr_code']) ?></strong></td>
                                            <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($item['size']) ?> / <?= htmlspecialchars($item['color']) ?></span></td>
                                            <td><span class="badge bg-success-subtle text-success border font-monospace"><?= number_format($item['qty']) ?> pcs</span></td>
                                            <td class="font-monospace small text-muted"><?= date('d M Y, h:i A', strtotime($item['assigned_at'])) ?></td>
                                            <td class="text-end pe-4">
                                                <a href="<?= base_url('company/packing-qr/traceability?query=' . urlencode($item['product_qr_code'])) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 font-monospace">
                                                    <i class="fa-solid fa-route me-1"></i> Trace Product
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No products logged in this carton box yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($searchType === 'product' && !empty($searchResult)): ?>
            <!-- ================= PRODUCT 2-WAY TRACEABILITY RESULT ================= -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-success font-monospace me-2">PRODUCT ITEM</span>
                        <h5 class="fw-bold text-dark d-inline font-monospace">Product QR: <?= htmlspecialchars($searchResult['scanned_qr_code']) ?></h5>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark border font-monospace fs-6 px-3 py-1.5">
                            Batch: <?= htmlspecialchars($searchResult['production_no']) ?> (<?= htmlspecialchars($searchResult['style_no']) ?>)
                        </span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Product Lifecycle Stepper Visualizer -->
                    <div class="row text-center font-monospace mb-4 g-2">
                        <div class="col">
                            <div class="p-3 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 10px;">STEP 1: PRODUCTION</small>
                                <strong class="text-primary fs-6"><i class="fa-solid fa-industry me-1"></i> Manufacturing</strong>
                                <small class="d-block text-success fw-bold mt-1">Batch <?= htmlspecialchars($searchResult['production_no']) ?></small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 10px;">STEP 2: PACKING</small>
                                <strong class="text-info fs-6"><i class="fa-solid fa-shirt me-1"></i> Quality & Pack</strong>
                                <small class="d-block text-success fw-bold mt-1"><?= date('d M Y', strtotime($searchResult['created_at'])) ?></small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 10px;">STEP 3: CARTON</small>
                                <strong class="text-warning fs-6"><i class="fa-solid fa-box-archive me-1"></i> Carton Box</strong>
                                <small class="d-block text-dark fw-bold mt-1">
                                    <?= !empty($searchResult['carton_no']) ? htmlspecialchars($searchResult['carton_no']) : 'Unassigned' ?>
                                </small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 10px;">STEP 4: DISPATCH / DELIVERY</small>
                                <strong class="text-success fs-6"><i class="fa-solid fa-truck-fast me-1"></i> Destination</strong>
                                <small class="d-block text-dark fw-bold mt-1">
                                    <?= ($searchResult['client_name'] ?: $searchResult['warehouse_name']) ?: 'In Factory' ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Stage Events Audit Log History Timeline -->
                    <h6 class="fw-bold text-dark font-monospace mb-3"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Product Event Audit Trail</h6>
                    <div class="table-responsive border rounded-3">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                                <tr>
                                    <th class="ps-4">Event Date & Time</th>
                                    <th>Stage Event</th>
                                    <th>Operator / User</th>
                                    <th>Notes / Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($searchResult['timeline'])): ?>
                                    <?php foreach ($searchResult['timeline'] as $evt): ?>
                                        <tr>
                                            <td class="ps-4 font-monospace small text-dark fw-bold"><?= date('d M Y, h:i:s A', strtotime($evt['created_at'])) ?></td>
                                            <td><span class="badge bg-primary-subtle text-primary border font-monospace"><?= strtoupper(htmlspecialchars($evt['stage'])) ?></span></td>
                                            <td class="font-monospace small"><?= htmlspecialchars($evt['operator_name'] ?: 'System / Admin') ?></td>
                                            <td class="font-monospace small text-muted"><?= htmlspecialchars($evt['notes'] ?: 'Completed stage') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-3 text-muted">No timeline events recorded.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-warning rounded-4 shadow-sm p-4 text-center font-monospace">
                <i class="fa-solid fa-triangle-exclamation fs-1 text-warning mb-2"></i>
                <h5 class="fw-bold text-dark m-0">No Traceability Records Found</h5>
                <p class="text-muted m-0 mt-1">No Product QR or Sealed Carton ID matching '<strong><?= htmlspecialchars($query) ?></strong>' was found in the database.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 bg-white p-5 text-center">
            <i class="fa-solid fa-route text-primary fs-1 mb-3 opacity-75"></i>
            <h4 class="fw-bold text-dark font-monospace">Enterprise 2-Way Lifecycle Traceability</h4>
            <p class="text-muted max-w-lg mx-auto" style="max-width: 600px;">
                Scan or enter any <strong>Product QR Code</strong> or <strong>Sealed Carton ID</strong> above to trace complete lifecycle history: <br>
                <code>Production → Quality → Packing → Carton Assignment → Dispatch → Warehouse/Client</code>
            </p>
        </div>
    <?php endif; ?>
</div>
