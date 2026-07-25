<style>
    .packing-card {
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        transition: all 0.2s ease-in-out;
    }
    .packing-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    }
    @media (max-width: 768px) {
        .mobile-card-stack {
            display: block !important;
        }
        .desktop-table-view {
            display: none !important;
        }
    }
    @media (min-width: 769px) {
        .mobile-card-stack {
            display: none !important;
        }
        .desktop-table-view {
            display: block !important;
        }
    }
</style>

<div class="container-fluid py-3 px-2 px-md-4">
    <!-- Header Banner -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold text-dark m-0 d-flex align-items-center">
                <i class="fa-solid fa-boxes-packing text-primary me-2"></i> Packing QR Hub
            </h4>
            <p class="text-muted small m-0 mt-0.5" style="font-size: 12px;">Manage sealed carton box assignments and Product-to-Carton QR workflows.</p>
        </div>
        <div class="d-flex gap-2 w-100 w-sm-auto">
            <a href="<?= base_url('company/packing-qr/traceability') ?>" class="btn btn-outline-primary btn-sm rounded-pill fw-bold px-3 flex-fill text-center">
                <i class="fa-solid fa-route me-1"></i> 2-Way Traceability
            </a>
            <a href="<?= base_url('company/dispatch') ?>" class="btn btn-primary btn-sm rounded-pill fw-bold px-3 flex-fill text-center">
                <i class="fa-solid fa-truck-ramp-box me-1"></i> Goods Dispatch
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards (Mobile-First 2-Column Grid) -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="packing-card p-3 d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-semibold text-uppercase font-monospace d-block" style="font-size: 10px;">Sealed Cartons</small>
                    <h3 class="fw-bold text-dark m-0 mt-1 font-monospace"><?= number_format($totalCartons) ?></h3>
                </div>
                <div class="rounded-circle p-2.5 bg-primary-subtle text-primary ms-2">
                    <i class="fa-solid fa-box-archive fs-5"></i>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="packing-card p-3 d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-semibold text-uppercase font-monospace d-block" style="font-size: 10px;">Total Packed Units</small>
                    <h3 class="fw-bold text-success m-0 mt-1 font-monospace"><?= number_format($totalAssignedUnits) ?> <small class="fs-6 text-muted">pcs</small></h3>
                </div>
                <div class="rounded-circle p-2.5 bg-success-subtle text-success ms-2">
                    <i class="fa-solid fa-shirt fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile-Optimized Filter Bar -->
    <div class="packing-card p-3 mb-3">
        <form method="GET" action="<?= base_url('company/packing-qr') ?>" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0 font-monospace" placeholder="Search Carton ID, Batch, Style..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="batch_no" class="form-select form-select-sm text-dark font-monospace">
                    <option value="">-- All Batches --</option>
                    <?php foreach ($batchOptions as $bNo): ?>
                        <option value="<?= htmlspecialchars($bNo) ?>" <?= ($filters['batch_no'] ?? '') === $bNo ? 'selected' : '' ?>><?= htmlspecialchars($bNo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm text-dark font-monospace">
                    <option value="">-- All Status --</option>
                    <option value="packed" <?= ($filters['status'] ?? '') === 'packed' ? 'selected' : '' ?>>Packed / Sealed</option>
                    <option value="dispatched" <?= ($filters['status'] ?? '') === 'dispatched' ? 'selected' : '' ?>>Dispatched</option>
                    <option value="delivered" <?= ($filters['status'] ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-1.5">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="<?= base_url('company/packing-qr') ?>" class="btn btn-sm btn-light border text-dark" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>

    <!-- ================= DESKTOP TABLE VIEW ================= -->
    <div class="packing-card overflow-hidden desktop-table-view">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="fw-bold text-dark m-0 font-monospace"><i class="fa-solid fa-boxes-packing text-primary me-2"></i> Sealed Carton Boxes List</h6>
            <span class="badge bg-light text-dark border font-monospace"><?= count($cartons) ?> Cartons</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                        <tr>
                            <th class="ps-4">Carton ID</th>
                            <th>Carton QR</th>
                            <th>Batch & Style</th>
                            <th>Packed Units</th>
                            <th>Packing Date & Time</th>
                            <th>Destination</th>
                            <th>Dispatch Status</th>
                            <th>Shipment</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cartons)): ?>
                            <?php foreach ($cartons as $c): 
                                $statusBadge = match($c['status']) {
                                    'packed' => '<span class="badge bg-primary">Packed / Sealed</span>',
                                    'dispatched' => '<span class="badge bg-warning text-dark"><i class="fa-solid fa-truck-fast me-1"></i> Dispatched</span>',
                                    'delivered' => '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Delivered</span>',
                                    default => '<span class="badge bg-secondary">Draft</span>'
                                };
                                $destLabel = ($c['destination_type'] === 'client') ? ($c['client_name'] ?: 'Client Direct') : (($c['destination_type'] === 'warehouse') ? ($c['warehouse_name'] ?: 'Company Warehouse') : 'Unassigned');
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <strong class="font-monospace text-dark fs-6"><?= htmlspecialchars($c['carton_no']) ?></strong>
                                        <small class="d-block text-muted" style="font-size: 11px;">By: <?= htmlspecialchars($c['created_by_name'] ?: 'Admin') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark-subtle text-dark border font-monospace" style="font-size: 10px;">
                                            <i class="fa-solid fa-qrcode me-1"></i> <?= htmlspecialchars($c['carton_no']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="font-monospace text-primary"><?= htmlspecialchars($c['production_no'] ?: 'N/A') ?></strong>
                                        <small class="d-block text-muted"><?= htmlspecialchars($c['style_no'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border font-monospace fs-6 px-2.5 py-1">
                                            <i class="fa-solid fa-shirt me-1"></i> <?= number_format($c['assigned_qty']) ?> pcs
                                        </span>
                                    </td>
                                    <td class="font-monospace small text-muted">
                                        <?= date('d M Y', strtotime($c['created_at'])) ?><br>
                                        <?= date('h:i A', strtotime($c['created_at'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($destLabel) ?></span>
                                    </td>
                                    <td><?= $statusBadge ?></td>
                                    <td>
                                        <?php if (!empty($c['shipment_no'])): ?>
                                            <span class="badge bg-info-subtle text-info border font-monospace"><i class="fa-solid fa-truck me-1"></i> <?= htmlspecialchars($c['shipment_no']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="<?= base_url('company/packing-qr/assign/' . $c['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3 me-1 fw-bold">
                                            <i class="fa-solid fa-box-open me-1"></i> Pack Goods
                                        </a>
                                        <a href="<?= base_url('company/dispatch/cartons/print?carton_id=' . $c['id']) ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-2.5" title="Print QR Label">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-boxes-packing fs-1 mb-2 opacity-50 text-secondary"></i>
                                    <p class="m-0 fw-semibold">No sealed carton boxes found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================= MOBILE CARD STACK VIEW ================= -->
    <div class="mobile-card-stack">
        <?php if (!empty($cartons)): ?>
            <?php foreach ($cartons as $c): 
                $destLabel = ($c['destination_type'] === 'client') ? ($c['client_name'] ?: 'Client Direct') : (($c['destination_type'] === 'warehouse') ? ($c['warehouse_name'] ?: 'Company Warehouse') : 'Unassigned');
            ?>
                <div class="packing-card p-3 mb-2.5">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-primary font-monospace fs-6 px-2.5 py-1 mb-1">
                                <i class="fa-solid fa-qrcode me-1"></i> <?= htmlspecialchars($c['carton_no']) ?>
                            </span>
                            <div class="small text-muted font-monospace">Batch: <strong class="text-dark"><?= htmlspecialchars($c['production_no']) ?></strong> (<?= htmlspecialchars($c['style_no'] ?: 'N/A') ?>)</div>
                        </div>
                        <span class="badge bg-success-subtle text-success border font-monospace fs-6 px-2.5 py-1">
                            <i class="fa-solid fa-shirt me-1"></i> <?= number_format($c['assigned_qty']) ?> pcs
                        </span>
                    </div>

                    <div class="row g-2 font-monospace small text-muted my-2 border-top border-bottom py-2" style="font-size: 11px;">
                        <div class="col-6">
                            <i class="fa-solid fa-location-dot text-primary me-1"></i> <strong>Dest:</strong> <?= htmlspecialchars($destLabel) ?>
                        </div>
                        <div class="col-6 text-end">
                            <i class="fa-solid fa-truck text-info me-1"></i> <strong>Shipment:</strong> <?= !empty($c['shipment_no']) ? htmlspecialchars($c['shipment_no']) : 'None' ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-1">
                        <small class="text-muted font-monospace" style="font-size: 11px;">
                            <?= date('d M Y, h:i A', strtotime($c['created_at'])) ?>
                        </small>
                        <div class="d-flex gap-1">
                            <a href="<?= base_url('company/dispatch/cartons/print?carton_id=' . $c['id']) ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-circle" style="width: 34px; height: 34px; padding: 5px;">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            <a href="<?= base_url('company/packing-qr/assign/' . $c['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                                <i class="fa-solid fa-box-open me-1"></i> Pack Goods
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="packing-card p-4 text-center text-muted font-monospace">
                <i class="fa-solid fa-boxes-packing fs-2 mb-2 opacity-50 text-secondary"></i>
                <p class="m-0">No sealed cartons found matching your filters.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
