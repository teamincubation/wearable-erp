<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\TallyVoucher;
use App\Services\TallyService;
use App\Models\AuditLog;

/**
 * Tally Prime Account Integration Exports Controller
 * Full Stack Developer - Antigravity
 */
class TallyController extends Controller {
    /**
     * Vouchers Queue List
     */
    public function vouchers(Request $request, Response $response): void {
        $tallyService = new TallyService();
        $companyId = Session::get('company_id');

        $unexported = $tallyService->getUnexportedVouchers($companyId);
        
        $voucherModel = new TallyVoucher();
        $exported = $voucherModel->findBy(['company_id' => $companyId, 'exported' => 1]);

        $this->renderView('company/tally_vouchers', [
            'title' => 'Tally Integration Queue | ERP',
            'unexported' => $unexported,
            'exported' => $exported
        ]);
    }

    /**
     * Generate Account Voucher manual entry
     */
    public function generateVoucher(Request $request, Response $response): void {
        $vchType = $request->get('voucher_type');
        $vchNo = trim($request->get('voucher_no'));
        $date = $request->get('date');
        $ledger = trim($request->get('ledger_name'));
        $amount = (float)$request->get('amount');
        $narration = trim($request->get('narration'));

        if (empty($vchType) || empty($vchNo) || empty($date) || empty($ledger) || $amount <= 0) {
            Session::setFlash('error', 'All fields are required. Amount must be positive.');
            $this->redirect('company/tally/vouchers');
        }

        $companyId = Session::get('company_id');
        $userId = Session::get('user_id');
        $tallyService = new TallyService();

        try {
            $tallyService->createVoucher(
                $companyId,
                $vchType,
                $vchNo,
                $date,
                $ledger,
                $amount,
                $narration,
                $userId
            );

            AuditLog::log($companyId, $userId, 'create_tally_voucher', 'TallyVoucher', null, null, null, "Created Tally voucher entry: {$vchNo}");
            Session::setFlash('success', 'Voucher added to Tally integration queue.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Failed to generate voucher: ' . $e->getMessage());
        }

        $this->redirect('company/tally/vouchers');
    }

    /**
     * Download Tally-Prime XML import envelope
     */
    public function downloadXml(Request $request, Response $response, string $id): void {
        $voucherModel = new TallyVoucher();
        $voucher = $voucherModel->find($id);

        if (!$voucher || $voucher['company_id'] !== Session::get('company_id')) {
            Session::setFlash('error', 'Voucher not found.');
            $this->redirect('company/tally/vouchers');
        }

        // Mark as exported
        $tallyService = new TallyService();
        $tallyService->markAsExported([$id]);

        // Output XML file attachment
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="tally_voucher_' . $voucher['voucher_no'] . '.xml"');
        echo $voucher['xml_payload'];
        exit;
    }

    /**
     * Export all vouchers to CSV format ledger
     */
    public function exportCsv(Request $request, Response $response): void {
        $companyId = Session::get('company_id');
        $voucherModel = new TallyVoucher();
        $vouchers = $voucherModel->findBy(['company_id' => $companyId]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="tally_ledgers_export.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Voucher ID', 'Voucher Type', 'Voucher Number', 'Date', 'Ledger Name', 'Amount (INR)', 'Narration', 'Export Status']);

        foreach ($vouchers as $v) {
            fputcsv($output, [
                $v['id'],
                strtoupper($v['voucher_type']),
                $v['voucher_no'],
                $v['date'],
                $v['ledger_name'],
                $v['amount'],
                $v['narration'],
                $v['exported'] ? 'Exported' : 'Pending'
            ]);
        }

        fclose($output);
        exit;
    }

    public function deleteVoucher(Request $request, Response $response, string $id): void {
        $model = new TallyVoucher();
        $model->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Tally voucher record deleted successfully.');
        $this->redirect('company/tally/vouchers');
    }
}
