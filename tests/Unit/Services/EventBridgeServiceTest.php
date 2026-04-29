<?php

declare(strict_types=1);

namespace anvildev\trails\tests\Unit\Services;

use anvildev\trails\services\EventBridgeService;
use anvildev\trails\tests\Support\TestCase;

class EventBridgeServiceTest extends TestCase
{
    public function testListenAcceptsValidEventType(): void
    {
        $bridge = new EventBridgeService();
        $bridge->listen('SomeClass', 'someEvent', 'myplugin.action', null);
        $this->assertSame(1, $bridge->getRegistrationCount());
    }

    public function testListenRejectsInvalidEventType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $bridge = new EventBridgeService();
        $bridge->listen('SomeClass', 'someEvent', 'invalid', null);
    }

    public function testListenRejectsReservedPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $bridge = new EventBridgeService();
        $bridge->listen('SomeClass', 'someEvent', 'element.saved', null);
    }

    public function testMultipleRegistrations(): void
    {
        $bridge = new EventBridgeService();
        $bridge->listen('A', 'event1', 'plugin.action1', null);
        $bridge->listen('B', 'event2', 'plugin.action2', null);
        $this->assertSame(2, $bridge->getRegistrationCount());
    }

    public function testBindAllCanBeCalledSafely(): void
    {
        $bridge = new EventBridgeService();
        $bridge->listen('NonExistentClass', 'someEvent', 'test.action', null);
        // bindAll should not throw even for non-existent classes
        // (Yii's Event::on accepts any string class name)
        $bridge->bindAll();
        $this->assertSame(1, $bridge->getRegistrationCount());
    }

    public function testBindAllIsIdempotent(): void
    {
        $bridge = new EventBridgeService();
        $bridge->listen('SomeClass', 'event', 'test.action', null);
        $bridge->bindAll();
        $bridge->bindAll(); // second call should be a no-op
        $this->assertSame(1, $bridge->getRegistrationCount());
    }
}
