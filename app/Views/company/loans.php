<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Employee Loans Manager</h3>
        <p class="text-secondary m-0">Issue, track, and manage advances or loans for company operators and staff</p>
    </div>
    <div>
        <a href="<?= base_url('company/hr/attendance') ?>" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Attendance
        </a>
    </div>
</div>

<!-- Employee Loans Card -->
<div class="pepp-card">
    <div class="pepp-card-header d-flex justify-content-between align-items-center">
        <h5 class="pepp-card-title m-0"><i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i> Employee Loans Registry</h5>
        <?php if (\App\Core\Auth::hasPermission('company.users.create')): ?>
            <button class="btn btn-sm btn-pepp-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addLoanModal">
                <i class="fa-solid fa-plus-circle me-1"></i> Issue Employee Loan
            </button>
        <?php endif; ?>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table table-hover pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Loan Month / Year</th>
                        <th>Loan Amount</th>
                        <th>Deduction Status</th>
                        <th>Issued On</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($loans)): ?>
                        <?php foreach ($loans as $loan): ?>
                            <?php 
                                $loanMonthName = date('F', mktime(0, 0, 0, $loan['month'], 10));
                            ?>
                            <tr>
                                <td><strong class="text-dark"><?= htmlspecialchars($loan['employee_name']) ?></strong></td>
                                <td><?= $loanMonthName ?> <?= $loan['year'] ?></td>
                                <td class="font-monospace fw-bold text-dark"><?= get_currency_symbol() ?><?= number_format($loan['amount'], 2) ?></td>
                                <td>
                                    <span class="badge <?= $loan['status'] === 'deducted' ? 'bg-success text-white' : 'bg-warning text-dark' ?>">
                                        <?= htmlspecialchars(ucfirst($loan['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-secondary small"><?= date('d M Y', strtotime($loan['created_at'])) ?></td>
                                <td class="text-end">
                                    <?php if ($loan['status'] === 'pending'): ?>
                                        <form action="<?= base_url('company/hr/loans/delete/' . $loan['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Cancel this loan request?');">
                                            <?= \App\Core\Session::csrfField() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-secondary small italic"><i class="fa-solid fa-lock me-1"></i> Deducted</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-hand-holding-dollar fs-1 mb-2 text-light"></i>
                                <p class="m-0">No active employee loans registered.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Loan Modal -->
<?php if (\App\Core\Auth::hasPermission('company.users.create')): ?>
    <div class="modal fade" id="addLoanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/hr/loans/create') ?>" method="POST" id="addLoanForm">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content text-start" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i> Issue Employee Loan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" id="loan_employee_id" class="form-select text-dark" required>
                                <option value="" data-salary="0">-- Choose Employee --</option>
                                <?php foreach ($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>" data-salary="<?= $e['base_salary'] ?? 0.00 ?>"><?= htmlspecialchars($e['name']) ?> (Basic: <?= get_currency_symbol() ?><?= number_format($e['base_salary'] ?? 0, 2) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Target Month <span class="text-danger">*</span></label>
                                <select name="month" id="loan_month" class="form-select text-dark" required>
                                    <?php for ($m=1; $m<=12; $m++): ?>
                                        <option value="<?= $m ?>" <?= date('n') == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 10)) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Target Year <span class="text-danger">*</span></label>
                                <input type="number" name="year" id="loan_year" class="form-control text-dark" value="<?= date('Y') ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Loan Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" id="loan_amount" class="form-control text-dark" placeholder="Max: Employee Basic Salary" required>
                            <div class="form-text text-secondary" id="loan_limit_help" style="font-size: 12px;">Select an employee to see the loan limit.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-pepp-primary px-4">Issue Loan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Loan limit and date constraints validation
        const loanEmpSelect = document.getElementById('loan_employee_id');
        const loanAmountInput = document.getElementById('loan_amount');
        const loanLimitHelp = document.getElementById('loan_limit_help');
        const loanForm = document.getElementById('addLoanForm');
        const loanMonthSelect = document.getElementById('loan_month');
        const loanYearInput = document.getElementById('loan_year');

        if (loanEmpSelect && loanAmountInput && loanLimitHelp) {
            function updateLoanLimit() {
                const selectedOption = loanEmpSelect.options[loanEmpSelect.selectedIndex];
                const baseSal = parseFloat(selectedOption.getAttribute('data-salary')) || 0.00;
                loanLimitHelp.innerText = `Maximum allowed loan amount for this employee is: ${baseSal.toFixed(2)}`;
                loanAmountInput.max = baseSal;
            }
            loanEmpSelect.addEventListener('change', updateLoanLimit);
        }

        if (loanForm) {
            loanForm.addEventListener('submit', function(e) {
                const selectedOption = loanEmpSelect.options[loanEmpSelect.selectedIndex];
                const baseSal = parseFloat(selectedOption.getAttribute('data-salary')) || 0.00;
                const amt = parseFloat(loanAmountInput.value) || 0.00;

                if (amt > baseSal) {
                    alert(`Loan amount cannot exceed the employee's basic monthly salary of ${baseSal.toFixed(2)}`);
                    e.preventDefault();
                    return false;
                }

                const today = new Date();
                const currentYear = today.getFullYear();
                const currentMonth = today.getMonth() + 1;

                const targetYear = parseInt(loanYearInput.value) || 0;
                const targetMonth = parseInt(loanMonthSelect.value) || 0;

                if (targetYear < currentYear || (targetYear === currentYear && targetMonth < currentMonth)) {
                    alert("Loan can only be issued for the current or upcoming months.");
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
    </script>
<?php endif; ?>
