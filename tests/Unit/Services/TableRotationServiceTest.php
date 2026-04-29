<?php

declare(strict_types=1);

namespace anvildev\trails\tests\Unit\Services;

use anvildev\trails\services\TableRotationService;
use anvildev\trails\tests\Support\TestCase;

class TableRotationServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // archiveTableName
    // -------------------------------------------------------------------------

    public function testArchiveTableName(): void
    {
        $this->assertSame('trails_logs_2026_04', TableRotationService::archiveTableName(2026, 4));
    }

    public function testArchiveTableNamePadsMonth(): void
    {
        $this->assertSame('trails_logs_2026_01', TableRotationService::archiveTableName(2026, 1));
    }

    public function testArchiveTableNameWithPrefix(): void
    {
        $this->assertSame('craft_trails_logs_2026_12', TableRotationService::archiveTableName(2026, 12, 'craft_'));
    }

    // -------------------------------------------------------------------------
    // resolveTablesForRange
    // -------------------------------------------------------------------------

    /** Build a minimal registry entry. */
    private function entry(string $tableName, string $dateFrom, string $dateTo, string $status = 'active'): array
    {
        return [
            'tableName' => $tableName,
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
            'status'    => $status,
        ];
    }

    public function testGetTablesForDateRangeReturnsActiveForCurrentMonth(): void
    {
        $registry = [
            $this->entry('trails_logs', '2026-04-01', '2026-04-30', 'active'),
        ];

        $result = TableRotationService::resolveTablesForRange($registry, '2026-04-10', '2026-04-20');

        $this->assertSame(['trails_logs'], $result);
    }

    public function testGetTablesForDateRangeSpansMultipleMonths(): void
    {
        $registry = [
            $this->entry('trails_logs_2026_02', '2026-02-01', '2026-02-28', 'archived'),
            $this->entry('trails_logs_2026_03', '2026-03-01', '2026-03-31', 'archived'),
            $this->entry('trails_logs',          '2026-04-01', '2026-04-30', 'active'),
        ];

        $result = TableRotationService::resolveTablesForRange($registry, '2026-02-15', '2026-04-10');

        $this->assertSame([
            'trails_logs_2026_02',
            'trails_logs_2026_03',
            'trails_logs',
        ], $result);
    }

    public function testGetTablesForDateRangeExcludesNonOverlapping(): void
    {
        $registry = [
            $this->entry('trails_logs_2026_02', '2026-02-01', '2026-02-28', 'archived'),
            $this->entry('trails_logs_2026_03', '2026-03-01', '2026-03-31', 'archived'),
            $this->entry('trails_logs',          '2026-04-01', '2026-04-30', 'active'),
        ];

        $result = TableRotationService::resolveTablesForRange($registry, '2026-02-01', '2026-02-28');

        $this->assertSame(['trails_logs_2026_02'], $result);
    }

    public function testGetTablesForDateRangeSkipsDroppedTables(): void
    {
        $registry = [
            $this->entry('trails_logs_2026_01', '2026-01-01', '2026-01-31', 'dropped'),
            $this->entry('trails_logs',          '2026-04-01', '2026-04-30', 'active'),
        ];

        $result = TableRotationService::resolveTablesForRange($registry, '2026-01-01', '2026-04-30');

        $this->assertSame(['trails_logs'], $result);
    }

    public function testGetTablesForDateRangeNullDatesReturnsAllNonDropped(): void
    {
        $registry = [
            $this->entry('trails_logs_2026_01', '2026-01-01', '2026-01-31', 'dropped'),
            $this->entry('trails_logs_2026_02', '2026-02-01', '2026-02-28', 'archived'),
            $this->entry('trails_logs',          '2026-04-01', '2026-04-30', 'active'),
        ];

        $result = TableRotationService::resolveTablesForRange($registry, null, null);

        $this->assertSame([
            'trails_logs_2026_02',
            'trails_logs',
        ], $result);
    }
}
