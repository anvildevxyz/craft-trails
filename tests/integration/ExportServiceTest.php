<?php

namespace anvildev\trails\tests\integration;

use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\services\ExportService;
use anvildev\trails\Trails;
use craft\test\TestCase;

class ExportServiceTest extends TestCase
{
    private ExportService $service;

    protected function _before(): void
    {
        parent::_before();
        $this->service = Trails::getInstance()->export;
    }

    // =========================================================================
    // CSV
    // =========================================================================

    public function testExportToCsvReturnsString(): void
    {
        $this->insertTestRecord();

        $csv = $this->service->exportToCsv([]);
        $this->assertIsString($csv);
        $this->assertNotEmpty($csv);
    }

    public function testCsvContainsHeaderRow(): void
    {
        $csv = $this->service->exportToCsv([]);
        $lines = explode("\n", trim($csv));
        $headers = str_getcsv($lines[0]);

        $this->assertCount(14, $headers);
        $this->assertEquals('ID', $headers[0]);
        $this->assertEquals('Timestamp', $headers[1]);
        $this->assertEquals('Event', $headers[2]);
        $this->assertEquals('Request Method', $headers[13]);
    }

    public function testCsvContainsDataRows(): void
    {
        $this->insertTestRecord('csv.test', ['elementTitle' => 'CSV Test Entry']);

        $csv = $this->service->exportToCsv(['event' => 'csv.test']);
        $lines = explode("\n", trim($csv));

        // Header + at least 1 data row
        $this->assertGreaterThanOrEqual(2, count($lines));
        $this->assertStringContainsString('CSV Test Entry', $csv);
    }

    // =========================================================================
    // JSON
    // =========================================================================

    public function testExportToJsonReturnsValidJson(): void
    {
        $this->insertTestRecord();

        $json = $this->service->exportToJson([]);
        $data = json_decode($json, true);

        $this->assertNotNull($data);
        $this->assertIsArray($data);
    }

    public function testJsonContainsExpectedFields(): void
    {
        $this->insertTestRecord('json.test', [
            'metadata' => json_encode(['key' => 'value']),
        ]);

        $json = $this->service->exportToJson(['event' => 'json.test']);
        $data = json_decode($json, true);

        $this->assertNotEmpty($data);
        $entry = $data[0];

        $this->assertArrayHasKey('id', $entry);
        $this->assertArrayHasKey('timestamp', $entry);
        $this->assertArrayHasKey('event', $entry);
        $this->assertArrayHasKey('category', $entry);
        $this->assertArrayHasKey('metadata', $entry);
        $this->assertEquals('json.test', $entry['event']);
    }

    public function testJsonDecodesMetadata(): void
    {
        $this->insertTestRecord('json.meta', [
            'metadata' => json_encode(['action' => 'test']),
        ]);

        $json = $this->service->exportToJson(['event' => 'json.meta']);
        $data = json_decode($json, true);

        $this->assertNotEmpty($data);
        $this->assertIsArray($data[0]['metadata']);
        $this->assertEquals('test', $data[0]['metadata']['action']);
    }

    // =========================================================================
    // HTML
    // =========================================================================

    public function testExportToHtmlContainsTableMarkup(): void
    {
        $this->insertTestRecord();

        $html = $this->service->exportToHtml([]);

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>Timestamp</th>', $html);
        $this->assertStringContainsString('<th>Event</th>', $html);
        $this->assertStringContainsString('Audit Trail Report', $html);
    }

    public function testHtmlEscapesSpecialCharacters(): void
    {
        $this->insertTestRecord('xss.test', [
            'elementTitle' => '<script>alert("xss")</script>',
            'userName' => 'user<b>bold</b>',
        ]);

        $html = $this->service->exportToHtml(['event' => 'xss.test']);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testHtmlShowsRecordCount(): void
    {
        $this->insertTestRecord('count.html1');
        $this->insertTestRecord('count.html2');

        $html = $this->service->exportToHtml([]);

        $this->assertStringContainsString('Total Records:', $html);
    }

    // =========================================================================
    // Filters
    // =========================================================================

    public function testExportRespectsEventFilter(): void
    {
        $this->insertTestRecord('export.included');
        $this->insertTestRecord('export.excluded');

        $csv = $this->service->exportToCsv(['event' => 'export.included']);

        $this->assertStringContainsString('export.included', $csv);
        $this->assertStringNotContainsString('export.excluded', $csv);
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private function insertTestRecord(string $event = 'test.export', array $overrides = []): AuditLogRecord
    {
        $record = new AuditLogRecord();
        $record->event = $event;
        $record->category = $overrides['category'] ?? explode('.', $event)[0];
        $record->elementTitle = $overrides['elementTitle'] ?? 'Test Element';
        $record->userName = $overrides['userName'] ?? 'testuser';
        $record->userEmail = $overrides['userEmail'] ?? 'test@example.com';
        $record->ipAddress = $overrides['ipAddress'] ?? '127.0.0.1';
        $record->requestUrl = '/test';
        $record->requestMethod = 'GET';
        $record->siteId = 1;
        $record->metadata = $overrides['metadata'] ?? null;
        $record->hash = hash('sha256', $event . microtime());

        $saved = $record->save();
        $this->assertTrue($saved, 'Test record should save: ' . json_encode($record->getErrors()));

        return $record;
    }
}
