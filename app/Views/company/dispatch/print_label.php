<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Print Carton Labels' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI Variable Text', 'Segoe UI Variable', sans-serif;
            color: #0f172a;
        }
        .carton-label {
            width: 4in;
            height: 6in;
            background: #ffffff;
            border: 2px solid #0f172a;
            border-radius: 8px;
            padding: 16px;
            margin: 20px auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
            page-break-after: always;
        }
        .label-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .qr-box {
            width: 120px;
            height: 120px;
            border: 1px solid #cbd5e1;
            padding: 4px;
            background: #ffffff;
            border-radius: 6px;
        }
        @media print {
            body {
                background: none;
            }
            .no-print {
                display: none !important;
            }
            .carton-label {
                margin: 0;
                box-shadow: none;
                border: 2px solid #000000;
            }
        }
    </style>
</head>
<body>

<div class="no-print p-3 bg-dark text-white d-flex justify-content-between align-items-center mb-3 shadow-sm">
    <div>
        <h5 class="m-0 fw-bold"><i class="fa-solid fa-print me-2 text-primary"></i> Thermal Carton Label Printer</h5>
        <small class="text-white-50">Standard 4x6 inch shipping & inventory carton label format</small>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold px-4 rounded-pill">
            <i class="fa-solid fa-print me-1"></i> Print Labels
        </button>
        <button onclick="window.close()" class="btn btn-outline-light btn-sm fw-bold px-3 rounded-pill ms-2">
            Close Window
        </button>
    </div>
</div>

<?php if (!empty($cartons)): ?>
    <?php foreach ($cartons as $c): 
        $qrData = $c['carton_no'] . '|BATCH:' . ($c['production_no'] ?? '') . '|PCS:' . $c['total_pcs'];
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrData);
    ?>
        <div class="carton-label d-flex flex-column justify-content-between">
            <div>
                <!-- Label Header -->
                <div class="label-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-dark text-white uppercase fw-bold font-monospace mb-1" style="font-size: 10px;">WEARABLE ERP</span>
                        <h4 class="fw-bold font-monospace m-0 text-dark"><?= htmlspecialchars($c['carton_no']) ?></h4>
                    </div>
                    <img src="<?= $qrUrl ?>" alt="Carton QR" class="qr-box">
                </div>

                <!-- Batch & Product Details -->
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <small class="text-secondary text-uppercase fw-bold d-block" style="font-size: 9px;">Batch Order #</small>
                        <strong class="font-monospace text-primary fs-6"><?= htmlspecialchars($c['production_no'] ?: 'N/A') ?></strong>
                    </div>
                    <div class="col-6">
                        <small class="text-secondary text-uppercase fw-bold d-block" style="font-size: 9px;">Buyer PO #</small>
                        <strong class="font-monospace text-dark fs-6"><?= htmlspecialchars($c['buyer_po_no'] ?: 'N/A') ?></strong>
                    </div>
                </div>

                <div class="mb-2 border-bottom pb-2">
                    <small class="text-secondary text-uppercase fw-bold d-block" style="font-size: 9px;">Style Specification</small>
                    <div class="fw-bold text-dark" style="font-size: 13px;"><?= htmlspecialchars($c['style_no'] ?? 'N/A') ?> - <?= htmlspecialchars($c['style_name'] ?? '') ?></div>
                </div>

                <!-- Destination -->
                <div class="mb-2 border-bottom pb-2">
                    <small class="text-secondary text-uppercase fw-bold d-block" style="font-size: 9px;">Destination / Consignee</small>
                    <div class="fw-bold text-success" style="font-size: 12px;">
                        <?= htmlspecialchars($c['client_name'] ?: ($c['warehouse_name'] ?: 'Unassigned Factory Stock')) ?>
                    </div>
                </div>

                <!-- Item Breakdown Table -->
                <div class="mb-2">
                    <small class="text-secondary text-uppercase fw-bold d-block mb-1" style="font-size: 9px;">Contents Summary</small>
                    <table class="table table-sm table-bordered text-center align-middle mb-0" style="font-size: 11px;">
                        <thead class="table-light">
                            <tr>
                                <th>Size</th>
                                <th>Color</th>
                                <th>Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($c['items'])): ?>
                                <?php foreach ($c['items'] as $itm): ?>
                                    <tr>
                                        <td class="fw-bold font-monospace"><?= htmlspecialchars($itm['size']) ?></td>
                                        <td><?= htmlspecialchars($itm['color']) ?></td>
                                        <td class="fw-bold font-monospace"><?= number_format($itm['item_qty']) ?> pcs</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="fw-bold font-monospace"><?= number_format($c['total_pcs']) ?> pcs Total</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer Metrics -->
            <div class="border-top pt-2 mt-2">
                <?php 
                    $hasWeights = ((float)($c['gross_weight_kg'] ?? 0) > 0 || (float)($c['net_weight_kg'] ?? 0) > 0);
                ?>
                <?php if ($hasWeights): ?>
                    <div class="row text-center font-monospace" style="font-size: 11px;">
                        <div class="col-4 border-end">
                            <small class="text-muted d-block" style="font-size: 9px;">TOTAL PCS</small>
                            <strong class="fs-6 text-dark"><?= number_format($c['total_pcs']) ?></strong>
                        </div>
                        <div class="col-4 border-end">
                            <small class="text-muted d-block" style="font-size: 9px;">GROSS WT</small>
                            <strong class="fs-6 text-dark"><?= number_format($c['gross_weight_kg'], 2) ?> kg</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block" style="font-size: 9px;">NET WT</small>
                            <strong class="fs-6 text-dark"><?= number_format($c['net_weight_kg'], 2) ?> kg</strong>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center font-monospace" style="font-size: 11px;">
                        <small class="text-muted d-block" style="font-size: 9px;">TOTAL QUANTITY</small>
                        <strong class="fs-5 text-dark"><?= number_format($c['total_pcs']) ?> PCS</strong>
                    </div>
                <?php endif; ?>
                <div class="text-center text-muted font-monospace mt-2" style="font-size: 9px;">
                    Packed Date: <?= date('d M Y, h:i A', strtotime($c['created_at'])) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
    window.addEventListener('DOMContentLoaded', (event) => {
        // Automatically open print dialog
        setTimeout(() => { window.print(); }, 500);
    });
</script>
</body>
</html>
