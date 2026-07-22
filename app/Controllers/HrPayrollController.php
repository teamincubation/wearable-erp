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

        // Fetch attendance with employee name
        $stmt = $db->prepare("SELECT att.*, u.name as employee_name 
                             FROM employee_attendance att
                             JOIN users u ON att.employee_id = u.id
                             WHERE att.company_id = ? AND att.deleted_at IS NULL
                             ORDER BY att.date DESC, att.id DESC LIMIT 100");
        $stmt->execute([$companyId]);
        $attendance = $stmt->fetchAll() ?: [];

        // Fetch active employees
        $userModel = new User();
        $employees = $userModel->all();

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
            'blocked_dates' => $blockedDates
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

        // Fetch payroll records
        $stmt = $db->prepare("SELECT pr.*, u.name as employee_name 
                             FROM payroll_records pr
                             JOIN users u ON pr.employee_id = u.id
                             WHERE pr.company_id = ? AND pr.deleted_at IS NULL
                             ORDER BY pr.year DESC, pr.month DESC");
        $stmt->execute([$companyId]);
        $payroll = $stmt->fetchAll() ?: [];

        // Fetch active employees
        $userModel = new User();
        $employees = $userModel->all();

        $this->renderView('company/payroll', [
            'title' => 'Payroll Processing | ERP',
            'payroll' => $payroll,
            'employees' => $employees
        ]);
    }

    /**
     * Process Payroll for an Employee
     */
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
            'overtime_hours' => $otHours,
            'status' => 'paid',
            'updated_by' => Session::get('user_id')
        ];

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

        // Total Overtime Hours
        $stmtOt = $db->prepare("SELECT SUM(overtime_hours) FROM employee_attendance WHERE company_id = ? AND employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ? AND status = 'present' AND deleted_at IS NULL");
        $stmtOt->execute([$companyId, $employeeId, $month, $year]);
        $otHours = (float)($stmtOt->fetchColumn() ?: 0.00);

        // Fetch Leave & Cut policies
        $settingsKeys = [
            'leave_allocation_cl' => '12',
            'leave_allocation_sl' => '10',
            'leave_allocation_el' => '15',
            'cut_policy_absent' => '100',
            'cut_policy_lop' => '100',
            'cut_policy_halfday' => '50'
        ];
        $policies = [];
        foreach ($settingsKeys as $key => $default) {
            $stmtSet = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = ? AND deleted_at IS NULL LIMIT 1");
            $stmtSet->execute([$companyId, $key]);
            $val = $stmtSet->fetchColumn();
            $policies[$key] = $val !== false ? (float)$val : (float)$default;
        }

        // Return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'base_salary' => $baseSalary,
            'present_days' => $present,
            'absent_days' => $absent,
            'leave_days' => $leave,
            'holiday_days' => $holiday,
            'overtime_hours' => $otHours,
            'policies' => $policies
        ]);
        exit;
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
}
