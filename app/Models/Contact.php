<?php
namespace App\Models;

use App\Core\Model;

/**
 * Company Contacts (Buyer, Supplier, Customer, Transporter, Agent) Model
 * Full Stack Developer - Antigravity
 */
class Contact extends Model {
    protected string $table = 'contacts';
    protected string $primaryKey = 'id';
    protected bool $isMultiTenant = true;
    protected bool $useSoftDeletes = true;
}
