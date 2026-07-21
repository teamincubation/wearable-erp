<?php
namespace App\Models;

use App\Core\Model;

/**
 * Garment Production Stage Log Entry Model
 * Full Stack Developer - Antigravity
 */
class ProductionStageLog extends Model {
    protected string $table = 'production_stage_logs';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = false; // Manufacturing journal history is immutable
}
