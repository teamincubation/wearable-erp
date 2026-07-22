<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Payroll Processing Manager</h3>
        <p class="text-secondary m-0">View monthly salary logs, customize attendance factors, and generate client-ready PDF payslips</p>
    </div>
    <?php if (\App\Core\Auth::hasPermission('company.users.create')): ?>
        <button class="btn btn-pepp-primary" data-bs-toggle="modal" data-bs-target="#processPayrollModal">
            <i class="fa-solid fa-plus-circle me-1"></i> Process Salary Sheet
        </button>
    <?php endif; ?>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-coins text-primary me-2"></i> Employee Monthly Salary Sheets</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table table-hover pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Month / Year</th>
                        <th>Base Salary</th>
                        <th>Attendance (P/A/L/H)</th>
                        <th>Overtime Earned</th>
                        <th>Bonus</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
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
                                <td>
                                    <strong class="text-dark"><?= htmlspecialchars($pr['employee_name']) ?></strong>
                                    <div class="text-secondary small font-monospace">Emp ID: <?= htmlspecialchars($pr['employee_code'] ?? 'N/A') ?></div>
                                </td>
                                <td><?= $monthName ?> <?= $pr['year'] ?></td>
                                <td class="font-monospace"><?= get_currency_symbol() ?><?= number_format($pr['base_salary'], 2) ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        <span class="badge bg-success text-white rounded-pill px-2" style="font-size: 10px;">P: <?= $pr['present_days'] ?? 0 ?></span>
                                        <span class="badge bg-danger text-white rounded-pill px-2" style="font-size: 10px;">A: <?= $pr['absent_days'] ?? 0 ?></span>
                                        <span class="badge bg-warning text-dark rounded-pill px-2" style="font-size: 10px;">L: <?= $pr['leave_days'] ?? 0 ?></span>
                                        <span class="badge bg-info text-dark rounded-pill px-2" style="font-size: 10px;">H: <?= $pr['holiday_days'] ?? 0 ?></span>
                                        <span class="badge bg-dark text-white rounded-pill px-2" style="font-size: 10px;"><i class="fa-regular fa-clock me-1"></i> <?= number_format($pr['overtime_hours'] ?? 0.00, 1) ?>h</span>
                                    </div>
                                </td>
                                <td class="text-success font-monospace">+<?= get_currency_symbol() ?><?= number_format($pr['overtime_pay'], 2) ?></td>
                                <td class="font-monospace"><?= get_currency_symbol() ?><?= number_format($pr['bonus'], 2) ?></td>
                                <td class="text-danger font-monospace">-<?= get_currency_symbol() ?><?= number_format($totalDeductions, 2) ?></td>
                                <td><strong class="text-primary font-monospace"><?= get_currency_symbol() ?><?= number_format($pr['net_salary'], 2) ?></strong></td>
                                <td>
                                    <span class="badge bg-success text-white text-capitalize"><?= htmlspecialchars($pr['status']) ?></span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary me-1 rounded-pill" onclick="downloadPayslipPdf(<?= htmlspecialchars(json_encode($pr)) ?>, '<?= htmlspecialchars($pr['employee_name']) ?>', '<?= $monthName ?>', '<?= $pr['year'] ?>')">
                                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                                    </button>
                                    <form action="<?= base_url('company/hr/payroll/delete/' . $pr['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this payroll record?');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center p-5 text-secondary">
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
        <div class="modal-dialog modal-lg">
            <form action="<?= base_url('company/hr/payroll/process') ?>" method="POST" id="payrollProcessForm">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content text-start" style="border-radius: 16px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-calculator text-primary me-2"></i> Process Salary Sheet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <!-- Left Column: Scope & Base Details -->
                            <div class="col-md-6 border-end pe-4">
                                <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-user-gear me-1"></i> 1. Scope & Core Earnings</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Select Employee <span class="text-danger">*</span></label>
                                    <select name="employee_id" id="calc_employee_id" class="form-select text-dark" required>
                                        <option value="">-- Choose Employee --</option>
                                        <?php foreach ($employees as $e): ?>
                                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Cycle Month <span class="text-danger">*</span></label>
                                        <select name="month" id="calc_month" class="form-select text-dark" required>
                                            <?php for ($m=1; $m<=12; $m++): ?>
                                                <option value="<?= $m ?>" <?= date('n') == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 10)) ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Cycle Year <span class="text-danger">*</span></label>
                                        <input type="number" name="year" id="calc_year" class="form-control text-dark" value="<?= date('Y') ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Base Monthly Salary (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="base_salary" id="calc_base_salary" class="form-control text-dark fw-bold" placeholder="e.g. 20000.00" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Incentive / Bonus (<?= get_currency_symbol() ?>)</label>
                                    <input type="number" step="0.01" name="bonus" id="calc_bonus" class="form-control text-dark" value="0.00">
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Loan Deductions (<?= get_currency_symbol() ?>)</label>
                                        <input type="number" step="0.01" name="loan_deduction" id="calc_loan_deduction" class="form-control text-dark text-danger" value="0.00">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">TDS / Profession Tax (<?= get_currency_symbol() ?>)</label>
                                        <input type="number" step="0.01" name="tax_deduction" id="calc_tax_deduction" class="form-control text-dark text-danger" value="0.00">
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Attendance Statistics & Overrides -->
                            <div class="col-md-6 ps-4">
                                <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-calendar-check me-1"></i> 2. Attendance & Adjustments</h6>
                                
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-success">Present Days</label>
                                        <input type="number" name="present_days" id="calc_present_days" class="form-control text-dark text-success" value="0">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-danger">Absent Days</label>
                                        <input type="number" name="absent_days" id="calc_absent_days" class="form-control text-dark text-danger" value="0">
                                    </div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-warning">Leave Days</label>
                                        <input type="number" name="leave_days" id="calc_leave_days" class="form-control text-dark text-warning" value="0">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-info">Paid Holidays</label>
                                        <input type="number" name="holiday_days" id="calc_holiday_days" class="form-control text-dark text-info" value="0">
                                    </div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-dark">Overtime Hours</label>
                                        <input type="number" step="0.1" name="overtime_hours" id="calc_overtime_hours" class="form-control text-dark fw-semibold" value="0.0">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-success">Overtime Pay (<?= get_currency_symbol() ?>)</label>
                                        <input type="number" step="0.01" name="overtime_pay" id="calc_overtime_pay" class="form-control text-dark text-success fw-bold" value="0.00" readonly>
                                    </div>
                                </div>

                                <!-- Dynamic calculations info card -->
                                <div class="bg-light p-3 rounded" style="border-left: 4px solid var(--primary-color);">
                                    <div class="small mb-1 text-secondary fw-semibold">Net Calculation Summary (Live)</div>
                                    <div class="d-flex justify-content-between text-dark mb-1">
                                        <span>Daily Pay Rate:</span>
                                        <span id="label_daily_rate" class="fw-semibold">0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-dark mb-1">
                                        <span>Deductions:</span>
                                        <span id="label_deductions" class="text-danger fw-semibold">0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-primary fw-bold" style="font-size: 15px;">
                                        <span>Net Salary:</span>
                                        <span><?= get_currency_symbol() ?><span id="label_net_salary">0.00</span></span>
                                    </div>
                                    <input type="hidden" name="net_salary" id="calc_net_salary" value="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-pepp-primary px-4">Process Salary Sheet</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Hidden Container for PDF Rendering -->
