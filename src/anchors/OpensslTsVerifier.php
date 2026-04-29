<?php

declare(strict_types=1);

namespace anvildev\trails\anchors;

/**
 * Verifies an RFC 3161 TimeStampReply by shelling out to `openssl ts -verify`.
 *
 * This is the same verification an external auditor would perform, which is
 * the property we want: the proof is independently re-checkable with widely
 * deployed tooling, not bespoke PHP crypto.
 */
final class OpensslTsVerifier
{
    private const DEFAULT_TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly string $opensslPath = 'openssl',
        private readonly int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
    ) {
    }

    /** Probes whether THIS verifier's configured openssl is callable. */
    public function isAvailable(): bool
    {
        return self::probe($this->opensslPath);
    }

    /** Static probe for a specific openssl path. */
    public static function probe(string $opensslPath = 'openssl'): bool
    {
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = @proc_open([$opensslPath, 'version'], $descriptors, $pipes);
        if (!is_resource($proc)) {
            return false;
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        return proc_close($proc) === 0;
    }

    public function verify(string $tsrBytes, string $digestHex, string $caBundlePath): VerificationResult
    {
        $this->assertHexDigest($digestHex);

        $tmpTsr = $this->writeTempFile($tsrBytes, '.tsr');
        try {
            return $this->runOpenssl([
                $this->opensslPath, 'ts', '-verify',
                '-digest', $digestHex,
                '-in', $tmpTsr,
                '-CAfile', $caBundlePath,
            ]);
        } finally {
            @unlink($tmpTsr);
        }
    }

    public function verifyWithInlinePem(string $tsrBytes, string $digestHex, string $caBundlePem): VerificationResult
    {
        $this->assertHexDigest($digestHex);

        $tmpTsr = $this->writeTempFile($tsrBytes, '.tsr');
        $tmpPem = $this->writeTempFile($caBundlePem, '.pem');
        try {
            return $this->runOpenssl([
                $this->opensslPath, 'ts', '-verify',
                '-digest', $digestHex,
                '-in', $tmpTsr,
                '-CAfile', $tmpPem,
            ]);
        } finally {
            @unlink($tmpTsr);
            @unlink($tmpPem);
        }
    }

    private function assertHexDigest(string $digest): void
    {
        if (strlen($digest) !== 64 || !ctype_xdigit($digest)) {
            throw new \InvalidArgumentException('Digest must be 64 hex characters (SHA-256).');
        }
    }

    private function writeTempFile(string $contents, string $suffix): string
    {
        $path = tempnam(sys_get_temp_dir(), 'trails-tsr-');
        if ($path === false) {
            throw new \RuntimeException('Could not create temp file.');
        }
        $finalPath = $path . $suffix;
        if (!rename($path, $finalPath)) {
            @unlink($path);
            throw new \RuntimeException('Could not rename temp file.');
        }
        if (file_put_contents($finalPath, $contents) === false) {
            @unlink($finalPath);
            throw new \RuntimeException('Could not write temp file.');
        }
        return $finalPath;
    }

    /** @param string[] $cmd */
    private function runOpenssl(array $cmd): VerificationResult
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($proc)) {
            return new VerificationResult(false, -1, '', 'Failed to spawn openssl');
        }
        fclose($pipes[0]);

        // Drain pipes with a timeout.
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $start = microtime(true);
        $stdout = '';
        $stderr = '';
        $exit = -1;
        while (true) {
            $status = proc_get_status($proc);
            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= (string) stream_get_contents($pipes[2]);
            if (!$status['running']) {
                // Capture the exit code from the first status read after exit;
                // proc_close() returns -1 once proc_get_status() has reaped the process.
                $exit = $status['exitcode'];
                break;
            }
            if (microtime(true) - $start > $this->timeoutSeconds) {
                proc_terminate($proc, 9);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);
                return new VerificationResult(false, -1, $stdout, "openssl timed out after {$this->timeoutSeconds}s\n" . $stderr);
            }
            usleep(50_000);
        }
        // Final drain to catch anything buffered after the process exited.
        $stdout .= (string) stream_get_contents($pipes[1]);
        $stderr .= (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);

        // Belt-and-braces: openssl can exit 0 with warnings on partial verification.
        // Only count it OK if it explicitly says so on stdout.
        $ok = $exit === 0 && str_contains($stdout, 'Verification: OK');
        return new VerificationResult($ok, $exit, $stdout, $stderr);
    }
}
