<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\Contact;
use App\Models\BomCategory;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\AuditLog;

/**
 * Master Data Hub Operations Controller
 * Full Stack PHP Engineer - Antigravity
 */
class MasterDataController extends Controller {
    /**
     * Master Data dashboard index page
     */
    public function index(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $stmt = $db->prepare("SELECT * FROM contacts WHERE company_id = ? AND type IN ('supplier', 'transporter', 'agent') AND deleted_at IS NULL ORDER BY id DESC");
        $stmt->execute([$companyId]);
        $contacts = $stmt->fetchAll() ?: [];

        $bomCatModel = new BomCategory();
        $categories = $bomCatModel->all();

        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->all();

        $branchModel = new Branch();
        $branches = $branchModel->all();

        // Fetch General Working Hours setting
        $stmtGwh = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = 'general_working_hours' AND deleted_at IS NULL LIMIT 1");
        $stmtGwh->execute([$companyId]);
        $gwh = (int)($stmtGwh->fetchColumn() ?: 8);

        // Fetch Shifts
        $shiftModel = new \App\Models\Shift();
        $shifts = $shiftModel->all();

        $this->renderView('company/masterdata', [
            'title' => 'Master Data Hub | Wearable ERP',
            'contacts' => $contacts,
            'categories' => $categories,
            'warehouses' => $warehouses,
            'branches' => $branches,
            'general_working_hours' => $gwh,
            'shifts' => $shifts
        ]);
    }

