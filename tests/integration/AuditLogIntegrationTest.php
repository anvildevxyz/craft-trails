<?php

namespace anvildev\trails\tests\integration;

use anvildev\trails\events\AuditEvent;
use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\services\AuditService;
use anvildev\trails\Trails;
use craft\test\TestCase;

class AuditLogIntegrationTest extends TestCase
{
    public function testLogCreatesRecord(): void
    {
        $service = Trails::getInstance()->audit;

        $result = $service->log(
            'integration.test',
            'craft\\elements\\Entry',
            999,
            ['title' => 'Integration Test'],
        );

        $this->assertTrue($result);

        $record = AuditLogRecord::find()
            ->where(['event' => 'integration.test'])
            ->one();

        $this->assertNotNull($record);
        $this->assertEquals('integration', $record->category);
        $this->assertEquals('craft\\elements\\Entry', $record->elementType);
        $this->assertEquals(999, $record->elementId);
        $this->assertEquals('Integration Test', $record->elementTitle);
        $this->assertNotEmpty($record->hash);
    }

    public function testLogStoresOldAndNewValues(): void
    {
        $service = Trails::getInstance()->audit;

        $service->log(
            'field.changed',
            null,
            null,
            [],
            ['title' => 'Old Title'],
            ['title' => 'New Title'],
        );

        $record = AuditLogRecord::find()
            ->where(['event' => 'field.changed'])
            ->one();

        $this->assertNotNull($record);

        $old = json_decode($record->oldValue, true);
        $new = json_decode($record->newValue, true);

        $this->assertEquals('Old Title', $old['title']);
        $this->assertEquals('New Title', $new['title']);
    }

    public function testLogStoresMetadata(): void
    {
        $service = Trails::getInstance()->audit;

        $service->log(
            'meta.test',
            null,
            null,
            ['title' => 'Test', 'extra' => 'data'],
        );

        $record = AuditLogRecord::find()
            ->where(['event' => 'meta.test'])
            ->one();

        $this->assertNotNull($record);
        $metadata = json_decode($record->metadata, true);
        $this->assertEquals('Test', $metadata['title']);
        $this->assertEquals('data', $metadata['extra']);
    }

    public function testBeforeLogEventCanSuppressLogging(): void
    {
        $service = Trails::getInstance()->audit;

        // Register a listener that suppresses all 'suppress.me' events
        $service->on(AuditService::EVENT_BEFORE_LOG, function(AuditEvent $event) {
            if ($event->event === 'suppress.me') {
                $event->isValid = false;
            }
        });

        $result = $service->log('suppress.me');
        $this->assertFalse($result);

        $record = AuditLogRecord::find()
            ->where(['event' => 'suppress.me'])
            ->one();

        $this->assertNull($record);
    }

    public function testAfterLogEventFiresWithRecord(): void
    {
        $service = Trails::getInstance()->audit;
        $capturedRecord = null;

        $service->on(AuditService::EVENT_AFTER_LOG, function(AuditEvent $event) use (&$capturedRecord) {
            $capturedRecord = $event->record;
        });

        $service->log('after.test');

        $this->assertNotNull($capturedRecord);
        $this->assertInstanceOf(AuditLogRecord::class, $capturedRecord);
        $this->assertEquals('after.test', $capturedRecord->event);
        $this->assertNotNull($capturedRecord->id);
    }

    public function testGetActivitySummaryReturnsExpectedShape(): void
    {
        $service = Trails::getInstance()->audit;

        // Insert some records
        $service->log('element.created', null, null, ['title' => 'Test']);
        $service->log('element.updated', null, null, ['title' => 'Test']);
        $service->log('element.deleted', null, null, ['title' => 'Test']);

        $summary = $service->getActivitySummary(7);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('totalEvents', $summary);
        $this->assertArrayHasKey('uniqueUsers', $summary);
        $this->assertArrayHasKey('logins', $summary);
        $this->assertArrayHasKey('elementsCreated', $summary);
        $this->assertArrayHasKey('elementsUpdated', $summary);
        $this->assertArrayHasKey('elementsDeleted', $summary);

        $this->assertGreaterThanOrEqual(3, $summary['totalEvents']);
        $this->assertGreaterThanOrEqual(1, $summary['elementsCreated']);
    }

    public function testGetEventTypesReturnsDistinctValues(): void
    {
        $service = Trails::getInstance()->audit;

        $service->log('unique.type.a');
        $service->log('unique.type.b');
        $service->log('unique.type.a'); // duplicate

        $types = $service->getEventTypes();

        $this->assertContains('unique.type.a', $types);
        $this->assertContains('unique.type.b', $types);
    }

    public function testGetCategoriesReturnsDistinctValues(): void
    {
        $service = Trails::getInstance()->audit;

        $service->log('cat1.event');
        $service->log('cat2.event');

        $categories = $service->getCategories();

        $this->assertContains('cat1', $categories);
        $this->assertContains('cat2', $categories);
    }
}
