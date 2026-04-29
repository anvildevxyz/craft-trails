<?php

declare(strict_types=1);

namespace anvildev\trails\dto;

/**
 * Builds the cache payload written after a CP "Run Verification" click.
 *
 * Pure transformation over the three service result arrays — no Craft/Yii deps.
 */
final class IntegrityRunSummary
{
    /**
     * @param array{verified:int,total:int,tampered:array<int,string>,missing?:array<int,string>,tables?:array} $logResult
     * @param array{verified:int,failed:int,failedIds:array<int,int>} $merkleResult
     * @param array{verified:int,failed:int,failedIds:array<int,int>} $anchorResult
     * @return array{at:int,verified:int,total:int,tampered:array<int,string>,missing:array<int,string>,merkleRootsVerified:int,merkleRootsFailed:int,anchorsVerified:int,anchorsFailed:int,overallStatus:string}
     */
    public static function fromResults(int $at, array $logResult, array $merkleResult, array $anchorResult): array
    {
        $isClean = empty($logResult['tampered'])
            && (int) $merkleResult['failed'] === 0
            && (int) $anchorResult['failed'] === 0;

        return [
            'at' => $at,
            'verified' => (int) $logResult['verified'],
            'total' => (int) $logResult['total'],
            'tampered' => $logResult['tampered'] ?? [],
            'missing' => $logResult['missing'] ?? [],
            'merkleRootsVerified' => (int) $merkleResult['verified'],
            'merkleRootsFailed' => (int) $merkleResult['failed'],
            'anchorsVerified' => (int) $anchorResult['verified'],
            'anchorsFailed' => (int) $anchorResult['failed'],
            'overallStatus' => $isClean ? 'passed' : 'failed',
        ];
    }
}
