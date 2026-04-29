<?php

declare(strict_types=1);

namespace anvildev\trails\jobs;

use anvildev\trails\Trails;
use Craft;
use craft\queue\BaseJob;
use yii\db\Query;

/**
 * GDPR Article 17 ("right to be forgotten") handler. Walks every non-dropped
 * partition table — active AND archives — and rewrites userName/userEmail to
 * the deletion sentinel, recomputing each row's integrity hash so the row's
 * self-check still passes.
 *
 * The hash chain breaks at each rewritten row by design: the row's new hash
 * differs from the original, so the next row's prevHash no longer links.
 * That trade-off is unavoidable with a simple HMAC chain — see DEVELOPER_GUIDE
 * "GDPR & Hash Chain Integrity" for details and the Merkle-anchor mitigation.
 */
class AnonymizeUserLogsJob extends BaseJob
{
    private const CHUNK_SIZE = 500;

    public int $userId = 0;

    public function execute($queue): void
    {
        $plugin = Trails::getInstance();
        if ($plugin === null || $this->userId <= 0) {
            return;
        }

        // Pull every non-dropped partition (active + archives). dateFrom/dateTo
        // are null so the rotation service returns all currently-readable
        // tables. Without this, archive rows retain PII forever — a real
        // GDPR violation, not just a known-limitation footnote.
        $tables = $plugin->tableRotation->getTablesForDateRange(null, null);
        if ($tables === []) {
            return;
        }

        $totalProcessed = 0;
        $tableIndex = 0;
        $tableCount = count($tables);

        foreach ($tables as $tableName) {
            $tableIndex++;
            $totalProcessed += $this->anonymizeTable($plugin, $queue, $tableName, $tableIndex, $tableCount);
        }

        Craft::info(
            "Anonymized {$totalProcessed} audit log records across {$tableCount} table(s) for deleted user {$this->userId}",
            'trails'
        );
    }

    private function anonymizeTable(
        Trails $plugin,
        $queue,
        string $tableName,
        int $tableIndex,
        int $tableCount,
    ): int {
        $db = Craft::$app->getDb();
        $lastId = 0;
        $processed = 0;

        while (true) {
            $rows = (new Query())
                ->from($tableName)
                ->where(['userId' => $this->userId])
                ->andWhere(['>', 'id', $lastId])
                ->orderBy(['id' => SORT_ASC])
                ->limit(self::CHUNK_SIZE)
                ->all();

            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $newRow = $row;
                $newRow['userName'] = '[deleted]';
                $newRow['userEmail'] = null;
                $newHash = $plugin->audit->hashRow($newRow);

                $db->createCommand()->update(
                    $tableName,
                    [
                        'userName' => '[deleted]',
                        'userEmail' => null,
                        'hash' => $newHash,
                    ],
                    ['id' => $row['id']]
                )->execute();

                $lastId = (int) $row['id'];
                $processed++;
            }

            // Report progress as (completed tables + fraction of current chunk)
            // / total tables. Bounded at 0.99 until the final loop iteration
            // returns and the parent execute() can flag completion.
            $tableProgress = ($tableIndex - 1 + min(1.0, count($rows) / self::CHUNK_SIZE)) / max($tableCount, 1);
            $this->setProgress($queue, min(0.99, $tableProgress));

            if (count($rows) < self::CHUNK_SIZE) {
                break;
            }
        }

        return $processed;
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('trails', 'Anonymizing audit logs for deleted user');
    }
}
