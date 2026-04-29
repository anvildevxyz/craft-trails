<?php

declare(strict_types=1);

namespace anvildev\trails\helpers;

final class GeoIpResolver
{
    public static function isResolvable(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }
        // Anonymized IPs (last octet replaced with 0) shouldn't hit external APIs
        if (str_ends_with($ip, '.0')) {
            return false;
        }
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * @return array{country: string, region: string, city: string}|null
     */
    public static function parseIpApiResponse(string $json): ?array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return null;
        }
        return [
            'country' => (string) ($data['countryCode'] ?? ''),
            'region' => (string) ($data['regionName'] ?? ''),
            'city' => (string) ($data['city'] ?? ''),
        ];
    }

    /**
     * Resolve an IP via the configured endpoint. Returns null on any failure.
     *
     * @return array{country: string, region: string, city: string}|null
     */
    public static function resolve(string $ip, string $endpoint = 'http://ip-api.com/json/'): ?array
    {
        if (!self::isResolvable($ip)) {
            return null;
        }
        $cacheKey = 'trails:geo:' . $ip;
        $cache = \Craft::$app->getCache();
        $cached = $cache->get($cacheKey);
        if ($cached !== false) {
            return is_array($cached) ? $cached : null;
        }
        if (HostGuard::isBlocked($endpoint)) {
            \Craft::warning("Trails: GeoIP endpoint blocked: {$endpoint}", 'trails');
            return null;
        }
        try {
            $client = \Craft::createGuzzleClient([
                'timeout' => 5,
                // SSRF: never follow redirects — HostGuard only validates the initial host.
                'allow_redirects' => false,
            ]);
            $response = $client->get(rtrim($endpoint, '/') . '/' . rawurlencode($ip));
            $result = self::parseIpApiResponse((string) $response->getBody());
            $cache->set($cacheKey, $result ?: null, 24 * 60 * 60); // 24h TTL
            return $result;
        } catch (\Throwable $e) {
            \Craft::warning("GeoIP lookup failed for {$ip}: " . $e->getMessage(), 'trails');
            return null;
        }
    }
}
