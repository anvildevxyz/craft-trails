<?php

namespace anvildev\trails\tests\Unit\Listeners;

use anvildev\trails\listeners\AuthEventListener;
use anvildev\trails\models\Settings;
use anvildev\trails\services\AuditService;
use anvildev\trails\tests\Support\TestCase;
use Mockery;

class AuthEventListenerTest extends TestCase
{
    public function testConstructorAcceptsDependencies(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $settings = new Settings();
        $listener = new AuthEventListener($audit, $settings);
        $this->assertInstanceOf(AuthEventListener::class, $listener);
    }

    public function testGdprAnonymizationAlwaysRegistered(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $settings = new Settings();
        $settings->logAuthentication = false;
        $settings->logFailedLogins = false;
        $listener = new AuthEventListener($audit, $settings);
        // Suppress PHP 8.4 deprecation warnings triggered by Craft CMS internals
        // (implicit nullable parameters) when attaching/resolving event handlers.
        $previousReporting = error_reporting(E_ALL & ~E_DEPRECATED);
        $listener->register();
        $hasHandlers = \yii\base\Event::hasHandlers(\craft\services\Elements::class, \craft\services\Elements::EVENT_AFTER_DELETE_ELEMENT);
        error_reporting($previousReporting);
        $this->assertTrue($hasHandlers);
    }
}
