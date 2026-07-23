<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\Contact;
use App\Models\AuditLog;

/**
 * Buyer / Client Master Controller
 * Garment & Apparel SaaS Platform - Antigravity
 */
class BuyerController extends Controller {
    /**
     * Display Buyers / Clients Directory
     */
    public function index(Request $request, Response $response): void {
        $db = Database::getInstance();
        $companyId = Session::get('company_id');

        // 1. Fetch all buyer contacts for company
        $stmt = $db->prepare("
            SELECT * FROM contacts 
            WHERE company_id = ? AND type = 'buyer' AND deleted_at IS NULL 
            ORDER BY id DESC
        ");
        $stmt->execute([$companyId]);
        $buyers = $stmt->fetchAll() ?: [];

        // 2. Metrics summary
        $totalBuyers = count($buyers);
        $activeBuyers = 0;
        $onHoldBuyers = 0;
        $currencies = [];

        foreach ($buyers as $b) {
            if ($b['status'] === 'active') {
                $activeBuyers++;
            } elseif ($b['status'] === 'on_hold' || $b['status'] === 'inactive') {
                $onHoldBuyers++;
            }
            if (!empty($b['currency'])) {
                $currencies[$b['currency']] = true;
            }
        }

        $this->renderView('company/buyers', [
            'title' => 'Buyers & Clients Master | ERP',
            'buyers' => $buyers,
            'total_buyers' => $totalBuyers,
            'active_buyers' => $activeBuyers,
            'on_hold_buyers' => $onHoldBuyers,
            'currencies_count' => count($currencies)
        ]);
    }

    /**
     * Register New Buyer / Client
     */
    public function create(Request $request, Response $response): void {
        $name = trim($request->get('name'));
        $code = trim($request->get('code'));
        $brandName = trim($request->get('brand_name'));
        $contactPerson = trim($request->get('contact_person'));
        $email = trim($request->get('email'));
        $phone = trim($request->get('phone'));
        $gstin = trim($request->get('gstin'));
        $country = trim($request->get('country')) ?: 'India';
        $currency = trim($request->get('currency')) ?: 'INR';
        $paymentTerms = trim($request->get('payment_terms'));
        $address = trim($request->get('address'));
        $shippingAddress = trim($request->get('shipping_address'));
        $status = $request->get('status') ?: 'active';

        if (empty($name)) {
            Session::setFlash('error', 'Buyer / Client Company Name is required.');
            $this->redirect('company/buyers');
        }

        if (empty($code)) {
            $code = 'BUY-' . rand(1000, 9999);
        }

        $contactModel = new Contact();
        $buyerId = $contactModel->insert([
            'type' => 'buyer',
            'name' => $name,
            'code' => $code,
            'brand_name' => $brandName ?: null,
            'contact_person' => $contactPerson ?: null,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'gstin' => $gstin ?: null,
            'country' => $country,
            'currency' => strtoupper($currency),
            'payment_terms' => $paymentTerms ?: null,
            'address' => $address ?: null,
            'shipping_address' => $shippingAddress ?: null,
            'status' => $status,
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'create_buyer', 'Contact', $buyerId, null, null, "Registered buyer client: {$name} ({$code})");
        Session::setFlash('success', "Buyer client '{$name}' registered successfully.");
        $this->redirect('company/buyers');
    }

    /**
     * Edit Buyer / Client parameters
     */
    public function edit(Request $request, Response $response, string $id): void {
        $contactModel = new Contact();
        $buyer = $contactModel->find($id);

        if (!$buyer || $buyer['type'] !== 'buyer') {
            Session::setFlash('error', 'Buyer record not found.');
            $this->redirect('company/buyers');
        }

        $name = trim($request->get('name')) ?: $buyer['name'];
        $code = trim($request->get('code')) ?: $buyer['code'];
        $brandName = trim($request->get('brand_name'));
        $contactPerson = trim($request->get('contact_person'));
        $email = trim($request->get('email'));
        $phone = trim($request->get('phone'));
        $gstin = trim($request->get('gstin'));
        $country = trim($request->get('country')) ?: 'India';
        $currency = trim($request->get('currency')) ?: 'INR';
        $paymentTerms = trim($request->get('payment_terms'));
        $address = trim($request->get('address'));
        $shippingAddress = trim($request->get('shipping_address'));
        $status = $request->get('status') ?: $buyer['status'];

        $contactModel->update($id, [
            'name' => $name,
            'code' => $code,
            'brand_name' => $brandName ?: null,
            'contact_person' => $contactPerson ?: null,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'gstin' => $gstin ?: null,
            'country' => $country,
            'currency' => strtoupper($currency),
            'payment_terms' => $paymentTerms ?: null,
            'address' => $address ?: null,
            'shipping_address' => $shippingAddress ?: null,
            'status' => $status,
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_buyer', 'Contact', (int)$id, null, null, "Updated buyer client profile: {$name}");
        Session::setFlash('success', "Buyer client '{$name}' details updated successfully.");
        $this->redirect('company/buyers');
    }

    /**
     * Change Buyer Status (Active, Inactive, On Hold)
     */
    public function updateStatus(Request $request, Response $response, string $id): void {
        $contactModel = new Contact();
        $buyer = $contactModel->find($id);

        if (!$buyer || $buyer['type'] !== 'buyer') {
            Session::setFlash('error', 'Buyer record not found.');
            $this->redirect('company/buyers');
        }

        $status = $request->get('status');
        if (in_array($status, ['active', 'inactive', 'on_hold'])) {
            $contactModel->update($id, [
                'status' => $status,
                'updated_by' => Session::get('user_id')
            ]);
            AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'update_buyer_status', 'Contact', (int)$id, null, null, "Changed buyer status to {$status}");
            Session::setFlash('success', "Buyer status updated to " . ucfirst(str_replace('_', ' ', $status)) . ".");
        }

        $this->redirect('company/buyers');
    }

    /**
     * Delete Buyer / Client Record
     */
    public function delete(Request $request, Response $response, string $id): void {
        $contactModel = new Contact();
        $contactModel->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Buyer client profile deleted successfully.');
        $this->redirect('company/buyers');
    }

    /**
     * Download Sample CSV / Excel Template for Buyers & Clients Import
     */
    public function downloadSampleTemplate(Request $request, Response $response): void {
        $filename = 'buyers_import_template.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        fputcsv($output, [
            'Company Name',
            'Buyer Code',
            'Brand Name',
            'Contact Person',
            'Email',
            'Phone',
            'GSTIN/Tax ID',
            'Country',
            'Currency',
            'Payment Terms',
            'Billing Address',
            'Shipping Address'
        ]);

        fputcsv($output, [
            'TOCCO Global Inc',
            'BUY-9001',
            'TOCCO Apparel',
            'John Miller',
            'john@tocco.com',
            '+1-555-0199',
            'GSTIN987654321',
            'United States',
            'USD',
            'Net 30 Days',
            '100 Fashion Ave, New York, NY',
            'Warehouse 4, Port Newark, NJ'
        ]);

        fputcsv($output, [
            'WellGro Trading Co',
            'BUY-9002',
            'WellGro Kids',
            'Priya Sharma',
            'priya@wellgro.in',
            '+91-9876543210',
            '33AAAAA0000A1Z5',
            'India',
            'INR',
            '50% Advance, 50% Delivery',
            'MG Road, Commercial District, Bangalore',
            'Plot 42, Industrial Zone, Hosur'
        ]);

        fclose($output);
        exit();
    }

    /**
     * Import Buyers & Clients from CSV / Excel File
     */
    public function importExcel(Request $request, Response $response): void {
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('error', 'Please select a valid CSV/Excel file to import.');
            $this->redirect('company/buyers');
            return;
        }

        $tmpName = $_FILES['excel_file']['tmp_name'];
        $handle = fopen($tmpName, 'r');
        if (!$handle) {
            Session::setFlash('error', 'Unable to open uploaded file.');
            $this->redirect('company/buyers');
            return;
        }

        // Read header line
        fgetcsv($handle);
        $importedCount = 0;
        $companyId = Session::get('company_id');
        $contactModel = new Contact();

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row) || empty(trim($row[0] ?? ''))) {
                continue;
            }

            $name = trim($row[0]);
            $code = trim($row[1] ?? '');
            $brandName = trim($row[2] ?? '');
            $contactPerson = trim($row[3] ?? '');
            $email = trim($row[4] ?? '');
            $phone = trim($row[5] ?? '');
            $gstin = trim($row[6] ?? '');
            $country = trim($row[7] ?? '') ?: 'India';
            $currency = trim($row[8] ?? '') ?: 'INR';
            $paymentTerms = trim($row[9] ?? '');
            $address = trim($row[10] ?? '');
            $shippingAddress = trim($row[11] ?? '');

            if (empty($code)) {
                $code = 'BUY-' . rand(1000, 9999);
            }

            $contactModel->insert([
                'type' => 'buyer',
                'name' => $name,
                'code' => $code,
                'brand_name' => $brandName ?: null,
                'contact_person' => $contactPerson ?: null,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'gstin' => $gstin ?: null,
                'country' => $country,
                'currency' => strtoupper($currency),
                'payment_terms' => $paymentTerms ?: null,
                'address' => $address ?: null,
                'shipping_address' => $shippingAddress ?: null,
                'status' => 'active',
                'created_by' => Session::get('user_id')
            ]);

            $importedCount++;
        }

        fclose($handle);

        AuditLog::log($companyId, Session::get('user_id'), 'import_buyers', 'Contact', null, null, null, "Imported {$importedCount} buyers & clients via CSV/Excel file.");
        Session::setFlash('success', "Successfully imported {$importedCount} buyers & clients.");
        $this->redirect('company/buyers');
    }
}
