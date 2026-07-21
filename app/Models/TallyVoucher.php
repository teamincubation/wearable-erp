<?php
namespace App\Models;

use App\Core\Model;

/**
 * Tally ERP/Prime Financial Voucher Queue Model
 * Full Stack Developer - Antigravity
 */
class TallyVoucher extends Model {
    protected string $table = 'tally_vouchers';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = false; // Transaction voucher logs are permanent
}
