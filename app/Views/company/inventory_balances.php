<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Inventory Balances Summary</h3>
        <p class="text-secondary m-0">Live stock balances and pricing values across all company facilities</p>
    </div>
    <div>
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
                                <th>Stock Level Balance</th>
                                <th>Avg Invoice Unit Price</th>
                                <th>Estimated Value</th>
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
                                            <strong class="font-monospace text-primary"><?= number_format($item['current_balance'], 2) ?></strong>
                                        </td>
                                        <td>₹<?= number_format($item['avg_price'], 2) ?></td>
                                        <td class="text-success font-monospace fw-bold">₹<?= number_format($estValue, 2) ?></td>
                                        <td>
                                            <?php if ($item['item_type'] === 'fabric' || $item['item_type'] === 'finished'): ?>
                                                <a href="<?= base_url('company/inventory/barcode?style_no=' . urlencode($item['item_name']) . '&qty=' . $item['current_balance']) ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                                                    <i class="fa-solid fa-barcode me-1"></i> Barcode Ticket
                                                </a>
                                            <?php else: ?>
                                                <span class="text-secondary small">Not Applicable</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center p-5 text-secondary">
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

    <!-- GRN Completed POs Stock Details -->
    <div class="col-12 mt-4">
        <div class="pepp-card">
            <div class="pepp-card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="pepp-card-title m-0 text-success"><i class="fa-solid fa-file-invoice-dollar me-2"></i> GRN Completed Procurement Stock Details</h5>
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold">Procurement Logs</span>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table pepp-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 15%;">PO Number</th>
                                <th style="width: 25%;">Supplier / Vendor</th>
                                <th style="width: 20%;">Paid From Account</th>
                                <th style="width: 15%;">Payment Date</th>
                                <th style="width: 25%;">Items Received (Type & Qty)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($grnCompletedPOs)): ?>
                                <?php foreach ($grnCompletedPOs as $po): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-primary font-monospace"><?= htmlspecialchars($po['po_no']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($po['supplier_name']) ?></td>
                                        <td>
                                            <span class="fw-semibold text-dark"><i class="fa-solid fa-building-columns me-1 text-secondary"></i> <?= htmlspecialchars($po['account_name'] ?: 'N/A') ?></span>
                                        </td>
                                        <td>
                                            <span class="text-secondary small font-monospace"><i class="fa-regular fa-calendar-check me-1"></i> <?= $po['payment_date'] ? date('d M Y', strtotime($po['payment_date'])) : 'N/A' ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <?php if (!empty($po['items'])): ?>
                                                    <?php foreach ($po['items'] as $item): ?>
                                                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1" style="font-size: 13px;">
                                                            <span class="text-dark fw-medium"><?= htmlspecialchars($item['item_name']) ?></span>
                                                            <span class="text-secondary">
                                                                <span class="badge bg-light text-dark text-capitalize me-1" style="font-size: 10px;"><?= htmlspecialchars($item['item_type']) ?></span>
                                                                <strong class="font-monospace text-primary"><?= number_format($item['quantity'], 2) ?></strong>
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-secondary small">No items listed</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center p-5 text-secondary">
                                        <i class="fa-solid fa-receipt fs-1 mb-3 text-light"></i>
                                        <p class="m-0">No GRN Completed Purchase Orders found with valid payments.</p>
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
