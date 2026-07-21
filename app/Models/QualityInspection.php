<?php
namespace App\Models;

use App\Core\Model;

/**
 * Quality Inspection AQL Audit Model
 * Full Stack Developer - Antigravity
 */
class QualityInspection extends Model {
    protected string $table = 'quality_inspections';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
