<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <a href="<?= base_url('company/production/orders') ?>" class="btn btn-sm btn-light border mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Batches</a>
        <h3 class="fw-bold"><i class="fa-solid fa-qrcode text-success me-2"></i> Batch RFID Barcode Generator</h3>
        <p class="text-secondary m-0">Generate and print serial-numbered identity cards for garment pieces</p>
    </div>
    <div class="d-flex">
        <button type="button" class="btn btn-success px-4 me-2" onclick="window.print();">
            <i class="fa-solid fa-print me-1"></i> Print RFID Cards
        </button>
    </div>
</div>

<!-- Guidelines Header -->
<div class="alert alert-warning border-warning-subtle d-print-none mb-4" style="border-radius: 12px; background-color: #fffbeb;">
    <div class="d-flex">
        <i class="fa-solid fa-circle-info fs-4 text-warning me-3 mt-1 animate-pulse"></i>
        <div>
            <h6 class="fw-bold text-warning-emphasis mb-1">Standard Operating Guidelines for Cutting Department</h6>
            <p class="m-0 text-secondary small">
                1. <strong>Print and Cut:</strong> Click the "Print RFID Cards" button above, print the document, and cut each card cleanly along the dotted cutting marks.<br>
                2. <strong>Hanging Price Tag:</strong> Insert the cut barcode card into a hanging price tag sleeve or clip.<br>
                3. <strong>Clip to Cut Pieces:</strong> Pin/clip each barcode securely to the corresponding fabric cut piece.<br>
                4. <strong>RFID Retention:</strong> Keep this RFID Barcode tag linked with the piece until the final packaging stage to maintain accurate WIP tracing.
            </p>
        </div>
    </div>
</div>

<!-- Config Control Panel -->
<div class="pepp-card mb-4 d-print-none">
    <div class="pepp-card-header bg-light">
        <h6 class="fw-bold m-0 text-dark"><i class="fa-solid fa-sliders me-1"></i> Generate Range Parameters</h6>
    </div>
    <div class="pepp-card-body">
        <form method="GET" action="<?= base_url('company/production/barcode') ?>" class="row g-3 align-items-end">
            <input type="hidden" name="id" value="<?= $batch['id'] ?>">
            <div class="col-md-3">
                <label class="form-label small fw-bold">From Serial Number</label>
                <input type="number" name="start" class="form-control text-dark" value="<?= $start ?>" min="1" max="<?= $batch['target_qty'] ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">To Serial Number</label>
                <input type="number" name="end" class="form-control text-dark" value="<?= $end ?>" min="1" max="<?= $batch['target_qty'] ?>" required>
            </div>
            <div class="col-md-4">
                <span class="text-secondary small">Total Batch Target Qty: <strong><?= number_format($batch['target_qty']) ?></strong> pcs</span>
            </div>
            <div class="col-md-2 text-end">
                <button type="submit" class="btn btn-primary w-100 rounded-pill"><i class="fa-solid fa-arrows-rotate me-1"></i> Generate</button>
            </div>
        </form>
    </div>
</div>

<!-- Card Information Summary -->
<div class="pepp-card mb-4 d-print-none">
    <div class="pepp-card-header">
        <h6 class="fw-bold m-0 text-dark"><i class="fa-solid fa-shirt me-1"></i> Style & Batch Metadata</h6>
    </div>
    <div class="pepp-card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <span class="text-secondary small d-block">STYLE NAME / CODE</span>
                <strong class="text-dark"><?= htmlspecialchars($batch['style_no']) ?> - <?= htmlspecialchars($batch['style_name']) ?></strong>
            </div>
            <div class="col-md-3">
                <span class="text-secondary small d-block">CATEGORY / BRAND</span>
                <strong class="text-dark text-capitalize"><?= htmlspecialchars($batch['style_category']) ?> | <?= htmlspecialchars($batch['style_brand'] ?: 'N/A') ?></strong>
            </div>
            <div class="col-md-3">
                <span class="text-secondary small d-block">FABRIC COMPOSITION</span>
                <strong class="text-dark"><?= htmlspecialchars($batch['fabric_composition'] ?: '100% Premium Cotton') ?></strong>
            </div>
            <div class="col-md-3">
                <span class="text-secondary small d-block">BATCH CODE / REF PO</span>
                <strong class="text-primary font-monospace"><?= htmlspecialchars($batch['production_no']) ?> | <?= htmlspecialchars($batch['buyer_po_no']) ?></strong>
            </div>
        </div>
    </div>
</div>

<?php
// Function to resolve size dynamically based on PO size breakdown
$sizes = json_decode($batch['sizes_json'] ?? '[]', true) ?: [];
function resolveSizeForSerialNum($serial, $sizes) {
    $accumulated = 0;
    foreach ($sizes as $size => $qty) {
        $accumulated += (int)$qty;
        if ($serial <= $accumulated) {
            return $size;
        }
    }
    return 'Free Size';
}
?>

