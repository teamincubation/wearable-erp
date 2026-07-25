<style>
    .trace-card {
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    }
    @media (max-width: 576px) {
        .stepper-col {
            margin-bottom: 8px;
        }
    }
</style>

<div class="container-fluid py-3 px-2 px-md-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 font-monospace small">
                    <li class="breadcrumb-item"><a href="<?= base_url('company/packing-qr') ?>" class="text-decoration-none">Packing QR</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Traceability</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark m-0 d-flex align-items-center mt-1">
                <i class="fa-solid fa-route text-primary me-2"></i> 2-Way Lifecycle Traceability
            </h4>
        </div>
        <div>
            <a href="<?= base_url('company/packing-qr') ?>" class="btn btn-light border btn-sm rounded-circle p-2" title="Back to Hub" style="width: 38px; height: 38px;">
                <i class="fa-solid fa-arrow-left fs-6"></i>
            </a>
        </div>
    </div>

    <!-- Mobile-First Search Input Bar -->
    <div class="trace-card p-3 mb-3">
        <form method="GET" action="<?= base_url('company/packing-qr/traceability') ?>" class="row g-2 align-items-center">
            <div class="col-12 col-md-9">
                <label class="form-label small fw-bold text-dark">Enter Product QR Code OR Sealed Carton ID</label>
                <div class="input-group">
                    <span class="input-group-text bg-primary text-white border-primary"><i class="fa-solid fa-qrcode"></i></span>
                    <input type="text" name="query" class="form-control font-monospace fw-bold text-dark bg-light" placeholder="e.g. Scan or paste Product QR (PROD-...) or Carton ID (CTN-2026-0001)..." value="<?= htmlspecialchars($query ?? '') ?>" autofocus required>
                </div>
            </div>
            <div class="col-12 col-md-3 mt-md-4">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold py-2 shadow-sm">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Trace Lifecycle
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($query)): ?>
        <?php if ($searchType === 'carton' && !empty($searchResult)): ?>
            <!-- ================= CARTON 2-WAY TRACEABILITY RESULT ================= -->
            <div class="trace-card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <div>
                        <span class="badge bg-primary font-monospace me-1.5">CARTON CONTAINER</span>
                        <h6 class="fw-bold text-dark d-inline font-monospace">Carton ID: <?= htmlspecialchars($searchResult['carton_no']) ?></h6>
                    </div>
                    <span class="badge bg-light text-dark border font-monospace">
                        Batch: <?= htmlspecialchars($searchResult['production_no']) ?>
                    </span>
                </div>

                <!-- Carton Lifecycle Stepper -->
                <div class="row row-cols-2 row-cols-md-4 text-center font-monospace mb-3 g-2">
                    <div class="col stepper-col">
                        <div class="p-2.5 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block" style="font-size: 9px;">STAGE 1</small>
                            <strong class="text-primary small"><i class="fa-solid fa-box-archive me-1"></i> Sealed Carton</strong>
                            <small class="d-block text-success fw-bold mt-0.5" style="font-size: 10px;"><?= date('d M Y, h:i A', strtotime($searchResult['created_at'])) ?></small>
                        </div>
                    </div>
                    <div class="col stepper-col">
                        <div class="p-2.5 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block" style="font-size: 9px;">STAGE 2</small>
                            <strong class="text-info small"><i class="fa-solid fa-location-dot me-1"></i> Destination</strong>
                            <small class="d-block text-dark fw-bold mt-0.5" style="font-size: 10px;">
                                <?= ($searchResult['destination_type'] === 'client') ? ($searchResult['client_name'] ?: 'Client Direct') : (($searchResult['destination_type'] === 'warehouse') ? ($searchResult['warehouse_name'] ?: 'Company Warehouse') : 'Unassigned') ?>
                            </small>
                        </div>
                    </div>
                    <div class="col stepper-col">
                        <div class="p-2.5 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block" style="font-size: 9px;">STAGE 3</small>
                            <strong class="text-warning small"><i class="fa-solid fa-truck me-1"></i> Shipment</strong>
                            <small class="d-block text-dark fw-bold mt-0.5" style="font-size: 10px;">
                                <?= !empty($searchResult['shipment_no']) ? htmlspecialchars($searchResult['shipment_no']) : 'Pending' ?>
                            </small>
                        </div>
                    </div>
                    <div class="col stepper-col">
                        <div class="p-2.5 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block" style="font-size: 9px;">STAGE 4</small>
                            <strong class="text-success small"><i class="fa-solid fa-flag-checkered me-1"></i> Status</strong>
                            <small class="d-block text-uppercase fw-bold text-success mt-0.5" style="font-size: 10px;"><?= htmlspecialchars($searchResult['status']) ?></small>
                        </div>
                    </div>
                </div>

                <!-- Contained Items Breakdown Table -->
                <h6 class="fw-bold text-dark font-monospace mb-2"><i class="fa-solid fa-boxes-stacked text-primary me-1.5"></i> Contained Products Inside (<?= count($searchResult['items']) ?> pcs)</h6>
                <div class="table-responsive border rounded-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                            <tr>
                                <th class="ps-3">Product QR Code</th>
                                <th>Size / Color</th>
                                <th>Quantity</th>
                                <th>Packing Date</th>
                                <th class="text-end pe-3">Trace</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($searchResult['items'])): ?>
                                <?php foreach ($searchResult['items'] as $item): 
                                    $itemQr = !empty($item['product_qr_code']) ? (string)$item['product_qr_code'] : ('ITEM-' . $item['id']);
                                ?>
                                    <tr>
                                        <td class="ps-3"><strong class="font-monospace text-primary"><?= htmlspecialchars($itemQr) ?></strong></td>
                                        <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($item['size'] ?: 'FREE') ?> / <?= htmlspecialchars($item['color'] ?: 'N/A') ?></span></td>
                                        <td><span class="badge bg-success-subtle text-success border font-monospace"><?= number_format((int)($item['qty'] ?: 1)) ?> pcs</span></td>
                                        <td class="font-monospace small text-muted"><?= date('d M Y, h:i A', strtotime($item['assigned_at'] ?? $item['created_at'])) ?></td>
                                        <td class="text-end pe-3">
                                            <a href="<?= base_url('company/packing-qr/traceability?query=' . urlencode($itemQr)) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 font-monospace">
                                                <i class="fa-solid fa-route me-1"></i> Trace
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted font-monospace">No products logged in this carton box yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($searchType === 'product' && !empty($searchResult)): ?>
            <!-- ================= PRODUCT 2-WAY TRACEABILITY RESULT ================= -->
            <div class="trace-card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <div>
                        <span class="badge bg-success font-monospace me-1.5">PRODUCT ITEM</span>
                        <h6 class="fw-bold text-dark d-inline font-monospace">Product QR: <?= htmlspecialchars($searchResult['scanned_qr_code']) ?></h6>
                    </div>
                    <span class="badge bg-light text-dark border font-monospace">
                        Batch: <?= htmlspecialchars($searchResult['production_no']) ?>
                    </span>
                </div>

                <!-- Product Lifecycle Stepper Visualizer -->
                <div class="row row-cols-2 row-cols-md-4 text-center font-monospace mb-3 g-2">
                    <div class="col stepper-col">
                        <div class="p-2.5 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block" style="font-size: 9px;">STEP 1: PRODUCTION</small>
                            <strong class="text-primary small"><i class="fa-solid fa-industry me-1"></i> Manufacturing</strong>
                            <small class="d-block text-success fw-bold mt-0.5" style="font-size: 10px;">Batch <?= htmlspecialchars($searchResult['production_no']) ?></small>
                        </div>
                    </div>
                    <div class="col stepper-col">
                        <div class="p-2.5 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block" style="font-size: 9px;">STEP 2: PACKING</small>
                            <strong class="text-info small"><i class="fa-solid fa-shirt me-1"></i> Quality & Pack</strong>
                            <small class="d-block text-success fw-bold mt-0.5" style="font-size: 10px;"><?= date('d M Y', strtotime($searchResult['created_at'])) ?></small>
                        </div>
                    </div>
                    <div class="col stepper-col">
                        <div class="p-2.5 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block" style="font-size: 9px;">STEP 3: CARTON</small>
                            <strong class="text-warning small"><i class="fa-solid fa-box-archive me-1"></i> Carton Box</strong>
                            <small class="d-block text-dark fw-bold mt-0.5" style="font-size: 10px;">
                                <?= !empty($searchResult['carton_no']) ? htmlspecialchars($searchResult['carton_no']) : 'Unassigned' ?>
                            </small>
                        </div>
                    </div>
                    <div class="col stepper-col">
                        <div class="p-2.5 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block" style="font-size: 9px;">STEP 4: DISPATCH</small>
                            <strong class="text-success small"><i class="fa-solid fa-truck-fast me-1"></i> Destination</strong>
                            <small class="d-block text-dark fw-bold mt-0.5" style="font-size: 10px;">
                                <?= ($searchResult['client_name'] ?: $searchResult['warehouse_name']) ?: 'In Factory' ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Stage Events Audit Log History Timeline -->
                <h6 class="fw-bold text-dark font-monospace mb-2"><i class="fa-solid fa-clock-rotate-left text-primary me-1.5"></i> Product Event Audit Trail</h6>
                <div class="table-responsive border rounded-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                            <tr>
                                <th class="ps-3">Event Date & Time</th>
                                <th>Stage Event</th>
                                <th>Operator / User</th>
                                <th>Notes / Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($searchResult['timeline'])): ?>
                                <?php foreach ($searchResult['timeline'] as $evt): ?>
                                    <tr>
                                        <td class="ps-3 font-monospace small text-dark fw-bold"><?= date('d M Y, h:i:s A', strtotime($evt['created_at'])) ?></td>
                                        <td><span class="badge bg-primary-subtle text-primary border font-monospace"><?= strtoupper(htmlspecialchars($evt['stage'])) ?></span></td>
                                        <td class="font-monospace small"><?= htmlspecialchars($evt['operator_name'] ?: 'System / Admin') ?></td>
                                        <td class="font-monospace small text-muted"><?= htmlspecialchars($evt['notes'] ?: 'Completed stage') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-3 text-muted font-monospace">No timeline events recorded.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-warning rounded-4 shadow-sm p-4 text-center font-monospace">
                <i class="fa-solid fa-triangle-exclamation fs-2 text-warning mb-2"></i>
                <h6 class="fw-bold text-dark m-0">No Traceability Records Found</h6>
                <p class="text-muted m-0 mt-1 small">No Product QR or Sealed Carton ID matching '<strong><?= htmlspecialchars($query) ?></strong>' was found.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="trace-card p-4 text-center">
            <i class="fa-solid fa-route text-primary fs-1 mb-2 opacity-75"></i>
            <h5 class="fw-bold text-dark font-monospace">Enterprise 2-Way Lifecycle Traceability</h5>
            <p class="text-muted small max-w-lg mx-auto m-0" style="max-width: 500px;">
                Scan or enter any <strong>Product QR Code</strong> or <strong>Sealed Carton ID</strong> above to trace complete lifecycle history.
            </p>
        </div>
    <?php endif; ?>
</div>
