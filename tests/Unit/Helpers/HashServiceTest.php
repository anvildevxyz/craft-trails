<?php

namespace anvildev\trails\tests\Unit\Helpers;

use anvildev\trails\helpers\HashService;
use anvildev\trails\tests\Support\TestCase;

class HashServiceTest extends TestCase
{
    private string $key = 'test-secret-key-for-hmac';

    private function sampleData(): array
    {
        return [
            'event' => 'entry.save',
            'elementType' => 'craft\\elements\\Entry',
            'elementId' => '42',
            'userId' => '1',
            'ipAddress' => '127.0.0.1',
            'dateCreated' => '2026-03-12 10:00:00',
            'oldValue' => '{"title":"Old Title"}',
            'newValue' => '{"title":"New Title"}',
            'metadata' => '{"siteId":1}',
        ];
    }

    public function testGenerateHashIncludesAllFields(): void
    {
        $hash = HashService::generate($this->sampleData(), $this->key);

        $this->assertIsString($hash);
        // v3 format: "v3:" prefix + 64-char HMAC-SHA256 hex = 67 chars
        $this->assertSame(67, strlen($hash), 'v3 hash should be 67 characters (3-char prefix + 64-char hex)');
        $this->assertMatchesRegularExpression('/^v3:[0-9a-f]{64}$/', $hash);
    }

    public function testVerifyAcceptsLegacyV2Hash(): void
    {
        $data = $this->sampleData();
        // Legacy v2 hash: HMAC over json-encoded ordered subset (no display fields).
        $v2Fields = ['event', 'elementType', 'elementId', 'userId', 'ipAddress', 'dateCreated', 'oldValue', 'newValue', 'metadata', 'prevHash'];
        $ordered = [];
        foreach ($v2Fields as $f) {
            $ordered[$f] = (string)($data[$f] ?? '');
        }
        $legacy = 'v2:' . hash_hmac('sha256', json_encode($ordered, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $this->key);

        $this->assertTrue(
            HashService::verify($data, $legacy, $this->key),
            'verify() must accept v2 hashes for backwards compatibility with pre-v3 log records'
        );
    }

    public function testTamperingDisplayFieldIsDetectedInV3(): void
    {
        $data = $this->sampleData() + [
            'elementTitle' => 'Original Title',
            'userName' => 'alice',
            'userEmail' => 'alice@example.test',
        ];
        $hash = HashService::generate($data, $this->key);

        // Tamper the display-only field — v3 must detect this, v2 did not.
        $data['elementTitle'] = 'FORGED Title';

        $this->assertFalse(HashService::verify($data, $hash, $this->key));
    }

    public function testVerifyAcceptsLegacyV1Hash(): void
    {
        $data = $this->sampleData();
        // Legacy v1 hash: HMAC over implode('|', $values), unprefixed.
        $legacy = hash_hmac('sha256', implode('|', [
            $data['event'], $data['elementType'], $data['elementId'], $data['userId'],
            $data['ipAddress'], $data['dateCreated'], $data['oldValue'], $data['newValue'], $data['metadata'],
        ]), $this->key);

        $this->assertTrue(
            HashService::verify($data, $legacy, $this->key),
            'verify() must accept v1 hashes for backwards compatibility with existing log records'
        );
    }

    public function testV2HashesAreCollisionResistantAcrossDelimiters(): void
    {
        // v1 was vulnerable to: event="a|b", elementType="c" colliding with
        // event="a", elementType="b|c". v2 must distinguish these.
        $base = $this->sampleData();
        $a = ['event' => 'a|b', 'elementType' => 'c'] + $base;
        $b = ['event' => 'a', 'elementType' => 'b|c'] + $base;

        $this->assertNotSame(
            HashService::generate($a, $this->key),
            HashService::generate($b, $this->key)
        );
    }

    public function testVerifyReturnsTrueForValidHash(): void
    {
        $data = $this->sampleData();
        $hash = HashService::generate($data, $this->key);

        $this->assertTrue(HashService::verify($data, $hash, $this->key));
    }

    public function testVerifyReturnsFalseForTamperedData(): void
    {
        $data = $this->sampleData();
        $hash = HashService::generate($data, $this->key);

        // Tamper with metadata
        $data['metadata'] = '{"siteId":99}';

        $this->assertFalse(HashService::verify($data, $hash, $this->key));
    }

    public function testVerifyReturnsFalseForTamperedOldNewValues(): void
    {
        $data = $this->sampleData();
        $hash = HashService::generate($data, $this->key);

        // Tamper with oldValue
        $data['oldValue'] = '{"title":"Tampered"}';

        $this->assertFalse(HashService::verify($data, $hash, $this->key));
    }

    public function testDifferentKeysProduceDifferentHashes(): void
    {
        $data = $this->sampleData();

        $hash1 = HashService::generate($data, 'key-one');
        $hash2 = HashService::generate($data, 'key-two');

        $this->assertNotSame($hash1, $hash2);
    }

    public function testGenerateIsDeterministic(): void
    {
        $data = $this->sampleData();

        $this->assertSame(
            HashService::generate($data, $this->key),
            HashService::generate($data, $this->key)
        );
    }

    public function testVerifyReturnsFalseForWrongKey(): void
    {
        $data = $this->sampleData();
        $hash = HashService::generate($data, $this->key);

        $this->assertFalse(HashService::verify($data, $hash, 'wrong-key'));
    }
}
