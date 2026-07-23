<?php
namespace App\Models;

use App\Core\Model;

/**
 * Style Variable Model
 * Managed custom style specifications (categories, GSM, colors, brands, size ranges)
 * Full Stack Developer - Antigravity
 */
class StyleVariable extends Model {
    protected string $table = 'style_variables';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
