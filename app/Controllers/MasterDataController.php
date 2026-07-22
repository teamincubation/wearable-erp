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

        $this->renderView('company/masterdata', [
            'title' => 'Master Data Hub | Wearable ERP',
            'contacts' => $contacts,
            'categories' => $categories,
            'warehouses' => $warehouses,
            'branches' => $branches,
            'general_working_hours' => $gwh,
            'shifts' => $shifts,
            'policySettings' => $policySettings,
            'holidays' => $holidays
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

        $this->redirect('company/masterdata');
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
            $this->redirect('company/masterdata');
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

        $this->redirect('company/masterdata');
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

        $this->redirect('company/masterdata');
    }

    /**
     * Auto-Generate Weekly Weekends for a Specific Year
     */
    public function generateWeekends(Request $request, Response $response): void {
        $year = (int)$request->get('year');
        if ($year < 2000 || $year > 2100) {
            Session::setFlash('error', 'Please enter a valid calendar year.');
            $this->redirect('company/masterdata');
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

        $this->redirect('company/masterdata');
    }

    /**
     * Clone Holidays from Source Year to Target Year
     */
    public function cloneHolidays(Request $request, Response $response): void {
        $sourceYear = (int)$request->get('source_year');
        $targetYear = (int)$request->get('target_year');

        if ($sourceYear < 2000 || $targetYear < 2000 || $sourceYear === $targetYear) {
            Session::setFlash('error', 'Please specify different valid calendar years.');
            $this->redirect('company/masterdata');
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

        $this->redirect('company/masterdata');
    }
}
