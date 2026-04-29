<?php

declare(strict_types=1);

namespace anvildev\trails\helpers;

final class HostGuard
{
    /**
     * Returns true if the URL's host resolves to a private / reserved / metadata IP.
     *
     * Resolves both IPv4 (A) and IPv6 (AAAA) records and validates every address.
     * Non-resolvable hosts return true (fail-closed) — this is intentional, since an
     * unresolvable host cannot be safely shipped to and may indicate DNS rebinding.
     */
    public static function isBlocked(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null || $host === false || $host === '') {
            return true;
        }

        // Strip IPv6 literal brackets: [::1] -> ::1
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        // If already an IP literal, check directly
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isIpBlocked($host);
        }

        // Resolve A and AAAA records (gethostbynamel is IPv4-only and would miss AAAA)
        $ips = self::resolveAll($host);
        if ($ips === []) {
            // Fail-closed: an unresolvable host could be DNS rebinding or simply unreachable.
            return true;
        }
        foreach ($ips as $ip) {
            if (self::isIpBlocked($ip)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string[] All A and AAAA addresses for the host.
     */
    private static function resolveAll(string $host): array
    {
        $ips = [];
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $r) {
                if (isset($r['ip'])) {
                    $ips[] = $r['ip'];
                } elseif (isset($r['ipv6'])) {
                    $ips[] = $r['ipv6'];
                }
            }
        }
        // Fall back to gethostbynamel for IPv4 if dns_get_record returned nothing
        // (some resolvers / containers don't support DNS_A | DNS_AAAA on the same call).
        if ($ips === []) {
            $v4 = @gethostbynamel($host);
            if (is_array($v4)) {
                $ips = $v4;
            }
        }
        return $ips;
    }

    public static function isIpBlocked(string $ip): bool
    {
        // Cloud metadata endpoints (well-known)
        if ($ip === '169.254.169.254' || $ip === 'fd00:ec2::254') {
            return true;
        }

        $packed = @inet_pton($ip);
        if ($packed === false) {
            return true; // Unparseable — fail closed
        }

        // IPv6 address (16 bytes)
        if (strlen($packed) === 16) {
            // Detect IPv4-mapped IPv6 (::ffff:a.b.c.d) and IPv4-compatible (::a.b.c.d)
            // and validate the embedded IPv4 instead — PHP's FILTER_FLAG_NO_PRIV_RANGE
            // does NOT reject these and would otherwise allow ::ffff:127.0.0.1.
            $isMapped = substr($packed, 0, 10) === str_repeat("\0", 10)
                && substr($packed, 10, 2) === "\xff\xff";
            $isCompat = substr($packed, 0, 12) === str_repeat("\0", 12)
                && $packed !== str_repeat("\0", 16)        // not ::
                && $packed !== str_repeat("\0", 15) . "\1"; // not ::1 (handled below)
            if ($isMapped || $isCompat) {
                $v4 = inet_ntop(substr($packed, 12, 4));
                return $v4 === false ? true : self::isIpBlocked($v4);
            }
            return self::isIpv6Blocked($packed);
        }

        // IPv4 address (4 bytes)
        return self::isIpv4Blocked($packed);
    }

    private static function isIpv4Blocked(string $packed): bool
    {
        $long = unpack('N', $packed)[1];

        // Reject every range that is NOT globally routable.
        // Ordering follows IANA IPv4 Special-Purpose Address Registry.
        $blocks = [
            ['0.0.0.0',         8],   // "this network"
            ['10.0.0.0',        8],   // RFC1918 private
            ['100.64.0.0',     10],   // RFC6598 CGNAT
            ['127.0.0.0',       8],   // loopback
            ['169.254.0.0',    16],   // link-local
            ['172.16.0.0',     12],   // RFC1918 private
            ['192.0.0.0',      24],   // protocol assignments
            ['192.0.2.0',      24],   // TEST-NET-1
            ['192.168.0.0',    16],   // RFC1918 private
            ['198.18.0.0',     15],   // benchmarking
            ['198.51.100.0',   24],   // TEST-NET-2
            ['203.0.113.0',    24],   // TEST-NET-3
            ['224.0.0.0',       4],   // multicast
            ['240.0.0.0',       4],   // reserved (incl. 255.255.255.255 broadcast)
        ];
        foreach ($blocks as [$net, $bits]) {
            $netLong = ip2long($net);
            $mask = (-1 << (32 - $bits)) & 0xFFFFFFFF;
            if (($long & $mask) === ($netLong & $mask)) {
                return true;
            }
        }
        return false;
    }

    private static function isIpv6Blocked(string $packed): bool
    {
        // ::          (unspecified)
        if ($packed === str_repeat("\0", 16)) {
            return true;
        }
        // ::1         (loopback)
        if ($packed === str_repeat("\0", 15) . "\1") {
            return true;
        }
        $first = ord($packed[0]);
        $second = ord($packed[1]);

        // fc00::/7    Unique Local Address (RFC 4193)
        if (($first & 0xFE) === 0xFC) {
            return true;
        }
        // fe80::/10   link-local
        if ($first === 0xFE && ($second & 0xC0) === 0x80) {
            return true;
        }
        // ff00::/8    multicast
        if ($first === 0xFF) {
            return true;
        }
        // 2001:db8::/32 documentation
        if ($first === 0x20 && $second === 0x01
            && ord($packed[2]) === 0x0D && ord($packed[3]) === 0xB8) {
            return true;
        }
        // 64:ff9b::/96 NAT64 well-known prefix — embedded IPv4 must be checked
        if (substr($packed, 0, 12) === "\x00\x64\xff\x9b\x00\x00\x00\x00\x00\x00\x00\x00") {
            $v4 = inet_ntop(substr($packed, 12, 4));
            return $v4 === false ? true : self::isIpBlocked($v4);
        }
        return false;
    }
}
