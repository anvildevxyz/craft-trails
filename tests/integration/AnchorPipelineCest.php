<?php

declare(strict_types=1);

namespace anvildev\trails\tests\integration;

use anvildev\trails\anchors\S3AnchorBackend;
use anvildev\trails\jobs\AnchorMerkleRootJob;
use anvildev\trails\records\AnchorRecord;
use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\records\MerkleRootRecord;
use anvildev\trails\Trails;

/**
 * Pre-launch smoke tests for the Trails 1.0.0 anchor backend.
 *
 * Five system-level paths the per-task work didn't cover:
 *   - audit hash chain still emits v3-prefixed hashes;
 *   - S3 backend's verify() distinguishes legacy hex vs JSON proofs;
 *   - the integrity:verify command tolerates mixed legacy + new anchor rows;
 *   - certificate JSON export contains anchor entries with the new shape;
 *   - the queue → job → service → backend → DB path produces an anchor row
 *     (live FreeTSA, gated on TRAILS_INTEGRATION_TSA=1, like AnchorBackendCest).
 */
class AnchorPipelineCest
{
    /** @var int[] AnchorRecord ids inserted during a test that need cleanup. */
    private array $anchorIdsToClean = [];

    /** @var int[] MerkleRootRecord ids inserted during a test that need cleanup. */
    private array $merkleRootIdsToClean = [];

    /** @var int[] AuditLogRecord ids inserted during a test that need cleanup. */
    private array $auditLogIdsToClean = [];

    public function _after(\IntegrationTester $I): void
    {
        if ($this->anchorIdsToClean !== []) {
            AnchorRecord::deleteAll(['id' => $this->anchorIdsToClean]);
            $this->anchorIdsToClean = [];
        }
        if ($this->merkleRootIdsToClean !== []) {
            MerkleRootRecord::deleteAll(['id' => $this->merkleRootIdsToClean]);
            $this->merkleRootIdsToClean = [];
        }
        if ($this->auditLogIdsToClean !== []) {
            AuditLogRecord::deleteAll(['id' => $this->auditLogIdsToClean]);
            $this->auditLogIdsToClean = [];
        }
    }

    // =========================================================================
    // Item 8 — AuditService::log() must still produce v3-prefixed hashes.
    // =========================================================================

    public function auditServiceProducesV3HashOnWrite(\IntegrationTester $I): void
    {
        $settings = Trails::getInstance()->getSettings();
        $settings->logRateLimit = 0;
        $settings->externalLoggingEnabled = false;

        $ok = Trails::getInstance()->audit->log(
            'smoketest.fired',
            null,
            null,
            ['note' => 'v3-hash-check'],
        );
        $I->assertTrue($ok, 'audit->log() must succeed for the smoke event.');

        /** @var AuditLogRecord|null $record */
        $record = AuditLogRecord::find()
            ->where(['event' => 'smoketest.fired'])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $I->assertNotNull($record, 'A row must be persisted by audit->log().');
        $this->auditLogIdsToClean[] = (int) $record->id;

        $hash = (string) $record->hash;
        $I->assertNotEmpty($hash, 'Persisted record must carry a hash.');
        $I->assertSame(
            'v3:',
            substr($hash, 0, 3),
            'Hash must use the v3 prefix; regression in HashService::generate() if not.',
        );
        $I->assertMatchesRegularExpression(
            '/^v3:[a-f0-9]{64}$/',
            $hash,
            'v3 hash must be "v3:" + 64-char lowercase hex.',
        );
    }

    // =========================================================================
    // Item 6 — S3AnchorBackend distinguishes legacy hex vs JSON anchor proofs.
    //
    // We exercise the static helpers directly: they are the boundary between
    // "treat as legacy, return false silently" and "parse as new shape, attempt
    // S3 verification". Driving verify() against a fake bucket would require
    // network/credentials, and the static helpers are the contract under test.
    // =========================================================================

