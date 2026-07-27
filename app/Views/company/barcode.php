<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <a href="<?= base_url('company/inventory/balances') ?>" class="btn btn-sm btn-light border mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Inventory</a>
        <h3 class="fw-bold">Barcode Cutting Ticket</h3>
        <p class="text-secondary m-0">Printable ticket for work-in-progress cutting bundle identification</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary px-4" onclick="window.print();">
            <i class="fa-solid fa-print me-1"></i> Print Ticket Label
        </button>
    </div>
</div>

<div class="d-flex justify-content-center align-items-center" style="min-height: 50vh;">
    <!-- Physical Label Mock -->
    <div class="barcode-label">
        <div class="barcode-label-header">
            <div class="fw-bold fs-5"><?= htmlspecialchars($company['name'] ?? 'GARMENT ERP') ?></div>
            <div class="small text-secondary">Tiruppur, India</div>
        </div>
        
        <div class="barcode-label-meta">
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <span class="label-heading">STYLE NO</span>
                    <strong class="label-value"><?= htmlspecialchars($style_no) ?></strong>
                </div>
                <div class="col-6 text-end">
                    <span class="label-heading">SIZE</span>
                    <strong class="label-value font-monospace"><?= htmlspecialchars($size) ?></strong>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <span class="label-heading">BUNDLE ID</span>
                    <strong class="label-value font-monospace"><?= htmlspecialchars($bundle_no) ?></strong>
                </div>
                <div class="col-6 text-end">
                    <span class="label-heading">QTY</span>
                    <strong class="label-value"><?= htmlspecialchars($qty) ?> Pcs</strong>
                </div>
            </div>
        </div>

        <!-- CSS Generated Barcode Graphic -->
        <div class="barcode-graphic-wrapper">
            <div class="barcode-graphic"></div>
        </div>
        
        <div class="barcode-text font-monospace text-center mt-2">
            *<?= htmlspecialchars($barcode_text) ?>*
        </div>

        <div class="barcode-footer mt-3 pt-2 border-top text-center small text-secondary">
            Generated: <?= date('d-M-Y H:i') ?> | WIP Production Ticket
        </div>
    </div>
</div>

<style>
.barcode-label {
    width: 380px;
    background: #ffffff;
    border: 2px dashed #000000;
    padding: 25px;
    border-radius: 4px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.barcode-label-header {
    border-bottom: 2px solid #000000;
    padding-bottom: 10px;
    margin-bottom: 15px;
    text-align: center;
}

.barcode-label-meta {
    margin-bottom: 20px;
}

.label-heading {
    display: block;
    font-size: 10px;
    color: #64748b;
    text-transform: uppercase;
}

.label-value {
    font-size: 16px;
    color: #0f172a;
}

.barcode-graphic-wrapper {
    background: #ffffff;
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    display: flex;
    justify-content: center;
}

.barcode-graphic {
    width: 280px;
    height: 70px;
    background: repeating-linear-gradient(
        90deg,
        #000,
        #000 2px,
        #fff 2px,
        #fff 5px,
        #000 5px,
        #000 8px,
        #fff 8px,
        #fff 10px,
        #000 10px,
        #000 11px,
        #fff 11px,
        #fff 15px,
        #000 15px,
        #000 19px,
        #fff 19px,
        #fff 21px
    );
}

.barcode-text {
    font-size: 12px;
    letter-spacing: 4px;
    font-weight: bold;
    color: #0f172a;
}

@media print {
    .d-print-none {
        display: none !important;
    }
    body {
        background: #ffffff !important;
        padding: 0 !important;
    }
    .barcode-label {
        border: 2px solid #000000 !important;
        box-shadow: none !important;
        margin: 0 auto;
    }
}
</style>
