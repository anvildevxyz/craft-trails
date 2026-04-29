<?php

declare(strict_types=1);

namespace anvildev\trails\anchors;

use anvildev\trails\records\MerkleRootRecord;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class S3AnchorBackend implements AnchorBackendInterface
{
    public function __construct(
        private readonly string $bucket,
        private readonly string $region,
        private readonly ?string $accessKeyId = null,
        private readonly ?string $secretAccessKey = null,
        private readonly int $retentionYears = 7,
        private readonly ?S3Client $injectedClient = null,
        /**
         * Custom S3 endpoint URL (e.g. for MinIO or LocalStack). NULL targets
         * real AWS S3 via the SDK's default endpoint resolution.
         */
        private readonly ?string $endpoint = null,
        /**
         * Use path-style addressing (bucket appears in the URL path). MinIO and
         * most S3-compatible servers require this; AWS S3 supports both styles
         * but defaults to virtual-hosted-style.
         */
        private readonly bool $usePathStyle = false,
    ) {
    }

    // =========================================================================
    // Static unit-testable methods
    // =========================================================================

    public static function buildManifest(
        string $rootHash,
        int $batchStart,
        int $batchEnd,
        int $recordCount,
        string $dateComputed,
    ): array {
        return [
            'version' => '1.0',
            'rootHash' => $rootHash,
            'batchStartPosition' => $batchStart,
            'batchEndPosition' => $batchEnd,
            'recordCount' => $recordCount,
            'dateComputed' => $dateComputed,
            'anchoredAt' => date('c'),
        ];
    }

    public static function buildObjectKey(string $rootHash, string $dateComputed): string
    {
        $date = date('Y/m/d', strtotime($dateComputed));
        $hashPrefix = substr($rootHash, 0, 16);
        return "trails/merkle/{$date}/{$hashPrefix}.json";
    }

    public static function buildAnchorProof(
        string $eTag,
        string $versionId,
        string $retainUntil,
    ): string {
        return json_encode([
            'eTag' => trim($eTag, '"'),
            'versionId' => $versionId,
            'retainUntil' => $retainUntil,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array{eTag:string,versionId:string,retainUntil:string}
     * @throws \InvalidArgumentException
     */
    public static function parseAnchorProof(string $proof): array
    {
        $decoded = json_decode($proof, true);
        if (!is_array($decoded)
            || !isset($decoded['eTag'], $decoded['versionId'], $decoded['retainUntil'])
        ) {
            throw new \InvalidArgumentException('Anchor proof is not a valid JSON object.');
        }
        return [
            'eTag' => (string) $decoded['eTag'],
            'versionId' => (string) $decoded['versionId'],
            'retainUntil' => (string) $decoded['retainUntil'],
        ];
    }

    public static function isLegacyProof(string $proof): bool
    {
        return strlen($proof) === 64 && ctype_xdigit($proof);
    }

    // =========================================================================
    // Instance methods (AWS SDK; integration-tested)
    // =========================================================================

    public function anchor(MerkleRootRecord $root): array
    {
        $manifest = self::buildManifest(
            $root->rootHash,
            (int) $root->batchStartPosition,
            (int) $root->batchEndPosition,
            (int) $root->recordCount,
            $root->dateComputed,
        );

        $body = json_encode(
            $manifest,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
        $objectKey = self::buildObjectKey($root->rootHash, $root->dateComputed);
        $retainUntil = (new \DateTimeImmutable("+{$this->retentionYears} years", new \DateTimeZone('UTC')))->format('c');

        $result = $this->client()->putObject([
            'Bucket' => $this->bucket,
            'Key' => $objectKey,
            'Body' => $body,
            'ContentType' => 'application/json',
            'ObjectLockMode' => 'COMPLIANCE',
            'ObjectLockRetainUntilDate' => $retainUntil,
        ]);

        $eTag = (string) ($result['ETag'] ?? '');
        $versionId = (string) ($result['VersionId'] ?? '');
        if ($eTag === '' || $versionId === '') {
            throw new \RuntimeException(
                'S3 PutObject did not return ETag/VersionId — bucket likely does not have versioning enabled, '
                . 'which Object Lock requires.'
            );
        }

        return [
            'anchorRef' => "s3://{$this->bucket}/{$objectKey}",
            'anchorProof' => self::buildAnchorProof($eTag, $versionId, $retainUntil),
        ];
    }

    public function verify(string $anchorRef, string $anchorProof, string $rootHash): bool
    {
        if (self::isLegacyProof($anchorProof)) {
            // Pre-fix rows used a local HMAC. We can no longer verify those — the key
            // they were signed with may have rotated, and the "proof" was never
            // third-party-verifiable anyway. Return false so the integrity report
            // shows the row as unverified, prompting the operator to re-anchor.
            \Craft::warning(
                "Trails S3AnchorBackend: legacy hex anchorProof for {$anchorRef}; re-anchor required.",
                'trails'
            );
            return false;
        }

        try {
            $parsed = self::parseAnchorProof($anchorProof);
        } catch (\InvalidArgumentException $e) {
            \Craft::warning(
                "Trails S3AnchorBackend: malformed anchorProof for {$anchorRef}",
                'trails'
            );
            return false;
        }

        $objectKey = str_replace("s3://{$this->bucket}/", '', $anchorRef);

        try {
            $head = $this->client()->headObject([
                'Bucket' => $this->bucket,
                'Key' => $objectKey,
                'VersionId' => $parsed['versionId'],
            ]);

            $actualETag = trim((string) ($head['ETag'] ?? ''), '"');
            if ($actualETag !== $parsed['eTag']) {
                \Craft::error(
                    "Trails S3AnchorBackend: ETag mismatch for {$anchorRef}: "
                    . "expected {$parsed['eTag']}, got {$actualETag}",
                    'trails'
                );
                return false;
            }

            // Object Lock retention must still cover the anchor.
            $retentionMode = $head['ObjectLockMode'] ?? null;
            if ($retentionMode !== 'COMPLIANCE') {
                \Craft::error(
                    "Trails S3AnchorBackend: Object Lock mode is '{$retentionMode}' "
                    . "(expected COMPLIANCE) for {$anchorRef}",
                    'trails'
                );
                return false;
            }

            // Validate retention has not been downgraded.
            $actualRetention = $head['ObjectLockRetainUntilDate'] ?? null;
            if ($actualRetention === null) {
                \Craft::error(
                    "Trails S3AnchorBackend: no ObjectLockRetainUntilDate on {$anchorRef}",
                    'trails'
                );
                return false;
            }
            $actualRetentionUtc = (new \DateTimeImmutable($actualRetention->format('c')))
                ->setTimezone(new \DateTimeZone('UTC'));
            $expectedRetentionUtc = (new \DateTimeImmutable($parsed['retainUntil']))
                ->setTimezone(new \DateTimeZone('UTC'));
            if ($actualRetentionUtc < $expectedRetentionUtc) {
                \Craft::error(
                    "Trails S3AnchorBackend: retention downgraded for {$anchorRef}: "
                    . "expected >= {$expectedRetentionUtc->format('c')}, "
                    . "got {$actualRetentionUtc->format('c')}",
                    'trails'
                );
                return false;
            }

            // The rootHash should appear inside the manifest body.
            $get = $this->client()->getObject([
                'Bucket' => $this->bucket,
                'Key' => $objectKey,
                'VersionId' => $parsed['versionId'],
            ]);
        } catch (AwsException $e) {
            \Craft::warning(
                "Trails S3AnchorBackend: AWS request failed for {$anchorRef}: " . $e->getAwsErrorCode(),
                'trails'
            );
            return false;
        }

        $manifest = json_decode((string) $get['Body'], true);
        return is_array($manifest)
            && isset($manifest['rootHash'])
            && hash_equals($rootHash, (string) $manifest['rootHash']);
    }

    private function client(): S3Client
    {
        if ($this->injectedClient !== null) {
            return $this->injectedClient;
        }
        $config = [
            'version' => 'latest',
            'region' => $this->region,
        ];
        if ($this->accessKeyId !== null && $this->accessKeyId !== ''
            && $this->secretAccessKey !== null && $this->secretAccessKey !== ''
        ) {
            $config['credentials'] = [
                'key' => $this->accessKeyId,
                'secret' => $this->secretAccessKey,
            ];
        }
        // No credentials block → SDK uses its default provider chain
        // (env vars, IAM role, instance profile, ECS task role, etc.).
        if ($this->endpoint !== null && $this->endpoint !== '') {
            $config['endpoint'] = $this->endpoint;
        }
        if ($this->usePathStyle) {
            $config['use_path_style_endpoint'] = true;
        }
        return new S3Client($config);
    }
}