    public function s3VerifyDistinguishesLegacyHexFromJsonProof(\IntegrationTester $I): void
    {
        // Legacy hex proof: 64 hex chars, recognised as legacy by the backend.
        $legacyProof = str_repeat('a', 64);
        $I->assertTrue(
            S3AnchorBackend::isLegacyProof($legacyProof),
            'A 64-char hex string must be classified as a legacy HMAC proof.',
        );

        // New JSON proof: not legacy, must parse without throwing.
        $jsonProof = S3AnchorBackend::buildAnchorProof(
            eTag: '"aabbccdd"',
            versionId: 'V123',
            retainUntil: '2030-01-01T00:00:00+00:00',
        );
        $I->assertFalse(
            S3AnchorBackend::isLegacyProof($jsonProof),
            'A JSON anchorProof must not be misclassified as legacy.',
        );

        $parsed = S3AnchorBackend::parseAnchorProof($jsonProof);
        $I->assertSame('aabbccdd', $parsed['eTag'], 'Quotes must be stripped from eTag.');
        $I->assertSame('V123', $parsed['versionId']);
        $I->assertSame('2030-01-01T00:00:00+00:00', $parsed['retainUntil']);

        // verify() short-circuits the legacy path with a warning + return false,
        // never attempting an AWS call. We exercise that with a backend that has
        // bogus, non-network-resolvable config so the test can never accidentally
        // hit AWS even if the short-circuit broke.
        $backend = new S3AnchorBackend(
            bucket: 'trails-smoke-nonexistent-bucket',
            region: 'eu-central-1',
            accessKeyId: 'AKIAEXAMPLENOTREAL',
            secretAccessKey: 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLENOTREAL',
        );

        $result = $backend->verify(
            's3://trails-smoke-nonexistent-bucket/anything.json',
            $legacyProof,
            hash('sha256', 'irrelevant'),
        );
        $I->assertFalse(
            $result,
            'Legacy hex proofs must return false silently without contacting AWS.',
        );
    }

    // =========================================================================
    // Item 2 — `php craft trails/integrity/verify` (full sweep) tolerates a mix
    // of legacy and new-shape anchors without crashing.
    //
    // We invoke the verifyAll() control flow programmatically by calling the
    // service layer the console action delegates to — the console action is
    // tested by manual smoke (item 4) and by virtue of being a thin wrapper.
    // =========================================================================

