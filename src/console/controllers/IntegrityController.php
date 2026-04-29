<?php

declare(strict_types=1);

namespace anvildev\trails\console\controllers;

use anvildev\trails\helpers\ChainLinkValidator;
use anvildev\trails\jobs\BackfillChainJob;
use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\Trails;
use Craft;
use craft\console\Controller;
use yii\console\ExitCode;

/**
 * Verify integrity of all audit logs.
 */
class IntegrityController extends Controller
{
    public int $batchSize = 500;
    public bool $verbose = false;

    public string $record = '';
    public string $range = '';
    public string $from = '';
    public string $to = '';
    public string $format = 'json';
    public string $output = '';

    public bool $dryRun = false;
    public string $table = '';
    public bool $force = false;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        if ($actionID === 'verify') {
            $options[] = 'batchSize';
            $options[] = 'verbose';
            $options[] = 'record';
            $options[] = 'range';
        }
        if ($actionID === 'certificate') {
            $options[] = 'from';
            $options[] = 'to';
            $options[] = 'format';
            $options[] = 'output';
        }
        if ($actionID === 'rehash-v3') {
            $options[] = 'batchSize';
            $options[] = 'dryRun';
            $options[] = 'table';
            $options[] = 'force';
            $options[] = 'verbose';
        }
        return $options;
    }

    /**
     * Verify audit log integrity hashes, chain links, Merkle roots, and anchors.
     *
     * Use --record=N to verify a single record by chain position.
     * Use --range=YYYY-MM-DD:YYYY-MM-DD to verify records within a date range.
     * Without flags, verifies all records, chain links, Merkle roots, and anchors.
     */
    public function actionVerify(): int
    {
        if ($this->record !== '') {
            return $this->verifySingleRecord((int)$this->record);
        }

        if ($this->range !== '') {
            return $this->verifyRange($this->range);
        }

        return $this->verifyAll();
    }

    /**
     * Generate an integrity certificate for a date range.
     *
     * Requires --from=YYYY-MM-DD and --to=YYYY-MM-DD.
     * Use --format=json (default), --format=pdf, or --format=json,pdf.
     * Use --output=PATH to write to a specific file (only valid with a single
     * format; with multiple formats, the controller falls back to default
     * filenames so the two outputs do not collide).
     */
    public function actionCertificate(): int
    {
        if ($this->from === '' || $this->to === '') {
            $this->stderr("Error: --from and --to are required.\n");
            return ExitCode::USAGE;
        }

        $formats = array_map('trim', explode(',', $this->format));
        if ($this->output !== '' && count($formats) > 1) {
            $this->stderr("Error: --output cannot be combined with multiple formats.\n");
            return ExitCode::USAGE;
        }

        $date = date('Y-m-d');
        $exitCode = ExitCode::OK;

        foreach ($formats as $format) {
            $normalizedFormat = strtolower($format);
            if (!in_array($normalizedFormat, ['json', 'pdf'], true)) {
                $this->stderr("Unknown format: {$format}. Use 'json' or 'pdf'.\n");
                $exitCode = ExitCode::USAGE;
                continue;
            }

            $generated = Trails::getInstance()->certificate->generate($this->from, $this->to, $normalizedFormat);

            if ($this->output !== '') {
                $path = $this->output;
            } else {
                $filename = "trails-certificate-{$date}.{$generated['extension']}";
                $path = getcwd() . '/' . $filename;
            }

            file_put_contents($path, $generated['content']);
            $this->stdout(strtoupper($generated['extension']) . " certificate written: {$path}\n");
        }

        return $exitCode;
    }

    /**
     * Queue a BackfillChainJob to backfill prevHash values for all records.
     */
    public function actionBackfill(): int
    {
        Craft::$app->getQueue()->push(new BackfillChainJob());
        $this->stdout("Backfill job queued.\n");
        return ExitCode::OK;
    }

    /**
     * Re-hash legacy v1/v2 audit rows using the current (v3) hash format so
     * display-field tampering (elementTitle, userName, userEmail, userAgent,
     * requestUrl, requestMethod, category, siteId, sessionId) is detected
     * retroactively on historical rows.
     *
     * Per-table chain semantics: each table is re-chained independently
     * because rotation pre-Fix-D forked the chain; cross-table continuity
     * only applies to rows written AFTER Fix D landed.
     *
     * Merkle roots tied to rehashed rows are recomputed in the same pass.
     * Any external anchors referencing the old roots will stop verifying —
     * the command aborts if anchors exist unless --force is passed.
     *
     * Flags: --dry-run (count only), --table=<name> (scope), --force,
     *        --batch-size=N, --verbose.
     */
    public function actionRehashV3(): int
    {
        $audit = Trails::getInstance()->audit;
        $merkle = Trails::getInstance()->merkle;
        $tables = $this->nonDroppedRegistryEntries();
        if ($tables === []) {
            $this->stderr("Error: no active/archived tables found in trails_log_months.\n");
            return ExitCode::DATAERR;
        }
        if ($this->table !== '') {
            $tables = array_values(array_filter($tables, fn(array $e) => $e['tableName'] === $this->table));
            if (empty($tables)) {
                $this->stderr("Error: table '{$this->table}' not found in registry.\n");
                return ExitCode::USAGE;
            }
        }

        $anchorCount = (int) (new \yii\db\Query())->from('{{%trails_anchors}}')->count();
        if ($anchorCount > 0 && !$this->force) {
            $this->stderr("Aborting: {$anchorCount} external anchor(s) exist. Rehashing invalidates the root hashes they reference. Pass --force to proceed.\n");
            return ExitCode::DATAERR;
        }

        $securityKey = Craft::$app->getConfig()->getGeneral()->securityKey;
        $totalRehashed = 0;
        $totalSkipped = 0;
        $affectedRootIds = [];

        foreach ($tables as $entry) {
            $tableName = $entry['tableName'];
            $this->stdout("\nProcessing table {$tableName}...\n");

            $count = (int) (new \yii\db\Query())->from('{{%' . $tableName . '}}')->count();
            if ($count === 0) {
                $this->stdout("  (empty)\n");
                continue;
            }

            // Count legacy rows for operator visibility.
            $legacyCount = (int) (new \yii\db\Query())
                ->from('{{%' . $tableName . '}}')
                ->where(['not like', 'hash', 'v3:%', false])
                ->count();

            $this->stdout("  Rows: {$count} total, {$legacyCount} legacy (v1/v2).\n");

            // Recompute roots for this table after rehashing, including reruns.
            $rootIds = (new \yii\db\Query())
                ->select(['id'])
                ->from('{{%trails_merkle_roots}}')
                ->where(['tableName' => [$tableName, '{{%' . $tableName . '}}']])
                ->column();
            foreach ($rootIds as $rid) {
                $affectedRootIds[(int) $rid] = true;
            }

            if ($legacyCount === 0) {
                continue;
            }

            if ($this->dryRun) {
                // Actual rewrite scope: every row with chainPosition, not just legacy hashes.
                $skipsThisTable = (int) (new \yii\db\Query())
                    ->from('{{%' . $tableName . '}}')
                    ->where(['chainPosition' => null])
                    ->count();
                $totalRehashed += $count - $skipsThisTable;
                $totalSkipped += $skipsThisTable;
                continue;
            }

            // Re-chain in chainPosition order so prevHash points to the updated predecessor.
            $skipped = (int) (new \yii\db\Query())
                ->from('{{%' . $tableName . '}}')
                ->where(['chainPosition' => null])
                ->count();

            $prevHashByPosition = null;
            $lastChainPos = 0;
            $rehashed = 0;

            // Paginate by chainPosition to preserve chain order across pages.
            while (true) {
                $rows = (new \yii\db\Query())
                    ->from('{{%' . $tableName . '}}')
                    ->where(['>', 'chainPosition', $lastChainPos])
                    ->andWhere(['not', ['chainPosition' => null]])
                    ->orderBy(['chainPosition' => SORT_ASC])
                    ->limit($this->batchSize)
                    ->all();

                if (empty($rows)) {
                    break;
                }

                foreach ($rows as $row) {
                    $cp = (int) $row['chainPosition'];

                    // Walk prevHash: genesis row has NULL prevHash.
                    $newPrevHash = $cp === 1 ? null : $prevHashByPosition;

                    $hashInput = [
                        'event' => $row['event'],
                        'category' => $row['category'] ?? null,
                        'elementType' => $row['elementType'] ?? null,
                        'elementId' => $row['elementId'] ?? null,
                        'elementTitle' => $row['elementTitle'] ?? null,
                        'userId' => $row['userId'] ?? null,
                        'userName' => $row['userName'] ?? null,
                        'userEmail' => $row['userEmail'] ?? null,
                        'ipAddress' => $row['ipAddress'] ?? null,
                        'userAgent' => $row['userAgent'] ?? null,
                        'requestUrl' => $row['requestUrl'] ?? null,
                        'requestMethod' => $row['requestMethod'] ?? null,
                        'siteId' => $row['siteId'] ?? null,
                        'sessionId' => $row['sessionId'] ?? null,
                        'dateCreated' => (string) ($row['dateCreated'] ?? ''),
                        'oldValue' => $row['oldValue'] ?? null,
                        'newValue' => $row['newValue'] ?? null,
                        'metadata' => $row['metadata'] ?? null,
                        'prevHash' => $newPrevHash,
                    ];

                    $newHash = \anvildev\trails\helpers\HashService::generate($hashInput, $securityKey);

                    Craft::$app->getDb()->createCommand()
                        ->update('{{%' . $tableName . '}}', [
                            'hash' => $newHash,
                            'prevHash' => $newPrevHash,
                        ], ['id' => (int) $row['id']])
                        ->execute();

                    $prevHashByPosition = $newHash;
                    $rehashed++;
                    $lastChainPos = $cp;

                    if ($this->verbose && $rehashed % 100 === 0) {
                        $this->stdout("    ... {$rehashed} / {$legacyCount}\n");
                    }
                }
            }

            $this->stdout("  Rehashed: {$rehashed}, skipped (no chain position): {$skipped}\n");
            $totalRehashed += $rehashed;
            $totalSkipped += $skipped;
        }

        if ($this->dryRun) {
            $this->stdout("\n[dry-run] Would rehash {$totalRehashed} rows; would skip {$totalSkipped}. No writes performed.\n");
            return ExitCode::OK;
        }

        // Recompute affected Merkle roots against the new hashes.
        if (!empty($affectedRootIds)) {
            $this->stdout("\nRecomputing " . count($affectedRootIds) . " Merkle root(s)...\n");
            foreach (array_keys($affectedRootIds) as $rootId) {
                $this->recomputeMerkleRoot((int) $rootId);
            }
        }

        // Reset the chain-position cache so the next log write re-reads MAX
        // from the DB (it will still be correct, but any cached predecessor
        // hash referring to a pre-rehash value is stale).
        Craft::$app->getCache()->delete('trails:chainPosition');

        $this->stdout("\nDone. Rehashed {$totalRehashed} row(s), skipped {$totalSkipped}.\n");
        return ExitCode::OK;
    }

    private function recomputeMerkleRoot(int $rootId): void
    {
        $root = \anvildev\trails\records\MerkleRootRecord::findOne($rootId);
        if ($root === null) {
            return;
        }

        // Normalize: tableName may be stored as the raw name or in Craft's
        // template-literal form ("{{%trails_logs}}"). Strip the wrapper so the
        // Query::from() we build below applies it exactly once.
        $rawTableName = $root->tableName !== '' ? $root->tableName : 'trails_logs';
        if (preg_match('/^\{\{%(.+)%?\}\}$/', $rawTableName, $m)) {
            $rawTableName = rtrim($m[1], '%');
        }

        $hashes = (new \yii\db\Query())
            ->from('{{%' . $rawTableName . '}}')
            ->where(['>=', 'chainPosition', $root->batchStartPosition])
            ->andWhere(['<=', 'chainPosition', $root->batchEndPosition])
            ->orderBy(['chainPosition' => SORT_ASC])
            ->select(['hash'])
            ->column();

        if (empty($hashes)) {
            $this->stderr("  Root #{$rootId}: no rows in range; skipped.\n");
            return;
        }

        $newRootHash = \anvildev\trails\services\MerkleService::computeRoot(array_map('strval', $hashes));
        $root->rootHash = $newRootHash;
        $root->save(false);

        if ($this->verbose) {
            $this->stdout("  Root #{$rootId} ({$rawTableName} positions {$root->batchStartPosition}-{$root->batchEndPosition}): rootHash updated.\n");
        }
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function verifySingleRecord(int $chainPosition): int
    {
        $this->stdout("Verifying record at chain position {$chainPosition}...\n");

        /** @var AuditLogRecord|null $record */
        $record = AuditLogRecord::find()
            ->where(['chainPosition' => $chainPosition])
            ->one();

        if ($record === null) {
            $this->stderr("No record found with chainPosition={$chainPosition}.\n");
            return ExitCode::DATAERR;
        }

        $audit = Trails::getInstance()->audit;
        $hashOk = $audit->verifyLogIntegrity($record);
        $this->stdout(sprintf("  Hash verification: %s\n", $hashOk ? 'OK' : 'FAILED'));

        // Check chain link
        $expectedPrevHash = null;
        if ($record->prevHash !== null && $chainPosition > 1) {
            /** @var AuditLogRecord|null $prev */
            $prev = AuditLogRecord::find()
                ->where(['chainPosition' => $chainPosition - 1])
                ->one();
            $expectedPrevHash = $prev?->hash;
        }

        $linkResult = ChainLinkValidator::validate(
            chainPosition: $chainPosition,
            prevHash: $record->prevHash,
            expectedPrevHash: $expectedPrevHash !== null ? (string) $expectedPrevHash : null,
        );
        $chainOk = $linkResult['status'] === 'ok';
        $this->stdout("  Chain link: {$linkResult['message']}\n");

        // Merkle inclusion proof
        $proof = Trails::getInstance()->merkle->getInclusionProof($chainPosition);
        if ($proof === null) {
            $this->stdout("  Merkle inclusion proof: not available (no Merkle root covers this position)\n");
        } else {
            $this->stdout(sprintf(
                "  Merkle inclusion proof: %s (leaf index %d in tree of %d)\n",
                $proof->verified ? 'verified' : 'FAILED',
                $proof->leafIndex,
                $proof->treeSize,
            ));
        }

        $allOk = $hashOk && $chainOk;
        if (!$allOk) {
            return ExitCode::DATAERR;
        }

        $this->stdout("Record #{$record->id} (chainPosition={$chainPosition}) verified OK.\n");
        return ExitCode::OK;
    }

    private function verifyRange(string $range): int
    {
        $parts = explode(':', $range, 2);
        if (count($parts) !== 2) {
            $this->stderr("Error: --range must be in the format YYYY-MM-DD:YYYY-MM-DD.\n");
            return ExitCode::USAGE;
        }

        [$dateFrom, $dateTo] = $parts;
        $this->stdout("Verifying records in range {$dateFrom} to {$dateTo}...\n");

        $audit = Trails::getInstance()->audit;
        $tables = $this->nonDroppedRegistryEntries();
        if ($tables === []) {
            $this->stderr("No active/archived tables found in trails_log_months.\n");
            return ExitCode::DATAERR;
        }

        $hashVerified = 0;
        $hashFailed = [];
        $chainFailed = [];
        $totalRows = 0;

        foreach ($tables as $entry) {
            $tableName = $entry['tableName'];

            $rows = (new \yii\db\Query())
                ->from('{{%' . $tableName . '}}')
                ->where(['>=', 'dateCreated', $dateFrom])
                ->andWhere(['<=', 'dateCreated', $dateTo . ' 23:59:59'])
                ->andWhere(['is not', 'chainPosition', null])
                ->orderBy(['chainPosition' => SORT_ASC])
                ->all();

            if (empty($rows)) {
                continue;
            }

            $totalRows += count($rows);

            $firstChainPosition = (int) $rows[0]['chainPosition'];
            $previousHash = null;
            $previousChainPosition = 0;

            // Seed the predecessor for the first in-range row so we do not
            // falsely fail links that cross the range boundary.
            if ($firstChainPosition > 1) {
                $previousRow = (new \yii\db\Query())
                    ->from('{{%' . $tableName . '}}')
                    ->select(['chainPosition', 'hash'])
                    ->where(['<', 'chainPosition', $firstChainPosition])
                    ->andWhere(['is not', 'chainPosition', null])
                    ->orderBy(['chainPosition' => SORT_DESC])
                    ->limit(1)
                    ->one();

                if (is_array($previousRow)) {
                    $previousChainPosition = (int) ($previousRow['chainPosition'] ?? 0);
                    $previousHash = (string) ($previousRow['hash'] ?? '');
                }
            }

            foreach ($rows as $row) {
                $id = $tableName . '#' . (int) $row['id'];

                // Hash check — archive-aware (reads columns from $row, not an AR).
                if ($audit->verifyRowIntegrity($row)) {
                    $hashVerified++;
                } else {
                    $hashFailed[] = $id;
                }

                // Chain link check — scoped to the current table. Cross-table
                // chain verification is a separate concern (see Bug D) and is
                // handled by the full verifyAll() path, not the range path.
                $cp = (int) $row['chainPosition'];
                $rowPrevHash = $row['prevHash'] !== null ? (string) $row['prevHash'] : null;

                // Only invoke ChainLinkValidator when the predecessor sits at cp-1.
                // Range boundaries can leave $previousChainPosition behind cp-1
                // (predecessor outside the range was seeded above), so a strict
                // adjacent-position check still applies before validating the link.
                $expectedPrev = ($previousChainPosition === $cp - 1) ? $previousHash : null;
                $linkResult = ChainLinkValidator::validate(
                    chainPosition: $cp,
                    prevHash: $rowPrevHash,
                    expectedPrevHash: $expectedPrev,
                );
                if ($linkResult['status'] !== 'ok') {
                    $chainFailed[] = $id;
                }

                $previousHash = (string) ($row['hash'] ?? '');
                $previousChainPosition = $cp;
            }
        }

        if ($totalRows === 0) {
            $this->stdout("No records with chain positions found in range.\n");
            return ExitCode::OK;
        }

        $this->stdout(sprintf(
            "  Hashes: %d / %d verified (%d failed)\n",
            $hashVerified,
            $totalRows,
            count($hashFailed),
        ));

        if (!empty($hashFailed)) {
            $this->stderr('  Hash-failed IDs: ' . implode(', ', array_slice($hashFailed, 0, 50)) . "\n");
        }

        $this->stdout(sprintf(
            "  Chain links: %d failed\n",
            count($chainFailed),
        ));

        if (!empty($chainFailed)) {
            $this->stderr('  Chain-failed IDs: ' . implode(', ', array_slice($chainFailed, 0, 50)) . "\n");
        }

        // Verify Merkle roots that fall within the range (by dateComputed)
        $merkleResult = $this->verifyMerkleRootsForRange($dateFrom, $dateTo);
        $this->stdout(sprintf(
            "  Merkle roots: %d verified, %d failed\n",
            $merkleResult['verified'],
            $merkleResult['failed'],
        ));

        if (!empty($hashFailed) || !empty($chainFailed) || $merkleResult['failed'] > 0) {
            return ExitCode::DATAERR;
        }

        $this->stdout("Range verification completed OK.\n");
        return ExitCode::OK;
    }

    /**
     * @return array<int, array{tableName: string, status: string}>
     */
    private function nonDroppedRegistryEntries(): array
    {
        $registry = Trails::getInstance()->tableRotation->getRegistry();
        $tables = array_values(array_filter($registry, static fn(array $e) => $e['status'] !== 'dropped'));

        if ($tables === []) {
            Craft::warning(
                'Trails: no active/archived tables found in trails_log_months. Integrity console commands cannot proceed.',
                'trails'
            );
        }

        return $tables;
    }

    private function verifyAll(): int
    {
        $this->stdout("Verifying audit log integrity...\n");

        $audit = Trails::getInstance()->audit;
        $lastPercent = 0;

        $result = $audit->verifyAllLogs(
            $this->batchSize,
            function(int $verified, int $total, array $tampered) use (&$lastPercent): void {
                if ($this->verbose) {
                    $this->stdout(sprintf("  %d / %d verified (%d tampered so far)\n", $verified, $total, count($tampered)));
                } elseif ($total > 0) {
                    $processed = $verified + count($tampered);
                    $percent = (int)(($processed / $total) * 100);
                    if ($percent > $lastPercent && $percent % 10 === 0) {
                        $this->stdout("  {$percent}% ({$processed}/{$total})\n");
                        $lastPercent = $percent;
                    }
                }
            }
        );

        $this->stdout(sprintf(
            "\nHash verification: %d / %d\n",
            $result['verified'],
            $result['total'],
        ));

        if (count($result['tampered']) > 0) {
            $this->stderr(sprintf("Tampered records: %d\n", count($result['tampered'])));
            $ids = array_slice($result['tampered'], 0, 50);
            $this->stderr('  IDs: ' . implode(', ', $ids) . "\n");
            if (count($result['tampered']) > 50) {
                $this->stderr('  ... and ' . (count($result['tampered']) - 50) . " more\n");
            }
        }

        // Chain link verification
        $this->stdout("\nVerifying chain links...\n");
        $chainResult = $audit->verifyChainLinks($this->batchSize);
        $this->stdout(sprintf(
            "Chain links: %d verified, %d failed\n",
            $chainResult['verified'],
            $chainResult['failed'],
        ));

        if (!empty($chainResult['failedIds'])) {
            $ids = array_slice($chainResult['failedIds'], 0, 50);
            $this->stderr('  Chain-broken IDs: ' . implode(', ', $ids) . "\n");
        }

        // Merkle root verification
        $this->stdout("\nVerifying Merkle roots...\n");
        $merkleResult = Trails::getInstance()->merkle->verifyAllRoots();
        $this->stdout(sprintf(
            "Merkle roots: %d verified, %d failed\n",
            $merkleResult['verified'],
            $merkleResult['failed'],
        ));

        if (!empty($merkleResult['failedIds'])) {
            $ids = array_slice($merkleResult['failedIds'], 0, 50);
            $this->stderr('  Failed Merkle root IDs: ' . implode(', ', $ids) . "\n");
        }

        // Anchor verification
        $this->stdout("\nVerifying anchors...\n");
        $anchorResult = Trails::getInstance()->anchor->verifyAll();
        $this->stdout(sprintf(
            "Anchors: %d verified, %d failed\n",
            $anchorResult['verified'],
            $anchorResult['failed'],
        ));

        if (!empty($anchorResult['failedIds'])) {
            $ids = array_slice($anchorResult['failedIds'], 0, 50);
            $this->stderr('  Failed anchor IDs: ' . implode(', ', $ids) . "\n");
        }

        $anyFailure = count($result['tampered']) > 0
            || $chainResult['failed'] > 0
            || $merkleResult['failed'] > 0
            || $anchorResult['failed'] > 0;

        if ($anyFailure) {
            return ExitCode::DATAERR;
        }

        $this->stdout("\nAll integrity checks passed OK.\n");
        return ExitCode::OK;
    }

    /**
     * Verify Merkle roots whose dateComputed falls within the given date range.
     *
     * @return array{verified: int, failed: int, failedIds: int[]}
     */
    private function verifyMerkleRootsForRange(string $dateFrom, string $dateTo): array
    {
        $merkle = Trails::getInstance()->merkle;

        // Delegate to the service but filter by date range
        $roots = \anvildev\trails\records\MerkleRootRecord::find()
            ->where(['>=', 'dateComputed', $dateFrom])
            ->andWhere(['<=', 'dateComputed', $dateTo . ' 23:59:59'])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $verified = 0;
        $failed = 0;
        $failedIds = [];

        foreach ($roots as $root) {
            $records = AuditLogRecord::find()
                ->where(['>=', 'chainPosition', $root->batchStartPosition])
                ->andWhere(['<=', 'chainPosition', $root->batchEndPosition])
                ->orderBy(['chainPosition' => SORT_ASC])
                ->all();

            if (empty($records)) {
                $failed++;
                $failedIds[] = (int)$root->id;
                continue;
            }

            $hashes = array_map(static fn($r) => (string)$r->hash, $records);
            try {
                $recomputed = \anvildev\trails\services\MerkleService::computeRoot($hashes);
            } catch (\InvalidArgumentException) {
                $failed++;
                $failedIds[] = (int)$root->id;
                continue;
            }

            if (hash_equals((string)$root->rootHash, $recomputed)) {
                $verified++;
            } else {
                $failed++;
                $failedIds[] = (int)$root->id;
            }
        }

        return [
            'verified' => $verified,
            'failed' => $failed,
            'failedIds' => $failedIds,
        ];
    }
}
