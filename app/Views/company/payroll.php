<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Payroll Processing</h3>
        <p class="text-secondary m-0">Process monthly operator salaries, overtime incentives, and loan deductions</p>
    </div>
    <div class="d-flex">
        <a href="<?= base_url('company/hr/attendance') ?>" class="btn btn-outline-secondary rounded-pill px-4 me-2">
            <i class="fa-solid fa-user-clock me-1"></i> Attendance Log Register
        </a>
        <?php if (\App\Core\Auth::hasPermission('company.users.create')): ?>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#processPayrollModal">
                <i class="fa-solid fa-calculator me-1"></i> Process Salary Slip
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-coins text-primary me-2"></i> Employee Monthly Salary Sheets</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Month / Year</th>
                        <th>Base Salary</th>
                        <th>Overtime Earned</th>
                        <th>Bonus / Incentives</th>
                        <th>Deductions</th>
                        <th>Net Salary Payable</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payroll)): ?>
                        <?php foreach ($payroll as $pr): ?>
                            <?php 
                                $totalDeductions = $pr['loan_deduction'] + $pr['tax_deduction'];
                                $monthName = date('F', mktime(0, 0, 0, $pr['month'], 10));
                            ?>
                            <tr>
                                <td><strong class="text-dark"><?= htmlspecialchars($pr['employee_name']) ?></strong></td>
                                <td><?= $monthName ?> <?= $pr['year'] ?></td>
                                <td>₹<?= number_format($pr['base_salary'], 2) ?></td>
                                <td><span class="text-success font-monospace">+₹<?= number_format($pr['overtime_pay'], 2) ?></span></td>
                                <td>₹<?= number_format($pr['bonus'], 2) ?></td>
                                <td><span class="text-danger">-₹<?= number_format($totalDeductions, 2) ?></span></td>
                                <td><strong class="text-primary font-monospace">₹<?= number_format($pr['net_salary'], 2) ?></strong></td>
                                <td>
                                    <span class="badge badge-pepp badge-success text-capitalize"><?= htmlspecialchars($pr['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-money-bill-wave fs-1 mb-3 text-light"></i>
                                <p class="m-0">No salary payroll slips processed for this cycle.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Process Payroll Modal -->
<?php if (\App\Core\Auth::hasPermission('company.users.create')): ?>
    <div class="modal fade" id="processPayrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/hr/payroll/process') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Process Salary Sheet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select text-dark" required>
                                <option value="">-- Choose Employee --</option>
                                <?php foreach ($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Salary Cycle Month <span class="text-danger">*</span></label>
                                <select name="month" class="form-select text-dark" required>
                                    <?php for ($m=1; $m<=12; $m++): ?>
                                        <option value="<?= $m ?>" <?= date('n') == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 10)) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Salary Cycle Year <span class="text-danger">*</span></label>
                                <input type="number" name="year" class="form-control text-dark" value="<?= date('Y') ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Base Monthly Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="base_salary" class="form-control" placeholder="e.g. 20000.00" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-bold">Incentive / Bonus (₹)</label>
                                <input type="number" step="0.01" name="bonus" class="form-control" value="0.00">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold">Loan Deductions (₹)</label>
                                <input type="number" step="0.01" name="loan_deduction" class="form-control" value="0.00">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold">TDS / Professional Tax (₹)</label>
                                <input type="number" step="0.01" name="tax_deduction" class="form-control" value="0.00">
                            </div>
                        </div>
                        <div class="form-text text-primary"><i class="fa-solid fa-circle-info"></i> The system will automatically fetch overtime attendance hours from shift logs to compute bonus overtime pay at 1.5x hourly rate.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Process Salary Sheet</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