    public function integrityVerifyHandlesMixedAnchorShapes(\IntegrationTester $I): void
    {
        // Configure S3 backend so anchor->verifyAll() picks an instance that can
        // handle both legacy and new-shape rows. Credentials are bogus and the
        // bucket doesn't exist, so any real HTTP attempt fails with AwsException
        // (which S3AnchorBackend::verify() catches and turns into "unverified").
        $settings = Trails::getInstance()->getSettings();
        $settings->anchorType = 's3';
        $settings->s3Bucket = 'trails-smoke-nonexistent-bucket';
        $settings->s3Region = 'eu-central-1';
        $settings->s3AccessKeyId = 'AKIAEXAMPLENOTREAL';
        $settings->s3SecretAccessKey = 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLENOTREAL';

        // Seed two MerkleRootRecords + two AnchorRecords: one legacy, one JSON.
        $legacyRoot = new MerkleRootRecord();
        $legacyRoot->batchStartPosition = 1_000_001;
        $legacyRoot->batchEndPosition = 1_000_010;
        $legacyRoot->recordCount = 10;
        $legacyRoot->rootHash = hash('sha256', 'mixed-anchor-legacy-root');
        $legacyRoot->tableName = 'trails_logs';
        $legacyRoot->dateComputed = '2025-01-01 00:00:00';
        $I->assertTrue(
            $legacyRoot->save(),
            'Legacy MerkleRootRecord must save: ' . json_encode($legacyRoot->getErrors()),
        );
        $this->merkleRootIdsToClean[] = (int) $legacyRoot->id;

        $newRoot = new MerkleRootRecord();
        $newRoot->batchStartPosition = 1_000_011;
        $newRoot->batchEndPosition = 1_000_020;
        $newRoot->recordCount = 10;
        $newRoot->rootHash = hash('sha256', 'mixed-anchor-new-root');
        $newRoot->tableName = 'trails_logs';
        $newRoot->dateComputed = '2025-01-02 00:00:00';
        $I->assertTrue(
            $newRoot->save(),
            'New-shape MerkleRootRecord must save: ' . json_encode($newRoot->getErrors()),
        );
        $this->merkleRootIdsToClean[] = (int) $newRoot->id;

        $legacyAnchor = new AnchorRecord();
        $legacyAnchor->merkleRootId = (int) $legacyRoot->id;
        $legacyAnchor->anchorType = 's3';
        $legacyAnchor->anchorRef = 's3://trails-smoke-nonexistent-bucket/legacy.json';
        $legacyAnchor->anchorProof = str_repeat('a', 64); // 64-char hex = legacy
        $legacyAnchor->verified = true; // pretend it was verified pre-overhaul
        $legacyAnchor->dateAnchored = '2025-01-01 00:00:00';
        $I->assertTrue(
            $legacyAnchor->save(),
            'Legacy AnchorRecord must save: ' . json_encode($legacyAnchor->getErrors()),
        );
        $this->anchorIdsToClean[] = (int) $legacyAnchor->id;

        $newAnchor = new AnchorRecord();
        $newAnchor->merkleRootId = (int) $newRoot->id;
        $newAnchor->anchorType = 's3';
        $newAnchor->anchorRef = 's3://trails-smoke-nonexistent-bucket/new.json';
        $newAnchor->anchorProof = S3AnchorBackend::buildAnchorProof(
            eTag: 'fakeETag123',
            versionId: 'fakeVersion',
            retainUntil: '2030-01-01T00:00:00+00:00',
        );
        $newAnchor->verified = true;
        $newAnchor->dateAnchored = '2025-01-02 00:00:00';
        $I->assertTrue(
            $newAnchor->save(),
            'New-shape AnchorRecord must save: ' . json_encode($newAnchor->getErrors()),
        );
        $this->anchorIdsToClean[] = (int) $newAnchor->id;

        // Drive the verifyAll() pathway the console command delegates to. This
        // must NOT throw — both shapes have to be handled distinctly.
        $result = Trails::getInstance()->anchor->verifyAll();

        $I->assertIsArray($result);
        $I->assertArrayHasKey('verified', $result);
        $I->assertArrayHasKey('failed', $result);
        $I->assertArrayHasKey('failedIds', $result);

        // Both seeded rows must end up in the failed bucket: legacy returns
        // false silently, new-shape can't reach the (bogus) bucket. The point
        // is that the call completed without throwing — i.e. mixed shapes are
        // handled distinctly along separate code paths.
        $I->assertContains(
            (int) $legacyAnchor->id,
            $result['failedIds'],
            'Legacy hex anchor must be flagged as unverified (re-anchor required).',
        );
        $I->assertContains(
            (int) $newAnchor->id,
            $result['failedIds'],
            'New-shape anchor against a bogus bucket must fail verification cleanly.',
        );

        // And the persisted verified flag must have been flipped on both rows.
        $reloadedLegacy = AnchorRecord::findOne((int) $legacyAnchor->id);
        $reloadedNew = AnchorRecord::findOne((int) $newAnchor->id);
        $I->assertNotNull($reloadedLegacy);
        $I->assertNotNull($reloadedNew);
        $I->assertFalse((bool) $reloadedLegacy->verified, 'Legacy row must be marked unverified after verifyAll().');
        $I->assertFalse((bool) $reloadedNew->verified, 'New-shape row must be marked unverified after verifyAll().');
    }

