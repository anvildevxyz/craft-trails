<?php

namespace anvildev\trails\tests\Unit\Events;

use anvildev\trails\events\AuditEvent;
use anvildev\trails\tests\Support\TestCase;

class AuditEventTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $event = new AuditEvent();

        $this->assertEquals('', $event->event);
        $this->assertNull($event->elementType);
        $this->assertNull($event->elementId);
        $this->assertEmpty($event->context);
        $this->assertNull($event->oldValues);
        $this->assertNull($event->newValues);
        $this->assertNull($event->record);
    }

    public function testIsValidDefaultsToTrue(): void
    {
        $event = new AuditEvent();

        $this->assertTrue($event->isValid);
    }

    public function testCanSuppressLogging(): void
    {
        $event = new AuditEvent();
        $event->isValid = false;

        $this->assertFalse($event->isValid);
    }

    public function testConstructorSetsProperties(): void
    {
        $event = new AuditEvent([
            'event' => 'element.created',
            'elementType' => 'craft\\elements\\Entry',
            'elementId' => 42,
            'context' => ['title' => 'Test'],
            'oldValues' => ['field' => 'old'],
            'newValues' => ['field' => 'new'],
        ]);

        $this->assertEquals('element.created', $event->event);
        $this->assertEquals('craft\\elements\\Entry', $event->elementType);
        $this->assertEquals(42, $event->elementId);
        $this->assertEquals(['title' => 'Test'], $event->context);
        $this->assertEquals(['field' => 'old'], $event->oldValues);
        $this->assertEquals(['field' => 'new'], $event->newValues);
    }
}
