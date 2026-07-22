<?php
namespace App\Models;

use App\Core\Model;

/**
 * Shift Entity Model
 * DevOps & Database Architect - Antigravity
 */
class Shift extends Model {
    protected string $table = 'shifts';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true; // Tenant isolation enabled
}
