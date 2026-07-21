<?php
namespace App\Models;

use App\Core\Model;

/**
 * Inventory Stock Ledger Transaction Model
 * Full Stack Developer - Antigravity
 */
class InventoryTransaction extends Model {
    protected string $table = 'inventory_transactions';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = false; // Stock ledger transactions are permanent history
}
