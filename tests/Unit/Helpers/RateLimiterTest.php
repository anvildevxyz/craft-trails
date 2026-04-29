<?php

declare(strict_types=1);

namespace anvildev\trails\tests\Unit\Helpers;

use anvildev\trails\helpers\RateLimiter;
use anvildev\trails\tests\Support\TestCase;

class RateLimiterTest extends TestCase
{
    public function testBuildCacheKey(): void
    {
        $key = RateLimiter::buildCacheKey('my-token', 60);

        $this->assertStringStartsWith('trails:rate:', $key);

        $expectedHash = substr(hash('sha256', 'my-token'), 0, 16);
        $this->assertStringContainsString($expectedHash, $key);
    }

    public function testBuildCacheKeyDiffersByToken(): void
    {
        $key1 = RateLimiter::buildCacheKey('token-aaa', 60);
        $key2 = RateLimiter::buildCacheKey('token-bbb', 60);

        $this->assertNotEquals($key1, $key2);
    }

    public function testBuildCacheKeyIncludesWindowBucket(): void
    {
        $window = 60;
        $key1 = RateLimiter::buildCacheKey('same-token', $window);
        $key2 = RateLimiter::buildCacheKey('same-token', $window);

        // Same token + same window within the same second should produce identical keys
        $this->assertEquals($key1, $key2);

        // The bucket value should be embedded in the key
        $bucket = (int) floor(time() / $window);
        $this->assertStringContainsString((string) $bucket, $key1);
    }

    public function testFormatRateLimitHeaders(): void
    {
        $headers = RateLimiter::formatHeaders(100, 42, 1700000000);

        $this->assertSame('100', $headers['X-RateLimit-Limit']);
        $this->assertSame('42', $headers['X-RateLimit-Remaining']);
        $this->assertSame('1700000000', $headers['X-RateLimit-Reset']);
    }

    public function testFormatRateLimitHeadersClampRemaining(): void
    {
        $headers = RateLimiter::formatHeaders(10, -5, 1700000000);

        $this->assertSame('0', $headers['X-RateLimit-Remaining']);
    }
}
