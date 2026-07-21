<?php
namespace App\Models;

use App\Core\Model;

/**
 * Garment Technical Specification Pack (Tech Pack / BOM) Model
 * Full Stack Developer - Antigravity
 */
class TechPack extends Model {
    protected string $table = 'tech_packs';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
