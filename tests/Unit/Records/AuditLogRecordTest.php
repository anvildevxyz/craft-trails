<?php

namespace anvildev\trails\tests\Unit\Records;

use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\tests\Support\TestCase;

class AuditLogRecordTest extends TestCase
{
    public function testTableName(): void
    {
        $this->assertEquals('{{%trails_logs}}', AuditLogRecord::tableName());
    }
}
