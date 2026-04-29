<?php

namespace anvildev\trails\tests\Unit\Dto;

use anvildev\trails\dto\AuditLogEntry;
use anvildev\trails\dto\CursorResult;
use anvildev\trails\tests\Support\TestCase;

class CursorResultTest extends TestCase
{
    public function testProperties(): void
    {
        $entry1 = AuditLogEntry::fromArray(['id' => 1, 'event' => 'element.saved', 'timestamp' => '2026-01-01T00:00:00Z']);
        $entry2 = AuditLogEntry::fromArray(['id' => 2, 'event' => 'user.login', 'timestamp' => '2026-01-01T00:01:00Z']);

        $result = new CursorResult(
            items: [$entry1, $entry2],
            nextCursor: 'eyJpZCI6Mn0=',
            totalCount: 42,
        );

        $this->assertCount(2, $result->items);
        $this->assertSame('eyJpZCI6Mn0=', $result->nextCursor);
        $this->assertSame(42, $result->totalCount);
        $this->assertTrue($result->hasMore());
    }

    public function testHasMoreIsFalseWhenNoCursor(): void
    {
        $entry = AuditLogEntry::fromArray(['id' => 10, 'event' => 'element.deleted', 'timestamp' => '2026-01-02T00:00:00Z']);

        $result = new CursorResult(
            items: [$entry],
            nextCursor: null,
            totalCount: 1,
        );

        $this->assertNull($result->nextCursor);
        $this->assertFalse($result->hasMore());
    }

    public function testEmptyResult(): void
    {
        $result = CursorResult::empty();

        $this->assertSame([], $result->items);
        $this->assertNull($result->nextCursor);
        $this->assertSame(0, $result->totalCount);
        $this->assertFalse($result->hasMore());
    }
}
