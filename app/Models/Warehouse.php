<?php
namespace App\Models;

use App\Core\Model;

/**
 * Company Warehouse / Storage Facility Model
 * Full Stack Developer - Antigravity
 */
class Warehouse extends Model {
    protected string $table = 'warehouses';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
