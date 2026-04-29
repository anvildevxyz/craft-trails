<?php

namespace anvildev\trails\tests\integration;

use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\services\RetentionService;
use anvildev\trails\Trails;
use craft\test\TestCase;

class RetentionServiceTest extends TestCase
{
    private RetentionService $service;

    protected function _before(): void
    {
        parent::_before();
        $this->service = Trails::getInstance()->retention;
    }

    public function testCleanupOldLogs(): void
    {
        // Insert an old record (2 years ago)
        $old = new AuditLogRecord();
        $old->event = 'retention.old';
        $old->category = 'retention';
        $old->hash = hash('sha256', 'old' . microtime());
        $old->save();

        // Manually set dateCreated to 2 years ago
        \Craft::$app->getDb()->createCommand()
            ->update(
                AuditLogRecord::tableName(),
                ['dateCreated' => '2022-01-01 00:00:00'],
                ['id' => $old->id]
            )
            ->execute();

        // Insert a recent record
        $recent = new AuditLogRecord();
        $recent->event = 'retention.recent';
        $recent->category = 'retention';
        $recent->hash = hash('sha256', 'recent' . microtime());
        $recent->save();

        // Run cleanup with 30-day retention
        $deleted = $this->service->cleanupOldLogs(30);

        $this->assertGreaterThanOrEqual(1, $deleted);
        $this->assertNull(AuditLogRecord::findOne($old->id));
        $this->assertNotNull(AuditLogRecord::findOne($recent->id));
    }

    public function testGetRetentionStatsReturnsExpectedShape(): void
    {
        // Insert at least one record
        $record = new AuditLogRecord();
        $record->event = 'stats.test';
        $record->category = 'stats';
        $record->hash = hash('sha256', 'stats' . microtime());
        $record->save();

        $stats = $this->service->getRetentionStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('totalLogs', $stats);
        $this->assertArrayHasKey('oldestLogDate', $stats);
        $this->assertArrayHasKey('retentionDays', $stats);
        $this->assertArrayHasKey('logsToDelete', $stats);
        $this->assertGreaterThanOrEqual(1, (int) $stats['totalLogs']);
    }
}
