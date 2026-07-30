<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Helpers\QrPayloadParser;

class QrPayloadParserTest extends TestCase {

    /**
     * @dataProvider validQrProvider
     */
    public function testValidQrFormat($qrCode, $expectedBatch, $expectedSize, $expectedSerial) {
        $result = QrPayloadParser::parse($qrCode);
        $this->assertNotNull($result, "Expected valid parse for: $qrCode");
        $this->assertEquals($expectedBatch, $result['batchNo']);
        $this->assertEquals($expectedSize, $result['size']);
        $this->assertEquals($expectedSerial, $result['serial']);
    }

    /**
     * @dataProvider invalidQrProvider
     */
    public function testInvalidQrFormat($qrCode) {
        $result = QrPayloadParser::parse($qrCode);
        $this->assertNull($result, "Expected parse to fail for: $qrCode");
    }

    public static function validQrProvider() {
        return [
            // Standard format
            ['BATCH-123-S-0001', 'BATCH-123', 'S', 1],
            
            // Production number with 0 hyphens
            ['B123-M-0042', 'B123', 'M', 42],
            
            // Production number with 1 hyphen
            ['PO-456-L-0100', 'PO-456', 'L', 100],
            
            // Production number with 3+ hyphens
            ['PO-456-SUB-BATCH-99-XL-0500', 'PO-456-SUB-BATCH-99', 'XL', 500],
            
            // Sizes containing a hyphen or multi-character
            ['BATCH-X-L-0020', 'BATCH', 'X-L', 20],
            ['BATCH-2XL-0030', 'BATCH', '2XL', 30],
            ['BATCH-EXTRA-LARGE-0040', 'BATCH-EXTRA', 'LARGE', 40], // Actually parses as Batch: BATCH-EXTRA, Size: LARGE. If "EXTRA-LARGE" was intended as size, standard format doesn't support a hyphen in size natively without confusing batch unless we parse differently. The current logic pops from right. So this tests current behavior.
            
            // Serials with leading zeros preserved (as int they drop, but rawSerial keeps it)
            ['BATCH-S-0005', 'BATCH', 'S', 5],
            ['BATCH-S-0000', 'BATCH', 'S', 0],
            
            // Very long input
            ['BATCH-REALLY-LONG-PRODUCTION-NUMBER-WITH-MANY-PARTS-S-9999', 'BATCH-REALLY-LONG-PRODUCTION-NUMBER-WITH-MANY-PARTS', 'S', 9999],
        ];
    }

    public static function invalidQrProvider() {
        return [
            // Fewer than 3 segments
            ['BATCH-0001'],
            ['0001'],
            
            // Serial not numeric
            ['BATCH-S-XXXX'],
            ['BATCH-S-123A'],
            
            // Empty string
            [''],
            
            // Whitespace padding (fails if strict, though trim usually happens before parse)
            ['   '],
            
            // Missing hyphen
            ['BATCH_S_0001'],
            
            // Trailing/leading hyphens (might yield empty segments depending on logic)
            ['-BATCH-S-0001'], // Batch becomes empty string if we check for it, currently batchNo = ""
            ['BATCH-S-0001-'], // Serial becomes empty, fails is_numeric
            
            // Unicode / injected characters in serial
            ['BATCH-S-😀0001'],
        ];
    }

    public function testRawSerialIsPreserved() {
        $result = QrPayloadParser::parse('BATCH-S-0075');
        $this->assertEquals(75, $result['serial']);
        $this->assertEquals('0075', $result['rawSerial']);
    }
}
