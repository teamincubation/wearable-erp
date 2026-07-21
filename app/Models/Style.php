<?php
namespace App\Models;

use App\Core\Model;

/**
 * Garment Style Master Model
 * Full Stack Developer - Antigravity
 */
class Style extends Model {
    protected string $table = 'styles';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
