<?php

namespace anvildev\trails\tests\Unit\Dto;

use anvildev\trails\dto\IntegrityRunSummary;
use anvildev\trails\tests\Support\TestCase;

class IntegrityRunSummaryTest extends TestCase
{
    private function logResult(int $verified = 100, int $total = 100, array $tampered = [], array $missing = []): array
    {
        return [
            'verified' => $verified,
            'total' => $total,
            'tampered' => $tampered,
            'missing' => $missing,
            'tables' => [],
        ];
    }

    public function testFromResultsAssemblesAllFields(): void
    {
        $payload = IntegrityRunSummary::fromResults(
            at: 1_700_000_000,
            logResult: $this->logResult(98, 100, ['trails_logs#42']),
            merkleResult: ['verified' => 4, 'failed' => 1, 'failedIds' => [3]],
            anchorResult: ['verified' => 2, 'failed' => 0, 'failedIds' => []],
        );

        $this->assertSame(1_700_000_000, $payload['at']);
        $this->assertSame(98, $payload['verified']);
        $this->assertSame(100, $payload['total']);
        $this->assertSame(['trails_logs#42'], $payload['tampered']);
        $this->assertSame(4, $payload['merkleRootsVerified']);
        $this->assertSame(1, $payload['merkleRootsFailed']);
        $this->assertSame(2, $payload['anchorsVerified']);
        $this->assertSame(0, $payload['anchorsFailed']);
    }

    public function testOverallStatusPassedWhenEverythingClean(): void
    {
        $payload = IntegrityRunSummary::fromResults(
            at: 1,
            logResult: $this->logResult(),
            merkleResult: ['verified' => 5, 'failed' => 0, 'failedIds' => []],
            anchorResult: ['verified' => 3, 'failed' => 0, 'failedIds' => []],
        );

        $this->assertSame('passed', $payload['overallStatus']);
    }

    public function testOverallStatusFailedWhenAnyDimensionFails(): void
    {
        $payload = IntegrityRunSummary::fromResults(
            at: 1,
            logResult: $this->logResult(),
            merkleResult: ['verified' => 4, 'failed' => 1, 'failedIds' => [7]],
            anchorResult: ['verified' => 3, 'failed' => 0, 'failedIds' => []],
        );

        $this->assertSame('failed', $payload['overallStatus']);
    }

    public function testOverallStatusFailedWhenLogsTampered(): void
    {
        $payload = IntegrityRunSummary::fromResults(
            at: 1,
            logResult: $this->logResult(99, 100, ['trails_logs#5']),
            merkleResult: ['verified' => 5, 'failed' => 0, 'failedIds' => []],
            anchorResult: ['verified' => 3, 'failed' => 0, 'failedIds' => []],
        );

        $this->assertSame('failed', $payload['overallStatus']);
    }

    public function testMissingDefaultsToEmptyArrayWhenAbsent(): void
    {
        $logResult = [
            'verified' => 10,
            'total' => 10,
            'tampered' => [],
            'tables' => [],
        ];
        $payload = IntegrityRunSummary::fromResults(
            at: 1,
            logResult: $logResult,
            merkleResult: ['verified' => 0, 'failed' => 0, 'failedIds' => []],
            anchorResult: ['verified' => 0, 'failed' => 0, 'failedIds' => []],
        );

        $this->assertSame([], $payload['missing']);
    }
}
