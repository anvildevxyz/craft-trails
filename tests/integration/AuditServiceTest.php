<?php

namespace anvildev\trails\tests\integration;

use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\services\AuditService;
use anvildev\trails\Trails;
use craft\test\TestCase;
use ReflectionMethod;

class AuditServiceTest extends TestCase
{
    private AuditService $service;

    protected function _before(): void
    {
        parent::_before();
        $this->service = new AuditService();
    }

    // =========================================================================
    // generateHash() — needs AuditLogRecord (ActiveRecord → DB)
    // =========================================================================

    public function testGenerateHashReturnsSha256(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'generateHash');
        $method->setAccessible(true);

        $record = new AuditLogRecord();
        $record->event = 'element.created';
        $record->elementType = 'craft\\elements\\Entry';
        $record->elementId = 42;
        $record->userId = 1;
        $record->ipAddress = '127.0.0.1';

        $hash = $method->invoke($this->service, $record);

        // v3 hash format: "v3:" prefix + 64-char HMAC-SHA256 hex = 67 chars
        $this->assertEquals(67, strlen($hash));
        $this->assertMatchesRegularExpression('/^v3:[a-f0-9]{64}$/', $hash);
    }

    public function testGenerateHashDiffersForDifferentInputs(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'generateHash');
        $method->setAccessible(true);

        $record1 = new AuditLogRecord();
        $record1->event = 'element.created';
        $record1->elementId = 1;

        $record2 = new AuditLogRecord();
        $record2->event = 'element.deleted';
        $record2->elementId = 1;

        $hash1 = $method->invoke($this->service, $record1);
        $hash2 = $method->invoke($this->service, $record2);

        $this->assertNotEquals($hash1, $hash2);
    }

    public function testGenerateHashUsesHmac(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'generateHash');
        $method->setAccessible(true);

        $record = new AuditLogRecord();
        $record->event = 'test.event';

        $hash = $method->invoke($this->service, $record);

        // Plain SHA-256 of the same data should NOT match (HMAC uses a key)
        $plainHash = hash('sha256', 'test.event|||||');
        $this->assertNotEquals($plainHash, $hash);
    }

    // =========================================================================
    // buildQuery() — needs DB
    // =========================================================================

    public function testBuildQueryReturnsActiveQuery(): void
    {
        $query = $this->service->buildQuery();

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query);
    }

    public function testBuildQueryFiltersEvent(): void
    {
        // Insert test records
        $this->insertTestRecord('element.created');
        $this->insertTestRecord('user.login');

        $query = $this->service->buildQuery(['event' => 'element.created']);
        $results = $query->all();

        $this->assertNotEmpty($results);
        foreach ($results as $record) {
            $this->assertEquals('element.created', $record->event);
        }
    }

    public function testBuildQueryFiltersCategory(): void
    {
        $this->insertTestRecord('element.created', ['category' => 'element']);
        $this->insertTestRecord('user.login', ['category' => 'user']);

        $query = $this->service->buildQuery(['category' => 'element']);
        $results = $query->all();

        $this->assertNotEmpty($results);
        foreach ($results as $record) {
            $this->assertEquals('element', $record->category);
        }
    }

    public function testBuildQueryFiltersDateRange(): void
    {
        $this->insertTestRecord('old.event', [
            'dateCreated' => '2020-01-01 00:00:00',
        ]);
        $this->insertTestRecord('new.event', [
            'dateCreated' => date('Y-m-d H:i:s'),
        ]);

        $query = $this->service->buildQuery([
            'dateFrom' => '2024-01-01 00:00:00',
        ]);
        $results = $query->all();

        foreach ($results as $record) {
            $this->assertNotEquals('old.event', $record->event);
        }
    }

    public function testBuildQuerySearchFiltersTitle(): void
    {
        $this->insertTestRecord('element.created', [
            'elementTitle' => 'Unique Searchable Title XYZ',
        ]);

        $query = $this->service->buildQuery(['search' => 'Unique Searchable']);
        $results = $query->all();

        $this->assertNotEmpty($results);
        $this->assertStringContainsString('Unique Searchable', $results[0]->elementTitle);
    }

    public function testGetLogsRespectsLimitAndOffset(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->insertTestRecord("test.event.{$i}");
        }

        $logs = $this->service->getLogs(['limit' => 2, 'offset' => 0]);
        $this->assertCount(2, $logs);

        $logs = $this->service->getLogs(['limit' => 2, 'offset' => 2]);
        $this->assertCount(2, $logs);
    }

    public function testCountLogsReturnsInteger(): void
    {
        $this->insertTestRecord('count.test');

        $count = $this->service->countLogs(['event' => 'count.test']);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    // =========================================================================
    // Race-condition fixes (shipping buffer + rate limit)
    // =========================================================================

    public function testShippingBufferDoesNotLoseRecordsUnderConcurrency(): void
    {
        // Regression for the read-modify-write race in AuditService::log() that
        // silently dropped audit records when concurrent log() calls each read
        // and overwrote the same shipping buffer cache entry.
        $settings = Trails::getInstance()->getSettings();
        $settings->externalLoggingEnabled = true;
        $settings->externalEndpoint = 'https://example.invalid/webhook';
        $settings->externalProvider = 'webhook';
        $settings->externalBatchSize = 1000; // ensure we never flush during the test
        $settings->logRateLimit = 0;          // disable rate limit for this scenario
        \Craft::$app->getCache()->delete('trails_shipping_buffer');

        $writes = 50;
        for ($i = 0; $i < $writes; $i++) {
            $this->service->log('myplugin.race_test', null, null, ['i' => $i]);
        }

        $buffer = \Craft::$app->getCache()->get('trails_shipping_buffer');
        $this->assertIsArray($buffer);
        $this->assertCount(
            $writes,
            $buffer,
            'Every log() call must append to the shipping buffer — the mutex prevents the read-modify-write race that previously dropped records.'
        );
    }

    public function testRateLimitMutexPreventsBypassUnderConcurrency(): void
    {
        // Regression for the rate-limit fallback TOCTOU. With the limit set to N,
        // exactly N writes within one second must succeed and the rest must be
        // suppressed — never more than N.
        $settings = Trails::getInstance()->getSettings();
        $settings->logRateLimit = 5;
        $settings->externalLoggingEnabled = false;

        // Drain any leftover counter from a previous test in this second.
        \Craft::$app->getCache()->delete('trails_rate_' . date('YmdHis'));

        $accepted = 0;
        for ($i = 0; $i < 20; $i++) {
            if ($this->service->log('myplugin.rate_test', null, null, ['i' => $i])) {
                $accepted++;
            }
        }

        $this->assertLessThanOrEqual(
            5,
            $accepted,
            'Rate limit must be enforced exactly — concurrent fallback path must not allow bypass.'
        );
    }

    public function testGeoIpJobNotQueuedForAnonymizedIp(): void
    {
        // When anonymizeIp is enabled, the stored IP is already obfuscated and
        // the geo lookup would be useless — log() must skip the queue push.
        $settings = Trails::getInstance()->getSettings();
        $settings->captureIpAddress = true;
        $settings->anonymizeIp = true;
        $settings->enableGeoIp = true;
        $settings->externalLoggingEnabled = false;
        $settings->logRateLimit = 0;

        $queue = \Craft::$app->getQueue();
        $before = method_exists($queue, 'getJobCount') ? $queue->getJobCount() : 0;

        $this->service->log('myplugin.geo_skip_test');

        $after = method_exists($queue, 'getJobCount') ? $queue->getJobCount() : 0;
        $this->assertSame(
            $before,
            $after,
            'No GeoIP resolve job should be queued when the IP has been anonymized.'
        );
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private function insertTestRecord(string $event, array $overrides = []): AuditLogRecord
    {
        $record = new AuditLogRecord();
        $record->event = $event;
        $record->category = $overrides['category'] ?? explode('.', $event)[0];
        $record->elementTitle = $overrides['elementTitle'] ?? null;
        $record->hash = hash('sha256', $event . microtime());

        if (isset($overrides['dateCreated'])) {
            $record->dateCreated = $overrides['dateCreated'];
        }

        $saved = $record->save();
        $this->assertTrue($saved, 'Test record should save: ' . json_encode($record->getErrors()));

        return $record;
    }
}
