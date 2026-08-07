<?php

namespace anvildev\trails\tests\integration;

use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\records\LogMonthRecord;
use anvildev\trails\services\TableRotationService;
use anvildev\trails\Trails;
use Craft;
use craft\test\TestCase;

/**
 * DB-backed tests for the monthly table rotation.
 *
 * Uses a synthetic far-future month so archive-table names can't collide with
 * anything a real rotation may have left in the test database.
 *
 * NOTE: rotate() executes DDL (RENAME/CREATE TABLE), which implicitly commits
 * the wrapping test transaction in MySQL — the usual rollback isolation does
 * NOT apply here, so _after() cleans up explicitly.
 */
class TableRotationIntegrationTest extends TestCase
{
    private const TEST_YEAR = 2030;
    private const TEST_MONTH = 1;

    private TableRotationService $service;
    private \DateTimeImmutable $month;
    private string $archiveName;

    protected function _before(): void
    {
        parent::_before();
        $this->service = Trails::getInstance()->tableRotation;
        $this->month = new \DateTimeImmutable('2030-01-15', new \DateTimeZone('UTC'));
        $this->archiveName = TableRotationService::archiveTableName(self::TEST_YEAR, self::TEST_MONTH);
    }

    protected function _after(): void
    {
        $db = Craft::$app->getDb();

        // Drop the archive created by rotate() and its registry row.
        $db->createCommand()->dropTableIfExists('{{%' . $this->archiveName . '}}')->execute();
        LogMonthRecord::deleteAll(['tableName' => $this->archiveName]);

        // The DDL committed our seeded rows and rotate() rewrote the active
        // registry row — restore both to the installed state.
        AuditLogRecord::deleteAll([]);
        LogMonthRecord::updateAll([
            'dateFrom' => date('Y-m-01'),
            'dateTo' => date('Y-m-t'),
            'rowCount' => 0,
            'firstChainPosition' => null,
            'lastChainPosition' => null,
            'status' => 'active',
        ], ['tableName' => $this->service->getActiveTableName()]);

        // rotate() primes chain-position state other tests depend on.
        Craft::$app->getCache()->delete('trails:chainPosition');
        Craft::$app->getCache()->delete('trails:lastRotation');

        parent::_after();
    }

    /**
     * Regression test for the 2026-08 queue failure: a duplicate rotation in
     * the same month (cache-flush re-enqueue, or a retried queue job) must
     * skip gracefully instead of dying on
     * "Base table or view already exists: trails_logs_YYYY_MM".
     */
    public function testRotateTwiceInSameMonthSkipsSecondRun(): void
    {
        $db = Craft::$app->getDb();

        $this->assertTrue($this->service->rotate($this->month));
        $this->assertFalse($this->service->rotate($this->month));

        // Exactly one registry row for the archive, and the hot table survived.
        $this->assertSame(1, (int) LogMonthRecord::find()->where(['tableName' => $this->archiveName])->count());
        $this->assertTrue($db->tableExists('{{%' . $this->service->getActiveTableName() . '}}', true));
        $this->assertTrue($db->tableExists('{{%' . $this->archiveName . '}}', true));
    }

    public function testIsRotatedForMonth(): void
    {
        $this->assertFalse($this->service->isRotatedForMonth($this->month));

        $this->service->rotate($this->month);

        $this->assertTrue($this->service->isRotatedForMonth($this->month));
    }

    /**
     * The normal registry-UPDATE path must record the archived table's
     * row/chain stats — previously only the out-of-sync backstop path did,
     * leaving every regular archive with rowCount 0.
     */
    public function testRotateRecordsArchiveStatsOnRegistryUpdatePath(): void
    {
        // Rows may have been committed by earlier DDL-committing tests, so
        // compute expectations relative to the live table state.
        $preCount = (int) AuditLogRecord::find()->count();
        $nextPosition = 100;

        foreach ([0, 1, 2] as $i) {
            $record = new AuditLogRecord();
            $record->event = 'rotation.test';
            $record->category = 'rotation';
            $record->hash = hash('sha256', 'rotation' . $i . microtime());
            $record->chainPosition = $nextPosition + $i;
            $record->save();
        }

        $this->assertTrue($this->service->rotate($this->month));

        $archiveRow = LogMonthRecord::findOne(['tableName' => $this->archiveName]);
        $this->assertNotNull($archiveRow);
        $this->assertSame('archived', $archiveRow->status);
        $this->assertSame($preCount + 3, (int) $archiveRow->rowCount);
        $this->assertSame($nextPosition + 2, (int) $archiveRow->lastChainPosition);
        $this->assertNotNull($archiveRow->firstChainPosition);

        // The fresh active row starts empty, and the chain cache continues
        // from the pre-rotate maximum.
        $activeRow = LogMonthRecord::findOne(['tableName' => $this->service->getActiveTableName()]);
        $this->assertNotNull($activeRow);
        $this->assertSame('active', $activeRow->status);
        $this->assertSame(0, (int) $activeRow->rowCount);
        $this->assertSame($nextPosition + 2, (int) Craft::$app->getCache()->get('trails:chainPosition'));
    }
}
