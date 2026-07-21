<?php
namespace App\Models;

use App\Core\Model;

/**
 * Goods Receipt Note (GRN) Model
 * Full Stack Developer - Antigravity
 */
class Grn extends Model {
    protected string $table = 'grns';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
