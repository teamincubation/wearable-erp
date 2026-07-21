<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Inventory Balances Summary</h3>
        <p class="text-secondary m-0">Live stock balances and pricing values across all company facilities</p>
    </div>
    <div>
        <a href="<?= base_url('company/inventory/ledger') ?>" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Ledger
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
</div>
