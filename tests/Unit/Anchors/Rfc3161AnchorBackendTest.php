<?php

declare(strict_types=1);

namespace anvildev\trails\tests\Unit\Anchors;

use anvildev\trails\anchors\OpensslTsVerifier;
use anvildev\trails\anchors\Rfc3161AnchorBackend;
use anvildev\trails\tests\Support\TestCase;

class Rfc3161AnchorBackendTest extends TestCase
{
    private string $rootHash = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';
    private string $tsaUrl = 'https://freetsa.org/tsr';

    // =========================================================================
    // buildTimestampRequest()
    // =========================================================================

    public function testBuildTimestampRequest(): void
    {
        $der = Rfc3161AnchorBackend::buildTimestampRequest($this->rootHash);

        $this->assertNotEmpty($der);
        $this->assertIsString($der);
    }

    public function testBuildTimestampRequestContainsHashBytes(): void
    {
        $der = Rfc3161AnchorBackend::buildTimestampRequest($this->rootHash);
        $hashBytes = hex2bin($this->rootHash);

        $this->assertStringContainsString($hashBytes, $der);
    }

    public function testBuildTimestampRequestStartsWithSequenceTag(): void
    {
        $der = Rfc3161AnchorBackend::buildTimestampRequest($this->rootHash);

        // DER SEQUENCE tag is 0x30
        $this->assertSame(0x30, ord($der[0]));
    }

    public function testBuildTimestampRequestIsDifferentEachCall(): void
    {
        // Nonce is random, so each call should produce a different result.
        // Note: lengths may differ by 1 byte if the nonce's high bit causes
        // DER two's-complement zero-padding to be added or omitted.
        $der1 = Rfc3161AnchorBackend::buildTimestampRequest($this->rootHash);
        $der2 = Rfc3161AnchorBackend::buildTimestampRequest($this->rootHash);

        $this->assertNotSame($der1, $der2);
    }

    // =========================================================================
    // buildAnchorRef()
    // =========================================================================

    public function testBuildAnchorRef(): void
    {
        $ref = Rfc3161AnchorBackend::buildAnchorRef($this->rootHash, $this->tsaUrl);

        $this->assertStringContainsString('freetsa.org', $ref);
        $this->assertStringContainsString(substr($this->rootHash, 0, 16), $ref);
    }

    public function testBuildAnchorRefUsesRfc3161Scheme(): void
    {
        $ref = Rfc3161AnchorBackend::buildAnchorRef($this->rootHash, $this->tsaUrl);

        $this->assertStringStartsWith('rfc3161://', $ref);
    }

    public function testBuildAnchorRefExtractsHostFromUrl(): void
    {
        $ref = Rfc3161AnchorBackend::buildAnchorRef($this->rootHash, 'https://timestamp.digicert.com/');

        $this->assertStringContainsString('timestamp.digicert.com', $ref);
        $this->assertStringNotContainsString('https://', $ref);
    }

    // =========================================================================
    // verify()
    // =========================================================================

    public function testVerifyAcceptsValidReplyWithCaBundle(): void
    {
        $tsr = (string) file_get_contents(dirname(__DIR__, 2) . '/_data/anchors/sample-valid.tsr');
        $digest = trim((string) file_get_contents(dirname(__DIR__, 2) . '/_data/anchors/sample-digest.txt'));
        $caPath = dirname(__DIR__, 2) . '/_data/anchors/freetsa-cacert.pem';

        $backend = new Rfc3161AnchorBackend('https://freetsa.org/tsr', $caPath);
        $proof = base64_encode($tsr);
        $this->assertTrue($backend->verify('rfc3161://freetsa.org/anything', $proof, $digest));
    }

    public function testVerifyRejectsTamperedReply(): void
    {
        $tsr = (string) file_get_contents(dirname(__DIR__, 2) . '/_data/anchors/sample-tampered.tsr');
        $digest = trim((string) file_get_contents(dirname(__DIR__, 2) . '/_data/anchors/sample-digest.txt'));
        $caPath = dirname(__DIR__, 2) . '/_data/anchors/freetsa-cacert.pem';

        $backend = new Rfc3161AnchorBackend('https://freetsa.org/tsr', $caPath);
        $this->assertFalse($backend->verify('rfc3161://freetsa.org/anything', base64_encode($tsr), $digest));
    }

    public function testVerifyReturnsFalseWithoutCaBundle(): void
    {
        $tsr = (string) file_get_contents(dirname(__DIR__, 2) . '/_data/anchors/sample-valid.tsr');
        $digest = trim((string) file_get_contents(dirname(__DIR__, 2) . '/_data/anchors/sample-digest.txt'));

        $backend = new Rfc3161AnchorBackend('https://freetsa.org/tsr');
        $this->assertFalse($backend->verify('rfc3161://anything', base64_encode($tsr), $digest));
    }

    public function testVerifyReturnsFalseOnInvalidBase64(): void
    {
        $backend = new Rfc3161AnchorBackend(
            'https://freetsa.org/tsr',
            dirname(__DIR__, 2) . '/_data/anchors/freetsa-cacert.pem',
        );
        $this->assertFalse(
            $backend->verify('rfc3161://x', '!!!not-valid-base64!!!', str_repeat('0', 64))
        );
    }

    public function testVerifyAcceptsValidReplyWithInlinePem(): void
    {
        $tsr = (string) file_get_contents(dirname(__DIR__, 2) . '/_data/anchors/sample-valid.tsr');
        $digest = trim((string) file_get_contents(dirname(__DIR__, 2) . '/_data/anchors/sample-digest.txt'));
        $pem = (string) file_get_contents(dirname(__DIR__, 2) . '/_data/anchors/freetsa-cacert.pem');

        $backend = new Rfc3161AnchorBackend(
            tsaUrl: 'https://freetsa.org/tsr',
            caBundlePem: $pem,
        );
        $this->assertTrue($backend->verify('rfc3161://freetsa.org/x', base64_encode($tsr), $digest));
    }

    public function testVerifyReturnsFalseWhenOpensslUnavailable(): void
    {
        $tsr = (string) file_get_contents(dirname(__DIR__, 2) . '/_data/anchors/sample-valid.tsr');
        $digest = trim((string) file_get_contents(dirname(__DIR__, 2) . '/_data/anchors/sample-digest.txt'));
        $caPath = dirname(__DIR__, 2) . '/_data/anchors/freetsa-cacert.pem';

        // A bogus openssl path makes isAvailable() return false; verify() should
        // refuse rather than throw.
        $verifier = new OpensslTsVerifier('/this/does/not/exist');
        $backend = new Rfc3161AnchorBackend(
            tsaUrl: 'https://freetsa.org/tsr',
            caBundlePath: $caPath,
            verifier: $verifier,
        );
        $this->assertFalse($backend->verify('rfc3161://x', base64_encode($tsr), $digest));
    }
}
