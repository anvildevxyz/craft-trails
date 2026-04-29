<?php

declare(strict_types=1);

namespace anvildev\trails\helpers;

use Craft;

/**
 * API rate limiting via Craft's cache layer.
 */
final class RateLimiter
{
    /**
     * Build a cache key for the given token identifier and time window.
     *
     * Format: "trails:rate:{first16charsOfSha256(token)}:{floor(time()/window)}"
     */
    public static function buildCacheKey(string $tokenIdentifier, int $windowSeconds): string
    {
        $hash = substr(hash('sha256', $tokenIdentifier), 0, 16);
        $bucket = (int) floor(time() / $windowSeconds);

        return "trails:rate:{$hash}:{$bucket}";
    }

    /**
     * Check the rate limit for a token identifier.
     *
     * Increments the request counter for the current window on each call.
     *
     * @return array{allowed: bool, remaining: int, resetAt: int}
     */
    public static function check(string $tokenIdentifier, int $maxRequests, int $windowSeconds = 60): array
    {
        $cache = Craft::$app->getCache();
        $key = self::buildCacheKey($tokenIdentifier, $windowSeconds);

        $bucket = (int) floor(time() / $windowSeconds);
        $resetAt = ($bucket + 1) * $windowSeconds;

        $current = $cache->get($key);
        if ($current === false) {
            $current = 0;
        }

        $current++;
        $cache->set($key, $current, $windowSeconds);

        $remaining = max(0, $maxRequests - $current);
        $allowed = $current <= $maxRequests;

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'resetAt' => $resetAt,
        ];
    }

    /**
     * Format rate limit values as HTTP response headers.
     *
     * @return array<string, string>
     */
    public static function formatHeaders(int $limit, int $remaining, int $resetAt): array
    {
        return [
            'X-RateLimit-Limit' => (string) $limit,
            'X-RateLimit-Remaining' => (string) max(0, $remaining),
            'X-RateLimit-Reset' => (string) $resetAt,
        ];
    }
}
