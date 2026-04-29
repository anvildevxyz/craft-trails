<?php

declare(strict_types=1);

namespace anvildev\trails\services;

use anvildev\trails\records\LogMonthRecord;
use Craft;
use craft\base\Component;
use yii\db\Exception as DbException;

class TableRotationService extends Component
{
    /**
     * Generates the archive table name for a given year/month.
     *
     * Example: archiveTableName(2026, 4) → 'trails_logs_2026_04'
     */
    public static function archiveTableName(int $year, int $month, string $tablePrefix = ''): string
    {
        return sprintf('%strails_logs_%04d_%02d', $tablePrefix, $year, $month);
    }

    /**
     * Pure function: given registry entries and an optional date range, returns
     * the table names whose date ranges overlap with the requested range.
     *
     * Dropped tables are always excluded. When both $dateFrom and $dateTo are
     * null every non-dropped table is returned.
     *
     * @param array<int, array{tableName: string, dateFrom: string, dateTo: string, status: string}> $registry
     * @return string[]
     */
    public static function resolveTablesForRange(array $registry, ?string $dateFrom, ?string $dateTo): array
    {
        $tables = [];

        foreach ($registry as $entry) {
            if (($entry['status'] ?? '') === 'dropped') {
                continue;
            }

            // No filter requested — return every non-dropped table.
            if ($dateFrom === null && $dateTo === null) {
                $tables[] = $entry['tableName'];
                continue;
            }

            // Overlap check: entry overlaps with [dateFrom, dateTo] when
            //   entry.dateFrom <= dateTo  AND  entry.dateTo >= dateFrom
            $entryFrom = $entry['dateFrom'];
            $entryTo = $entry['dateTo'];

            $fromOk = $dateTo === null || $entryFrom <= $dateTo;
            $toOk = $dateFrom === null || $entryTo >= $dateFrom;

            if ($fromOk && $toOk) {
                $tables[] = $entry['tableName'];
            }
        }

        return $tables;
    }

    /**
     * Reads all log-month registry entries from the database.
     *
     * @return array<int, array{tableName: string, dateFrom: string, dateTo: string, status: string, rowCount: int}>
     */
    public function getRegistry(): array
    {
        /** @var LogMonthRecord[] $records */
        $records = LogMonthRecord::find()
            ->orderBy(['dateFrom' => SORT_ASC])
            ->all();

        return array_map(static function(LogMonthRecord $r): array {
            return [
                'tableName' => $r->tableName,
                'dateFrom' => $r->dateFrom,
                'dateTo' => $r->dateTo,
                'status' => $r->status,
                'rowCount' => (int) $r->rowCount,
                'firstChainPosition' => $r->firstChainPosition !== null ? (int) $r->firstChainPosition : null,
                'lastChainPosition' => $r->lastChainPosition !== null ? (int) $r->lastChainPosition : null,
            ];
        }, $records);
    }

    /**
     * Returns table names from the registry that overlap with the given date
     * range, querying live DB data.
     *
     * @return string[]
     */
    public function getTablesForDateRange(?string $dateFrom, ?string $dateTo): array
    {
        return self::resolveTablesForRange($this->getRegistry(), $dateFrom, $dateTo);
    }

    /**
     * Returns the name of the currently active (hot) log table.
     */
    public function getActiveTableName(): string
    {
        return 'trails_logs';
    }

