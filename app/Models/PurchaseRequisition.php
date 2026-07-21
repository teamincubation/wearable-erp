<?php
namespace App\Models;

use App\Core\Model;

/**
 * Material Purchase Requisition Model
 * Full Stack Developer - Antigravity
 */
class PurchaseRequisition extends Model {
    protected string $table = 'purchase_requisitions';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
