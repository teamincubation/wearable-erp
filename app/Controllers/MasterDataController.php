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
use App\Models\StyleVariable;

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

        $whTypeModel = new \App\Models\WarehouseType();
        $warehouseTypes = $whTypeModel->all();

        // Fetch General Working Hours setting
        $stmtGwh = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = 'general_working_hours' AND deleted_at IS NULL LIMIT 1");
        $stmtGwh->execute([$companyId]);
        $gwh = (int)($stmtGwh->fetchColumn() ?: 8);

        // Fetch Shifts
        $shiftModel = new \App\Models\Shift();
        $shifts = $shiftModel->all();

        // Fetch Leave & Cut policies
        $settingsKeys = [
            'leave_allocation_cl' => '12',
            'leave_allocation_sl' => '10',
            'leave_allocation_el' => '15',
            'cut_policy_absent' => '100',
            'cut_policy_lop' => '100',
            'cut_policy_halfday' => '50',
            'overtime_pay_hour_charge' => '150.00'
        ];
        $policySettings = [];
        foreach ($settingsKeys as $key => $default) {
            $stmtSet = $db->prepare("SELECT setting_value FROM system_settings WHERE company_id = ? AND setting_key = ? AND deleted_at IS NULL LIMIT 1");
            $stmtSet->execute([$companyId, $key]);
            $val = $stmtSet->fetchColumn();
            $policySettings[$key] = $val !== false ? $val : $default;
        }

        // Fetch Holidays list
        $stmtHolidays = $db->prepare("SELECT * FROM company_holidays WHERE company_id = ? ORDER BY date DESC");
        $stmtHolidays->execute([$companyId]);
        $holidays = $stmtHolidays->fetchAll() ?: [];

        // Fetch designations list
        $stmtD = $db->prepare("SELECT * FROM designations WHERE company_id = ? AND deleted_at IS NULL ORDER BY title ASC");
        $stmtD->execute([$companyId]);
        $designations = $stmtD->fetchAll() ?: [];

        // Fetch style variables
        $styleVariables = [];
        try {
            $styleVarModel = new StyleVariable();
            $styleVariables = $styleVarModel->all();
        } catch (\Throwable $e) {
            \App\Core\Migrator::runAutoMigration();
            try {
                $styleVarModel = new StyleVariable();
                $styleVariables = $styleVarModel->all();
            } catch (\Throwable $ex) {
                $styleVariables = [];
            }
        }

        $this->renderView('company/masterdata', [
            'title' => 'Master Data Hub | Wearable ERP',
            'contacts' => $contacts,
            'categories' => $categories,
            'warehouses' => $warehouses,
            'branches' => $branches,
            'warehouseTypes' => $warehouseTypes,
            'general_working_hours' => $gwh,
            'shifts' => $shifts,
            'policySettings' => $policySettings,
            'holidays' => $holidays,
            'designations' => $designations,
            'styleVariables' => $styleVariables
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
            $this->redirect('company/masterdata?tab=contacts');
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
        $this->redirect('company/masterdata?tab=contacts');
    }

    /**
     * Create BOM Category
     */
    public function createBomCategory(Request $request, Response $response): void {
        $name = trim($request->get('name'));
        $code = trim($request->get('code'));

        if (empty($name) || empty($code)) {
            Session::setFlash('error', 'Category Name and Code are required.');
            $this->redirect('company/masterdata?tab=categories');
        }

        $bomCatModel = new BomCategory();
        $id = $bomCatModel->insert([
            'name' => $name,
            'code' => $code,
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_bom_category', 'BomCategory', $id, null, null, "Created BOM category: {$name}");
        Session::setFlash('success', "BOM Category '{$name}' added successfully.");
        $this->redirect('company/masterdata?tab=categories');
    }

    /**
     * Edit BOM Category
     */
    public function editBomCategory(Request $request, Response $response, string $id): void {
        $bomCatModel = new BomCategory();
        $cat = $bomCatModel->find($id);

        if (!$cat) {
            Session::setFlash('error', 'BOM Category not found.');
            $this->redirect('company/masterdata?tab=categories');
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
        $this->redirect('company/masterdata?tab=categories');
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
            $this->redirect('company/masterdata?tab=locations');
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
        $this->redirect('company/masterdata?tab=locations');
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
            $this->redirect('company/masterdata?tab=locations');
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
        $this->redirect('company/masterdata?tab=locations');
    }

    /**
     * Edit Company Branch Office
     */
    public function editBranch(Request $request, Response $response, string $id): void {
        $name = trim($request->get('name'));
        $code = trim($request->get('code'));
        $address = trim($request->get('address'));

        if (empty($name) || empty($code)) {
            Session::setFlash('error', 'Branch Name and Code are required.');
            $this->redirect('company/masterdata?tab=locations');
            return;
        }

        $branchModel = new Branch();
        $branchModel->update((int)$id, [
            'name' => $name,
            'code' => $code,
            'address' => $address ?: null,
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_branch', 'Branch', (int)$id, null, null, "Updated branch office: {$name}");
        Session::setFlash('success', "Branch office '{$name}' updated successfully.");
        $this->redirect('company/masterdata?tab=locations');
    }

    /**
     * Edit Warehouse Store
     */
    public function editWarehouse(Request $request, Response $response, string $id): void {
        $name = trim($request->get('name'));
        $code = trim($request->get('code'));
        $type = trim($request->get('type'));
        $branchId = $request->get('branch_id') ? (int)$request->get('branch_id') : null;

        if (empty($name) || empty($code) || empty($type)) {
            Session::setFlash('error', 'Warehouse Name, Code, and Storage Type are required.');
            $this->redirect('company/masterdata?tab=locations');
            return;
        }

        $warehouseModel = new Warehouse();
        $warehouseModel->update((int)$id, [
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'branch_id' => $branchId,
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_warehouse', 'Warehouse', (int)$id, null, null, "Updated warehouse: {$name}");
        Session::setFlash('success', "Warehouse '{$name}' updated successfully.");
        $this->redirect('company/masterdata?tab=locations');
    }

    /**
     * Create Warehouse Storage Type
     */
    public function createWarehouseType(Request $request, Response $response): void {
        $label = trim($request->get('type_label'));
        $key = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($request->get('type_key') ?: $label)));

        if (empty($label)) {
            Session::setFlash('error', 'Storage Type Name is required.');
            $this->redirect('company/masterdata?tab=locations');
            return;
        }

        $typeModel = new \App\Models\WarehouseType();
        $typeModel->insert([
            'type_key' => $key,
            'type_label' => $label,
            'status' => 'active',
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_warehouse_type', 'WarehouseType', null, null, null, "Created storage type: {$label}");
        Session::setFlash('success', "Storage Type '{$label}' registered successfully.");
        $this->redirect('company/masterdata?tab=locations');
    }

    /**
     * Edit Warehouse Storage Type
     */
    public function editWarehouseType(Request $request, Response $response, string $id): void {
        $label = trim($request->get('type_label'));
        $key = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($request->get('type_key') ?: $label)));

        if (empty($label)) {
            Session::setFlash('error', 'Storage Type Name is required.');
            $this->redirect('company/masterdata?tab=locations');
            return;
        }

        $typeModel = new \App\Models\WarehouseType();
        $typeModel->update((int)$id, [
            'type_key' => $key,
            'type_label' => $label,
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_warehouse_type', 'WarehouseType', (int)$id, null, null, "Updated storage type: {$label}");
        Session::setFlash('success', "Storage Type '{$label}' updated successfully.");
        $this->redirect('company/masterdata?tab=locations');
    }

    /**
     * Delete Warehouse Storage Type
     */
    public function deleteWarehouseType(Request $request, Response $response, string $id): void {
        $model = new \App\Models\WarehouseType();
        $model->delete((int)$id, Session::get('user_id'));
        Session::setFlash('success', 'Storage type deleted successfully.');
        $this->redirect('company/masterdata?tab=locations');
    }

    public function deleteContact(Request $request, Response $response, string $id): void {
        $model = new Contact();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Party contact deleted successfully.');
        $this->redirect('company/masterdata?tab=contacts');
    }

    public function deleteBomCategory(Request $request, Response $response, string $id): void {
        $model = new BomCategory();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'BOM category deleted successfully.');
        $this->redirect('company/masterdata?tab=categories');
    }

    public function deleteWarehouse(Request $request, Response $response, string $id): void {
        $model = new Warehouse();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Warehouse location deleted successfully.');
        $this->redirect('company/masterdata?tab=locations');
    }

    public function deleteBranch(Request $request, Response $response, string $id): void {
        $model = new Branch();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Branch office deleted successfully.');
        $this->redirect('company/masterdata?tab=locations');
    }

    /**
     * Update General Working Hours
     */
    public function updateGeneralHours(Request $request, Response $response): void {
        $gwh = (int) $request->get('general_working_hours');
        if ($gwh <= 0) {
            Session::setFlash('error', 'Working hours must be a positive integer.');
            $this->redirect('company/masterdata?tab=shifts');
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

        $this->redirect('company/masterdata?tab=shifts');
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
            $this->redirect('company/masterdata?tab=shifts');
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
        $this->redirect('company/masterdata?tab=shifts');
    }

    /**
     * Edit Shift Schedule
     */
    public function editShift(Request $request, Response $response, string $id): void {
        $shiftModel = new \App\Models\Shift();
        $shift = $shiftModel->find($id);

        if (!$shift) {
            Session::setFlash('error', 'Shift not found.');
            $this->redirect('company/masterdata?tab=shifts');
        }

        $name = trim($request->get('name'));
        $startTime = trim($request->get('start_time'));
        $endTime = trim($request->get('end_time'));

        if (empty($name) || empty($startTime) || empty($endTime)) {
            Session::setFlash('error', 'Shift Title, Start Time, and End Time are required.');
            $this->redirect('company/masterdata?tab=shifts');
        }

        $shiftModel->update($id, [
            'name' => $name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_shift', 'Shift', $id, $shift, null, "Updated shift schedule to: {$name} ({$startTime} - {$endTime})");
        Session::setFlash('success', 'Shift schedule updated successfully.');
        $this->redirect('company/masterdata?tab=shifts');
    }

    /**
     * Delete Shift Schedule
     */
    public function deleteShift(Request $request, Response $response, string $id): void {
        $shiftModel = new \App\Models\Shift();
        $shiftModel->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Shift schedule deleted successfully.');
        $this->redirect('company/masterdata?tab=shifts');
    }

    /**
     * Update HR Leaves Allocation and Salary Cut Policies
     */
    public function updateHrPolicies(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        $keys = [
            'leave_allocation_cl',
            'leave_allocation_sl',
            'leave_allocation_el',
            'cut_policy_absent',
            'cut_policy_lop',
            'cut_policy_halfday',
            'overtime_pay_hour_charge'
        ];

        try {
            foreach ($keys as $key) {
                $value = trim($request->get($key));
                
                $stmtCheck = $db->prepare("SELECT id FROM system_settings WHERE company_id = ? AND setting_key = ? AND deleted_at IS NULL LIMIT 1");
                $stmtCheck->execute([$companyId, $key]);
                $settingId = $stmtCheck->fetchColumn();

                if ($settingId) {
                    $stmtUpdate = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
                    $stmtUpdate->execute([$value, $userId, $settingId]);
                } else {
                    $stmtInsert = $db->prepare("INSERT INTO system_settings (company_id, setting_key, setting_value, created_by, updated_by) VALUES (?, ?, ?, ?, ?)");
                    $stmtInsert->execute([$companyId, $key, $value, $userId, $userId]);
                }
            }

            AuditLog::log($companyId, $userId, 'update_hr_policies', 'SystemSetting', null, null, null, "Updated HR leave allocation and salary cut policy values");
            Session::setFlash('success', 'HR leave allocations and salary cut policies saved successfully.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to update HR policies: ' . $e->getMessage());
        }

        $this->redirect('company/masterdata?tab=hrpolicies');
    }

    /**
     * Create Single Holiday / Weekend Date
     */
    public function createHoliday(Request $request, Response $response): void {
        $name = trim($request->get('name'));
        $date = trim($request->get('date'));
        $type = $request->get('type') ?: 'holiday';

        if (empty($name) || empty($date)) {
            Session::setFlash('error', 'Holiday Title and Date are required.');
            $this->redirect('company/masterdata?tab=hrpolicies');
        }

        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        try {
            $stmt = $db->prepare("INSERT INTO company_holidays (company_id, date, name, type, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$companyId, $date, $name, $type, Session::get('user_id')]);
            
            AuditLog::log($companyId, Session::get('user_id'), 'create_holiday', 'Holiday', $db->lastInsertId(), null, null, "Added {$type}: {$name} on {$date}");
            Session::setFlash('success', "{$type} registered successfully.");
        } catch (\Exception $e) {
            Session::setFlash('error', 'Date already has a registered holiday/weekend.');
        }

        $this->redirect('company/masterdata?tab=hrpolicies');
    }

    /**
     * Delete Holiday
     */
    public function deleteHoliday(Request $request, Response $response, string $id): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        try {
            $stmt = $db->prepare("DELETE FROM company_holidays WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            Session::setFlash('success', 'Holiday/Weekend deleted successfully.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to delete holiday.');
        }

        $this->redirect('company/masterdata?tab=hrpolicies');
    }

    /**
     * Auto-Generate Weekly Weekends for a Specific Year
     */
    public function generateWeekends(Request $request, Response $response): void {
        $year = (int)$request->get('year');
        if ($year < 2000 || $year > 2100) {
            Session::setFlash('error', 'Please enter a valid calendar year.');
            $this->redirect('company/masterdata?tab=hrpolicies');
        }

        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        try {
            $db->beginTransaction();
            $count = 0;
            
            // Loop through all days of the year and find Saturdays and Sundays
            $start = new \DateTime("{$year}-01-01");
            $end = new \DateTime("{$year}-12-31 23:59:59");
            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($start, $interval, $end);

            $stmtCheck = $db->prepare("SELECT id FROM company_holidays WHERE company_id = ? AND date = ? LIMIT 1");
            $stmtInsert = $db->prepare("INSERT INTO company_holidays (company_id, date, name, type, created_by) VALUES (?, ?, ?, 'weekend', ?)");

            foreach ($period as $dateObj) {
                $dayOfWeek = (int)$dateObj->format('w'); // 0 = Sunday, 6 = Saturday
                if ($dayOfWeek === 0 || $dayOfWeek === 6) {
                    $formattedDate = $dateObj->format('Y-m-d');
                    $dayName = $dateObj->format('l');

                    // Check if already registered
                    $stmtCheck->execute([$companyId, $formattedDate]);
                    if (!$stmtCheck->fetch()) {
                        $stmtInsert->execute([$companyId, $formattedDate, "Weekly Weekend ({$dayName})", $userId]);
                        $count++;
                    }
                }
            }

            $db->commit();
            AuditLog::log($companyId, $userId, 'generate_weekends', 'Holiday', null, null, null, "Auto-generated {$count} weekends for the year {$year}");
            Session::setFlash('success', "Successfully generated {$count} weekends for the year {$year}.");
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Failed to generate weekends: ' . $e->getMessage());
        }

        $this->redirect('company/masterdata?tab=hrpolicies');
    }

    /**
     * Clone Holidays from Source Year to Target Year
     */
    public function cloneHolidays(Request $request, Response $response): void {
        $sourceYear = (int)$request->get('source_year');
        $targetYear = (int)$request->get('target_year');

        if ($sourceYear < 2000 || $targetYear < 2000 || $sourceYear === $targetYear) {
            Session::setFlash('error', 'Please specify different valid calendar years.');
            $this->redirect('company/masterdata?tab=hrpolicies');
        }

        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');

        try {
            $db->beginTransaction();
            
            // Get all holidays of type='holiday' from source year
            $stmtSource = $db->prepare("SELECT name, date, type FROM company_holidays WHERE company_id = ? AND YEAR(date) = ?");
            $stmtSource->execute([$companyId, $sourceYear]);
            $sourceHolidays = $stmtSource->fetchAll();

            $count = 0;
            $stmtCheck = $db->prepare("SELECT id FROM company_holidays WHERE company_id = ? AND date = ? LIMIT 1");
            $stmtInsert = $db->prepare("INSERT INTO company_holidays (company_id, date, name, type, created_by) VALUES (?, ?, ?, ?, ?)");

            foreach ($sourceHolidays as $sh) {
                // Map the date to target year keeping month and day
                $dateObj = new \DateTime($sh['date']);
                $newDate = sprintf('%04d-%02d-%02d', $targetYear, $dateObj->format('m'), $dateObj->format('d'));

                $stmtCheck->execute([$companyId, $newDate]);
                if (!$stmtCheck->fetch()) {
                    $stmtInsert->execute([$companyId, $newDate, $sh['name'], $sh['type'], $userId]);
                    $count++;
                }
            }

            $db->commit();
            AuditLog::log($companyId, $userId, 'clone_holidays', 'Holiday', null, null, null, "Cloned {$count} holidays from {$sourceYear} to {$targetYear}");
            Session::setFlash('success', "Successfully cloned {$count} holidays from {$sourceYear} to {$targetYear}.");
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Failed to clone calendar holidays: ' . $e->getMessage());
        }

        $this->redirect('company/masterdata?tab=hrpolicies');
    }

    /**
     * Create a Designation
     */
    public function createDesignation(Request $request, Response $response): void {
        $title = trim($request->get('title'));
        $description = trim($request->get('description'));

        if (empty($title)) {
            Session::setFlash('error', 'Designation Title is required.');
            $this->redirect('company/masterdata?tab=designations');
        }

        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        try {
            // Check uniqueness
            $stmtCheck = $db->prepare("SELECT id FROM designations WHERE company_id = ? AND title = ? AND deleted_at IS NULL LIMIT 1");
            $stmtCheck->execute([companyId, $title]);
            if ($stmtCheck->fetch()) {
                Session::setFlash('error', 'This Designation already exists.');
                $this->redirect('company/masterdata?tab=designations');
            }

            $stmt = $db->prepare("INSERT INTO designations (company_id, title, description, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$companyId, $title, $description ?: null, $userId]);

            AuditLog::log($companyId, $userId, 'create_designation', 'Designation', $db->lastInsertId(), null, null, "Created designation: {$title}");
            Session::setFlash('success', 'Designation created successfully.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to create designation: ' . $e->getMessage());
        }

        $this->redirect('company/masterdata?tab=designations');
    }

    /**
     * Edit a Designation
     */
    public function editDesignation(Request $request, Response $response, string $id): void {
        $title = trim($request->get('title'));
        $description = trim($request->get('description'));

        if (empty($title)) {
            Session::setFlash('error', 'Designation Title is required.');
            $this->redirect('company/masterdata?tab=designations');
        }

        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        try {
            // Check uniqueness excluding current ID
            $stmtCheck = $db->prepare("SELECT id FROM designations WHERE company_id = ? AND title = ? AND id != ? AND deleted_at IS NULL LIMIT 1");
            $stmtCheck->execute([$companyId, $title, $id]);
            if ($stmtCheck->fetch()) {
                Session::setFlash('error', 'Another Designation with this title already exists.');
                $this->redirect('company/masterdata?tab=designations');
            }

            $stmt = $db->prepare("UPDATE designations SET title = ?, description = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$title, $description ?: null, $id, $companyId]);

            AuditLog::log($companyId, $userId, 'edit_designation', 'Designation', $id, null, null, "Updated designation ID {$id} to: {$title}");
            Session::setFlash('success', 'Designation updated successfully.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to update designation: ' . $e->getMessage());
        }

        $this->redirect('company/masterdata?tab=designations');
    }

    /**
     * Delete a Designation
     */
    public function deleteDesignation(Request $request, Response $response, string $id): void {
        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $db = Database::getInstance();

        try {
            $stmt = $db->prepare("UPDATE designations SET deleted_at = NOW() WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);

            AuditLog::log($companyId, $userId, 'delete_designation', 'Designation', $id, null, null, "Deleted designation ID {$id}");
            Session::setFlash('success', 'Designation deleted successfully.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to delete designation: ' . $e->getMessage());
        }

        $this->redirect('company/masterdata?tab=designations');
    }

    /**
     * Create a Style Variable
     */
    public function createStyleVariable(Request $request, Response $response): void {
        $type = trim($request->get('type'));
        $value = trim($request->get('value'));

        if (empty($type) || empty($value)) {
            Session::setFlash('error', 'Variable type and value are required.');
            $this->redirect('company/masterdata?tab=style_vars');
            return;
        }

        $styleVarModel = new StyleVariable();
        $id = $styleVarModel->insert([
            'type' => $type,
            'value' => $value,
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_style_variable', 'StyleVariable', $id, null, null, "Created Style Variable: [{$type}] {$value}");
        Session::setFlash('success', "Style Variable '{$value}' added successfully.");
        $this->redirect('company/masterdata?tab=style_vars');
    }

    /**
     * Edit a Style Variable
     */
    public function editStyleVariable(Request $request, Response $response, string $id): void {
        $styleVarModel = new StyleVariable();
        $var = $styleVarModel->find($id);

        if (!$var) {
            Session::setFlash('error', 'Style Variable not found.');
            $this->redirect('company/masterdata?tab=style_vars');
            return;
        }

        $value = trim($request->get('value'));
        if (empty($value)) {
            Session::setFlash('error', 'Variable value cannot be empty.');
            $this->redirect('company/masterdata?tab=style_vars');
            return;
        }

        $styleVarModel->update($id, [
            'value' => $value,
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_style_variable', 'StyleVariable', (int)$id, null, null, "Updated Style Variable: [{$var['type']}] {$value}");
        Session::setFlash('success', "Style Variable updated successfully.");
        $this->redirect('company/masterdata?tab=style_vars');
    }

    /**
     * Delete a Style Variable
     */
    public function deleteStyleVariable(Request $request, Response $response, string $id): void {
        $styleVarModel = new StyleVariable();
        $var = $styleVarModel->find($id);

        if (!$var) {
            Session::setFlash('error', 'Style Variable not found.');
            $this->redirect('company/masterdata?tab=style_vars');
            return;
        }

        $styleVarModel->delete($id, Session::get('user_id'));

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'delete_style_variable', 'StyleVariable', (int)$id, null, null, "Deleted Style Variable: [{$var['type']}] {$var['value']}");
        Session::setFlash('success', "Style Variable deleted successfully.");
        $this->redirect('company/masterdata?tab=style_vars');
    }
}
