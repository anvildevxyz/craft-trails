<?php

declare(strict_types=1);

namespace anvildev\trails\tests\Unit\Anchors;

use anvildev\trails\anchors\OpensslTsVerifier;
use anvildev\trails\tests\Support\TestCase;

class OpensslTsVerifierTest extends TestCase
{
    private string $fixturesDir;
    private string $digest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesDir = dirname(__DIR__, 2) . '/_data/anchors';
        $this->digest = trim((string) file_get_contents($this->fixturesDir . '/sample-digest.txt'));
    }

    public function testVerifyAcceptsValidReply(): void
    {
        $verifier = new OpensslTsVerifier();
        $tsr = (string) file_get_contents($this->fixturesDir . '/sample-valid.tsr');
        $caPath = $this->fixturesDir . '/freetsa-cacert.pem';

        $result = $verifier->verify($tsr, $this->digest, $caPath);
        $this->assertTrue($result->ok, 'Valid TSR with correct CA should verify. stderr: ' . $result->stderr);
        $this->assertSame(0, $result->exitCode);
    }

    public function testVerifyRejectsTamperedReply(): void
    {
        $verifier = new OpensslTsVerifier();
        $tsr = (string) file_get_contents($this->fixturesDir . '/sample-tampered.tsr');
        $caPath = $this->fixturesDir . '/freetsa-cacert.pem';

        $result = $verifier->verify($tsr, $this->digest, $caPath);
        $this->assertFalse($result->ok);
        $this->assertNotSame(0, $result->exitCode);
    }

    public function testVerifyRejectsWrongDigest(): void
    {
        $verifier = new OpensslTsVerifier();
        $tsr = (string) file_get_contents($this->fixturesDir . '/sample-valid.tsr');
        $caPath = $this->fixturesDir . '/freetsa-cacert.pem';
        $wrongDigest = str_repeat('0', 64);

        $result = $verifier->verify($tsr, $wrongDigest, $caPath);
        $this->assertFalse($result->ok);
    }

    public function testVerifyRejectsMissingCaBundle(): void
    {
        $verifier = new OpensslTsVerifier();
        $tsr = (string) file_get_contents($this->fixturesDir . '/sample-valid.tsr');

        $result = $verifier->verify($tsr, $this->digest, '/nonexistent/ca.pem');
        $this->assertFalse($result->ok);
    }

    public function testVerifyAcceptsInlinePem(): void
    {
        $verifier = new OpensslTsVerifier();
        $tsr = (string) file_get_contents($this->fixturesDir . '/sample-valid.tsr');
        $pem = (string) file_get_contents($this->fixturesDir . '/freetsa-cacert.pem');

        $result = $verifier->verifyWithInlinePem($tsr, $this->digest, $pem);
        $this->assertTrue($result->ok, 'stderr: ' . $result->stderr);
    }

    public function testVerifyRejectsNonHexDigest(): void
    {
        $verifier = new OpensslTsVerifier();
        $tsr = (string) file_get_contents($this->fixturesDir . '/sample-valid.tsr');
        $caPath = $this->fixturesDir . '/freetsa-cacert.pem';

        $this->expectException(\InvalidArgumentException::class);
        $verifier->verify($tsr, 'not-a-hex-digest', $caPath);
    }

    public function testIsAvailableReturnsTrueWhenOpensslOnPath(): void
    {
        $this->assertTrue((new OpensslTsVerifier())->isAvailable());
        $this->assertTrue(OpensslTsVerifier::probe());
    }
}
