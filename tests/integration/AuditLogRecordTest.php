<?php

namespace anvildev\trails\tests\integration;

use anvildev\trails\records\AuditLogRecord;
use craft\test\TestCase;

class AuditLogRecordTest extends TestCase
{
    public function testEventIsRequired(): void
    {
        $record = new AuditLogRecord();
        $record->event = '';

        $this->assertFalse($record->validate(['event']));
    }

    public function testEventMaxLength(): void
    {
        $record = new AuditLogRecord();
        $record->event = str_repeat('a', 101);

        $this->assertFalse($record->validate(['event']));
    }

    public function testEventAcceptsValidLength(): void
    {
        $record = new AuditLogRecord();
        $record->event = 'element.created';

        $this->assertTrue($record->validate(['event']));
    }

    public function testCategoryMaxLength(): void
    {
        $record = new AuditLogRecord();
        $record->category = str_repeat('a', 51);

        $this->assertFalse($record->validate(['category']));
    }

    public function testIpAddressMaxLength(): void
    {
        $record = new AuditLogRecord();
        $record->ipAddress = str_repeat('a', 46);

        $this->assertFalse($record->validate(['ipAddress']));
    }

    public function testIpAddressAcceptsIPv6(): void
    {
        $record = new AuditLogRecord();
        $record->ipAddress = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $this->assertTrue($record->validate(['ipAddress']));
    }

    public function testSessionIdMaxLength(): void
    {
        $record = new AuditLogRecord();
        $record->sessionId = str_repeat('a', 65);

        $this->assertFalse($record->validate(['sessionId']));
    }

    public function testHashMaxLength(): void
    {
        $record = new AuditLogRecord();
        $record->hash = hash('sha256', 'test');

        $this->assertTrue($record->validate(['hash']));
    }

    public function testIntegerFieldsAcceptNull(): void
    {
        $record = new AuditLogRecord();
        $record->event = 'test.event';
        $record->elementId = null;
        $record->userId = null;
        $record->siteId = null;

        $this->assertTrue($record->validate(['elementId', 'userId', 'siteId']));
    }

    public function testTextFieldsAcceptJson(): void
    {
        $record = new AuditLogRecord();
        $record->event = 'test.event';
        $record->oldValue = json_encode(['field' => 'old']);
        $record->newValue = json_encode(['field' => 'new']);
        $record->metadata = json_encode(['key' => 'value']);

        $this->assertTrue($record->validate(['oldValue', 'newValue', 'metadata']));
    }

    public function testRecordCanBeSavedAndRetrieved(): void
    {
        $record = new AuditLogRecord();
        $record->event = 'test.integration';
        $record->category = 'test';
        $record->elementType = 'craft\\elements\\Entry';
        $record->elementId = 1;
        $record->elementTitle = 'Integration Test Entry';
        $record->userId = 1;
        $record->userName = 'admin';
        $record->userEmail = 'admin@example.com';
        $record->ipAddress = '127.0.0.1';
        $record->requestUrl = '/test';
        $record->requestMethod = 'POST';
        $record->siteId = 1;
        $record->hash = hash('sha256', 'test');

        $this->assertTrue($record->save(), 'Record should save: ' . json_encode($record->getErrors()));

        $found = AuditLogRecord::findOne($record->id);
        $this->assertNotNull($found);
        $this->assertEquals('test.integration', $found->event);
        $this->assertEquals('Integration Test Entry', $found->elementTitle);
    }
}
