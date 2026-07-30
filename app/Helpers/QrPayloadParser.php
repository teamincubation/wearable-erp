<?php
namespace App\Helpers;

class QrPayloadParser {
    /**
     * Parse a QR Code string into its components.
     * Expected format: {production_no}-{SIZE}-{SERIAL4}
     * E.g. BATCH-CODE-001-S-0005
     *
     * @param string $qrCode
     * @return array|null Returns associative array with batchNo, size, serial, or null if invalid format.
     */
    public static function parse(string $qrCode): ?array {
        $parts = explode('-', $qrCode);
        if (count($parts) < 3) {
            return null; // Invalid tag format
        }
        
        $serialStr = array_pop($parts);
        $size = array_pop($parts);
        $batchNo = implode('-', $parts);

        // Make sure serial is numeric
        if (!is_numeric($serialStr)) {
            return null;
        }

        return [
            'batchNo' => $batchNo,
            'size'    => $size,
            'serial'  => (int)$serialStr,
            'rawSerial' => $serialStr // Keep raw string in case leading zeros matter elsewhere
        ];
    }
}
