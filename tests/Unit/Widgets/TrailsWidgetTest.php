<?php

namespace anvildev\trails\tests\Unit\Widgets;

use anvildev\trails\tests\Support\TestCase;
use anvildev\trails\widgets\TrailsWidget;

class TrailsWidgetTest extends TestCase
{
    public function testExtendsWidget(): void
    {
        $widget = new TrailsWidget();
        $this->assertInstanceOf(\craft\base\Widget::class, $widget);
    }

    public function testDisplayNameReturnsString(): void
    {
        $name = TrailsWidget::displayName();
        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    public function testDefaultDaysIsSeven(): void
    {
        $widget = new TrailsWidget();
        $this->assertSame(7, $widget->days);
    }

    public function testValidationAcceptsAllowedDaysValues(): void
    {
        foreach ([7, 14, 30] as $days) {
            $widget = new TrailsWidget(['days' => $days]);
            $this->assertTrue($widget->validate(['days']), "days=$days should be valid");
        }
    }

    public function testValidationRejectsInvalidDaysValue(): void
    {
        $widget = new TrailsWidget(['days' => 5]);
        $this->assertFalse($widget->validate(['days']));
    }
}
