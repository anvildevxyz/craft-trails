<?php

declare(strict_types=1);

namespace anvildev\trails\anchors;

use anvildev\trails\records\MerkleRootRecord;
use GuzzleHttp\Client;

class Rfc3161AnchorBackend implements AnchorBackendInterface
{
    public function __construct(
        private readonly string $tsaUrl,
        private readonly string $caBundlePath = '',
        private readonly string $caBundlePem = '',
        private readonly ?OpensslTsVerifier $verifier = null,
    ) {
    }

    // =========================================================================
    // Static unit-testable methods
    // =========================================================================

    /**
     * Build a minimal DER-encoded TimeStampReq for the given hex-encoded SHA-256 hash.
     *
     * The hash must be exactly 64 hex characters (32 bytes).
     *
     * Structure (simplified):
     *   TimeStampReq ::= SEQUENCE {
     *     version         INTEGER { v1(1) },
     *     messageImprint  MessageImprint,
     *     nonce           INTEGER OPTIONAL,
     *     certReq         BOOLEAN DEFAULT FALSE,
     *   }
     *   MessageImprint ::= SEQUENCE {
     *     hashAlgorithm   AlgorithmIdentifier,
     *     hashedMessage   OCTET STRING,
     *   }
     */
    public static function buildTimestampRequest(string $hexHash): string
    {
        // SHA-256 OID: 2.16.840.1.101.3.4.2.1
        $sha256Oid = "\x60\x86\x48\x01\x65\x03\x04\x02\x01";
        // AlgorithmIdentifier SEQUENCE: OID + NULL params
        $algorithmIdentifier = self::derSequence(
            self::derOid($sha256Oid) . self::derNull()
        );

        // Hash bytes
        $hashBytes = hex2bin($hexHash);
        $hashedMessage = self::derOctetString($hashBytes);

        // MessageImprint SEQUENCE
        $messageImprint = self::derSequence($algorithmIdentifier . $hashedMessage);

        // version INTEGER v1 = 1
        $version = self::derInteger(1);

        // Random nonce (8 bytes)
        $nonceBytes = random_bytes(8);
        $nonceInt = hexdec(bin2hex($nonceBytes));
        $nonce = self::derInteger((int) $nonceInt);

        // certReq BOOLEAN TRUE
        $certReq = "\x01\x01\xff";

        // Outer TimeStampReq SEQUENCE
        return self::derSequence($version . $messageImprint . $nonce . $certReq);
    }

    /**
     * Build an anchor reference URI from the root hash and TSA URL.
     *
     * Returns: rfc3161://{host}/{first16chars}
     */
    public static function buildAnchorRef(string $rootHash, string $tsaUrl): string
    {
        $host = parse_url($tsaUrl, PHP_URL_HOST);
        $hashPrefix = substr($rootHash, 0, 16);

        return "rfc3161://{$host}/{$hashPrefix}";
    }

    // =========================================================================
    // Instance methods (Guzzle-based, not unit-tested)
    // =========================================================================

    public function anchor(MerkleRootRecord $root): array
    {
        $tsRequest = self::buildTimestampRequest($root->rootHash);

        $client = new Client();
        $response = $client->post($this->tsaUrl, [
            'headers' => [
                'Content-Type' => 'application/timestamp-query',
                'Accept' => 'application/timestamp-reply',
            ],
            'body' => $tsRequest,
        ]);

        $tsReply = (string) $response->getBody();

        return [
            'anchorRef' => self::buildAnchorRef($root->rootHash, $this->tsaUrl),
            'anchorProof' => base64_encode($tsReply),
        ];
    }

    public function verify(string $anchorRef, string $anchorProof, string $rootHash): bool
    {
        $tsReply = base64_decode($anchorProof, true);
        if ($tsReply === false) {
            return false;
        }

        $verifier = $this->verifier ?? new OpensslTsVerifier();

        if (!$verifier->isAvailable()) {
            \Craft::warning(
                'Trails Rfc3161AnchorBackend: openssl binary not available; cannot verify TSR.',
                'trails'
            );
            return false;
        }

        if ($this->caBundlePath !== '') {
            $result = $verifier->verify($tsReply, $rootHash, $this->caBundlePath);
        } elseif ($this->caBundlePem !== '') {
            $result = $verifier->verifyWithInlinePem($tsReply, $rootHash, $this->caBundlePem);
        } else {
            \Craft::warning(
                'Trails Rfc3161AnchorBackend: no CA bundle configured (settings.tsaTrustedCaBundle '
                . 'or settings.tsaCaBundlePem); refusing to claim verification.',
                'trails'
            );
            return false;
        }

        if (!$result->ok) {
            \Craft::warning(
                "Trails Rfc3161AnchorBackend: verify failed for {$anchorRef}: "
                . mb_substr($result->stderr, 0, 500),
                'trails'
            );
        }
        return $result->ok;
    }

    // =========================================================================
    // Private DER encoding helpers
    // =========================================================================

    private static function derSequence(string $contents): string
    {
        return "\x30" . self::derLength(strlen($contents)) . $contents;
    }

    private static function derOid(string $oidBytes): string
    {
        return "\x06" . self::derLength(strlen($oidBytes)) . $oidBytes;
    }

    private static function derNull(): string
    {
        return "\x05\x00";
    }

    private static function derOctetString(string $bytes): string
    {
        return "\x04" . self::derLength(strlen($bytes)) . $bytes;
    }

    private static function derInteger(int $value): string
    {
        if ($value === 0) {
            return "\x02\x01\x00";
        }

        // Encode as minimal big-endian two's complement
        $hex = dechex($value);
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }
        $bytes = hex2bin($hex);

        // Prepend 0x00 if high bit set (to keep positive)
        if (ord($bytes[0]) >= 0x80) {
            $bytes = "\x00" . $bytes;
        }

        return "\x02" . self::derLength(strlen($bytes)) . $bytes;
    }

    private static function derLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        if ($length < 0x100) {
            return "\x81" . chr($length);
        }

        return "\x82" . chr($length >> 8) . chr($length & 0xff);
    }
}
