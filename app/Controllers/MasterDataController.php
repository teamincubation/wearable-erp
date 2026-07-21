<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
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
        $contactModel = new Contact();
        $contacts = $contactModel->all();

        $bomCatModel = new BomCategory();
        $categories = $bomCatModel->all();

        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->all();

        $branchModel = new Branch();
        $branches = $branchModel->all();

        $this->renderView('company/masterdata', [
            'title' => 'Master Data Hub | Wearable ERP',
            'contacts' => $contacts,
            'categories' => $categories,
            'warehouses' => $warehouses,
            'branches' => $branches
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
}
