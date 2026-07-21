<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Database Diagnostics Monitor</h3>
        <p class="text-secondary m-0">Live MySQL database size, index statistics, and system variables</p>
    </div>
</div>

<div class="row g-4">
    <!-- Server specs -->
    <div class="col-md-4">
        <div class="pepp-card h-100">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-server text-primary me-2"></i> MySQL Server variables</h5>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table pepp-table mb-0 small">
                        <thead>
                            <tr>
                                <th>Variable Name</th>
                                <th>System Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($variables as $v): ?>
                                <tr>
                                    <td class="font-monospace text-secondary"><?= htmlspecialchars($v['Variable_name']) ?></td>
                                    <td><strong class="text-dark font-monospace"><?= htmlspecialchars($v['Value']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Storage Engine and Table breakdown -->
    <div class="col-md-8">
        <div class="pepp-card h-100">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-table text-primary me-2"></i> Database Table Stats</h5>
            </div>
            <div class="pepp-card-body p-0">
                <div class="table-responsive border-0">
                    <table class="table pepp-table mb-0">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Engine</th>
                                <th>Row Count</th>
                                <th>Data Size</th>
                                <th>Index Size</th>
                                <th>Collation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $totalRows = 0;
                                $totalData = 0;
                                $totalIdx = 0;
                                foreach ($tables as $t): 
                                    $dataKb = $t['Data_length'] / 1024;
                                    $idxKb = $t['Index_length'] / 1024;
                                    
                                    $totalRows += $t['Rows'];
                                    $totalData += $dataKb;
                                    $totalIdx += $idxKb;
                            ?>
                                <tr>
                                    <td><strong class="text-primary font-monospace"><?= htmlspecialchars($t['Name']) ?></strong></td>
                                    <td><span class="badge bg-light text-secondary"><?= htmlspecialchars($t['Engine']) ?></span></td>
                                    <td class="font-monospace text-dark"><?= number_format($t['Rows']) ?></td>
                                    <td class="font-monospace text-dark"><?= number_format($dataKb, 1) ?> KB</td>
                                    <td class="font-monospace text-dark"><?= number_format($idxKb, 1) ?> KB</td>
                                    <td class="small text-secondary"><?= htmlspecialchars($t['Collation']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-light fw-bold">
                                <td>TOTAL SUMMARY</td>
                                <td>--</td>
                                <td class="font-monospace text-primary"><?= number_format($totalRows) ?></td>
                                <td class="font-monospace text-primary"><?= number_format($totalData, 1) ?> KB</td>
                                <td class="font-monospace text-primary"><?= number_format($totalIdx, 1) ?> KB</td>
                                <td>--</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
