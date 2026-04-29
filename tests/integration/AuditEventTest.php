<?php

namespace anvildev\trails\tests\integration;

use anvildev\trails\events\AuditEvent;
use anvildev\trails\records\AuditLogRecord;
use craft\test\TestCase;

class AuditEventTest extends TestCase
{
    public function testRecordCanBeSet(): void
    {
        $record = new AuditLogRecord();
        $record->event = 'test.event';

        $event = new AuditEvent(['record' => $record]);

        $this->assertInstanceOf(AuditLogRecord::class, $event->record);
        $this->assertEquals('test.event', $event->record->event);
    }
}
