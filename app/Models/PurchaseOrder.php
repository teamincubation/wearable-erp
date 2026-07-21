<?php
namespace App\Models;

use App\Core\Model;

/**
 * Supplier Purchase Order Model
 * Full Stack Developer - Antigravity
 */
class PurchaseOrder extends Model {
    protected string $table = 'purchase_orders';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