    // =========================================================================
    // Item 5 — Certificate JSON export contains the new anchor shape.
    //
    // CertificateService::generateJson() currently omits anchorProof from each
    // anchor entry (anchorRef + anchorType + verified + dateAnchored only).
    // We assert the structure that's actually produced and pin it down so a
    // regression silently dropping a field will be caught.
    // =========================================================================

    public function certificateExportContainsAnchorEntries(\IntegrationTester $I): void
    {
        $root = new MerkleRootRecord();
        $root->batchStartPosition = 2_000_001;
        $root->batchEndPosition = 2_000_010;
        $root->recordCount = 10;
        $root->rootHash = hash('sha256', 'cert-export-root');
        $root->tableName = 'trails_logs';
        $root->dateComputed = '2025-06-01 12:00:00';
        $I->assertTrue($root->save(), 'MerkleRootRecord must save: ' . json_encode($root->getErrors()));
        $this->merkleRootIdsToClean[] = (int) $root->id;

        $jsonProof = S3AnchorBackend::buildAnchorProof(
            eTag: 'certETag',
            versionId: 'certVersion',
            retainUntil: '2032-06-01T12:00:00+00:00',
        );

        $anchor = new AnchorRecord();
        $anchor->merkleRootId = (int) $root->id;
        $anchor->anchorType = 's3';
        $anchor->anchorRef = 's3://trails-smoke-bucket/trails/merkle/2025/06/01/cert-export.json';
        $anchor->anchorProof = $jsonProof;
        $anchor->verified = true;
        $anchor->dateAnchored = '2025-06-01 12:00:00';
        $I->assertTrue($anchor->save(), 'AnchorRecord must save: ' . json_encode($anchor->getErrors()));
        $this->anchorIdsToClean[] = (int) $anchor->id;

        $generated = Trails::getInstance()->certificate->generate(
            '2025-06-01',
            '2025-06-30',
            'json',
        );

        $I->assertSame('application/json', $generated['contentType']);
        $I->assertSame('json', $generated['extension']);

        $decoded = json_decode($generated['content'], true);
        $I->assertIsArray($decoded, 'Certificate body must be valid JSON.');
        $I->assertArrayHasKey('anchors', $decoded);
        $I->assertArrayHasKey('merkleRoots', $decoded);
        $I->assertArrayHasKey('signature', $decoded);

        // Pin the seeded anchor down by id and assert the documented shape.
        $entries = array_values(array_filter(
            $decoded['anchors'],
            static fn(array $a): bool => (int) ($a['id'] ?? 0) === (int) $anchor->id,
        ));
        $I->assertCount(
            1,
            $entries,
            'Seeded AnchorRecord must appear exactly once in the certificate anchors list.',
        );

        $entry = $entries[0];
        $I->assertSame((int) $anchor->id, (int) $entry['id']);
        $I->assertSame((int) $root->id, (int) $entry['merkleRootId']);
        $I->assertSame('s3', $entry['anchorType']);
        $I->assertSame(
            's3://trails-smoke-bucket/trails/merkle/2025/06/01/cert-export.json',
            $entry['anchorRef'],
            'anchorRef must round-trip through certificate JSON.',
        );
        $I->assertTrue((bool) $entry['verified']);
        $I->assertSame('2025-06-01 12:00:00', (string) $entry['dateAnchored']);

        // Also confirm the corresponding Merkle root is included so an auditor
        // can rebuild the rootHash → anchorRef link.
        $rootEntries = array_values(array_filter(
            $decoded['merkleRoots'],
            static fn(array $r): bool => (int) ($r['id'] ?? 0) === (int) $root->id,
        ));
        $I->assertCount(1, $rootEntries, 'Seeded MerkleRootRecord must appear in the certificate.');
        $I->assertSame(
            hash('sha256', 'cert-export-root'),
            (string) $rootEntries[0]['rootHash'],
            'rootHash must round-trip — it is the link auditors hash against.',
        );
    }

