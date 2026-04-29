<?php

declare(strict_types=1);

namespace anvildev\trails\tests\Unit\Helpers;

use anvildev\trails\helpers\GeoIpResolver;
use anvildev\trails\tests\Support\TestCase;

class GeoIpResolverTest extends TestCase
{
    public function testIsResolvableReturnsFalseForPrivateIPv4(): void
    {
        $this->assertFalse(GeoIpResolver::isResolvable('192.168.1.1'));
        $this->assertFalse(GeoIpResolver::isResolvable('10.0.0.1'));
        $this->assertFalse(GeoIpResolver::isResolvable('172.16.0.1'));
    }

    public function testIsResolvableReturnsFalseForLoopback(): void
    {
        $this->assertFalse(GeoIpResolver::isResolvable('127.0.0.1'));
        $this->assertFalse(GeoIpResolver::isResolvable('::1'));
    }

    public function testIsResolvableReturnsFalseForLinkLocal(): void
    {
        $this->assertFalse(GeoIpResolver::isResolvable('169.254.1.1'));
    }

    public function testIsResolvableReturnsTrueForPublicIPv4(): void
    {
        $this->assertTrue(GeoIpResolver::isResolvable('8.8.8.8'));
        $this->assertTrue(GeoIpResolver::isResolvable('1.1.1.1'));
    }

    public function testIsResolvableReturnsFalseForAnonymized(): void
    {
        // IPs with trailing zero octet are likely anonymized — skip external lookup
        $this->assertFalse(GeoIpResolver::isResolvable('192.168.1.0'));
        $this->assertFalse(GeoIpResolver::isResolvable(''));
    }

    public function testParseIpApiResponseExtractsFields(): void
    {
        $json = '{"status":"success","country":"United States","countryCode":"US","regionName":"California","city":"Mountain View","query":"8.8.8.8"}';
        $result = GeoIpResolver::parseIpApiResponse($json);
        $this->assertSame(['country' => 'US', 'region' => 'California', 'city' => 'Mountain View'], $result);
    }

    public function testParseIpApiResponseReturnsNullOnFailure(): void
    {
        $this->assertNull(GeoIpResolver::parseIpApiResponse('{"status":"fail","message":"invalid query"}'));
        $this->assertNull(GeoIpResolver::parseIpApiResponse('not json'));
        $this->assertNull(GeoIpResolver::parseIpApiResponse(''));
    }

    public function testParseIpApiResponseHandlesPartialData(): void
    {
        // Missing city field — should default to empty string
        $json = '{"status":"success","countryCode":"US","regionName":"California"}';
        $result = GeoIpResolver::parseIpApiResponse($json);
        $this->assertNotNull($result);
        $this->assertSame('US', $result['country']);
        $this->assertSame('California', $result['region']);
        $this->assertSame('', $result['city']);
    }

    public function testParseIpApiResponseHandlesEmptySuccessResponse(): void
    {
        // Only status field present — all geo fields default to empty string
        $json = '{"status":"success"}';
        $result = GeoIpResolver::parseIpApiResponse($json);
        $this->assertNotNull($result);
        $this->assertSame('', $result['country']);
        $this->assertSame('', $result['region']);
        $this->assertSame('', $result['city']);
    }
}