<div id="payslip-pdf-template" style="display: none;">
    <div style="padding: 40px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6;">
        <!-- Header -->
        <table style="width: 100%; border-bottom: 2px solid #0056b3; padding-bottom: 20px; margin-bottom: 20px;">
            <tr>
                <td>
                    <h2 style="margin: 0; color: #0056b3; font-weight: bold;"><?= htmlspecialchars(\App\Core\Session::get('current_tenant')['name'] ?? 'Wearable ERP Tenant') ?></h2>
                    <p style="margin: 3px 0 0 0; font-size: 12px; color: #777;"><?= htmlspecialchars(\App\Core\Session::get('current_tenant')['city'] ?? 'Tiruppur') ?>, <?= htmlspecialchars(\App\Core\Session::get('current_tenant')['state'] ?? 'Tamil Nadu') ?></p>
                </td>
                <td style="text-align: right;">
                    <h3 style="margin: 0; color: #555; text-transform: uppercase; font-weight: normal; letter-spacing: 1px;">Salary Payslip</h3>
                    <p style="margin: 3px 0 0 0; font-size: 13px; font-weight: bold;" id="pdf_cycle_period"></p>
                </td>
            </tr>
        </table>

        <!-- Employee Info -->
        <table style="width: 100%; margin-bottom: 30px; font-size: 14px;">
            <tr>
                <td style="width: 50%;">
                    <strong>Employee Name:</strong> <span id="pdf_emp_name"></span><br>
                    <strong>Employee Code:</strong> <span id="pdf_emp_code"></span>
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Designation:</strong> Employee<br>
                    <strong>Status:</strong> Processed & Paid
                </td>
            </tr>
        </table>

        <!-- Summary Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 14px;">
            <thead>
                <tr style="background-color: #f8f9fa; border-top: 1px solid #dee2e6; border-bottom: 2px solid #dee2e6;">
                    <th style="padding: 10px; text-align: left; font-weight: bold;">Earnings / Allowances</th>
                    <th style="padding: 10px; text-align: right; font-weight: bold;">Amount</th>
                    <th style="padding: 10px; text-align: left; font-weight: bold; padding-left: 20px;">Deductions</th>
                    <th style="padding: 10px; text-align: right; font-weight: bold;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;">Base Salary</td>
                    <td style="padding: 10px; text-align: right;" id="pdf_base_salary"></td>
                    <td style="padding: 10px; padding-left: 20px;">Loan Deduction</td>
                    <td style="padding: 10px; text-align: right; color: #dc3545;" id="pdf_loan_ded"></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;">Overtime Pay</td>
                    <td style="padding: 10px; text-align: right; color: #198754;" id="pdf_ot_pay"></td>
                    <td style="padding: 10px; padding-left: 20px;">Tax / Profession Tax</td>
                    <td style="padding: 10px; text-align: right; color: #dc3545;" id="pdf_tax_ded"></td>
                </tr>
                <tr style="border-bottom: 2px solid #dee2e6;">
                    <td style="padding: 10px;">Bonus / Incentives</td>
                    <td style="padding: 10px; text-align: right; color: #198754;" id="pdf_bonus"></td>
                    <td style="padding: 10px; padding-left: 20px;">Absence / LOP Cuts</td>
                    <td style="padding: 10px; text-align: right; color: #dc3545;" id="pdf_absent_cuts"></td>
                </tr>
                <tr style="background-color: #fdfdfd; font-size: 15px; font-weight: bold; border-bottom: 2px solid #555;">
                    <td style="padding: 12px; color: #0056b3;">Gross Earnings</td>
                    <td style="padding: 12px; text-align: right; color: #198754;" id="pdf_gross"></td>
                    <td style="padding: 12px; padding-left: 20px; color: #777;">Total Deductions</td>
                    <td style="padding: 12px; text-align: right; color: #dc3545;" id="pdf_total_ded"></td>
                </tr>
            </tbody>
        </table>

        <!-- Attendance Stats Breakdown -->
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 40px; font-size: 12px; color: #555;">
            <strong style="display: block; margin-bottom: 5px; color: #333;">Attendance breakdown for the month:</strong>
            Present Days: <span id="pdf_days_p"></span> | Absent Days: <span id="pdf_days_a"></span> | Leave Days: <span id="pdf_days_l"></span> | Holidays: <span id="pdf_days_h"></span> | Overtime: <span id="pdf_days_ot"></span> hours
        </div>

        <!-- Net Payable -->
        <table style="width: 100%; border: 1px solid #0056b3; background-color: #f1f8ff; padding: 15px; margin-bottom: 40px;">
            <tr>
                <td style="font-size: 16px; font-weight: bold; color: #0056b3;">Net Salary Payable:</td>
                <td style="font-size: 20px; font-weight: bold; color: #0056b3; text-align: right;" id="pdf_net_salary"></td>
            </tr>
        </table>

        <!-- Signature Lines -->
        <table style="width: 100%; margin-top: 60px; font-size: 13px;">
            <tr>
                <td style="width: 50%; border-top: 1px dashed #999; padding-top: 5px; text-align: center;">
                    Employee Signature
                </td>
                <td style="width: 50%; border-top: 1px dashed #999; padding-top: 5px; text-align: center;">
                    Authorized Signature (HR Dept)
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- Load html2pdf CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeSelect = document.getElementById('calc_employee_id');
    const monthSelect = document.getElementById('calc_month');
    const yearInput = document.getElementById('calc_year');

    const baseSalaryInput = document.getElementById('calc_base_salary');
    const bonusInput = document.getElementById('calc_bonus');
    const loanInput = document.getElementById('calc_loan_deduction');
    const taxInput = document.getElementById('calc_tax_deduction');

    const presentInput = document.getElementById('calc_present_days');
    const absentInput = document.getElementById('calc_absent_days');
    const leaveInput = document.getElementById('calc_leave_days');
    const holidayInput = document.getElementById('calc_holiday_days');
    const overtimeHoursInput = document.getElementById('calc_overtime_hours');
    const overtimePayInput = document.getElementById('calc_overtime_pay');

    const labelDailyRate = document.getElementById('label_daily_rate');
    const labelDeductions = document.getElementById('label_deductions');
    const labelNetSalary = document.getElementById('label_net_salary');
    const inputNetSalary = document.getElementById('calc_net_salary');

    let currentPolicies = {
        cut_policy_absent: 100,
        cut_policy_lop: 100,
        cut_policy_halfday: 50
    };

    function recalculatePayrollStats() {
        const baseVal = parseFloat(baseSalaryInput.value) || 0.00;
        const bonusVal = parseFloat(bonusInput.value) || 0.00;
        const loanVal = parseFloat(loanInput.value) || 0.00;
        const taxVal = parseFloat(taxInput.value) || 0.00;

        const presentVal = parseInt(presentInput.value) || 0;
        const absentVal = parseInt(absentInput.value) || 0;
        const leaveVal = parseInt(leaveInput.value) || 0;
        const holidayVal = parseInt(holidayInput.value) || 0;
        const otHoursVal = parseFloat(overtimeHoursInput.value) || 0.00;

        const m = parseInt(monthSelect.value) || 1;
        const y = parseInt(yearInput.value) || 2026;

        // Calculate days in month
        const daysInMonth = new Date(y, m, 0).getDate();
        const dailyRate = baseVal / (daysInMonth || 30);
        labelDailyRate.innerText = dailyRate.toFixed(2);

        // Standard hourly rate is: dailyRate / 8
        const hourlyRate = dailyRate / 8;
        const otPayVal = otHoursVal * hourlyRate * 1.5;
        overtimePayInput.value = otPayVal.toFixed(2);

        // Calculate salary cuts based on configured policies
        const absentCuts = absentVal * dailyRate * (currentPolicies.cut_policy_absent / 100);
        const lopCuts = leaveVal * dailyRate * (currentPolicies.cut_policy_lop / 100);
        
        const totalCuts = absentCuts + lopCuts;
        labelDeductions.innerText = totalCuts.toFixed(2);

        // Net Salary calculation
        const netSalVal = baseVal + otPayVal + bonusVal - totalCuts - loanVal - taxVal;
        labelNetSalary.innerText = netSalVal.toFixed(2);
        inputNetSalary.value = netSalVal.toFixed(2);
    }

    function fetchEmployeeStats() {
        const empId = employeeSelect.value;
        const m = monthSelect.value;
        const y = yearInput.value;

        if (!empId) return;

        fetch(`<?= base_url('company/hr/payroll/calculate') ?>?employee_id=${empId}&month=${m}&year=${y}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    baseSalaryInput.value = data.base_salary;
                    presentInput.value = data.present_days;
                    absentInput.value = data.absent_days;
                    leaveInput.value = data.leave_days;
                    holidayInput.value = data.holiday_days;
                    overtimeHoursInput.value = data.overtime_hours.toFixed(1);
                    
                    if (data.policies) {
                        currentPolicies = data.policies;
                    }
                    recalculatePayrollStats();
                }
            })
            .catch(err => console.error("Error fetching payroll stats", err));
    }

    if (employeeSelect) {
        employeeSelect.addEventListener('change', fetchEmployeeStats);
        monthSelect.addEventListener('change', fetchEmployeeStats);
        yearInput.addEventListener('change', fetchEmployeeStats);

        const listeners = [
            baseSalaryInput, bonusInput, loanInput, taxInput,
            presentInput, absentInput, leaveInput, holidayInput, overtimeHoursInput
        ];
        listeners.forEach(input => {
            if (input) input.addEventListener('input', recalculatePayrollStats);
        });
    }
});

function downloadPayslipPdf(pr, employeeName, monthName, year) {
    // Populate printable elements
    document.getElementById('pdf_cycle_period').innerText = `${monthName} ${year}`;
    document.getElementById('pdf_emp_name').innerText = employeeName;
    document.getElementById('pdf_emp_code').innerText = pr.employee_code || 'N/A';

    const baseVal = parseFloat(pr.base_salary) || 0;
    const otVal = parseFloat(pr.overtime_pay) || 0;
    const bonusVal = parseFloat(pr.bonus) || 0;
    const loanVal = parseFloat(pr.loan_deduction) || 0;
    const taxVal = parseFloat(pr.tax_deduction) || 0;

    const daysInMonth = new Date(year, pr.month, 0).getDate();
    const dailyRate = baseVal / (daysInMonth || 30);
    
    // Absent & Leaves deduction values
    const absentCuts = (parseInt(pr.absent_days) || 0) * dailyRate;
    const lopCuts = (parseInt(pr.leave_days) || 0) * dailyRate;
    const cutsVal = absentCuts + lopCuts;

    const gross = baseVal + otVal + bonusVal;
    const deductions = loanVal + taxVal + cutsVal;
    const net = gross - deductions;

    const symbol = '<?= get_currency_symbol() ?>';

    document.getElementById('pdf_base_salary').innerText = `${symbol}${baseVal.toFixed(2)}`;
    document.getElementById('pdf_ot_pay').innerText = `${symbol}${otVal.toFixed(2)}`;
    document.getElementById('pdf_bonus').innerText = `${symbol}${bonusVal.toFixed(2)}`;

    document.getElementById('pdf_loan_ded').innerText = `${symbol}${loanVal.toFixed(2)}`;
    document.getElementById('pdf_tax_ded').innerText = `${symbol}${taxVal.toFixed(2)}`;
    document.getElementById('pdf_absent_cuts').innerText = `${symbol}${cutsVal.toFixed(2)}`;

    document.getElementById('pdf_gross').innerText = `${symbol}${gross.toFixed(2)}`;
    document.getElementById('pdf_total_ded').innerText = `${symbol}${deductions.toFixed(2)}`;
    document.getElementById('pdf_net_salary').innerText = `${symbol}${net.toFixed(2)}`;

    document.getElementById('pdf_days_p').innerText = pr.present_days ?? '0';
    document.getElementById('pdf_days_a').innerText = pr.absent_days ?? '0';
    document.getElementById('pdf_days_l').innerText = pr.leave_days ?? '0';
    document.getElementById('pdf_days_h').innerText = pr.holiday_days ?? '0';
    document.getElementById('pdf_days_ot').innerText = pr.overtime_hours ?? '0.00';

    const element = document.getElementById('payslip-pdf-template');
    element.style.display = 'block';

    const opt = {
        margin:       10,
        filename:     `payslip-${employeeName.replace(/\s+/g, '_')}-${monthName}-${year}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        element.style.display = 'none';
    });
}
</script>