    /**
     * Rotates the active table:
     *   1. Renames the active table to an archive name (year_month of now).
     *   2. Creates a fresh active table with the same schema.
     *   3. Updates the registry — marks the old entry archived, inserts a new active entry.
     *
     * @throws DbException
     */
    public function rotate(): void
    {
        $db = Craft::$app->getDb();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        $archiveName = self::archiveTableName($year, $month);
        $activeName = $this->getActiveTableName();

        // Build the first/last day of the current month for registry bookkeeping.
        $dateFrom = $now->format('Y-m-01');
        $dateTo = $now->format('Y-m-t');

        // Hold the same mutex AuditService::log() uses around the chain-position
        // read/write. Without it, a concurrent log write that already read the
        // pre-rotate cache value can commit to the new (empty) active table at
        // position N+1, then this rotate() primes the cache back to N, and the
        // very next log write produces a DUPLICATE chainPosition.
        $chainMutex = Craft::$app->getMutex();
        $chainMutexKey = 'trails_chain_position_lock';
        if (!$chainMutex->acquire($chainMutexKey, 10)) {
            throw new DbException('Trails: could not acquire chain mutex for rotate(); aborting to avoid chain fork.');
        }

        try {
            // Capture the pre-rotate chain state so the post-rotate chain picks
            // up at N+1 instead of restarting at 1. Without this, the first
            // write to the new (empty) active table would compute
            // lastPosition=0 from its MAX() fallback and fork the audit chain.
            $preRotateMaxPos = (int) ((new \yii\db\Query())
                ->from('{{%' . $activeName . '}}')
                ->max('[[chainPosition]]')
                ?: 0);
            $preRotateRowCount = (int) (new \yii\db\Query())->from('{{%' . $activeName . '}}')->count();
            $preRotateMinPos = $preRotateRowCount > 0
                ? (int) ((new \yii\db\Query())->from('{{%' . $activeName . '}}')->min('[[chainPosition]]') ?: 0)
                : 0;

            // 1. Rename active → archive.
            $db->createCommand()
                ->renameTable('{{%' . $activeName . '}}', $archiveName)
                ->execute();

            // 2. Re-create the active table with the same schema. SQL `LIKE`
            //    keeps keys/indexes/defaults consistent with the archive table.
            $db->createCommand("CREATE TABLE {{%{$activeName}}} LIKE {{%{$archiveName}}}")
                ->execute();

            // 3a. Update any existing active registry entry to archived AND
            //     retarget its tableName to the renamed archive table.
            //     Without the tableName update, the registry would keep
            //     pointing at "trails_logs" for the rotated month, breaking
            //     archive-aware verify/query paths.
            LogMonthRecord::updateAll(
                ['status' => 'archived', 'tableName' => $archiveName],
                ['status' => 'active', 'tableName' => $activeName]
            );

            // 3b. Backstop: if UPDATE matched nothing (registry out of sync),
            //     insert a row for the archive so it's discoverable.
            //     Use the actual min/max chainPositions observed in the data
            //     rather than assuming every archive starts at 1 (code review
            //     L1).
            if (LogMonthRecord::findOne(['tableName' => $archiveName]) === null) {
                $record = new LogMonthRecord();
                $record->tableName = $archiveName;
                $record->dateFrom = $dateFrom;
                $record->dateTo = $dateTo;
                $record->rowCount = $preRotateRowCount;
                $record->firstChainPosition = $preRotateMinPos ?: null;
                $record->lastChainPosition = $preRotateMaxPos ?: null;
                $record->status = 'archived';
                $record->save(false);
            }

            // 3c. Ensure the hot table always has an active registry row for
            //     this month.
            $activeRecord = LogMonthRecord::findOne(['tableName' => $activeName]);
            if ($activeRecord === null) {
                $activeRecord = new LogMonthRecord();
                $activeRecord->tableName = $activeName;
            }
            $activeRecord->dateFrom = $dateFrom;
            $activeRecord->dateTo = $dateTo;
            $activeRecord->rowCount = 0;
            $activeRecord->firstChainPosition = null;
            $activeRecord->lastChainPosition = null;
            $activeRecord->status = 'active';
            $activeRecord->save(false);

            // 4. Prime the chainPosition cache so the next write continues from
            //    pre-rotate max. Done inside the chain mutex so a concurrent
            //    log() cannot race between table rename and cache update.
            Craft::$app->getCache()->set('trails:chainPosition', $preRotateMaxPos, 86400 * 35);
        } finally {
            $chainMutex->release($chainMutexKey);
        }

        Craft::info(
            "Trails: rotated active table '{$activeName}' → archive '{$archiveName}' (chain continues at " . ($preRotateMaxPos + 1) . ")",
            'trails'
        );
    }

    /**
     * Drops an archive table and marks its registry entry as 'dropped'.
     *
     * Returns true on success, false if the table was not found in the registry.
     *
     * @throws DbException
     */
    public function dropArchive(string $tableName): bool
    {
        $record = LogMonthRecord::findOne(['tableName' => $tableName]);

        if ($record === null) {
            return false;
        }

        Craft::$app->getDb()->createCommand()
            ->dropTableIfExists($tableName)
            ->execute();

        $record->status = 'dropped';
        $record->save(false);

        Craft::info("Trails: dropped archive table '{$tableName}'", 'trails');

        return true;
    }
}
