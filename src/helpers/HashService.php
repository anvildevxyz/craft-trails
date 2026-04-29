<?php

namespace anvildev\trails\helpers;

/**
 * HMAC-SHA256 integrity hashes for audit log records.
 * Covers all audit data columns to detect tampering.
 */
class HashService
{
    /**
     * v3 adds coverage for denormalized display fields so tampering with any
     * human-visible attribution (userName, userEmail, elementTitle, etc.) also
     * invalidates the hash. Excluded by design: country/region/city
     * (populated asynchronously after hash); hash/dateUpdated/uid (metadata
     * managed by the record itself).
     */
    private const HASH_FIELDS_V3 = [
        'event', 'category', 'elementType', 'elementId', 'elementTitle',
        'userId', 'userName', 'userEmail',
        'ipAddress', 'userAgent', 'requestUrl', 'requestMethod',
        'siteId', 'sessionId',
        'dateCreated', 'oldValue', 'newValue', 'metadata',
        'prevHash',
    ];

    /**
     * v2 field list. Retained for verifying legacy rows written before the
     * display-field expansion.
     */
    private const HASH_FIELDS_V2 = [
        'event', 'elementType', 'elementId', 'userId',
        'ipAddress', 'dateCreated', 'oldValue', 'newValue', 'metadata',
        'prevHash',
    ];

    /**
     * Original v1 field list (without prevHash). Used only for verifying
     * legacy hashes stored before the partitioning schema change.
     */
    private const HASH_FIELDS_V1 = [
        'event', 'elementType', 'elementId', 'userId',
        'ipAddress', 'dateCreated', 'oldValue', 'newValue', 'metadata',
    ];

    /**
     * Current hash format version.
     *
     * v1 (legacy, unprefixed): implode('|', $values) — vulnerable to collisions.
     * v2 ("v2:"): json_encode of ordered assoc, covers core attribution fields.
     * v3 ("v3:"): json_encode of ordered assoc, also covers display fields
     *   (userName, userEmail, elementTitle, userAgent, requestUrl, requestMethod,
     *    category, siteId, sessionId).
     */
    private const VERSION_V3 = 'v3:';
    private const VERSION_V2 = 'v2:';

    /** @return string Versioned hash, e.g. "v3:<64-char hex>". */
    public static function generate(array $data, string $key): string
    {
        return self::VERSION_V3 . self::computeV3($data, $key);
    }

    /**
     * Timing-safe verification. Dispatches on the stored hash's version prefix
     * so existing v1/v2 records remain valid after upgrade, while new records
     * written with generate() always use v3.
     */
    public static function verify(array $data, string $hash, string $key): bool
    {
        if ($hash === '') {
            return false;
        }

        // Strictly anchor version detection on (prefix + exact 64-char hex
        // body). A legacy v1 hash that happens to start with "v2:" or "v3:"
        // (≈1-in-4096 probability per row) would otherwise be misrouted and
        // falsely reported as tampered.
        if (self::looksVersioned($hash, self::VERSION_V3)) {
            $expected = self::computeV3($data, $key);
            return hash_equals($expected, substr($hash, strlen(self::VERSION_V3)));
        }
        if (self::looksVersioned($hash, self::VERSION_V2)) {
            $expected = self::computeV2($data, $key);
            return hash_equals($expected, substr($hash, strlen(self::VERSION_V2)));
        }

        // Legacy v1 — compare against the old delimiter-joined HMAC.
        return hash_equals(self::computeV1($data, $key), $hash);
    }

    private static function looksVersioned(string $hash, string $prefix): bool
    {
        if (!str_starts_with($hash, $prefix)) {
            return false;
        }
        $body = substr($hash, strlen($prefix));
        return strlen($body) === 64 && ctype_xdigit($body);
    }

    private static function computeV3(array $data, string $key): string
    {
        return self::computeOrdered(self::HASH_FIELDS_V3, $data, $key);
    }

    private static function computeV2(array $data, string $key): string
    {
        return self::computeOrdered(self::HASH_FIELDS_V2, $data, $key);
    }

    /** @param string[] $fields */
    private static function computeOrdered(array $fields, array $data, string $key): string
    {
        $ordered = [];
        foreach ($fields as $f) {
            $ordered[$f] = (string)($data[$f] ?? '');
        }
        $payload = json_encode($ordered, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return hash_hmac('sha256', (string)$payload, $key);
    }

    private static function computeV1(array $data, string $key): string
    {
        return hash_hmac('sha256', implode('|', array_map(
            fn($f) => (string)($data[$f] ?? ''),
            self::HASH_FIELDS_V1
        )), $key);
    }
}
