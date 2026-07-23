<?php
namespace App\Models;

use App\Core\Model;

/**
 * Warehouse Storage Type Model
 * Full Stack Developer - Antigravity
 */
class WarehouseType extends Model {
    protected string $table = 'warehouse_types';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
