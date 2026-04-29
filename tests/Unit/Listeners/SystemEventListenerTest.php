<?php

namespace anvildev\trails\tests\Unit\Listeners;

use anvildev\trails\listeners\SystemEventListener;
use anvildev\trails\models\Settings;
use anvildev\trails\services\AuditService;
use anvildev\trails\tests\Support\TestCase;
use Mockery;

class SystemEventListenerTest extends TestCase
{
    public function testConstructorAcceptsDependencies(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $settings = new Settings();
        $listener = new SystemEventListener($audit, $settings);
        $this->assertInstanceOf(SystemEventListener::class, $listener);
    }

    public function testRegistersConfigListenerWhenEnabled(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $settings = new Settings();
        $settings->logConfigChanges = true;
        $listener = new SystemEventListener($audit, $settings);

        \yii\base\Event::offAll();
        // Suppress PHP 8.4 deprecation warnings triggered by Craft CMS internals
        // (implicit nullable parameters) when attaching/resolving event handlers.
        $previousReporting = error_reporting(E_ALL & ~E_DEPRECATED);
        $listener->register();
        error_reporting($previousReporting);

        $this->assertTrue(
            \yii\base\Event::hasHandlers(\craft\services\ProjectConfig::class, \craft\services\ProjectConfig::EVENT_AFTER_APPLY_CHANGES)
        );
    }

    public function testSkipsConfigListenerWhenDisabled(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $settings = new Settings();
        $settings->logPermissionChanges = false;
        $settings->logConfigChanges = false;
        $listener = new SystemEventListener($audit, $settings);

        \yii\base\Event::offAll();
        // Suppress PHP 8.4 deprecation warnings triggered by Craft CMS internals
        // (implicit nullable parameters) when attaching/resolving event handlers.
        $previousReporting = error_reporting(E_ALL & ~E_DEPRECATED);
        $listener->register();
        error_reporting($previousReporting);

        $this->assertFalse(
            \yii\base\Event::hasHandlers(\craft\services\ProjectConfig::class, \craft\services\ProjectConfig::EVENT_AFTER_APPLY_CHANGES)
        );
    }
}
