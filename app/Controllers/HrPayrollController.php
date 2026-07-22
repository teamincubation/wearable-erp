<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\EmployeeAttendance;
use App\Models\PayrollRecord;
use App\Models\User;
use App\Models\AuditLog;

/**
 * HR and Payroll Operations Controller
 * Full Stack Developer - Antigravity
 */
class HrPayrollController extends Controller {
    /**
     * Attendance Register View
     */
    public function attendance(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Read filter date or default to current day
        $filterDate = $request->get('date_filter');
        if (empty($filterDate)) {
            $filterDate = date('Y-m-d');
        }

        // Fetch attendance with employee name, filtered by date
        if ($filterDate === 'all') {
            $stmt = $db->prepare("SELECT att.*, u.name as employee_name 
                                 FROM employee_attendance att
                                 JOIN users u ON att.employee_id = u.id
                                 WHERE att.company_id = ? AND u.role_id != 1 AND att.deleted_at IS NULL
                                 ORDER BY att.date DESC, att.id DESC LIMIT 500");
            $stmt->execute([$companyId]);
        } else {
            $stmt = $db->prepare("SELECT att.*, u.name as employee_name 
                                 FROM employee_attendance att
                                 JOIN users u ON att.employee_id = u.id
                                 WHERE att.company_id = ? AND att.date = ? AND u.role_id != 1 AND att.deleted_at IS NULL
                                 ORDER BY att.id DESC");
            $stmt->execute([$companyId, $filterDate]);
        }
        $attendance = $stmt->fetchAll() ?: [];

        // Fetch active employees
        $userModel = new User();
        $employees = $userModel->getActiveCompanyEmployees();

        // Fetch shifts
        $stmt = $db->prepare("SELECT id, name, start_time, end_time FROM shifts WHERE company_id = ? AND deleted_at IS NULL");
        $stmt->execute([$companyId]);
        $shifts = $stmt->fetchAll() ?: [];

        // Fetch General Working Hours setting
        $stmtGwh = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = 'general_working_hours' AND deleted_at IS NULL LIMIT 1");
        $stmtGwh->execute([$companyId]);
        $gwh = (int)($stmtGwh->fetchColumn() ?: 8);

        // Fetch company holidays
        $stmtH = $db->prepare("SELECT date FROM company_holidays WHERE company_id = ?");
        $stmtH->execute([$companyId]);
        $blockedDates = $stmtH->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        $this->renderView('company/attendance', [
            'title' => 'Attendance Register | ERP',
            'attendance' => $attendance,
            'employees' => $employees,
            'shifts' => $shifts,
            'gwh' => $gwh,
            'blocked_dates' => $blockedDates,
            'filter_date' => $filterDate
        ]);
    }

    /**
     * Submit Attendance Clock Log
     */
    public function clock(Request $request, Response $response): void {
        $employeeId = (int)$request->get('employee_id');
        $date = $request->get('date');
        $clockIn = $request->get('clock_in');
        $clockOut = $request->get('clock_out');
        $shiftId = $request->get('shift_id') ? (int)$request->get('shift_id') : null;
        $status = $request->get('status') ?: 'present';

        if (empty($employeeId) || empty($date)) {
            Session::setFlash('error', 'Employee selection and Date are required.');
            $this->redirect('company/hr/attendance');
        }

        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Block holiday and weekend dates
        $stmtH = $db->prepare("SELECT id FROM company_holidays WHERE company_id = ? AND date = ? LIMIT 1");
        $stmtH->execute([$companyId, $date]);
        if ($stmtH->fetch()) {
            Session::setFlash('error', 'Attendance cannot be logged on a pre-configured Holiday or Weekend.');
            $this->redirect('company/hr/attendance');
        }

        // Fetch General Working Hours setting
        $stmtGwh = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = 'general_working_hours' AND deleted_at IS NULL LIMIT 1");
        $stmtGwh->execute([$companyId]);
        $gwh = (float)($stmtGwh->fetchColumn() ?: 8.00);

        // Calculate overtime hours if clocked in & out
        $ot = 0.00;
        if (!empty($clockIn) && !empty($clockOut)) {
            $in = strtotime($date . ' ' . $clockIn);
            $out = strtotime($date . ' ' . $clockOut);
            $duration = ($out - $in) / 3600; // Total hours
            if ($duration > $gwh) {
                $ot = $duration - $gwh;
            }
        }

        $attModel = new EmployeeAttendance();

        // Check if attendance already exists for this date & employee
        $existing = $attModel->findOneBy([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'date' => $date
        ]);

        if ($existing) {
            $attModel->update($existing['id'], [
                'clock_in' => $clockIn ?: null,
                'clock_out' => $clockOut ?: null,
                'shift_id' => $shiftId,
                'overtime_hours' => $ot,
                'status' => $status,
                'updated_by' => Session::get('user_id')
            ]);
            $attId = $existing['id'];
        } else {
            $attId = $attModel->insert([
                'employee_id' => $employeeId,
                'date' => $date,
                'clock_in' => $clockIn ?: null,
                'clock_out' => $clockOut ?: null,
                'shift_id' => $shiftId,
                'overtime_hours' => $ot,
                'status' => $status,
                'created_by' => Session::get('user_id')
            ]);
        }

        AuditLog::log($companyId, Session::get('user_id'), 'log_attendance', 'EmployeeAttendance', $attId, null, null, "Logged attendance status {$status} for employee ID {$employeeId}");
        Session::setFlash('success', 'Attendance record saved successfully.');
        $this->redirect('company/hr/attendance');
    }

    /**
     * Payroll List View
     */
    public function payroll(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch payroll records with employee designation & code
        $stmt = $db->prepare("SELECT pr.*, u.name as employee_name, u.employee_code, u.designation 
                             FROM payroll_records pr
                             JOIN users u ON pr.employee_id = u.id
                             WHERE pr.company_id = ? AND u.role_id != 1 AND pr.deleted_at IS NULL
                             ORDER BY pr.year DESC, pr.month DESC");
        $stmt->execute([$companyId]);
        $payroll = $stmt->fetchAll() ?: [];

        // Fetch active employees
        $userModel = new User();
        $employees = $userModel->getActiveCompanyEmployees();

        // Fetch payment accounts
        $stmtAccounts = $db->prepare("SELECT * FROM payment_accounts WHERE company_id = ? AND deleted_at IS NULL");
        $stmtAccounts->execute([$companyId]);
        $paymentAccounts = $stmtAccounts->fetchAll() ?: [];

        $this->renderView('company/payroll', [
            'title' => 'Payroll Processing | ERP',
            'payroll' => $payroll,
            'employees' => $employees,
            'paymentAccounts' => $paymentAccounts
        ]);
    }

    public function processPayroll(Request $request, Response $response): void {
        $employeeId = (int)$request->get('employee_id');
        $month = (int)$request->get('month');
        $year = (int)$request->get('year');
        $baseSalary = (float)$request->get('base_salary');
        $bonus = (float)$request->get('bonus');
        $loan = (float)$request->get('loan_deduction');
        $tax = (float)$request->get('tax_deduction');

        $presentDays = (int)$request->get('present_days');
        $absentDays = (int)$request->get('absent_days');
        $leaveDays = (int)$request->get('leave_days');
        $holidayDays = (int)$request->get('holiday_days');
        $halfDays = (int)$request->get('half_days');
        $otHours = (float)$request->get('overtime_hours');
        $otPay = (float)$request->get('overtime_pay');
        $netSalary = (float)$request->get('net_salary');

        if (empty($employeeId) || empty($month) || empty($year) || $baseSalary <= 0) {
            Session::setFlash('error', 'Employee, Month, Year, and Base Salary are required.');
            $this->redirect('company/hr/payroll');
        }

        $companyId = Session::get('company_id');
        $payrollModel = new PayrollRecord();
        $existing = $payrollModel->findOneBy([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'month' => $month,
            'year' => $year
        ]);

        $payrollData = [
            'base_salary' => $baseSalary,
            'overtime_pay' => $otPay,
            'bonus' => $bonus,
            'loan_deduction' => $loan,
            'tax_deduction' => $tax,
            'net_salary' => $netSalary,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'leave_days' => $leaveDays,
            'holiday_days' => $holidayDays,
            'half_days' => $halfDays,
            'overtime_hours' => $otHours,
            'status' => 'pending',
            'updated_by' => Session::get('user_id')
        ];

        $db = Database::getInstance();

        if ($existing) {
            $payrollModel->update($existing['id'], $payrollData);
            $payrollId = $existing['id'];
        } else {
            $payrollData['company_id'] = $companyId;
            $payrollData['employee_id'] = $employeeId;
            $payrollData['month'] = $month;
            $payrollData['year'] = $year;
            $payrollData['created_by'] = Session::get('user_id');
            $payrollId = $payrollModel->insert($payrollData);
        }

        // Mark employee loans as deducted
        $stmtDeduct = $db->prepare("UPDATE employee_loans SET status = 'deducted' WHERE company_id = ? AND employee_id = ? AND month = ? AND year = ? AND status = 'pending'");
        $stmtDeduct->execute([$companyId, $employeeId, $month, $year]);

        AuditLog::log($companyId, Session::get('user_id'), 'process_payroll', 'PayrollRecord', $payrollId, null, null, "Processed payroll for employee {$employeeId} for {$month}/{$year}");
        Session::setFlash('success', 'Payroll salary record processed successfully.');
        $this->redirect('company/hr/payroll');
    }

    /**
     * AJAX Endpoint: Calculate default attendance statistics & parameters for an employee
     */
    public function calculatePayrollStats(Request $request, Response $response): void {
        $employeeId = (int)$request->get('employee_id');
        $month = (int)$request->get('month');
        $year = (int)$request->get('year');

        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch employee base salary
        $stmtEmp = $db->prepare("SELECT base_salary FROM users WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1");
        $stmtEmp->execute([$employeeId, $companyId]);
        $baseSalary = (float)($stmtEmp->fetchColumn() ?: 0.00);

        // Fetch counts from attendance
        // Present
        $stmtP = $db->prepare("SELECT COUNT(*) FROM employee_attendance WHERE company_id = ? AND employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ? AND status = 'present' AND deleted_at IS NULL");
        $stmtP->execute([$companyId, $employeeId, $month, $year]);
        $present = (int)$stmtP->fetchColumn();

        // Half Day
        $stmtHD = $db->prepare("SELECT COUNT(*) FROM employee_attendance WHERE company_id = ? AND employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ? AND status = 'half_day' AND deleted_at IS NULL");
        $stmtHD->execute([$companyId, $employeeId, $month, $year]);
        $halfDays = (int)$stmtHD->fetchColumn();

        // Absent
        $stmtA = $db->prepare("SELECT COUNT(*) FROM employee_attendance WHERE company_id = ? AND employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ? AND status = 'absent' AND deleted_at IS NULL");
        $stmtA->execute([$companyId, $employeeId, $month, $year]);
        $absent = (int)$stmtA->fetchColumn();

        // Leave
        $stmtL = $db->prepare("SELECT COUNT(*) FROM employee_attendance WHERE company_id = ? AND employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ? AND status = 'leave' AND deleted_at IS NULL");
        $stmtL->execute([$companyId, $employeeId, $month, $year]);
        $leave = (int)$stmtL->fetchColumn();

        // Holiday (official holiday)
        $stmtH = $db->prepare("SELECT COUNT(*) FROM employee_attendance WHERE company_id = ? AND employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ? AND status = 'holiday' AND deleted_at IS NULL");
        $stmtH->execute([$companyId, $employeeId, $month, $year]);
        $holiday = (int)$stmtH->fetchColumn();

        // Total Overtime Hours (include present and half_day overtime)
        $stmtOt = $db->prepare("SELECT SUM(overtime_hours) FROM employee_attendance WHERE company_id = ? AND employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ? AND status IN ('present', 'half_day') AND deleted_at IS NULL");
        $stmtOt->execute([$companyId, $employeeId, $month, $year]);
        $otHours = (float)($stmtOt->fetchColumn() ?: 0.00);

        // Fetch Leave & Cut policies
        $settingsKeys = [
            'leave_allocation_cl' => '12',
            'leave_allocation_sl' => '10',
            'leave_allocation_el' => '15',
            'cut_policy_absent' => '100',
            'cut_policy_lop' => '100',
            'cut_policy_halfday' => '50',
            'overtime_pay_hour_charge' => '150.00'
        ];
        $policies = [];
        foreach ($settingsKeys as $key => $default) {
            $stmtSet = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = ? AND deleted_at IS NULL LIMIT 1");
            $stmtSet->execute([$companyId, $key]);
            $val = $stmtSet->fetchColumn();
            $policies[$key] = $val !== false ? (float)$val : (float)$default;
        }

        // Fetch pending employee loans for this month/year
        $stmtLoan = $db->prepare("SELECT SUM(amount) FROM employee_loans WHERE company_id = ? AND employee_id = ? AND month = ? AND year = ? AND status = 'pending' AND deleted_at IS NULL");
        $stmtLoan->execute([$companyId, $employeeId, $month, $year]);
        $pendingLoan = (float)($stmtLoan->fetchColumn() ?: 0.00);

        // Return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'base_salary' => $baseSalary,
            'present_days' => $present,
            'half_days' => $halfDays,
            'absent_days' => $absent,
            'leave_days' => $leave,
            'holiday_days' => $holiday,
            'overtime_hours' => $otHours,
            'pending_loan' => $pendingLoan,
            'policies' => $policies
        ]);
        exit;
    }

    /**
     * Mark a Payroll Record as Paid with specific Payment Account
     */
    public function payPayroll(Request $request, Response $response, string $id): void {
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        $paidFromAccount = (int)$request->get('paid_from_account_id');
        $paidAmount = (float)$request->get('paid_amount');
        $paidDate = $request->get('paid_date') ?: date('Y-m-d');

        $payrollModel = new PayrollRecord();
        $payroll = $payrollModel->find($id);

        if (!$payroll) {
            Session::setFlash('error', 'Payroll record not found.');
            $this->redirect('company/hr/payroll');
        }

        if ($paidAmount <= 0) {
            Session::setFlash('error', 'Paid amount must be positive.');
            $this->redirect('company/hr/payroll');
        }

        // Validate paid amount cannot exceed net salary
        if ($paidAmount > $payroll['net_salary']) {
            Session::setFlash('error', 'Paid amount cannot exceed the net salary payable.');
            $this->redirect('company/hr/payroll');
        }

        $balanceAmount = $payroll['net_salary'] - $paidAmount;
        $status = $balanceAmount <= 0 ? 'paid' : 'pending';

        // Fetch admin user name to log who paid
        $stmtUser = $db->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
        $stmtUser->execute([$userId]);
        $adminName = $stmtUser->fetchColumn() ?: 'Admin';

        try {
            $payrollModel->update($id, [
                'status' => $status,
                'paid_from_account_id' => $paidFromAccount,
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'paid_date' => $paidDate,
                'paid_by_user_id' => $userId,
                'updated_by' => $userId
            ]);

            AuditLog::log($companyId, $userId, 'pay_payroll', 'PayrollRecord', $id, null, null, "Logged payment of {$paidAmount} from account {$paidFromAccount} for payroll ID {$id} by {$adminName}");
            Session::setFlash('success', 'Payroll payment logged successfully.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to log payment: ' . $e->getMessage());
        }

        $this->redirect('company/hr/payroll');
    }

    public function deleteAttendance(Request $request, Response $response, string $id): void {
        $model = new EmployeeAttendance();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Attendance record deleted successfully.');
        $this->redirect('company/hr/attendance');
    }

    public function deletePayroll(Request $request, Response $response, string $id): void {
        $model = new PayrollRecord();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Payroll record deleted successfully.');
        $this->redirect('company/hr/payroll');
    }

    /**
     * Issue an Employee Loan
     */
    public function createLoan(Request $request, Response $response): void {
        $employeeId = (int)$request->get('employee_id');
        $amount = (float)$request->get('amount');
        $month = (int)$request->get('month');
        $year = (int)$request->get('year');

        if (empty($employeeId) || $amount <= 0 || empty($month) || empty($year)) {
            Session::setFlash('error', 'All fields are required and loan amount must be positive.');
            $this->redirect('company/hr/loans');
        }

        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch employee details to validate base salary
        $stmtEmp = $db->prepare("SELECT base_salary FROM users WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1");
        $stmtEmp->execute([$employeeId, $companyId]);
        $baseSalary = (float)($stmtEmp->fetchColumn() ?: 0.00);

        if ($amount > $baseSalary) {
            Session::setFlash('error', 'Loan amount cannot exceed the employee\'s basic monthly salary.');
            $this->redirect('company/hr/loans');
        }

        // Validate month & year are current or upcoming
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n');

        if ($year < $currentYear || ($year === $currentYear && $month < $currentMonth)) {
            Session::setFlash('error', 'Loan can only be issued for the current or upcoming months.');
            $this->redirect('company/hr/loans');
        }

        try {
            $stmt = $db->prepare("INSERT INTO employee_loans (company_id, employee_id, amount, month, year, status, created_by) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$companyId, $employeeId, $amount, $month, $year, Session::get('user_id')]);

            AuditLog::log($companyId, Session::get('user_id'), 'create_loan', 'EmployeeLoan', $db->lastInsertId(), null, null, "Issued loan of {$amount} to employee {$employeeId} for {$month}/{$year}");
            Session::setFlash('success', 'Employee loan record saved successfully.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to save loan record: ' . $e->getMessage());
        }

        $this->redirect('company/hr/loans');
    }

    /**
     * Soft delete an employee loan
     */
    public function deleteLoan(Request $request, Response $response, string $id): void {
        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        try {
            // Only allow deleting pending loans to avoid tampering with already deducted ones
            $stmt = $db->prepare("UPDATE employee_loans SET deleted_at = NOW() WHERE id = ? AND company_id = ? AND status = 'pending'");
            $stmt->execute([$id, $companyId]);

            AuditLog::log($companyId, Session::get('user_id'), 'delete_loan', 'EmployeeLoan', $id, null, null, "Soft-deleted employee loan ID {$id}");
            Session::setFlash('success', 'Employee loan cancelled successfully.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to delete loan record: ' . $e->getMessage());
        }

        $this->redirect('company/hr/loans');
    }

    /**
     * Standalone Employee Loans Dashboard Page
     */
    public function loansPage(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // Fetch active employees
        $userModel = new User();
        $employees = $userModel->getActiveCompanyEmployees();

        // Fetch employee loans
        $stmtLoans = $db->prepare("SELECT el.*, u.name as employee_name, u.base_salary 
                                  FROM employee_loans el
                                  JOIN users u ON el.employee_id = u.id
                                  WHERE el.company_id = ? AND u.role_id != 1 AND el.deleted_at IS NULL
                                  ORDER BY el.id DESC");
        $stmtLoans->execute([$companyId]);
        $loans = $stmtLoans->fetchAll() ?: [];

        $this->renderView('company/loans', [
            'title' => 'Employee Loans Registry | ERP',
            'employees' => $employees,
            'loans' => $loans
        ]);
    }
}