<!-- Printable RFID Barcode Cards Grid -->
<div class="rfid-cards-grid">
    <?php for ($s = $start; $s <= $end; $s++): 
        $size = resolveSizeForSerialNum($s, $sizes);
        $uniqueCode = "{$batch['production_no']}-{$size}-" . sprintf("%04d", $s);
    ?>
        <div class="rfid-card-wrapper">
            <div class="rfid-card-content">
                <!-- Header -->
                <div class="rfid-card-header d-flex justify-content-between align-items-center pb-1 mb-2 border-bottom">
                    <span class="fw-bold" style="font-size: 11px; letter-spacing: 0.5px; color: #000;">TOCCO RFID TAG</span>
                    <span class="font-monospace text-secondary" style="font-size: 9px;">Sl: <?= $s ?> / <?= $batch['target_qty'] ?></span>
                </div>
                
                <!-- Main Style Details -->
                <div class="row g-1 mb-2 text-dark" style="font-size: 10px; line-height: 1.3;">
                    <div class="col-8">
                        <div class="text-truncate">Style: <strong><?= htmlspecialchars($batch['style_name']) ?></strong></div>
                        <div class="text-muted text-capitalize" style="font-size: 9px;"><?= htmlspecialchars($batch['style_category']) ?> | <?= htmlspecialchars($batch['style_brand'] ?: 'N/A') ?></div>
                        <div class="text-muted" style="font-size: 9px;"><?= htmlspecialchars($batch['fabric_composition'] ?: '100% Cotton') ?></div>
                        <div class="mt-1" style="font-size: 9px;">Batch: <span class="font-monospace fw-bold text-primary"><?= htmlspecialchars($batch['production_no']) ?></span></div>
                    </div>
                    <!-- Size Badge design -->
                    <div class="col-4 text-end d-flex flex-column justify-content-center align-items-end">
                        <span class="text-muted" style="font-size: 8px; text-transform: uppercase;">SIZE</span>
                        <div class="size-badge-display mt-0.5"><?= htmlspecialchars($size) ?></div>
                    </div>
                </div>

                <!-- Double Barcodes (Large and Small) -->
                <div class="double-barcode-container">
                    <!-- Primary Large Barcode -->
                    <div class="barcode-block large-barcode mb-2">
                        <div class="barcode-graphic-small"></div>
                        <span class="barcode-num-text font-monospace">*<?= $uniqueCode ?>*</span>
                    </div>

                    <!-- Backup Small Barcode -->
                    <div class="barcode-block small-barcode">
                        <div class="barcode-graphic-xs"></div>
                        <span class="barcode-num-text-xs font-monospace">BACKUP: <?= $uniqueCode ?></span>
                    </div>
                </div>

                <!-- Footer Timestamp -->
                <div class="rfid-card-footer mt-2 pt-1 border-top text-center text-muted" style="font-size: 8px;">
                    Issued: <?= date('d-M-Y H:i') ?> | Keep tag with garment piece
                </div>
            </div>
        </div>
    <?php endfor; ?>
</div>

<!-- Styles for Card & Cutting Layout -->
<style>
.rfid-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 15px;
    background: transparent;
}

.rfid-card-wrapper {
    background: #ffffff;
    border: 1px dashed #94a3b8; /* Cutting mark */
    padding: 12px;
    border-radius: 6px;
    position: relative;
    box-sizing: border-box;
}

/* Pseudo indicators for cutting marks */
.rfid-card-wrapper::before {
    content: '✂';
    position: absolute;
    top: -8px;
    left: 10px;
    font-size: 11px;
    color: #64748b;
    z-index: 10;
}

.rfid-card-content {
    border: 1px solid #e2e8f0;
    padding: 10px;
    border-radius: 4px;
    background: #ffffff;
}

.size-badge-display {
    background: #0f172a;
    color: #ffffff;
    font-weight: 800;
    font-size: 14px;
    padding: 3px 8px;
    border-radius: 4px;
    min-width: 32px;
    text-align: center;
    font-family: monospace;
}

/* Barcode Graphics (pure CSS simulations matching garment labels) */
.double-barcode-container {
    background: #f8fafc;
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
}

.barcode-block {
    text-align: center;
}

.barcode-graphic-small {
    height: 32px;
    background: repeating-linear-gradient(
        90deg,
        #000,
        #000 1.5px,
        #fff 1.5px,
        #fff 3.5px,
        #000 3.5px,
        #000 5px,
        #fff 5px,
        #fff 7px
    );
}

.barcode-graphic-xs {
    height: 16px;
    background: repeating-linear-gradient(
        90deg,
        #000,
        #000 1px,
        #fff 1px,
        #fff 2.5px,
        #000 2.5px,
        #000 3.5px,
        #fff 3.5px,
        #fff 4.5px
    );
}

.barcode-num-text {
    font-size: 9px;
    letter-spacing: 2px;
    color: #0f172a;
    font-weight: bold;
    display: block;
}

.barcode-num-text-xs {
    font-size: 8px;
    color: #475569;
    display: block;
}

/* Print Overrides */
@media print {
    .d-print-none {
        display: none !important;
    }
    
    body {
        background: #ffffff !important;
        color: #000000 !important;
        padding: 0 !important;
    }
    
    .rfid-cards-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0 !important;
    }
    
    .rfid-card-wrapper {
        border: 1px dashed #000000 !important; /* Dotted line on print for cutting */
        page-break-inside: avoid;
        margin: 5px;
    }
}
</style>
