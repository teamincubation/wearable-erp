<?php
namespace App\Models;

use App\Core\Model;

/**
 * Garment Cost Sheet Model
 * Full Stack Developer - Antigravity
 */
class CostSheet extends Model {
    protected string $table = 'cost_sheets';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
