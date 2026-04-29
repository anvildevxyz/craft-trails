<?php

declare(strict_types=1);

namespace anvildev\trails\tests\Unit\Anchors;

use anvildev\trails\anchors\S3AnchorBackend;
use anvildev\trails\tests\Support\TestCase;

class S3AnchorBackendTest extends TestCase
{
    private string $rootHash = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';
    private string $dateComputed = '2024-06-15T10:00:00+00:00';

    // =========================================================================
    // buildManifest()
    // =========================================================================

    public function testBuildManifest(): void
    {
        $manifest = S3AnchorBackend::buildManifest(
            $this->rootHash,
            100,
            200,
            101,
            $this->dateComputed,
        );

        $this->assertArrayHasKeys(
            ['version', 'rootHash', 'batchStartPosition', 'batchEndPosition', 'recordCount', 'dateComputed', 'anchoredAt'],
            $manifest
        );

        $this->assertSame('1.0', $manifest['version']);
        $this->assertSame($this->rootHash, $manifest['rootHash']);
        $this->assertSame(100, $manifest['batchStartPosition']);
        $this->assertSame(200, $manifest['batchEndPosition']);
        $this->assertSame(101, $manifest['recordCount']);
        $this->assertSame($this->dateComputed, $manifest['dateComputed']);
        $this->assertNotEmpty($manifest['anchoredAt']);
    }

    // =========================================================================
    // Constructor — endpoint + pathStyle for non-AWS (MinIO/LocalStack)
    // =========================================================================

    public function testConstructorAcceptsEndpointAndPathStyle(): void
    {
        $backend = new S3AnchorBackend(
            bucket: 'trails-anchors',
            region: 'us-east-1',
            accessKeyId: 'minio',
            secretAccessKey: 'minio',
            endpoint: 'http://minio:10101',
            usePathStyle: true,
        );

        $reflection = new \ReflectionClass($backend);

        $endpointProp = $reflection->getProperty('endpoint');
        $this->assertSame('http://minio:10101', $endpointProp->getValue($backend));

        $pathStyleProp = $reflection->getProperty('usePathStyle');
        $this->assertTrue($pathStyleProp->getValue($backend));
    }

    public function testEndpointDefaultsToNullForRealAws(): void
    {
        $backend = new S3AnchorBackend(
            bucket: 'b',
            region: 'us-east-1',
        );

        $reflection = new \ReflectionClass($backend);
        $this->assertNull($reflection->getProperty('endpoint')->getValue($backend));
        $this->assertFalse($reflection->getProperty('usePathStyle')->getValue($backend));
    }

    // =========================================================================
    // buildObjectKey()
    // =========================================================================

    public function testBuildObjectKey(): void
    {
        $key = S3AnchorBackend::buildObjectKey($this->rootHash, $this->dateComputed);

        $this->assertStringContainsString('trails/merkle/', $key);
        $this->assertStringContainsString(substr($this->rootHash, 0, 16), $key);
        $this->assertStringEndsWith('.json', $key);
    }

    public function testBuildObjectKeyContainsDate(): void
    {
        $key = S3AnchorBackend::buildObjectKey($this->rootHash, $this->dateComputed);

        $this->assertStringContainsString('2024/06/15', $key);
    }

    // =========================================================================
    // buildAnchorProof()
    // =========================================================================

    public function testBuildAnchorProofProducesJsonObject(): void
    {
        $proof = S3AnchorBackend::buildAnchorProof(
            eTag: '"abc123def456"',
            versionId: 'XYZ789',
            retainUntil: '2031-04-27T10:00:00+00:00',
        );

        $decoded = json_decode($proof, true);
        $this->assertIsArray($decoded);
        $this->assertSame('abc123def456', $decoded['eTag']);
        $this->assertSame('XYZ789', $decoded['versionId']);
        $this->assertSame('2031-04-27T10:00:00+00:00', $decoded['retainUntil']);
    }

    public function testBuildAnchorProofStripsETagQuotes(): void
    {
        $proof = S3AnchorBackend::buildAnchorProof(
            eTag: '"abc123"',
            versionId: 'v1',
            retainUntil: '2031-04-27T10:00:00+00:00',
        );

        $decoded = json_decode($proof, true);
        $this->assertSame('abc123', $decoded['eTag'], 'AWS ETag is wrapped in quotes; we store unwrapped.');
    }

    public function testParseAnchorProofRoundtrips(): void
    {
        $original = S3AnchorBackend::buildAnchorProof(
            eTag: 'abc123',
            versionId: 'v1',
            retainUntil: '2031-04-27T10:00:00+00:00',
        );

        $parsed = S3AnchorBackend::parseAnchorProof($original);
        $this->assertSame('abc123', $parsed['eTag']);
        $this->assertSame('v1', $parsed['versionId']);
        $this->assertSame('2031-04-27T10:00:00+00:00', $parsed['retainUntil']);
    }

    public function testParseAnchorProofRejectsMalformedJson(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        S3AnchorBackend::parseAnchorProof('not json');
    }

    public function testIsLegacyProofDetectsHexHmac(): void
    {
        // Legacy proofs were 64-char hex HMAC strings.
        $legacyHex = str_repeat('a', 64);
        $this->assertTrue(S3AnchorBackend::isLegacyProof($legacyHex));
        $this->assertFalse(S3AnchorBackend::isLegacyProof('{"eTag":"x","versionId":"y","retainUntil":"z"}'));
    }
}
