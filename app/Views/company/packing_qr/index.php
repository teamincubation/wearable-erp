<div class="container-fluid py-4">
    <!-- Header Banner -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark m-0 d-flex align-items-center">
                <i class="fa-solid fa-boxes-packing text-primary me-2.5"></i> Packing QR Hub
            </h3>
            <p class="text-muted small m-0 mt-1">Manage sealed carton box assignments, capacity tracking, and Product-to-Carton QR workflows.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('company/packing-qr/traceability') ?>" class="btn btn-outline-primary rounded-pill fw-bold px-3">
                <i class="fa-solid fa-route me-1.5"></i> 2-Way Traceability
            </a>
            <a href="<?= base_url('company/dispatch') ?>" class="btn btn-primary rounded-pill fw-bold px-3.5">
                <i class="fa-solid fa-truck-ramp-box me-1.5"></i> Finished Goods Hub
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-semibold text-uppercase font-monospace" style="font-size: 11px;">Total Sealed Cartons</small>
                        <h3 class="fw-bold text-dark m-0 mt-1 font-monospace"><?= number_format($totalCartons) ?></h3>
                    </div>
                    <div class="rounded-3 p-3 bg-primary-subtle text-primary">
                        <i class="fa-solid fa-box-archive fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-semibold text-uppercase font-monospace" style="font-size: 11px;">Assigned Units</small>
                        <h3 class="fw-bold text-success m-0 mt-1 font-monospace"><?= number_format($totalAssignedUnits) ?> <small class="fs-6 text-muted">pcs</small></h3>
                    </div>
                    <div class="rounded-3 p-3 bg-success-subtle text-success">
                        <i class="fa-solid fa-shirt fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-semibold text-uppercase font-monospace" style="font-size: 11px;">Total Capacity</small>
                        <h3 class="fw-bold text-info m-0 mt-1 font-monospace"><?= number_format($totalCapacity) ?> <small class="fs-6 text-muted">pcs</small></h3>
                    </div>
                    <div class="rounded-3 p-3 bg-info-subtle text-info">
                        <i class="fa-solid fa-scale-balanced fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-semibold text-uppercase font-monospace" style="font-size: 11px;">Fully Packed Cartons</small>
                        <h3 class="fw-bold text-warning m-0 mt-1 font-monospace"><?= number_format($fullyPackedCartons) ?></h3>
                    </div>
                    <div class="rounded-3 p-3 bg-warning-subtle text-warning">
                        <i class="fa-solid fa-circle-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Control Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('company/packing-qr') ?>" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0 font-monospace" placeholder="Search Carton ID, Batch, Style, Client..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select name="batch_no" class="form-select text-dark font-monospace">
                        <option value="">-- All Batches --</option>
                        <?php foreach ($batchOptions as $bNo): ?>
                            <option value="<?= htmlspecialchars($bNo) ?>" <?= ($filters['batch_no'] ?? '') === $bNo ? 'selected' : '' ?>><?= htmlspecialchars($bNo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select text-dark font-monospace">
                        <option value="">-- All Carton Status --</option>
                        <option value="packed" <?= ($filters['status'] ?? '') === 'packed' ? 'selected' : '' ?>>Packed / Sealed</option>
                        <option value="dispatched" <?= ($filters['status'] ?? '') === 'dispatched' ? 'selected' : '' ?>>Dispatched</option>
                        <option value="delivered" <?= ($filters['status'] ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="destination_type" class="form-select text-dark font-monospace">
                        <option value="">-- All Destinations --</option>
                        <option value="client" <?= ($filters['destination_type'] ?? '') === 'client' ? 'selected' : '' ?>>Client / Buyer</option>
                        <option value="warehouse" <?= ($filters['destination_type'] ?? '') === 'warehouse' ? 'selected' : '' ?>>Company Warehouse</option>
                        <option value="unassigned" <?= ($filters['destination_type'] ?? '') === 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="<?= base_url('company/packing-qr') ?>" class="btn btn-light border text-dark" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Sealed Cartons Data Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="fw-bold text-dark m-0 font-monospace"><i class="fa-solid fa-boxes-packing text-primary me-2"></i> Sealed Carton Boxes List</h5>
            <span class="badge bg-light text-dark border font-monospace">Showing <?= count($cartons) ?> Carton Boxes</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                        <tr>
                            <th class="ps-4">Carton ID</th>
                            <th>Carton QR</th>
                            <th>Batch & Style</th>
                            <th>Capacity Gauge</th>
                            <th>Assigned / Remaining</th>
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
                                $barClass = ($c['fill_percentage'] >= 100) ? 'bg-success' : (($c['fill_percentage'] > 50) ? 'bg-info' : 'bg-warning');
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
                                    <td style="min-width: 140px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1 font-monospace" style="font-size: 11px;">
                                            <span><?= number_format($c['fill_percentage'], 1) ?>%</span>
                                            <small class="text-muted"><?= number_format($c['assigned_qty']) ?> / <?= number_format($c['max_capacity_pcs']) ?> pcs</small>
                                        </div>
                                        <div class="progress" style="height: 6px; border-radius: 4px;">
                                            <div class="progress-bar <?= $barClass ?>" role="progressbar" style="width: <?= min(100, $c['fill_percentage']) ?>%;"></div>
                                        </div>
                                    </td>
                                    <td class="font-monospace small">
                                        <strong class="text-success"><?= number_format($c['assigned_qty']) ?> pcs assigned</strong><br>
                                        <span class="<?= $c['remaining_capacity'] == 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                            <?= number_format($c['remaining_capacity']) ?> pcs remaining
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
                                        <a href="<?= base_url('company/packing-qr/assign/' . $c['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3 me-1 fw-bold" title="Open Carton Packing Assignment">
                                            <i class="fa-solid fa-box-open me-1"></i> Pack Goods
                                        </a>
                                        <a href="<?= base_url('company/dispatch/cartons/print?carton_id=' . $c['id']) ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-2.5" title="Print Carton QR Label">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-boxes-packing fs-1 mb-2 opacity-50 text-secondary"></i>
                                    <p class="m-0 fw-semibold">No sealed carton boxes found matching your filters.</p>
                                    <small>Pack new cartons from the Finished Goods Dispatch Hub to manage assignments.</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
