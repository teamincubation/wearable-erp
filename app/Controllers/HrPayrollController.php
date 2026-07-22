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

        $this->renderView('company/attendance', [
            'title' => 'Attendance Register | ERP',
            'attendance' => $attendance,
            'employees' => $employees,
            'shifts' => $shifts,
            'gwh' => $gwh
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

        if (empty($employeeId) || empty($month) || empty($year) || $baseSalary <= 0) {
            Session::setFlash('error', 'Employee, Month, Year, and Base Salary are required.');
            $this->redirect('company/hr/payroll');
        }

        $companyId = Session::get('company_id');
        $db = Database::getInstance();

        // Calculate total overtime hours worked in this month
        $stmt = $db->prepare("SELECT SUM(overtime_hours) as total_ot 
                             FROM employee_attendance 
                             WHERE company_id = ? AND employee_id = ? 
                               AND MONTH(date) = ? AND YEAR(date) = ? AND status = 'present' AND deleted_at IS NULL");
        $stmt->execute([$companyId, $employeeId, $month, $year]);
        $result = $stmt->fetch();
        $otHours = (float)($result['total_ot'] ?? 0.00);

        // Standard hourly rate is: baseSalary / (26 days * 8 hours)
        $hourlyRate = $baseSalary / (26 * 8);
        $otPay = $otHours * $hourlyRate * 1.5; // OT at 1.5x standard rate

        // Compute net salary
        $netSalary = $baseSalary + $otPay + $bonus - $loan - $tax;

        $payrollModel = new PayrollRecord();
        $existing = $payrollModel->findOneBy([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'month' => $month,
            'year' => $year
        ]);

        if ($existing) {
            $payrollModel->update($existing['id'], [
                'base_salary' => $baseSalary,
                'overtime_pay' => $otPay,
                'bonus' => $bonus,
                'loan_deduction' => $loan,
                'tax_deduction' => $tax,
                'net_salary' => $netSalary,
                'status' => 'draft',
                'updated_by' => Session::get('user_id')
            ]);
            $payrollId = $existing['id'];
        } else {
            $payrollId = $payrollModel->insert([
                'employee_id' => $employeeId,
                'month' => $month,
                'year' => $year,
                'base_salary' => $baseSalary,
                'overtime_pay' => $otPay,
                'bonus' => $bonus,
                'loan_deduction' => $loan,
                'tax_deduction' => $tax,
                'net_salary' => $netSalary,
                'status' => 'draft',
                'created_by' => Session::get('user_id')
            ]);
        }

        AuditLog::log($companyId, Session::get('user_id'), 'process_payroll', 'PayrollRecord', $payrollId, null, null, "Processed payroll for employee {$employeeId} for {$month}/{$year}");
        Session::setFlash('success', 'Payroll salary record processed successfully.');
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
}
