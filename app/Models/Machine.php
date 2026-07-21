<?php
namespace App\Models;

use App\Core\Model;

/**
 * Production Machine Model
 * Full Stack Developer - Antigravity
 */
class Machine extends Model {
    protected string $table = 'machines';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