    // =========================================================================
    // Item 1 — End-to-end RFC 3161 anchor via the AnchorMerkleRootJob.
    //
    // GATED on TRAILS_INTEGRATION_TSA=1 (mirrors AnchorBackendCest). Drives the
    // full chain: settings → job → AnchorService → Rfc3161AnchorBackend → DB.
    // =========================================================================

    public function rfc3161QueueJobPersistsAnchorRecord(\IntegrationTester $I): void
    {
        if (getenv('TRAILS_INTEGRATION_TSA') !== '1') {
            throw new \PHPUnit\Framework\SkippedTestError(
                'Set TRAILS_INTEGRATION_TSA=1 to run live FreeTSA queue-pipeline test.'
            );
        }

        $caBundle = dirname(__DIR__) . '/_data/anchors/freetsa-cacert.pem';
        $I->assertFileExists($caBundle, 'FreeTSA CA bundle fixture must exist for the gated test.');

        $settings = Trails::getInstance()->getSettings();
        $settings->anchorType = 'rfc3161';
        $settings->tsaUrl = 'https://freetsa.org/tsr';
        $settings->tsaTrustedCaBundle = $caBundle;
        $settings->tsaCaBundlePem = '';

        // Seed a MerkleRootRecord for the job to anchor.
        $root = new MerkleRootRecord();
        $root->batchStartPosition = 3_000_001;
        $root->batchEndPosition = 3_000_010;
        $root->recordCount = 10;
        $root->rootHash = hash('sha256', 'queue-pipeline-' . microtime(true));
        $root->tableName = 'trails_logs';
        $root->dateComputed = date('Y-m-d H:i:s');
        $I->assertTrue($root->save(), 'MerkleRootRecord must save: ' . json_encode($root->getErrors()));
        $this->merkleRootIdsToClean[] = (int) $root->id;

        // Drive the job directly. Calling execute() exercises the queue→service→
        // backend→DB chain without depending on a queue worker being available
        // in the test environment (Craft's default queue runner doesn't always
        // process synchronously inside Codeception).
        $job = new AnchorMerkleRootJob(['merkleRootId' => (int) $root->id]);
        $job->execute(\Craft::$app->getQueue());

        /** @var AnchorRecord|null $persisted */
        $persisted = AnchorRecord::find()
            ->where(['merkleRootId' => (int) $root->id])
            ->one();

        $I->assertNotNull(
            $persisted,
            'AnchorMerkleRootJob must persist an AnchorRecord for the seeded root.',
        );
        $this->anchorIdsToClean[] = (int) $persisted->id;

        $I->assertSame('rfc3161', (string) $persisted->anchorType);
        $I->assertNotEmpty((string) $persisted->anchorRef, 'anchorRef must be populated.');
        $I->assertNotEmpty((string) $persisted->anchorProof, 'anchorProof must be populated.');
        $I->assertTrue((bool) $persisted->verified, 'Newly-anchored row must be flagged verified.');

        // anchorProof for rfc3161 is a base64-encoded TSR. Sanity-check it.
        $decodedProof = base64_decode((string) $persisted->anchorProof, true);
        $I->assertNotFalse($decodedProof, 'rfc3161 anchorProof must be valid base64.');
        $I->assertGreaterThan(64, strlen($decodedProof), 'TSR payload should be non-trivially large.');

        // Bonus: verifyAll() should leave the row verified.
        $verifyAll = Trails::getInstance()->anchor->verifyAll();
        $I->assertNotContains(
            (int) $persisted->id,
            $verifyAll['failedIds'],
            'verifyAll() must keep a freshly-anchored row verified.',
        );

        $reloaded = AnchorRecord::findOne((int) $persisted->id);
        $I->assertNotNull($reloaded);
        $I->assertTrue((bool) $reloaded->verified, 'Row must remain verified after verifyAll().');
    }
}
