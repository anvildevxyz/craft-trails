<?php

namespace anvildev\trails\tests\Unit\Dto;

use anvildev\trails\dto\IntegrityReport;
use anvildev\trails\tests\Support\TestCase;

class IntegrityReportTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function makeCleanReport(): IntegrityReport
    {
        return new IntegrityReport(
            totalRecords: 100,
            validHashes: 100,
            invalidHashes: 0,
            invalidHashIds: [],
            chainValid: true,
            chainBrokenAt: null,
            merkleRootsVerified: 5,
            merkleRootsFailed: 0,
            anchorsVerified: 3,
            anchorsFailed: 0,
        );
    }

    // ---------------------------------------------------------------------------
    // testConstruction
    // ---------------------------------------------------------------------------

    public function testConstruction(): void
    {
        $report = new IntegrityReport(
            totalRecords: 200,
            validHashes: 195,
            invalidHashes: 5,
            invalidHashIds: [10, 20, 30, 40, 50],
            chainValid: false,
            chainBrokenAt: 42,
            merkleRootsVerified: 4,
            merkleRootsFailed: 2,
            anchorsVerified: 1,
            anchorsFailed: 1,
        );

        $this->assertSame(200, $report->totalRecords);
        $this->assertSame(195, $report->validHashes);
        $this->assertSame(5, $report->invalidHashes);
        $this->assertSame([10, 20, 30, 40, 50], $report->invalidHashIds);
        $this->assertFalse($report->chainValid);
        $this->assertSame(42, $report->chainBrokenAt);
        $this->assertSame(4, $report->merkleRootsVerified);
        $this->assertSame(2, $report->merkleRootsFailed);
        $this->assertSame(1, $report->anchorsVerified);
        $this->assertSame(1, $report->anchorsFailed);
    }

    public function testConstructionWithNullChainBrokenAt(): void
    {
        $report = $this->makeCleanReport();

        $this->assertNull($report->chainBrokenAt);
    }

    // ---------------------------------------------------------------------------
    // testIsClean
    // ---------------------------------------------------------------------------

    public function testIsClean(): void
    {
        $report = $this->makeCleanReport();

        $this->assertTrue($report->isClean());
    }

    public function testIsCleanReturnsFalseForInvalidHashes(): void
    {
        $report = new IntegrityReport(
            totalRecords: 100,
            validHashes: 98,
            invalidHashes: 2,
            invalidHashIds: [5, 17],
            chainValid: true,
            chainBrokenAt: null,
            merkleRootsVerified: 5,
            merkleRootsFailed: 0,
            anchorsVerified: 3,
            anchorsFailed: 0,
        );

        $this->assertFalse($report->isClean());
    }

    public function testIsCleanReturnsFalseForBrokenChain(): void
    {
        $report = new IntegrityReport(
            totalRecords: 100,
            validHashes: 100,
            invalidHashes: 0,
            invalidHashIds: [],
            chainValid: false,
            chainBrokenAt: 55,
            merkleRootsVerified: 5,
            merkleRootsFailed: 0,
            anchorsVerified: 3,
            anchorsFailed: 0,
        );

        $this->assertFalse($report->isClean());
    }

    public function testIsCleanReturnsFalseForFailedMerkleRoots(): void
    {
        $report = new IntegrityReport(
            totalRecords: 100,
            validHashes: 100,
            invalidHashes: 0,
            invalidHashIds: [],
            chainValid: true,
            chainBrokenAt: null,
            merkleRootsVerified: 4,
            merkleRootsFailed: 1,
            anchorsVerified: 3,
            anchorsFailed: 0,
        );

        $this->assertFalse($report->isClean());
    }

    public function testIsCleanReturnsFalseForFailedAnchors(): void
    {
        $report = new IntegrityReport(
            totalRecords: 100,
            validHashes: 100,
            invalidHashes: 0,
            invalidHashIds: [],
            chainValid: true,
            chainBrokenAt: null,
            merkleRootsVerified: 5,
            merkleRootsFailed: 0,
            anchorsVerified: 2,
            anchorsFailed: 1,
        );

        $this->assertFalse($report->isClean());
    }

    // ---------------------------------------------------------------------------
    // testToArray
    // ---------------------------------------------------------------------------

    public function testToArray(): void
    {
        $report = new IntegrityReport(
            totalRecords: 50,
            validHashes: 48,
            invalidHashes: 2,
            invalidHashIds: [7, 13],
            chainValid: true,
            chainBrokenAt: null,
            merkleRootsVerified: 2,
            merkleRootsFailed: 0,
            anchorsVerified: 1,
            anchorsFailed: 0,
        );

        $array = $report->toArray();

        $expectedKeys = [
            'totalRecords', 'validHashes', 'invalidHashes', 'invalidHashIds',
            'chainValid', 'chainBrokenAt',
            'merkleRootsVerified', 'merkleRootsFailed',
            'anchorsVerified', 'anchorsFailed',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array);
        }

        $this->assertSame(50, $array['totalRecords']);
        $this->assertSame(48, $array['validHashes']);
        $this->assertSame(2, $array['invalidHashes']);
        $this->assertSame([7, 13], $array['invalidHashIds']);
        $this->assertTrue($array['chainValid']);
        $this->assertNull($array['chainBrokenAt']);
        $this->assertSame(2, $array['merkleRootsVerified']);
        $this->assertSame(0, $array['merkleRootsFailed']);
        $this->assertSame(1, $array['anchorsVerified']);
        $this->assertSame(0, $array['anchorsFailed']);

        $this->assertCount(12, $array);
    }

    public function testToArrayIncludesRecordsInRange(): void
    {
        $report = new IntegrityReport(
            totalRecords: 100,
            validHashes: 100,
            invalidHashes: 0,
            invalidHashIds: [],
            chainValid: true,
            chainBrokenAt: null,
            merkleRootsVerified: 5,
            merkleRootsFailed: 0,
            anchorsVerified: 3,
            anchorsFailed: 0,
            recordsInRange: 7,
        );

        $array = $report->toArray();

        $this->assertArrayHasKey('recordsInRange', $array);
        $this->assertSame(7, $array['recordsInRange']);
    }

    public function testRecordsInRangeDefaultsToNullWhenOmitted(): void
    {
        $report = $this->makeCleanReport();

        $this->assertNull($report->recordsInRange);
        $this->assertNull($report->toArray()['recordsInRange']);
    }

    public function testToArrayIncludesOverallStatusPassed(): void
    {
        $array = $this->makeCleanReport()->toArray();

        $this->assertArrayHasKey('overallStatus', $array);
        $this->assertSame('passed', $array['overallStatus']);
    }

    public function testToArrayOverallStatusFailedForInvalidHashes(): void
    {
        $report = new IntegrityReport(
            totalRecords: 100,
            validHashes: 99,
            invalidHashes: 1,
            invalidHashIds: ['trails_logs#42'],
            chainValid: true,
            chainBrokenAt: null,
            merkleRootsVerified: 5,
            merkleRootsFailed: 0,
            anchorsVerified: 3,
            anchorsFailed: 0,
        );

        $this->assertSame('failed', $report->toArray()['overallStatus']);
    }

    public function testToArrayOverallStatusFailedForFailedMerkleRoots(): void
    {
        $report = new IntegrityReport(
            totalRecords: 100,
            validHashes: 100,
            invalidHashes: 0,
            invalidHashIds: [],
            chainValid: true,
            chainBrokenAt: null,
            merkleRootsVerified: 4,
            merkleRootsFailed: 1,
            anchorsVerified: 3,
            anchorsFailed: 0,
        );

        $this->assertSame('failed', $report->toArray()['overallStatus']);
    }

    // ---------------------------------------------------------------------------
    // Structural guards
    // ---------------------------------------------------------------------------

    public function testPropertiesAreReadonly(): void
    {
        $report = $this->makeCleanReport();
        $reflection = new \ReflectionClass($report);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property \${$property->getName()} should be readonly"
            );
        }
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(IntegrityReport::class);

        $this->assertTrue($reflection->isFinal(), 'IntegrityReport must be a final class');
    }
}
