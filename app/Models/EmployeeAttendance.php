<?php
namespace App\Models;

use App\Core\Model;

/**
 * Employee Attendance Tracking Model
 * Full Stack Developer - Antigravity
 */
class EmployeeAttendance extends Model {
    protected string $table = 'employee_attendance';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
