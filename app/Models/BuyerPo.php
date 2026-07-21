<?php
namespace App\Models;

use App\Core\Model;

/**
 * Buyer Purchase Order (PO) Model
 * Full Stack Developer - Antigravity
 */
class BuyerPo extends Model {
    protected string $table = 'buyer_pos';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