    /**
     * Create Contact (Supplier / Customer / Buyer)
     */
    public function createContact(Request $request, Response $response): void {
        $type = $request->get('type');
        $name = trim($request->get('name'));
        $code = trim($request->get('code'));
        $email = trim($request->get('email'));
        $phone = trim($request->get('phone'));
        $address = trim($request->get('address'));
        $gstin = trim($request->get('gstin'));

        if (empty($type) || empty($name) || empty($code)) {
            Session::setFlash('error', 'Contact Type, Name, and Reference Code are required.');
            $this->redirect('company/masterdata');
        }

        $contactModel = new Contact();
        $id = $contactModel->insert([
            'type' => $type,
            'name' => $name,
            'code' => $code,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'address' => $address ?: null,
            'gstin' => $gstin ?: null,
            'status' => 'active',
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_contact', 'Contact', $id, null, null, "Created contact: {$name} ({$type})");
        Session::setFlash('success', "Contact '{$name}' created successfully.");
        $this->redirect('company/masterdata');
    }

    /**
     * Create BOM Category
     */
    public function createBomCategory(Request $request, Response $response): void {
        $name = trim($request->get('name'));
        $code = trim($request->get('code'));

        if (empty($name) || empty($code)) {
            Session::setFlash('error', 'Category Name and Code are required.');
            $this->redirect('company/masterdata');
        }

        $bomCatModel = new BomCategory();
        $id = $bomCatModel->insert([
            'name' => $name,
            'code' => $code,
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_bom_category', 'BomCategory', $id, null, null, "Created BOM category: {$name}");
        Session::setFlash('success', "BOM Category '{$name}' added successfully.");
        $this->redirect('company/masterdata');
    }

    /**
     * Edit BOM Category
     */
    public function editBomCategory(Request $request, Response $response, string $id): void {
        $bomCatModel = new BomCategory();
        $cat = $bomCatModel->find($id);

        if (!$cat) {
            Session::setFlash('error', 'BOM Category not found.');
            $this->redirect('company/masterdata');
        }

        $name = trim($request->get('name')) ?: $cat['name'];
        $code = trim($request->get('code')) ?: $cat['code'];

        $bomCatModel->update($id, [
            'name' => $name,
            'code' => $code,
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_bom_category', 'BomCategory', (int)$id, null, null, "Updated BOM category: {$name}");
        Session::setFlash('success', "BOM Category '{$name}' updated successfully.");
        $this->redirect('company/masterdata');
    }

    /**
     * Create Warehouse location
     */
    public function createWarehouse(Request $request, Response $response): void {
        $name = trim($request->get('name'));
        $code = trim($request->get('code'));
        $type = $request->get('type');
        $branchId = $request->get('branch_id') ? (int)$request->get('branch_id') : null;

        if (empty($name) || empty($code) || empty($type)) {
            Session::setFlash('error', 'Warehouse Name, Code, and Storage Type are required.');
            $this->redirect('company/masterdata');
        }

        $warehouseModel = new Warehouse();
        $id = $warehouseModel->insert([
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'branch_id' => $branchId,
            'status' => 'active',
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_warehouse', 'Warehouse', $id, null, null, "Created warehouse: {$name}");
        Session::setFlash('success', "Warehouse '{$name}' configured successfully.");
        $this->redirect('company/masterdata');
    }

    /**
     * Create Company Branch Office
     */
    public function createBranch(Request $request, Response $response): void {
        $name = trim($request->get('name'));
        $code = trim($request->get('code'));
        $address = trim($request->get('address'));

        if (empty($name) || empty($code)) {
            Session::setFlash('error', 'Branch Name and Code are required.');
            $this->redirect('company/masterdata');
        }

        $branchModel = new Branch();
        $id = $branchModel->insert([
            'name' => $name,
            'code' => $code,
            'address' => $address ?: null,
            'status' => 'active',
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_branch', 'Branch', $id, null, null, "Created branch: {$name}");
        Session::setFlash('success', "Branch '{$name}' registered successfully.");
        $this->redirect('company/masterdata');
    }

    public function deleteContact(Request $request, Response $response, string $id): void {
        $model = new Contact();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Party contact deleted successfully.');
        $this->redirect('company/masterdata');
    }

    public function deleteBomCategory(Request $request, Response $response, string $id): void {
        $model = new BomCategory();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'BOM category deleted successfully.');
        $this->redirect('company/masterdata');
    }

    public function deleteWarehouse(Request $request, Response $response, string $id): void {
        $model = new Warehouse();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Warehouse location deleted successfully.');
        $this->redirect('company/masterdata');
    }

    public function deleteBranch(Request $request, Response $response, string $id): void {
        $model = new Branch();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Branch office deleted successfully.');
        $this->redirect('company/masterdata');
    }

    /**
     * Update General Working Hours
     */
    public function updateGeneralHours(Request $request, Response $response): void {
        $gwh = (int) $request->get('general_working_hours');
        if ($gwh <= 0) {
            Session::setFlash('error', 'Working hours must be a positive integer.');
            $this->redirect('company/masterdata');
        }

        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        try {
            $stmt = $db->prepare("SELECT id FROM system_settings WHERE company_id = ? AND setting_key = 'general_working_hours' AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$companyId]);
            $settingId = $stmt->fetchColumn();

            if ($settingId) {
                $stmtUpdate = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
                $stmtUpdate->execute([$gwh, Session::get('user_id'), $settingId]);
            } else {
                $stmtInsert = $db->prepare("INSERT INTO system_settings (company_id, setting_key, setting_value, created_by, updated_by) VALUES (?, 'general_working_hours', ?, ?, ?)");
                $stmtInsert->execute([$companyId, $gwh, Session::get('user_id'), Session::get('user_id')]);
            }

            AuditLog::log($companyId, Session::get('user_id'), 'update_gwh', 'SystemSetting', null, null, null, "Updated general working hours to: {$gwh} hours");
            Session::setFlash('success', 'General working hours updated successfully.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to update working hours: ' . $e->getMessage());
        }

        $this->redirect('company/masterdata');
    }

    /**
     * Create Shift Schedule
     */
    public function createShift(Request $request, Response $response): void {
        $name = trim($request->get('name'));
        $startTime = trim($request->get('start_time'));
        $endTime = trim($request->get('end_time'));

        if (empty($name) || empty($startTime) || empty($endTime)) {
            Session::setFlash('error', 'Shift Title, Start Time, and End Time are required.');
            $this->redirect('company/masterdata');
        }

        $shiftModel = new \App\Models\Shift();
        $id = $shiftModel->insert([
            'name' => $name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_shift', 'Shift', $id, null, null, "Created shift: {$name} ({$startTime} - {$endTime})");
        Session::setFlash('success', "Shift schedule '{$name}' created successfully.");
        $this->redirect('company/masterdata');
    }

    /**
     * Edit Shift Schedule
     */
    public function editShift(Request $request, Response $response, string $id): void {
        $shiftModel = new \App\Models\Shift();
        $shift = $shiftModel->find($id);

        if (!$shift) {
            Session::setFlash('error', 'Shift not found.');
            $this->redirect('company/masterdata');
        }

        $name = trim($request->get('name'));
        $startTime = trim($request->get('start_time'));
        $endTime = trim($request->get('end_time'));

        if (empty($name) || empty($startTime) || empty($endTime)) {
            Session::setFlash('error', 'Shift Title, Start Time, and End Time are required.');
            $this->redirect('company/masterdata');
        }

        $shiftModel->update($id, [
            'name' => $name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_shift', 'Shift', $id, $shift, null, "Updated shift schedule to: {$name} ({$startTime} - {$endTime})");
        Session::setFlash('success', 'Shift schedule updated successfully.');
        $this->redirect('company/masterdata');
    }

    /**
     * Delete Shift Schedule
     */
    public function deleteShift(Request $request, Response $response, string $id): void {
        $shiftModel = new \App\Models\Shift();
        $shiftModel->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Shift schedule deleted successfully.');
        $this->redirect('company/masterdata');
    }
}
