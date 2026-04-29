<?php

namespace anvildev\trails\tests\Unit\Services;

use anvildev\trails\services\AuditService;
use anvildev\trails\tests\Support\TestCase;
use ReflectionMethod;

class AuditServiceTest extends TestCase
{
    private AuditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditService();
    }

    // =========================================================================
    // getCategoryFromEvent()
    // =========================================================================

    public function testGetCategoryFromElementEvent(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'getCategoryFromEvent');
        $method->setAccessible(true);

        $this->assertEquals('element', $method->invoke($this->service, 'element.created'));
        $this->assertEquals('element', $method->invoke($this->service, 'element.updated'));
        $this->assertEquals('element', $method->invoke($this->service, 'element.deleted'));
        $this->assertEquals('element', $method->invoke($this->service, 'element.restored'));
    }

    public function testGetCategoryFromUserEvent(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'getCategoryFromEvent');
        $method->setAccessible(true);

        $this->assertEquals('user', $method->invoke($this->service, 'user.login'));
        $this->assertEquals('user', $method->invoke($this->service, 'user.logout'));
        $this->assertEquals('user', $method->invoke($this->service, 'user.login.failed'));
    }

    public function testGetCategoryFromAssetEvent(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'getCategoryFromEvent');
        $method->setAccessible(true);

        $this->assertEquals('asset', $method->invoke($this->service, 'asset.uploaded'));
        $this->assertEquals('asset', $method->invoke($this->service, 'asset.replaced'));
    }

    public function testGetCategoryFromConfigEvent(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'getCategoryFromEvent');
        $method->setAccessible(true);

        $this->assertEquals('config', $method->invoke($this->service, 'config.changed'));
    }

    public function testGetCategoryFromCustomEvent(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'getCategoryFromEvent');
        $method->setAccessible(true);

        $this->assertEquals('payment', $method->invoke($this->service, 'payment.processed'));
        $this->assertEquals('custom', $method->invoke($this->service, 'custom.myevent'));
    }

    public function testGetCategoryFromEventWithNoDot(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'getCategoryFromEvent');
        $method->setAccessible(true);

        $this->assertEquals('general', $method->invoke($this->service, 'general'));
    }

    // =========================================================================
    // anonymizeIpAddress()
    // =========================================================================

    public function testAnonymizeIpV4ZerosLastTwoOctets(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'anonymizeIpAddress');
        $method->setAccessible(true);

        $this->assertEquals('192.168.0.0', $method->invoke($this->service, '192.168.1.42'));
        $this->assertEquals('10.0.0.0', $method->invoke($this->service, '10.0.0.255'));
        $this->assertEquals('1.2.0.0', $method->invoke($this->service, '1.2.3.4'));
    }

    public function testAnonymizeIpV6ZerosLast5Segments(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'anonymizeIpAddress');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        $this->assertEquals('2001:0db8:85a3:0000:0000:0000:0000:0000', $result);
    }

    public function testAnonymizeIpV6PreservesFirst3SegmentsOnly(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'anonymizeIpAddress');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'fe80:1234:5678:9abc:def0:1111:2222:3333');
        $parts = explode(':', $result);

        // First 3 segments preserved (/48)
        $this->assertEquals('fe80', $parts[0]);
        $this->assertEquals('1234', $parts[1]);
        $this->assertEquals('5678', $parts[2]);

        // Last 5 segments zeroed
        $this->assertEquals('0000', $parts[3]);
        $this->assertEquals('0000', $parts[4]);
        $this->assertEquals('0000', $parts[5]);
        $this->assertEquals('0000', $parts[6]);
        $this->assertEquals('0000', $parts[7]);
    }

    public function testAnonymizeInvalidIpReturnsUnchanged(): void
    {
        $method = new ReflectionMethod(AuditService::class, 'anonymizeIpAddress');
        $method->setAccessible(true);

        $this->assertEquals('not-an-ip', $method->invoke($this->service, 'not-an-ip'));
    }

    // =========================================================================
    // Event constants
    // =========================================================================

    public function testEventConstantsExist(): void
    {
        $this->assertEquals('beforeLog', AuditService::EVENT_BEFORE_LOG);
        $this->assertEquals('afterLog', AuditService::EVENT_AFTER_LOG);
    }

    // =========================================================================
    // assertValidEventType()
    // =========================================================================

    /**
     * @dataProvider validEventTypeProvider
     */
    public function testAssertValidEventTypeAcceptsValidFormats(string $type): void
    {
        // Should not throw
        AuditService::assertValidEventType($type);
        $this->addToAssertionCount(1);
    }

    public static function validEventTypeProvider(): array
    {
        return [
            'plugin-name.action'           => ['booked.reservation_cancelled'],
            'hyphenated-plugin.action_1'   => ['my-plugin.action_1'],
            'single-char namespace'        => ['A.B'],
            'underscores in both'          => ['my_plugin.my_action'],
            'numbers in namespace'         => ['plugin2.event99'],
        ];
    }

    /**
     * @dataProvider invalidEventTypeProvider
     */
    public function testAssertValidEventTypeRejectsInvalidFormats(string $type): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AuditService::assertValidEventType($type);
    }

    public static function invalidEventTypeProvider(): array
    {
        return [
            'no dot'             => ['no-dot-here'],
            'too many dots'      => ['too.many.dots'],
            'only a dot'         => ['.'],
            'leading dot'        => ['.b'],
            'trailing dot'       => ['a.'],
            'space in namespace' => ['with space.here'],
            'empty string'       => [''],
        ];
    }

    // =========================================================================
    // logCustomEvent() — validation path (no Craft app required)
    // =========================================================================

    public function testLogCustomEventThrowsOnInvalidEventType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->logCustomEvent('no-dot-here', 'booking');
    }

    public function testLogCustomEventThrowsOnTooManyDots(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->logCustomEvent('too.many.dots', 'workflow');
    }

    public function testLogCustomEventThrowsOnEmptyEventType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->logCustomEvent('', 'category');
    }

    public function testLogCustomEventMethodSignatureIsCorrect(): void
    {
        $method = new \ReflectionMethod(AuditService::class, 'logCustomEvent');
        $params = $method->getParameters();

        $names = array_map(fn($p) => $p->getName(), $params);
        $this->assertContains('eventType', $names);
        $this->assertContains('category', $names);
        $this->assertContains('description', $names);
        $this->assertContains('metadata', $names);
        $this->assertContains('elementId', $names);
        $this->assertContains('elementType', $names);
        $this->assertContains('elementTitle', $names);

        // All params after the first two must be optional
        foreach (array_slice($params, 2) as $param) {
            $this->assertTrue($param->isOptional(), "Parameter \${$param->getName()} should be optional");
        }
    }

    // =========================================================================
    // getDailyActivity()
    // =========================================================================

    public function testGetDailyActivityMethodExists(): void
    {
        $this->assertTrue(method_exists(\anvildev\trails\services\AuditService::class, 'getDailyActivity'));
    }

    public function testGetDailyActivityAcceptsIntParameter(): void
    {
        $method = new \ReflectionMethod(\anvildev\trails\services\AuditService::class, 'getDailyActivity');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('days', $params[0]->getName());
        $this->assertSame(7, $params[0]->getDefaultValue());
    }

    // =========================================================================
    // logCustomEvent() — reserved prefix validation
    // =========================================================================

    /**
     * @dataProvider reservedPrefixProvider
     */
    public function testLogCustomEventRejectsReservedPrefixes(string $eventType): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/reserved for system events/');
        $this->service->logCustomEvent($eventType, 'test');
    }

    public static function reservedPrefixProvider(): array
    {
        return [
            'element prefix'  => ['element.deleted'],
            'user prefix'     => ['user.login'],
            'asset prefix'    => ['asset.uploaded'],
            'config prefix'   => ['config.changed'],
            'audit prefix'    => ['audit.purged'],
            'trails prefix'   => ['trails.internal'],
            'auth prefix'     => ['auth.token_issued'],
            'case insensitive element' => ['Element.Created'],
            'case insensitive user'    => ['USER.logout'],
        ];
    }

    public function testLogCustomEventAllowsNonReservedPrefixes(): void
    {
        // Should not throw on valid custom prefixes
        try {
            $this->service->logCustomEvent('myplugin.action', 'booking');
            $this->assertTrue(true);
        } catch (\InvalidArgumentException $e) {
            $this->fail('Did not expect InvalidArgumentException: ' . $e->getMessage());
        } catch (\Throwable $e) {
            // Other exceptions (e.g., DB not available) are acceptable
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // logCustomEvent() — metadata size validation
    // =========================================================================

    public function testLogCustomEventRejectsMetadataOverSizeLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/exceeds \d+ bytes/');
        // Build a huge metadata array: a single string of 70000 bytes
        $huge = ['data' => str_repeat('x', 70000)];
        $this->service->logCustomEvent('myplugin.action', 'test', metadata: $huge);
    }

    public function testLogCustomEventRejectsUnencodableMetadata(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/could not be JSON-encoded/');
        // Resource type cannot be JSON-encoded
        $resource = fopen('php://memory', 'r');
        try {
            $this->service->logCustomEvent('myplugin.action', 'test', metadata: ['fh' => $resource]);
        } finally {
            fclose($resource);
        }
    }

    public function testLogCustomEventAcceptsMetadataAtLimit(): void
    {
        // This should NOT throw an InvalidArgumentException. Create metadata just under the limit.
        $size = \anvildev\trails\services\AuditService::MAX_CUSTOM_METADATA_BYTES - 200;
        $data = ['payload' => str_repeat('x', $size)];
        try {
            $this->service->logCustomEvent('myplugin.action', 'test', metadata: $data);
            $this->assertTrue(true); // made it past validation
        } catch (\InvalidArgumentException $e) {
            $this->fail('Did not expect InvalidArgumentException: ' . $e->getMessage());
        } catch (\Throwable $e) {
            // Other exceptions (e.g., DB init) are acceptable — validation passed
            $this->assertTrue(true);
        }
    }
}
