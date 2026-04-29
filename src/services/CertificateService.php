<?php

declare(strict_types=1);

namespace anvildev\trails\services;

use anvildev\trails\dto\IntegrityReport;
use anvildev\trails\records\AnchorRecord;
use anvildev\trails\records\MerkleRootRecord;
use anvildev\trails\Trails;
use Craft;
use craft\base\Component;

/**
 * Generates integrity certificates (JSON or PDF) summarising a date-range audit.
 */
class CertificateService extends Component
{
    /**
     * Generate a certificate payload in a supported format.
     *
     * @return array{content:string,contentType:string,extension:string}
     */
    public function generate(string $dateFrom, string $dateTo, string $format = 'json'): array
    {
        $normalizedFormat = strtolower($format);
        if ($normalizedFormat === 'pdf') {
            return [
                'content' => $this->generatePdf($dateFrom, $dateTo),
                'contentType' => 'application/pdf',
                'extension' => 'pdf',
            ];
        }

        return [
            'content' => $this->generateJson($dateFrom, $dateTo),
            'contentType' => 'application/json',
            'extension' => 'json',
        ];
    }

    /**
     * Build an integrity certificate and return it as pretty-printed JSON.
     *
     * The certificate includes a summary of the IntegrityReport, all Merkle roots
     * and anchors that fall within the requested date range, and an HMAC-SHA256
     * signature computed with Craft's securityKey.
     */
    public function generateJson(string $dateFrom, string $dateTo): string
    {
        $report = $this->buildReport($dateFrom, $dateTo, withRecordsInRange: true);
        $roots = $this->getRootsForRange($dateFrom, $dateTo);
        $anchors = $this->getAnchorsForRoots($roots);
        $generatedAt = (new \DateTime())->format('c');

        $merkleRootsData = array_map(static function(MerkleRootRecord $root): array {
            return [
                'id' => (int) $root->id,
                'batchStartPosition' => (int) $root->batchStartPosition,
                'batchEndPosition' => (int) $root->batchEndPosition,
                'recordCount' => (int) $root->recordCount,
                'rootHash' => $root->rootHash,
                'tableName' => $root->tableName,
                'dateComputed' => $root->dateComputed,
            ];
        }, $roots);

        $anchorsData = array_map(static function(AnchorRecord $anchor): array {
            return [
                'id' => (int) $anchor->id,
                'merkleRootId' => (int) $anchor->merkleRootId,
                'anchorType' => $anchor->anchorType,
                'anchorRef' => $anchor->anchorRef,
                'verified' => (bool) $anchor->verified,
                'dateAnchored' => $anchor->dateAnchored,
            ];
        }, $anchors);

        $certificate = [
            'version' => '1.0',
            'generator' => 'Trails/' . Trails::getInstance()->version,
            'dateRange' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'generatedAt' => $generatedAt,
            'summary' => $report->toArray(),
            'merkleRoots' => $merkleRootsData,
            'anchors' => $anchorsData,
        ];

        $payload = json_encode($certificate, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $securityKey = Craft::$app->getConfig()->getGeneral()->securityKey;
        $signature = hash_hmac('sha256', $payload, $securityKey);

        $certificate['signature'] = $signature;

        return json_encode($certificate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Build an integrity certificate and return it as a PDF (or HTML fallback).
     *
     * Renders the `trails/integrity/_certificate` Twig template, then converts
     * to PDF via Dompdf if the library is available. Falls back to raw HTML.
     */
    public function generatePdf(string $dateFrom, string $dateTo): string
    {
        $report = $this->buildReport($dateFrom, $dateTo, withRecordsInRange: true);
        $roots = $this->getRootsForRange($dateFrom, $dateTo);
        $anchors = $this->getAnchorsForRoots($roots);
        $generatedAt = (new \DateTime())->format('Y-m-d H:i:s T');
        $siteName = Craft::$app->getSites()->getCurrentSite()->getName();

        $html = Craft::$app->getView()->renderTemplate('trails/integrity/_certificate', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'report' => $report,
            'roots' => $roots,
            'anchors' => $anchors,
            'generatedAt' => $generatedAt,
            'siteName' => $siteName,
        ]);

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            return $dompdf->output();
        }

        return $html;
    }

    /**
     * Run all verification checks and assemble an IntegrityReport DTO.
     *
     * When $withRecordsInRange is true, also count rows whose dateCreated
     * falls inside [$dateFrom, $dateTo] so the certificate can show the
     * range-scoped count alongside the system-wide totalRecords.
     */
    private function buildReport(string $dateFrom, string $dateTo, bool $withRecordsInRange = false): IntegrityReport
    {
        $trails = Trails::getInstance();

        $logResult = $trails->audit->verifyAllLogs();
        $chainResult = $trails->audit->verifyChainLinks();
        $merkleResult = $trails->merkle->verifyAllRoots();
        $anchorResult = $trails->anchor->verifyAll();

        $tampered = $logResult['tampered'] ?? [];
        $total = (int) ($logResult['total'] ?? 0);
        $verified = (int) ($logResult['verified'] ?? 0);

        // Chain validity considers both per-row hash tampering and chain-link gaps.
        $chainValid = empty($tampered) && (int) ($chainResult['failed'] ?? 0) === 0;
        $chainBrokenAt = null;
        if (!empty($tampered)) {
            // Hash tampering yields a tampered ID list; report the smallest as the break point.
            $chainBrokenAt = (int) min($tampered);
        } elseif (!empty($chainResult['firstFailedAt'])) {
            // No hash tampering but a chain gap was detected — report the first gap position.
            $chainBrokenAt = (int) $chainResult['firstFailedAt'];
        }

        $recordsInRange = $withRecordsInRange
            ? $trails->audit->countRecordsInRange($dateFrom, $dateTo)
            : null;

        return new IntegrityReport(
            totalRecords: $total,
            validHashes: $verified,
            invalidHashes: count($tampered),
            invalidHashIds: $tampered,
            chainValid: $chainValid,
            chainBrokenAt: $chainBrokenAt,
            merkleRootsVerified: (int) ($merkleResult['verified'] ?? 0),
            merkleRootsFailed: (int) ($merkleResult['failed'] ?? 0),
            anchorsVerified: (int) ($anchorResult['verified'] ?? 0),
            anchorsFailed: (int) ($anchorResult['failed'] ?? 0),
            recordsInRange: $recordsInRange,
        );
    }

    /**
     * Query MerkleRootRecords whose dateComputed falls within the given date range.
     *
     * @return MerkleRootRecord[]
     */
    private function getRootsForRange(string $dateFrom, string $dateTo): array
    {
        /** @var MerkleRootRecord[] $records */
        $records = MerkleRootRecord::find()
            ->where(['>=', 'dateComputed', $dateFrom])
            ->andWhere(['<=', 'dateComputed', $dateTo . ' 23:59:59'])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return $records;
    }

    /**
     * Query AnchorRecords whose merkleRootId is among the provided roots.
     *
     * @param MerkleRootRecord[] $roots
     * @return AnchorRecord[]
     */
    private function getAnchorsForRoots(array $roots): array
    {
        if (empty($roots)) {
            return [];
        }

        $rootIds = array_map(static fn(MerkleRootRecord $r) => (int) $r->id, $roots);

        /** @var AnchorRecord[] $anchors */
        $anchors = AnchorRecord::find()
            ->where(['merkleRootId' => $rootIds])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return $anchors;
    }
}
