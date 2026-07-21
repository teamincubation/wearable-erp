<?php
namespace App\Models;

use App\Core\Model;

/**
 * Employee Payroll Record Model
 * Full Stack Developer - Antigravity
 */
class PayrollRecord extends Model {
    protected string $table = 'payroll_records';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
