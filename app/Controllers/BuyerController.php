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
}
