<?php
namespace App\Models;

use App\Core\Model;

/**
 * Garment Bill of Materials (BOM) Category Model
 * Full Stack Developer - Antigravity
 */
class BomCategory extends Model {
    protected string $table = 'bom_categories';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
