<?php

declare(strict_types=1);

namespace anvildev\trails\tests\integration;

use anvildev\trails\anchors\Rfc3161AnchorBackend;

/**
 * Round-trips against real FreeTSA. Skipped unless TRAILS_INTEGRATION_TSA=1.
 * Use sparingly — FreeTSA is rate-limited and the test is non-deterministic.
 */
class AnchorBackendCest
{
    public function _before(\IntegrationTester $I): void
    {
        if (getenv('TRAILS_INTEGRATION_TSA') !== '1') {
            throw new \PHPUnit\Framework\SkippedTestError(
                'Set TRAILS_INTEGRATION_TSA=1 to run live TSA tests.'
            );
        }
    }

    public function rfc3161RoundtripVerifiesAgainstFreeTSA(\IntegrationTester $I): void
    {
        $caBundle = dirname(__DIR__) . '/_data/anchors/freetsa-cacert.pem';
        $rootHash = hash('sha256', 'integration-test-' . time());

        $backend = new Rfc3161AnchorBackend(
            tsaUrl: 'https://freetsa.org/tsr',
            caBundlePath: $caBundle,
        );

        $merkleRoot = new \anvildev\trails\records\MerkleRootRecord();
        $merkleRoot->rootHash = $rootHash;
        $merkleRoot->dateComputed = date('Y-m-d H:i:s');

        $result = $backend->anchor($merkleRoot);
        $I->assertNotEmpty($result['anchorRef']);
        $I->assertNotEmpty($result['anchorProof']);

        $verified = $backend->verify($result['anchorRef'], $result['anchorProof'], $rootHash);
        $I->assertTrue($verified, 'Live FreeTSA round-trip must verify.');
    }
}
