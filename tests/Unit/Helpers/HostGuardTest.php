<?php

declare(strict_types=1);

namespace anvildev\trails\tests\Unit\Helpers;

use anvildev\trails\helpers\HostGuard;
use anvildev\trails\tests\Support\TestCase;

class HostGuardTest extends TestCase
{
    public function testBlocksPrivateIPv4Literals(): void
    {
        $this->assertTrue(HostGuard::isBlocked('http://192.168.1.1/'));
        $this->assertTrue(HostGuard::isBlocked('http://10.0.0.1/'));
        $this->assertTrue(HostGuard::isBlocked('http://172.16.0.1/'));
    }

    public function testBlocksLoopbackIPv4Literals(): void
    {
        $this->assertTrue(HostGuard::isBlocked('http://127.0.0.1/'));
    }

    public function testBlocksLinkLocalAndMetadata(): void
    {
        $this->assertTrue(HostGuard::isBlocked('http://169.254.169.254/latest/meta-data/'));
        $this->assertTrue(HostGuard::isBlocked('http://169.254.1.1/'));
    }

    public function testBlocksEmptyOrMalformedUrls(): void
    {
        $this->assertTrue(HostGuard::isBlocked(''));
        $this->assertTrue(HostGuard::isBlocked('not a url'));
        $this->assertTrue(HostGuard::isBlocked('file:///etc/passwd'));
    }

    public function testAllowsPublicIPv4Literals(): void
    {
        $this->assertFalse(HostGuard::isBlocked('http://8.8.8.8/'));
        $this->assertFalse(HostGuard::isBlocked('https://1.1.1.1/'));
    }

    public function testBlocksIPv6Literals(): void
    {
        // IPv6 literals are wrapped in brackets in URLs; HostGuard must strip them
        // and validate the inner address against IPv6 reserved ranges.
        $this->assertTrue(HostGuard::isBlocked('http://[::1]/'), '::1 (loopback) must be blocked');
        $this->assertTrue(HostGuard::isBlocked('http://[::]/'), ':: (unspecified) must be blocked');
        $this->assertTrue(HostGuard::isBlocked('http://[fe80::1]/'), 'fe80::/10 link-local must be blocked');
        $this->assertTrue(HostGuard::isBlocked('http://[fc00::1]/'), 'fc00::/7 ULA must be blocked');
        $this->assertTrue(HostGuard::isBlocked('http://[ff02::1]/'), 'ff00::/8 multicast must be blocked');
    }

    public function testBlocksIPv4MappedIPv6(): void
    {
        // PHP's FILTER_FLAG_NO_PRIV_RANGE does NOT reject ::ffff:127.0.0.1 — the
        // SSRF guard must explicitly extract and re-validate the embedded IPv4.
        $this->assertTrue(HostGuard::isBlocked('http://[::ffff:127.0.0.1]/'));
        $this->assertTrue(HostGuard::isBlocked('http://[::ffff:169.254.169.254]/'));
        $this->assertTrue(HostGuard::isBlocked('http://[::ffff:10.0.0.1]/'));
    }

    public function testAllowsPublicDomain(): void
    {
        // HostGuard resolves hostnames via DNS; without DNS, fail-closed returns blocked.
        $probe = @dns_get_record('google.com', DNS_A);
        if (!is_array($probe) || $probe === []) {
            $this->markTestSkipped('DNS unavailable; cannot assert public hostname resolution path');
        }
        $this->assertFalse(HostGuard::isBlocked('https://google.com/'));
    }

    /**
     * Every IANA-reserved IPv4 range must be blocked. PHP's
     * FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE alone is incomplete
     * (it misses 0.0.0.0/8, 100.64.0.0/10 CGNAT, 198.18/15 benchmarking, etc.).
     *
     * @dataProvider reservedIpv4Provider
     */
    public function testBlocksAllReservedIpv4Ranges(string $ip): void
    {
        $this->assertTrue(
            HostGuard::isBlocked("http://{$ip}/"),
            "Reserved IPv4 {$ip} must be blocked"
        );
    }

    public static function reservedIpv4Provider(): array
    {
        return [
            'this-network 0.0.0.0/8'  => ['0.0.0.1'],
            'CGNAT 100.64.0.0/10'     => ['100.64.0.1'],
            'loopback 127.0.0.0/8'    => ['127.255.255.254'],
            'link-local 169.254/16'   => ['169.254.5.5'],
            'private 10.0.0.0/8'      => ['10.255.255.254'],
            'private 172.16.0.0/12'   => ['172.31.255.254'],
            'private 192.168.0.0/16'  => ['192.168.255.254'],
            'protocol 192.0.0.0/24'   => ['192.0.0.5'],
            'TEST-NET-1 192.0.2.0/24' => ['192.0.2.5'],
            'benchmarking 198.18/15'  => ['198.19.0.1'],
            'TEST-NET-2'              => ['198.51.100.5'],
            'TEST-NET-3'              => ['203.0.113.5'],
            'multicast 224/4'         => ['239.255.255.254'],
            'reserved 240/4'          => ['240.0.0.1'],
            'broadcast'               => ['255.255.255.255'],
        ];
    }

    /**
     * @dataProvider publicIpv4Provider
     */
    public function testAllowsPublicIpv4(string $ip): void
    {
        $this->assertFalse(
            HostGuard::isBlocked("http://{$ip}/"),
            "Public IPv4 {$ip} should not be blocked"
        );
    }

    public static function publicIpv4Provider(): array
    {
        return [
            'Google DNS' => ['8.8.8.8'],
            'Cloudflare' => ['1.1.1.1'],
            'Quad9'      => ['9.9.9.9'],
            'OpenDNS'    => ['208.67.222.222'],
        ];
    }

    public function testBlocksNat64WellKnownPrefix(): void
    {
        // 64:ff9b::/96 NAT64 well-known prefix embeds an IPv4 address in the
        // last 32 bits — if that IPv4 is private, the address must be blocked.
        $this->assertTrue(HostGuard::isBlocked('http://[64:ff9b::a00:1]/'), '64:ff9b::10.0.0.1 must be blocked');
        $this->assertTrue(HostGuard::isBlocked('http://[64:ff9b::7f00:1]/'), '64:ff9b::127.0.0.1 must be blocked');
    }

    public function testBlocksDocumentationIpv6Range(): void
    {
        // 2001:db8::/32 is the documentation range — never legitimately routable.
        $this->assertTrue(HostGuard::isBlocked('http://[2001:db8::1]/'));
    }

    public function testStripsBracketedIpv6Literals(): void
    {
        // parse_url returns the host without brackets in some setups but with
        // them in others; HostGuard must handle both consistently.
        $this->assertTrue(HostGuard::isBlocked('http://[fe80::1]/path'));
    }

    public function testRejectsUnparseableIpLiterals(): void
    {
        // An invalid IP literal must fail closed (return blocked) rather than
        // silently passing through to the HTTP client.
        $this->assertTrue(HostGuard::isIpBlocked('999.999.999.999'));
        $this->assertTrue(HostGuard::isIpBlocked('not-an-ip'));
    }
}
