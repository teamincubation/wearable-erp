<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Inventory Balances Summary</h3>
        <p class="text-secondary m-0">Live stock balances and pricing values across all company facilities</p>
    </div>
    <div>
        <a href="<?= base_url('company/purchase/orders') ?>" class="btn btn-outline-success rounded-pill px-4 me-2">
            <i class="fa-solid fa-cart-shopping me-1"></i> Procurement
        </a>
        <a href="<?= base_url('company/inventory/ledger') ?>" class="btn btn-primary rounded-pill px-4">
            <i class="fa-solid fa-list me-1"></i> Stock Transaction Ledger
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Summary Table -->
    <div class="col-12">
        <div class="pepp-card">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-calculator text-primary me-2"></i> Current Stock Levels</h5>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table pepp-table mb-0">
                        <thead>
                            <tr>
                                <th>Item Description Name</th>
                                <th>Category / Type</th>
                                <th>Total Stock Count</th>
                                <th>Avg Invoice Unit Price</th>
                                <th>Worth</th>
                                <th>Used Count</th>
                                <th>Pending in Stock</th>
                                <th>Generate Bundle Tickets</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($summary)): ?>
                                <?php foreach ($summary as $item): ?>
                                    <?php $estValue = $item['current_balance'] * $item['avg_price']; ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark"><?= htmlspecialchars($item['item_name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary text-capitalize"><?= htmlspecialchars($item['item_type']) ?></span>
                                        </td>
                                        <td>
                                            <span class="font-monospace text-secondary"><?= number_format($item['total_received'], 2) ?></span>
                                        </td>
                                        <td>₹<?= number_format($item['avg_price'], 2) ?></td>
                                        <td class="text-success font-monospace fw-bold">₹<?= number_format($estValue, 2) ?></td>
                                        <td>
                                            <span class="font-monospace text-danger">-<?= number_format($item['total_used'], 2) ?></span>
                                        </td>
                                        <td>
                                            <strong class="font-monospace text-primary"><?= number_format($item['current_balance'], 2) ?></strong>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-info px-3 rounded-pill me-1" data-bs-toggle="modal" data-bs-target="#viewStockModal-<?= md5($item['item_name']) ?>">
                                                <i class="fa-regular fa-eye me-1"></i> View
                                            </button>
                                            <?php if ($item['item_type'] === 'fabric' || $item['item_type'] === 'finished'): ?>
                                                <a href="<?= base_url('company/inventory/barcode?style_no=' . urlencode($item['item_name']) . '&qty=' . $item['current_balance']) ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                                                    <i class="fa-solid fa-barcode me-1"></i> Ticket
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- View Stock Details Modal -->
                                    <div class="modal fade" id="viewStockModal-<?= md5($item['item_name']) ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content text-start" style="border-radius: 12px;">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Stock Item Details: <?= htmlspecialchars($item['item_name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-dark">
                                                    <div class="d-flex flex-wrap align-items-center gap-3 p-3 bg-light rounded-3 mb-3 border">
                                                        <div>
                                                            <span class="text-secondary small d-block">Category / Type</span>
                                                            <span class="badge bg-primary text-white text-capitalize"><?= htmlspecialchars($item['item_type']) ?></span>
                                                        </div>
                                                        <div class="vr"></div>
                                                        <div>
                                                            <span class="text-secondary small d-block">Total Received</span>
                                                            <strong class="font-monospace text-dark"><?= number_format($item['total_received'], 2) ?></strong>
                                                        </div>
                                                        <div class="vr"></div>
                                                        <div>
                                                            <span class="text-secondary small d-block">Used Count</span>
                                                            <strong class="font-monospace text-danger">-<?= number_format($item['total_used'], 2) ?></strong>
                                                        </div>
                                                        <div class="vr"></div>
                                                        <div>
                                                            <span class="text-secondary small d-block">Pending in Stock</span>
                                                            <strong class="font-monospace text-primary fs-6"><?= number_format($item['current_balance'], 2) ?></strong>
                                                        </div>
                                                        <div class="vr"></div>
                                                        <div>
                                                            <span class="text-secondary small d-block">Avg Unit Rate</span>
                                                            <strong class="text-dark">₹<?= number_format($item['avg_price'], 2) ?></strong>
                                                        </div>
                                                        <div class="vr"></div>
                                                        <div>
                                                            <span class="text-secondary small d-block">Total Worth</span>
                                                            <strong class="text-success font-monospace fs-6">₹<?= number_format($estValue, 2) ?></strong>
                                                        </div>
                                                    </div>

                                                    <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-file-invoice-dollar me-1 text-primary"></i> Procurement Receipts & Supplier PO Breakdown</h6>
                                                    <div class="table-responsive border rounded-3">
                                                        <table class="table pepp-table mb-0 align-middle">
                                                            <thead>
                                                                <tr class="bg-light">
                                                                    <th>PO Number</th>
                                                                    <th>Supplier / Vendor</th>
                                                                    <th>Receiving Warehouse</th>
                                                                    <th>Paid From Account</th>
                                                                    <th>Payment Date</th>
                                                                    <th class="text-end">Qty Received</th>
                                                                    <th class="text-end">Unit Rate</th>
                                                                    <th class="text-end">Total Cost</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (!empty($item['po_receipts'])): ?>
                                                                    <?php foreach ($item['po_receipts'] as $receipt): ?>
                                                                        <tr>
                                                                            <td><strong class="text-primary font-monospace"><?= htmlspecialchars($receipt['po_no']) ?></strong></td>
                                                                            <td><?= htmlspecialchars($receipt['supplier_name']) ?></td>
                                                                            <td><span class="fw-medium text-dark"><i class="fa-solid fa-warehouse me-1 text-secondary"></i> <?= htmlspecialchars($receipt['warehouse_name'] ?: 'Default Warehouse') ?></span></td>
                                                                            <td><span class="text-dark small"><i class="fa-solid fa-building-columns me-1 text-secondary"></i> <?= htmlspecialchars($receipt['account_name'] ?: 'N/A') ?></span></td>
                                                                            <td><span class="text-secondary small font-monospace"><?= $receipt['payment_date'] ? date('d M Y', strtotime($receipt['payment_date'])) : 'N/A' ?></span></td>
                                                                            <td class="text-end font-monospace fw-bold text-dark"><?= number_format($receipt['quantity'], 2) ?></td>
                                                                            <td class="text-end font-monospace">₹<?= number_format($receipt['unit_price'], 2) ?></td>
                                                                            <td class="text-end font-monospace text-success fw-bold">₹<?= number_format($receipt['total_price'], 2) ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <tr>
                                                                        <td colspan="8" class="text-center py-4 text-secondary small">
                                                                            No linked PO procurement logs found for this item.
                                                                        </td>
                                                                    </tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center p-5 text-secondary">
                                        <i class="fa-solid fa-warehouse fs-1 mb-3 text-light"></i>
                                        <p class="m-0">No active stock levels found. Stock levels will update once a GRN is approved.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
