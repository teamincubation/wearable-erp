<?php
namespace App\Models;

use App\Core\Model;

/**
 * Company Branch Model
 * Full Stack Developer - Antigravity
 */
class Branch extends Model {
    protected string $table = 'branches';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
