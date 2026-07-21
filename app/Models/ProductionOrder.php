<?php
namespace App\Models;

use App\Core\Model;

/**
 * Production Manufacturing Batch Order Model
 * Full Stack Developer - Antigravity
 */
class ProductionOrder extends Model {
    protected string $table = 'production_orders';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
