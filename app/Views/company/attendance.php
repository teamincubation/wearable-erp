<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Attendance Register</h3>
        <p class="text-secondary m-0">Log employee daily attendance, shift context, and overtime hours</p>
    </div>
    <div class="d-flex">
        <a href="<?= base_url('company/hr/payroll') ?>" class="btn btn-outline-secondary rounded-pill px-4 me-2">
            <i class="fa-solid fa-money-bill-wave me-1"></i> Salary Payroll Sheets
        </a>
        <?php if (\App\Core\Auth::hasPermission('company.users.create')): ?>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
                <i class="fa-solid fa-user-check me-1"></i> Log Daily Clock In
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-user-clock text-primary me-2"></i> Shift Logs Register</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Employee / Operator</th>
                        <th>Shift Date</th>
                        <th>Clock In / Out</th>
                        <th>Overtime Hours</th>
                        <th>Duty Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($attendance)): ?>
                        <?php foreach ($attendance as $att): ?>
                            <tr>
                                <td><strong class="text-dark"><?= htmlspecialchars($att['employee_name']) ?></strong></td>
                                <td><?= date('d M Y', strtotime($att['date'])) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark font-monospace">
                                        <?= htmlspecialchars($att['clock_in'] ?: '--:--') ?>
                                    </span>
                                    <i class="fa-solid fa-arrow-right mx-1 small text-secondary"></i>
                                    <span class="badge bg-light text-dark font-monospace">
                                        <?= htmlspecialchars($att['clock_out'] ?: '--:--') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($att['overtime_hours'] > 0): ?>
                                        <span class="badge bg-warning text-dark font-monospace">+<?= number_format($att['overtime_hours'], 2) ?> hrs</span>
                                    <?php else: ?>
                                        <span class="text-secondary small">--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-pepp 
                                        <?php 
                                            if ($att['status'] === 'present') echo 'badge-success';
                                            elseif ($att['status'] === 'half_day') echo 'badge-info';
                                            elseif ($att['status'] === 'absent') echo 'badge-danger';
                                            elseif ($att['status'] === 'leave') echo 'badge-warning';
                                            else echo 'badge-secondary';
                                        ?>">
                                        <?= htmlspecialchars(str_replace('_', ' ', ucfirst($att['status']))) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form action="<?= base_url('company/hr/attendance/delete/' . $att['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this attendance log?');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-user-clock fs-1 mb-3 text-light"></i>
                                <p class="m-0">No attendance records logged for this month.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Employee Loans Card -->
<div class="pepp-card mt-4">
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
                            <td colspan="6" class="text-center p-4 text-secondary">
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
<?php endif; ?>

<!-- Add Attendance Modal -->
<?php if (\App\Core\Auth::hasPermission('company.users.create')): ?>
    <div class="modal fade" id="addAttendanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/hr/attendance/clock') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Log Employee Attendance</h5>
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
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Duty Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select text-dark" required>
                                <option value="present">Present (Standard hours)</option>
                                <option value="half_day">Half Day</option>
                                <option value="absent">Absent</option>
                                <option value="leave">On Approved Leave</option>
                                <option value="holiday">Official Holiday</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Shift Schedule Mapping</label>
                            <select name="shift_id" class="form-select text-dark">
                                <option value="">-- Standard General (None) --</option>
                                <?php foreach ($shifts as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-start="<?= date('H:i', strtotime($s['start_time'])) ?>" data-end="<?= date('H:i', strtotime($s['end_time'])) ?>"><?= htmlspecialchars($s['name']) ?> (<?= date('h:i A', strtotime($s['start_time'])) ?> - <?= date('h:i A', strtotime($s['end_time'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Clock In Time</label>
                                <input type="time" name="clock_in" class="form-control text-dark" value="08:00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Clock Out Time</label>
                                <input type="time" name="clock_out" class="form-control text-dark" value="16:00">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Save Attendance</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const shiftSelect = document.querySelector('select[name="shift_id"]');
        const clockInInput = document.querySelector('input[name="clock_in"]');
        const clockOutInput = document.querySelector('input[name="clock_out"]');
        const dateInput = document.querySelector('input[name="date"]');
        const gwh = <?= (int)($gwh ?? 8) ?>;
        const blockedDates = <?= json_encode($blocked_dates ?? []) ?>;

        if (shiftSelect && clockInInput && clockOutInput) {
            function updateClockTimes() {
                const selectedOption = shiftSelect.options[shiftSelect.selectedIndex];
                if (shiftSelect.value === "") {
                    // Standard General (None)
                    clockInInput.value = "08:00";
                    
                    // Calculate end time as 8:00 AM + GWH hours
                    let inHours = 8;
                    let outHours = (inHours + gwh) % 24;
                    let outHoursStr = String(outHours).padStart(2, '0');
                    clockOutInput.value = `${outHoursStr}:00`;
                } else {
                    // Custom shift schedule
                    const start = selectedOption.getAttribute('data-start');
                    const end = selectedOption.getAttribute('data-end');
                    if (start) clockInInput.value = start;
                    if (end) clockOutInput.value = end;
                }
            }

            shiftSelect.addEventListener('change', updateClockTimes);
            
            // Set initial value on setup
            updateClockTimes();
        }

        if (dateInput) {
            dateInput.addEventListener('change', function() {
                const selectedDate = this.value;
                if (blockedDates.includes(selectedDate)) {
                    alert("This date is configured as a Holiday or Weekend. Attendance cannot be logged on holidays or weekends.");
                    this.value = "";
                }
            });
            // Initial check on load
            if (blockedDates.includes(dateInput.value)) {
                dateInput.value = "";
            }
        }

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
