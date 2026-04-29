<?php

namespace anvildev\trails\tests\Unit\Dto;

use anvildev\trails\dto\AuditLogEntry;
use anvildev\trails\tests\Support\TestCase;

class AuditLogEntryTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function fullData(): array
    {
        return [
            'id' => 42,
            'event' => 'element.saved',
            'dateCreated' => '2026-01-15 10:30:00',
            'category' => 'entries',
            'elementType' => 'craft\\elements\\Entry',
            'elementId' => 7,
            'elementTitle' => 'My Blog Post',
            'userId' => 3,
            'userName' => 'alice',
            'userEmail' => 'alice@example.com',
            'ipAddress' => '192.168.1.1',
            'country' => 'US',
            'region' => 'CA',
            'city' => 'San Francisco',
            'userAgent' => 'Mozilla/5.0',
            'requestUrl' => 'https://example.com/admin/entries/7',
            'requestMethod' => 'POST',
            'siteId' => 1,
            'oldValue' => '{"title":"Old Title"}',
            'newValue' => '{"title":"My Blog Post"}',
            'metadata' => '{"source":"cp","version":2}',
            'sessionId' => 'sess_abc123',
            'hash' => 'sha256hashvalue',
            'chainPosition' => 5,
            'prevHash' => 'sha256prevhashvalue',
        ];
    }

    // ---------------------------------------------------------------------------
    // testConstructFromArray
    // ---------------------------------------------------------------------------

    public function testConstructFromArray(): void
    {
        $entry = AuditLogEntry::fromArray($this->fullData());

        $this->assertSame(42, $entry->id);
        $this->assertSame('element.saved', $entry->event);
        $this->assertSame('2026-01-15 10:30:00', $entry->dateCreated);
        $this->assertSame('entries', $entry->category);
        $this->assertSame('craft\\elements\\Entry', $entry->elementType);
        $this->assertSame(7, $entry->elementId);
        $this->assertSame('My Blog Post', $entry->elementTitle);
        $this->assertSame(3, $entry->userId);
        $this->assertSame('alice', $entry->userName);
        $this->assertSame('alice@example.com', $entry->userEmail);
        $this->assertSame('192.168.1.1', $entry->ipAddress);
        $this->assertSame('US', $entry->country);
        $this->assertSame('CA', $entry->region);
        $this->assertSame('San Francisco', $entry->city);
        $this->assertSame('Mozilla/5.0', $entry->userAgent);
        $this->assertSame('https://example.com/admin/entries/7', $entry->requestUrl);
        $this->assertSame('POST', $entry->requestMethod);
        $this->assertSame(1, $entry->siteId);
        $this->assertSame('{"title":"Old Title"}', $entry->oldValue);
        $this->assertSame('{"title":"My Blog Post"}', $entry->newValue);
        $this->assertSame('{"source":"cp","version":2}', $entry->metadata);
        $this->assertSame('sess_abc123', $entry->sessionId);
        $this->assertSame('sha256hashvalue', $entry->hash);
        $this->assertSame(5, $entry->chainPosition);
        $this->assertSame('sha256prevhashvalue', $entry->prevHash);
    }

    public function testFromArrayCastsIdToInt(): void
    {
        $entry = AuditLogEntry::fromArray(['id' => '99', 'event' => 'test', 'dateCreated' => '2026-01-01']);

        $this->assertSame(99, $entry->id);
    }

    // ---------------------------------------------------------------------------
    // testMissingOptionalFieldsDefaultToNull
    // ---------------------------------------------------------------------------

    public function testMissingOptionalFieldsDefaultToNull(): void
    {
        $entry = AuditLogEntry::fromArray([
            'id' => 1,
            'event' => 'element.saved',
            'dateCreated' => '2026-01-01 00:00:00',
        ]);

        $this->assertNull($entry->category);
        $this->assertNull($entry->elementType);
        $this->assertNull($entry->elementId);
        $this->assertNull($entry->elementTitle);
        $this->assertNull($entry->userId);
        $this->assertNull($entry->userName);
        $this->assertNull($entry->userEmail);
        $this->assertNull($entry->ipAddress);
        $this->assertNull($entry->country);
        $this->assertNull($entry->region);
        $this->assertNull($entry->city);
        $this->assertNull($entry->userAgent);
        $this->assertNull($entry->requestUrl);
        $this->assertNull($entry->requestMethod);
        $this->assertNull($entry->siteId);
        $this->assertNull($entry->oldValue);
        $this->assertNull($entry->newValue);
        $this->assertNull($entry->metadata);
        $this->assertNull($entry->sessionId);
        $this->assertNull($entry->hash);
        $this->assertNull($entry->chainPosition);
        $this->assertNull($entry->prevHash);
    }

    public function testFromArrayWithCompletelyEmptyArray(): void
    {
        $entry = AuditLogEntry::fromArray([]);

        $this->assertSame(0, $entry->id);
        $this->assertSame('', $entry->event);
        $this->assertSame('', $entry->dateCreated);
        $this->assertNull($entry->category);
        $this->assertNull($entry->userId);
    }

    // ---------------------------------------------------------------------------
    // testDecodedMetadata
    // ---------------------------------------------------------------------------

    public function testDecodedMetadata(): void
    {
        $entry = AuditLogEntry::fromArray(array_merge($this->fullData(), [
            'metadata' => '{"source":"cp","version":2}',
        ]));

        $decoded = $entry->decodedMetadata();

        $this->assertIsArray($decoded);
        $this->assertSame('cp', $decoded['source']);
        $this->assertSame(2, $decoded['version']);
    }

    public function testDecodedMetadataReturnsNullWhenEmpty(): void
    {
        $entry = AuditLogEntry::fromArray([
            'id' => 1,
            'event' => 'element.saved',
            'dateCreated' => '2026-01-01 00:00:00',
        ]);

        $this->assertNull($entry->decodedMetadata());
    }

    public function testDecodedMetadataReturnsNullForInvalidJson(): void
    {
        $entry = AuditLogEntry::fromArray([
            'id' => 1,
            'event' => 'element.saved',
            'dateCreated' => '2026-01-01 00:00:00',
            'metadata' => 'not-valid-json',
        ]);

        $this->assertNull($entry->decodedMetadata());
    }

    // ---------------------------------------------------------------------------
    // testDecodedOldValueAndNewValue
    // ---------------------------------------------------------------------------

    public function testDecodedOldValueAndNewValue(): void
    {
        $entry = AuditLogEntry::fromArray(array_merge($this->fullData(), [
            'oldValue' => '{"title":"Old Title","status":"disabled"}',
            'newValue' => '{"title":"New Title","status":"enabled"}',
        ]));

        $old = $entry->decodedOldValue();
        $new = $entry->decodedNewValue();

        $this->assertIsArray($old);
        $this->assertSame('Old Title', $old['title']);
        $this->assertSame('disabled', $old['status']);

        $this->assertIsArray($new);
        $this->assertSame('New Title', $new['title']);
        $this->assertSame('enabled', $new['status']);
    }

    public function testDecodedOldValueReturnsNullWhenNotSet(): void
    {
        $entry = AuditLogEntry::fromArray([
            'id' => 1,
            'event' => 'element.saved',
            'dateCreated' => '2026-01-01 00:00:00',
        ]);

        $this->assertNull($entry->decodedOldValue());
        $this->assertNull($entry->decodedNewValue());
    }

    public function testDecodedOldValueReturnsNullForInvalidJson(): void
    {
        $entry = AuditLogEntry::fromArray([
            'id' => 1,
            'event' => 'element.saved',
            'dateCreated' => '2026-01-01 00:00:00',
            'oldValue' => 'invalid json string',
            'newValue' => 'also invalid',
        ]);

        $this->assertNull($entry->decodedOldValue());
        $this->assertNull($entry->decodedNewValue());
    }

    // ---------------------------------------------------------------------------
    // testFromRecord
    // ---------------------------------------------------------------------------

    public function testFromRecord(): void
    {
        $record = new \stdClass();
        $record->id = 10;
        $record->event = 'user.login';
        $record->dateCreated = '2026-02-01 08:00:00';
        $record->userId = 5;
        $record->userName = 'bob';
        $record->userEmail = 'bob@example.com';
        $record->category = null;
        $record->elementType = null;
        $record->elementId = null;
        $record->elementTitle = null;
        $record->ipAddress = '10.0.0.1';
        $record->country = 'DE';
        $record->region = null;
        $record->city = 'Berlin';
        $record->userAgent = 'Safari/15.0';
        $record->requestUrl = '/login';
        $record->requestMethod = 'POST';
        $record->siteId = 1;
        $record->oldValue = null;
        $record->newValue = null;
        $record->metadata = null;
        $record->sessionId = 'sess_xyz';
        $record->hash = 'hashvalue';
        $record->chainPosition = 1;
        $record->prevHash = null;

        $entry = AuditLogEntry::fromRecord($record);

        $this->assertSame(10, $entry->id);
        $this->assertSame('user.login', $entry->event);
        $this->assertSame('2026-02-01 08:00:00', $entry->dateCreated);
        $this->assertSame(5, $entry->userId);
        $this->assertSame('bob', $entry->userName);
        $this->assertSame('bob@example.com', $entry->userEmail);
        $this->assertSame('10.0.0.1', $entry->ipAddress);
        $this->assertSame('DE', $entry->country);
        $this->assertSame('Berlin', $entry->city);
        $this->assertSame('sess_xyz', $entry->sessionId);
        $this->assertSame(1, $entry->chainPosition);
        $this->assertNull($entry->prevHash);
        $this->assertNull($entry->metadata);
    }

    // ---------------------------------------------------------------------------
    // testToArray
    // ---------------------------------------------------------------------------

    public function testToArray(): void
    {
        $data = $this->fullData();
        $entry = AuditLogEntry::fromArray($data);
        $array = $entry->toArray();

        $expectedKeys = [
            'id', 'event', 'dateCreated',
            'category', 'elementType', 'elementId', 'elementTitle',
            'userId', 'userName', 'userEmail',
            'ipAddress', 'country', 'region', 'city',
            'userAgent', 'requestUrl', 'requestMethod',
            'siteId',
            'oldValue', 'newValue', 'metadata',
            'sessionId', 'hash',
            'chainPosition', 'prevHash',
        ];

        $this->assertArrayHasKeys($expectedKeys, $array);

        $this->assertSame(42, $array['id']);
        $this->assertSame('element.saved', $array['event']);
        $this->assertSame('2026-01-15 10:30:00', $array['dateCreated']);
        $this->assertSame('entries', $array['category']);
        $this->assertSame(3, $array['userId']);
        $this->assertSame('sha256hashvalue', $array['hash']);
        $this->assertSame(5, $array['chainPosition']);
        $this->assertSame('sha256prevhashvalue', $array['prevHash']);

        // Raw JSON strings must survive round-trip unchanged
        $this->assertSame('{"source":"cp","version":2}', $array['metadata']);
        $this->assertSame('{"title":"Old Title"}', $array['oldValue']);
        $this->assertSame('{"title":"My Blog Post"}', $array['newValue']);
    }

    public function testToArrayContainsAllTwentyFiveKeys(): void
    {
        $entry = AuditLogEntry::fromArray($this->fullData());
        $array = $entry->toArray();

        $this->assertCount(25, $array);
    }

    // ---------------------------------------------------------------------------
    // Immutability guard
    // ---------------------------------------------------------------------------

    public function testPropertiesAreReadonly(): void
    {
        $entry = AuditLogEntry::fromArray($this->fullData());

        $reflection = new \ReflectionClass($entry);
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property \${$property->getName()} should be readonly"
            );
        }
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(AuditLogEntry::class);

        $this->assertTrue($reflection->isFinal(), 'AuditLogEntry must be a final class');
    }
}
