<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold m-0"><i class="fa-solid fa-truck-ramp-box text-primary me-2"></i> Finished Goods Dispatch & Packing Hub</h3>
        <p class="text-secondary m-0 mt-1">Central management for production-completed goods, carton packaging, barcode/QR labeling, and shipments.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if (\App\Core\Auth::hasPermission('company.dispatch.manage')): ?>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#packCartonModal">
                <i class="fa-solid fa-box-archive me-1"></i> Pack New Carton
            </button>
            <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#createShipmentModal">
                <i class="fa-solid fa-truck-fast me-1"></i> Create Shipment
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-uppercase small text-secondary fw-bold d-block mb-1" style="font-size: 11px;">Total Finished Output</span>
                    <h3 class="fw-bold font-monospace text-dark m-0"><?= number_format($totalFinishedUnits) ?> <span class="fs-6 text-muted fw-normal">pcs</span></h3>
                </div>
                <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-4">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-uppercase small text-secondary fw-bold d-block mb-1" style="font-size: 11px;">Packed in Cartons</span>
                    <h3 class="fw-bold font-monospace text-info m-0"><?= number_format($totalPackedUnits) ?> <span class="fs-6 text-muted fw-normal">pcs</span></h3>
                </div>
                <div class="bg-info-subtle text-info p-3 rounded-circle fs-4">
                    <i class="fa-solid fa-box-open"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-uppercase small text-secondary fw-bold d-block mb-1" style="font-size: 11px;">Sealed Cartons</span>
                    <h3 class="fw-bold font-monospace text-warning m-0"><?= number_format($totalCartonsCount) ?> <span class="fs-6 text-muted fw-normal">(<?= $packedCartonsCount ?> Ready)</span></h3>
                </div>
                <div class="bg-warning-subtle text-warning p-3 rounded-circle fs-4">
                    <i class="fa-solid fa-tape"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-uppercase small text-secondary fw-bold d-block mb-1" style="font-size: 11px;">Dispatched Consignments</span>
                    <h3 class="fw-bold font-monospace text-success m-0"><?= number_format($dispatchedCartonsCount) ?> <span class="fs-6 text-muted fw-normal">cartons</span></h3>
                </div>
                <div class="bg-success-subtle text-success p-3 rounded-circle fs-4">
                    <i class="fa-solid fa-truck-dispatch"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('company/dispatch') ?>" class="row g-2 align-items-center">
            <div class="col-md-3 col-12">
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 text-dark" value="<?= htmlspecialchars($search) ?>" placeholder="Search Batch, Style, Carton ID, PO...">
                </div>
            </div>
            <div class="col-md-2 col-6">
                <select name="filter_packing" class="form-select text-dark" onchange="this.form.submit()">
                    <option value="">-- All Packing Status --</option>
                    <option value="unpacked" <?= $filterPacking === 'unpacked' ? 'selected' : '' ?>>Unpacked</option>
                    <option value="partially_packed" <?= $filterPacking === 'partially_packed' ? 'selected' : '' ?>>Partially Packed</option>
                    <option value="fully_packed" <?= $filterPacking === 'fully_packed' ? 'selected' : '' ?>>Fully Packed</option>
                </select>
            </div>
            <div class="col-md-2 col-6">
                <select name="filter_carton" class="form-select text-dark" onchange="this.form.submit()">
                    <option value="">-- All Carton Status --</option>
                    <option value="packed" <?= $filterCarton === 'packed' ? 'selected' : '' ?>>Packed / Sealed</option>
                    <option value="dispatched" <?= $filterCarton === 'dispatched' ? 'selected' : '' ?>>Dispatched</option>
                    <option value="delivered" <?= $filterCarton === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                </select>
            </div>
            <div class="col-md-3 col-6">
                <select name="filter_buyer" class="form-select text-dark" onchange="this.form.submit()">
                    <option value="">-- All Buyers / Clients --</option>
                    <?php foreach ($buyers as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ((int)$filterBuyer === (int)$b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-6 text-end">
                <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> Apply</button>
            </div>
        </form>
    </div>
</div>

<!-- Main Navigation Tabs -->
<ul class="nav nav-pills custom-pills mb-3" id="dispatchTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold px-4 py-2.5 rounded-pill" id="batches-tab" data-bs-toggle="tab" data-bs-target="#batches-pane" type="button" role="tab">
            <i class="fa-solid fa-boxes-stacked me-1.5"></i> Production Finished Batches (<?= count($batches) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-4 py-2.5 rounded-pill" id="cartons-tab" data-bs-toggle="tab" data-bs-target="#cartons-pane" type="button" role="tab">
            <i class="fa-solid fa-box-archive me-1.5"></i> Sealed Cartons Hub (<?= count($cartons) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="dispatchTabsContent">

    <!-- TAB 1: PRODUCTION BATCHES & FINISHED GOODS -->
    <div class="tab-pane fade show active" id="batches-pane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase fw-bold">
                        <tr>
                            <th>Batch No</th>
                            <th>Style & Category</th>
                            <th>Buyer & PO</th>
                            <th class="text-center">Target Qty</th>
                            <th class="text-center">Finished Output</th>
                            <th class="text-center">Packed Qty</th>
                            <th class="text-center">Unpacked Balance</th>
                            <th class="text-center">Packing Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($batches)): ?>
                            <?php foreach ($batches as $b): 
                                $finishedQty = (int)$b['finished_output_qty'];
                                $packedQty = (int)$b['packed_in_cartons_qty'];
                                $targetQty = (int)$b['target_qty'];
                                $unpackedBal = max(0, $finishedQty - $packedQty);

                                if ($packedQty == 0) {
                                    $pStatusBadge = '<span class="badge bg-secondary">Unpacked</span>';
                                } elseif ($unpackedBal <= 0) {
                                    $pStatusBadge = '<span class="badge bg-success">Fully Packed</span>';
                                } else {
                                    $pStatusBadge = '<span class="badge bg-warning text-dark">Partially Packed</span>';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <strong class="font-monospace text-primary fs-6"><?= htmlspecialchars($b['production_no']) ?></strong>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($b['style_no']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($b['style_name']) ?> (<?= htmlspecialchars($b['style_category']) ?>)</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($b['buyer_name'] ?: 'N/A') ?></div>
                                        <small class="text-muted font-monospace">PO: <?= htmlspecialchars($b['buyer_po_no']) ?></small>
                                    </td>
                                    <td class="text-center font-monospace fw-bold"><?= number_format($targetQty) ?></td>
                                    <td class="text-center font-monospace text-primary fw-bold"><?= number_format($finishedQty) ?></td>
                                    <td class="text-center font-monospace text-success fw-bold"><?= number_format($packedQty) ?></td>
                                    <td class="text-center font-monospace text-danger fw-bold"><?= number_format($unpackedBal) ?></td>
                                    <td class="text-center"><?= $pStatusBadge ?></td>
                                    <td class="text-end">
                                        <?php if (\App\Core\Auth::hasPermission('company.dispatch.manage')): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="openPackModal(<?= $b['production_order_id'] ?>, '<?= htmlspecialchars($b['production_no']) ?>', <?= $unpackedBal ?>)">
                                                <i class="fa-solid fa-box-open me-1"></i> Pack Carton
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">View Only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-boxes-stacked fs-1 mb-2 opacity-50"></i>
                                    <p class="m-0">No finished goods production batches found matching current filters.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: SEALED CARTONS HUB -->
    <div class="tab-pane fade" id="cartons-pane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase fw-bold">
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAllCartons" class="form-check-input" onclick="toggleSelectAllCartons(this)"></th>
                            <th>Carton ID</th>
                            <th>Batch & Style</th>
                            <th>Items & Qty</th>
                            <th>Gross/Net Weight</th>
                            <th>Volume (CBM)</th>
                            <th>Status</th>
                            <th>Destination</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cartons)): ?>
                            <?php foreach ($cartons as $c): 
                                $statusBadge = match($c['status']) {
                                    'packed' => '<span class="badge bg-primary">Packed / Sealed</span>',
                                    'dispatched' => '<span class="badge bg-warning text-dark"><i class="fa-solid fa-truck-fast me-1"></i> Dispatched</span>',
                                    'delivered' => '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Item Moved</span>',
                                    default => '<span class="badge bg-secondary">Draft</span>'
                                };
                                $destLabel = ($c['destination_type'] === 'client') ? ($c['client_name'] ?: 'Client Direct') : (($c['destination_type'] === 'warehouse') ? ($c['warehouse_name'] ?: 'Company Warehouse') : 'Unassigned');
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($c['status'] === 'packed'): ?>
                                            <input type="checkbox" name="selected_carton_ids[]" value="<?= $c['id'] ?>" class="form-check-input carton-select-checkbox">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="font-monospace text-dark fs-6"><?= htmlspecialchars($c['carton_no']) ?></strong>
                                        <small class="d-block text-muted" style="font-size: 11px;">By: <?= htmlspecialchars($c['created_by_name'] ?: 'Admin') ?></small>
                                    </td>
                                    <td>
                                        <strong class="font-monospace text-primary"><?= htmlspecialchars($c['production_no'] ?: 'N/A') ?></strong>
                                        <small class="d-block text-muted"><?= htmlspecialchars($c['style_no'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace fs-6 px-2.5 py-1"><?= number_format($c['total_items_qty']) ?> pcs</span>
                                    </td>
                                    <td class="font-monospace small">
                                        <?php if ((float)$c['gross_weight_kg'] > 0 || (float)$c['net_weight_kg'] > 0): ?>
                                            G: <?= number_format($c['gross_weight_kg'], 2) ?> kg<br>
                                            N: <?= number_format($c['net_weight_kg'], 2) ?> kg
                                        <?php else: ?>
                                            <span class="text-muted italic">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-monospace small"><?= (float)$c['volume_cbm'] > 0 ? number_format($c['volume_cbm'], 3) . ' m³' : '<span class="text-muted">N/A</span>' ?></td>
                                    <td>
                                        <?= $statusBadge ?>
                                        <?php if (!empty($c['tracking_no'])): ?>
                                            <small class="d-block font-monospace text-primary mt-1" style="font-size: 10px;">Track: <?= htmlspecialchars($c['tracking_no']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border"><?= htmlspecialchars($destLabel) ?></span>
                                        <?php if (!empty($c['shipment_no'])): ?>
                                            <small class="d-block font-monospace text-primary mt-1">Shp: <?= htmlspecialchars($c['shipment_no']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if (\App\Core\Auth::hasPermission('company.dispatch.manage')): ?>
                                            <?php if ($c['status'] === 'packed'): ?>
                                                <?php if (empty($c['shipment_id'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-2.5 me-1 fw-bold" 
                                                            onclick="openShipmentModalForCarton(<?= $c['id'] ?>, '<?= htmlspecialchars($c['carton_no']) ?>', '<?= htmlspecialchars($c['destination_type']) ?>', <?= (int)($c['client_id'] ?? 0) ?>, <?= (int)($c['warehouse_id'] ?? 0) ?>)"
                                                            title="Create Shipment Consignment first to dispatch">
                                                        <i class="fa-solid fa-truck-fast me-1"></i> Dispatch Status
                                                    </button>
                                                <?php else: ?>
                                                    <form action="<?= base_url('company/dispatch/cartons/' . $c['id'] . '/status') ?>" method="POST" class="d-inline" onsubmit="return confirm('Confirm dispatch for carton <?= htmlspecialchars($c['carton_no']) ?>?\nTracking ID: <?= htmlspecialchars($c['tracking_no'] ?: $c['carton_no']) ?>');">
                                                        <?= \App\Core\Session::csrfField() ?>
                                                        <input type="hidden" name="status" value="dispatched">
                                                        <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-2.5 me-1 fw-bold" title="Click to mark as Dispatched">
                                                            <i class="fa-solid fa-truck-fast me-1"></i> Dispatch Status
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php elseif ($c['status'] === 'dispatched'): ?>
                                                <form action="<?= base_url('company/dispatch/cartons/' . $c['id'] . '/status') ?>" method="POST" class="d-inline" onsubmit="return confirm('Confirm item moved / delivered for carton <?= htmlspecialchars($c['carton_no']) ?>?');">
                                                    <?= \App\Core\Session::csrfField() ?>
                                                    <input type="hidden" name="status" value="delivered">
                                                    <button type="submit" class="btn btn-sm btn-info text-white rounded-pill px-2.5 me-1 fw-bold" title="Tracking ID: <?= htmlspecialchars($c['tracking_no'] ?: $c['carton_no']) ?> (Click to mark Item Moved / Delivered)">
                                                        <i class="fa-solid fa-location-dot me-1"></i> Item Moved
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border px-2.5 py-1.5 me-1" title="Item Delivered at destination">
                                                    <i class="fa-solid fa-circle-check me-1"></i> Delivered (Moved)
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <a href="<?= base_url('company/dispatch/cartons/print?carton_id=' . $c['id']) ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-2.5 me-1" title="Print QR Label">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        <?php if ($c['status'] === 'packed' && \App\Core\Auth::hasPermission('company.dispatch.manage')): ?>
                                            <form action="<?= base_url('company/dispatch/cartons/' . $c['id'] . '/reopen') ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to unpack and reopen this carton?');">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5" title="Reopen / Unpack Carton">
                                                    <i class="fa-solid fa-box-open"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-box-archive fs-1 mb-2 opacity-50"></i>
                                    <p class="m-0">No packed cartons found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Pack New Carton -->
<div class="modal fade" id="packCartonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= base_url('company/dispatch/cartons/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <input type="hidden" name="production_order_id" id="pack_order_id">
            <div class="modal-content text-dark" style="border-radius: 14px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-box-archive text-primary me-2"></i> Pack Goods into Carton</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Production Batch <span class="text-danger">*</span></label>
                        <select id="pack_batch_select" class="form-select text-dark" onchange="updatePackBatchInfo(this)" required>
                            <option value="">-- Choose Production Batch --</option>
                            <?php foreach ($allBatches as $b): ?>
                                <option value="<?= $b['production_order_id'] ?>" data-no="<?= htmlspecialchars($b['production_no']) ?>" data-unpacked="<?= max(0, $b['finished_output_qty'] - $b['packed_in_cartons_qty']) ?>" data-buyer-id="<?= $b['buyer_id'] ?? '' ?>">
                                    Batch: <?= htmlspecialchars($b['production_no']) ?> (Style: <?= htmlspecialchars($b['style_no']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="pack_fully_packed_alert" class="alert alert-warning py-2 px-3 mb-3 rounded-3 small font-monospace d-none">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> This production batch is already fully packed (0 pcs unpacked balance remaining).
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Total Quantity to Pack (pcs) <span class="text-danger">*</span></label>
                            <input type="number" name="total_qty" id="pack_total_qty" class="form-control font-monospace" min="1" max="1" value="1" required oninput="validatePackQty(this)">
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Available Unpacked Balance: <strong id="pack_unpacked_count" class="text-primary font-monospace">0</strong> pcs
                            </small>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Destination Type</label>
                            <select name="destination_type" id="pack_dest_type" class="form-select text-dark" onchange="togglePackDestFields(this)">
                                <option value="unassigned">Unassigned (In-Factory)</option>
                                <option value="client">Client / Buyer</option>
                                <option value="warehouse">Company Warehouse</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Sub-dropdown for Client / Buyer -->
                    <div class="mb-3 d-none" id="pack_client_wrapper">
                        <label class="form-label small fw-bold">Select Client / Buyer <span class="text-danger">*</span></label>
                        <select name="client_id" id="pack_client_id" class="form-select text-dark">
                            <option value="">-- Choose Client / Buyer --</option>
                            <?php foreach ($buyers as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dynamic Sub-dropdown for Company Warehouse -->
                    <div class="mb-3 d-none" id="pack_warehouse_wrapper">
                        <label class="form-label small fw-bold">Select Company Warehouse <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="pack_warehouse_id" class="form-select text-dark">
                            <option value="">-- Choose Warehouse --</option>
                            <?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars($w['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Weight & Volume Toggle -->
                    <div class="form-check form-switch mb-3 p-2.5 bg-light rounded-3 border d-flex align-items-center">
                        <input class="form-check-input ms-0 me-2 fs-5" type="checkbox" role="switch" id="toggle_weight_details" name="include_weight_details" value="1" onchange="toggleWeightFields(this)">
                        <label class="form-check-label small fw-bold text-dark m-0 cursor-pointer" for="toggle_weight_details">
                            <i class="fa-solid fa-weight-hanging me-1 text-primary"></i> Add Weight & Volume Details (Optional)
                        </label>
                    </div>

                    <div class="row g-2 mb-3 d-none" id="weight_fields_wrapper">
                        <div class="col-4">
                            <label class="form-label small fw-bold">Gross Wt (kg)</label>
                            <input type="number" step="0.01" name="gross_weight_kg" class="form-control font-monospace" value="0.00" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold">Net Wt (kg)</label>
                            <input type="number" step="0.01" name="net_weight_kg" class="form-control font-monospace" value="0.00" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold">Volume (CBM)</label>
                            <input type="number" step="0.001" name="volume_cbm" class="form-control font-monospace" value="0.000" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Carton Packing Notes / Remarks</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Export 7-ply heavy carton sealed with tape">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="pack_submit_btn" class="btn btn-primary px-4 fw-bold"><i class="fa-solid fa-tape me-1"></i> Seal & Generate QR</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Create Shipment Consignment -->
<div class="modal fade" id="createShipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="<?= base_url('company/dispatch/shipments/create') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            <div class="modal-content text-dark" style="border-radius: 14px;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-truck-fast text-success me-2"></i> Create Shipment Consignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div id="shipment_notice_alert" class="alert alert-info py-2 px-3 mb-3 rounded-3 small font-monospace d-none"></div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Shipment Destination Type <span class="text-danger">*</span></label>
                            <select name="destination_type" id="shipment_dest_type" class="form-select text-dark" onchange="toggleShipmentDestFields(this)" required>
                                <option value="client">Client / Buyer</option>
                                <option value="warehouse">Company Warehouse</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="client_dest_wrapper">
                            <label class="form-label small fw-bold">Select Client / Buyer</label>
                            <select name="client_id" class="form-select text-dark">
                                <option value="">-- Choose Client --</option>
                                <?php foreach ($buyers as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-none" id="warehouse_dest_wrapper">
                            <label class="form-label small fw-bold">Select Destination Warehouse</label>
                            <select name="warehouse_id" class="form-select text-dark">
                                <option value="">-- Choose Warehouse --</option>
                                <?php foreach ($warehouses as $w): ?>
                                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars($w['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle / Courier Details</label>
                            <input type="text" name="vehicle_courier_details" class="form-control" placeholder="e.g. DHL Express / Truck No KA-01-AB-1234">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tracking Number / AWB</label>
                            <input type="text" name="tracking_no" class="form-control font-monospace" placeholder="e.g. AWB-9988776655">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Dispatch Date</label>
                            <input type="date" name="dispatch_date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Expected Delivery Date</label>
                            <input type="date" name="expected_delivery_date" class="form-control text-dark" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Dispatch Note / Consignment Remarks</label>
                        <input type="text" name="dispatch_note" class="form-control" placeholder="e.g. Consignment dispatched under invoice #INV-2026-001">
                    </div>

                    <div class="border-top pt-3">
                        <label class="form-label small fw-bold text-primary">Select Cartons to Include in Shipment <span class="text-danger">*</span></label>
                        <div class="table-responsive border rounded-3" style="max-height: 200px;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light small">
                                    <tr>
                                        <th width="30">#</th>
                                        <th>Carton ID</th>
                                        <th>Batch No</th>
                                        <th>Items Qty</th>
                                        <th>Gross Wt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $readyCartons = array_filter($allCartons, fn($c) => $c['status'] === 'packed');
                                        if (!empty($readyCartons)):
                                            foreach ($readyCartons as $rc):
                                    ?>
                                        <tr>
                                            <td><input type="checkbox" name="carton_ids[]" value="<?= $rc['id'] ?>" class="form-check-input"></td>
                                            <td><strong class="font-monospace text-dark"><?= htmlspecialchars($rc['carton_no']) ?></strong></td>
                                            <td><span class="font-monospace text-primary"><?= htmlspecialchars($rc['production_no'] ?: 'N/A') ?></span></td>
                                            <td><span class="badge bg-light text-dark border font-monospace"><?= number_format($rc['total_items_qty']) ?> pcs</span></td>
                                            <td class="font-monospace small"><?= number_format($rc['gross_weight_kg'], 2) ?> kg</td>
                                        </tr>
                                    <?php endforeach; else: ?>
                                        <tr><td colspan="5" class="text-center py-3 text-muted">No sealed cartons currently ready for dispatch. Pack cartons first.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="fa-solid fa-truck-dispatch me-1"></i> Dispatch Shipment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function validatePackQty(inputEl) {
        const maxVal = parseInt(inputEl.max);
        const val = parseInt(inputEl.value) || 0;
        if (!isNaN(maxVal) && maxVal > 0 && val > maxVal) {
            inputEl.value = maxVal;
        } else if (val < 1 && maxVal > 0) {
            inputEl.value = 1;
        }
    }

    function openPackModal(orderId, prodNo, unpackedQty) {
        document.getElementById('pack_order_id').value = orderId;
        const selectEl = document.getElementById('pack_batch_select');
        if (selectEl) {
            selectEl.value = orderId;
            updatePackBatchInfo(selectEl);
        }
        
        const modal = new bootstrap.Modal(document.getElementById('packCartonModal'));
        modal.show();
    }

    function updatePackBatchInfo(selectEl) {
        const selectedOpt = selectEl.options[selectEl.selectedIndex];
        const qtyEl = document.getElementById('pack_total_qty');
        const countEl = document.getElementById('pack_unpacked_count');
        const alertEl = document.getElementById('pack_fully_packed_alert');
        const submitBtn = document.getElementById('pack_submit_btn');

        if (selectedOpt && selectedOpt.value) {
            document.getElementById('pack_order_id').value = selectedOpt.value;
            const unpackedRaw = selectedOpt.getAttribute('data-unpacked');
            const unpackedQty = unpackedRaw !== null ? Math.max(0, parseInt(unpackedRaw)) : 0;

            if (countEl) countEl.textContent = unpackedQty;

            if (qtyEl) {
                if (unpackedQty > 0) {
                    qtyEl.max = unpackedQty;
                    qtyEl.value = Math.min(qtyEl.value || unpackedQty, unpackedQty);
                    if (qtyEl.value < 1) qtyEl.value = 1;
                    qtyEl.disabled = false;
                    if (submitBtn) submitBtn.disabled = false;
                    if (alertEl) alertEl.classList.add('d-none');
                } else {
                    qtyEl.max = 0;
                    qtyEl.value = 0;
                    qtyEl.disabled = true;
                    if (submitBtn) submitBtn.disabled = true;
                    if (alertEl) alertEl.classList.remove('d-none');
                }
            }

            // Auto-select client/buyer if mapped to this batch
            const buyerId = selectedOpt.getAttribute('data-buyer-id');
            if (buyerId) {
                const destTypeEl = document.getElementById('pack_dest_type');
                const clientSelectEl = document.getElementById('pack_client_id');
                if (destTypeEl && clientSelectEl) {
                    destTypeEl.value = 'client';
                    clientSelectEl.value = buyerId;
                    togglePackDestFields(destTypeEl);
                }
            }
        } else {
            if (qtyEl) {
                qtyEl.max = 1;
                qtyEl.value = 1;
                qtyEl.disabled = false;
            }
            if (submitBtn) submitBtn.disabled = false;
            if (alertEl) alertEl.classList.add('d-none');
            if (countEl) countEl.textContent = '0';
        }
    }

    function togglePackDestFields(selectEl) {
        const clientWrap = document.getElementById('pack_client_wrapper');
        const whWrap = document.getElementById('pack_warehouse_wrapper');
        if (!clientWrap || !whWrap) return;

        if (selectEl.value === 'client') {
            whWrap.classList.add('d-none');
            clientWrap.classList.remove('d-none');
        } else if (selectEl.value === 'warehouse') {
            clientWrap.classList.add('d-none');
            whWrap.classList.remove('d-none');
        } else {
            clientWrap.classList.add('d-none');
            whWrap.classList.add('d-none');
        }
    }

    function toggleWeightFields(toggleEl) {
        const wrapper = document.getElementById('weight_fields_wrapper');
        if (!wrapper) return;
        if (toggleEl.checked) {
            wrapper.classList.remove('d-none');
        } else {
            wrapper.classList.add('d-none');
        }
    }

    function toggleShipmentDestFields(selectEl) {
        const clientWrap = document.getElementById('client_dest_wrapper');
        const whWrap = document.getElementById('warehouse_dest_wrapper');
        if (selectEl.value === 'warehouse') {
            clientWrap.classList.add('d-none');
            whWrap.classList.remove('d-none');
        } else {
            whWrap.classList.add('d-none');
            clientWrap.classList.remove('d-none');
        }
    }

    function openShipmentModalForCarton(cartonId, cartonNo, destType, clientId, warehouseId) {
        const modalEl = document.getElementById('createShipmentModal');
        if (!modalEl) return;

        const noticeEl = document.getElementById('shipment_notice_alert');
        if (noticeEl) {
            noticeEl.innerHTML = `<i class="fa-solid fa-circle-info me-1"></i> <strong>Shipment Consignment Required:</strong> Please complete consignment details below to dispatch carton <strong>${cartonNo}</strong>.`;
            noticeEl.classList.remove('d-none');
        }

        const destSelect = document.getElementById('shipment_dest_type');
        if (destSelect) {
            if (destType === 'warehouse') {
                destSelect.value = 'warehouse';
                toggleShipmentDestFields(destSelect);
                if (warehouseId > 0) {
                    const whSelect = modalEl.querySelector('select[name="warehouse_id"]');
                    if (whSelect) whSelect.value = warehouseId;
                }
            } else {
                destSelect.value = 'client';
                toggleShipmentDestFields(destSelect);
                if (clientId > 0) {
                    const clientSelect = modalEl.querySelector('select[name="client_id"]');
                    if (clientSelect) clientSelect.value = clientId;
                }
            }
        }

        // Check checkbox for this carton ID in the shipment items checklist
        const checkboxes = modalEl.querySelectorAll('input[name="carton_ids[]"]');
        checkboxes.forEach(cb => {
            if (parseInt(cb.value) === parseInt(cartonId)) {
                cb.checked = true;
                const tr = cb.closest('tr');
                if (tr) tr.classList.add('table-warning');
            }
        });

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function toggleSelectAllCartons(masterCb) {
        const checkboxes = document.querySelectorAll('.carton-select-checkbox');
        checkboxes.forEach(cb => cb.checked = masterCb.checked);
    }
</script>
