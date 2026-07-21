<?php
namespace App\Services;

use App\Core\Database;
use Exception;
use PDO;

/**
 * Tally ERP/Prime XML Voucher Integration & Accounts Export Service
 * Financial Integration Architect - Antigravity
 */
class TallyService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create and record a financial voucher to the ledger queue
     */
    public function createVoucher(
        int $companyId,
        string $voucherType, // 'sales', 'purchase', 'contra', 'payment', 'receipt', 'journal'
        string $voucherNo,
        string $date,
        string $ledgerName,
        float $amount,
        ?string $narration = null,
        ?int $userId = null
    ): int {
        // Generate Tally-compatible XML payload
        $xmlPayload = $this->generateTallyXml($voucherType, $voucherNo, $date, $ledgerName, $amount, $narration);

        $sql = "INSERT INTO tally_vouchers (
                    company_id, voucher_type, voucher_no, date, 
                    ledger_name, amount, narration, xml_payload, 
                    exported, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $companyId,
            $voucherType,
            $voucherNo,
            $date,
            $ledgerName,
            $amount,
            $narration,
            $xmlPayload,
            $userId
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Generate standard Tally XML envelope structure for a voucher import
     */
    public function generateTallyXml(
        string $voucherType,
        string $voucherNo,
        string $date,
        string $ledgerName,
        float $amount,
        ?string $narration = null
    ): string {
        $formattedDate = date('Ymd', strtotime($date));
        $tallyVchType = ucfirst($voucherType);
        $escapedLedger = htmlspecialchars($ledgerName, ENT_XML1, 'UTF-8');
        $escapedNarration = htmlspecialchars($narration ?? '', ENT_XML1, 'UTF-8');
        
        $xml = "<ENVELOPE>\n";
        $xml .= "  <HEADER>\n";
        $xml .= "    <TALLYREQUEST>Import Data</TALLYREQUEST>\n";
        $xml .= "  </HEADER>\n";
        $xml .= "  <BODY>\n";
        $xml .= "    <IMPORTDATA>\n";
        $xml .= "      <REQUESTDESC>\n";
        $xml .= "        <REPORTNAME>All Masters</REPORTNAME>\n";
        $xml .= "      </REQUESTDESC>\n";
        $xml .= "      <REQUESTDATA>\n";
        $xml .= "        <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n";
        $xml .= "          <VOUCHER VCHTYPE=\"{$tallyVchType}\" ACTION=\"Create\">\n";
        $xml .= "            <DATE>{$formattedDate}</DATE>\n";
        $xml .= "            <VOUCHERNUMBER>{$voucherNo}</VOUCHERNUMBER>\n";
        $xml .= "            <PARTYLEDGERNAME>{$escapedLedger}</PARTYLEDGERNAME>\n";
        $xml .= "            <NARRATION>{$escapedNarration}</NARRATION>\n";
        $xml .= "            <EFFECTIVEDATE>{$formattedDate}</EFFECTIVEDATE>\n";
        $xml .= "            <!-- Ledger Entries -->\n";
        $xml .= "            <ALLLEDGERENTRIES.LIST>\n";
        $xml .= "              <LEDGERNAME>{$escapedLedger}</LEDGERNAME>\n";
        $xml .= "              <ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>\n";
        $xml .= "              <AMOUNT>-{$amount}</AMOUNT>\n";
        $xml .= "            </ALLLEDGERENTRIES.LIST>\n";
        $xml .= "            <ALLLEDGERENTRIES.LIST>\n";
        $xml .= "              <LEDGERNAME>Garment Production Ledger</LEDGERNAME>\n";
        $xml .= "              <ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE>\n";
        $xml .= "              <AMOUNT>{$amount}</AMOUNT>\n";
        $xml .= "            </ALLLEDGERENTRIES.LIST>\n";
        $xml .= "          </VOUCHER>\n";
        $xml .= "        </TALLYMESSAGE>\n";
        $xml .= "      </REQUESTDATA>\n";
        $xml .= "    </IMPORTDATA>\n";
        $xml .= "  </BODY>\n";
        $xml .= "</ENVELOPE>";

        return $xml;
    }

    /**
     * Mark vouchers as exported to Tally
     */
    public function markAsExported(array $voucherIds): void {
        if (empty($voucherIds)) return;
        
        $placeholders = implode(',', array_fill(0, count($voucherIds), '?'));
        $sql = "UPDATE tally_vouchers 
                SET exported = 1, exported_at = NOW() 
                WHERE id IN ({$placeholders})";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($voucherIds);
    }

    /**
     * Get list of unexported vouchers for sync queue
     */
    public function getUnexportedVouchers(int $companyId): array {
        $sql = "SELECT * FROM tally_vouchers 
                WHERE company_id = ? AND exported = 0 
                ORDER BY date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$companyId]);
        return $stmt->fetchAll() ?: [];
    }
}
