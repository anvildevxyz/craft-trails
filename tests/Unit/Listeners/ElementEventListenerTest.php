<?php

namespace anvildev\trails\tests\Unit\Listeners;

use anvildev\trails\listeners\ElementEventListener;
use anvildev\trails\models\Settings;
use anvildev\trails\services\AuditService;
use anvildev\trails\tests\Support\TestCase;
use Mockery;

class ElementEventListenerTest extends TestCase
{
    public function testConstructorAcceptsDependencies(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $settings = new Settings();
        $listener = new ElementEventListener($audit, $settings);
        $this->assertInstanceOf(ElementEventListener::class, $listener);
    }

    public function testIsElementIncludedExcludesConfiguredTypes(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $settings = new Settings();
        $settings->excludedElementTypes = [\craft\elements\Asset::class];
        $listener = new ElementEventListener($audit, $settings);

        $method = new \ReflectionMethod($listener, 'isElementIncluded');
        $method->setAccessible(true);

        // Suppress PHP 8.4 deprecation warnings triggered during Mockery proxy class generation
        // for Craft CMS elements that have implicit nullable parameters.
        $previousReporting = error_reporting(E_ALL & ~E_DEPRECATED);
        $asset = Mockery::mock(\craft\elements\Asset::class);
        $entry = Mockery::mock(\craft\elements\Entry::class);
        error_reporting($previousReporting);

        $this->assertFalse($method->invoke($listener, $asset));
        $this->assertTrue($method->invoke($listener, $entry));
    }

    public function testCalculateFieldChangesDetectsDifferences(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $settings = new Settings();
        $listener = new ElementEventListener($audit, $settings);

        $method = new \ReflectionMethod($listener, 'calculateFieldChanges');
        $method->setAccessible(true);

        $old = ['title' => 'Old Title', 'slug' => 'old', 'enabled' => true, 'fields' => ['body' => 'old text']];
        $new = ['title' => 'New Title', 'slug' => 'old', 'enabled' => true, 'fields' => ['body' => 'new text']];

        $result = $method->invoke($listener, $old, $new);
        $this->assertSame('Old Title', $result['old']['title']);
        $this->assertSame('New Title', $result['new']['title']);
        $this->assertSame('old text', $result['old']['fields']['body']);
        $this->assertSame('new text', $result['new']['fields']['body']);
        $this->assertArrayNotHasKey('slug', $result['old']);
    }

    public function testCalculateFieldChangesReturnsEmptyWhenNoChanges(): void
    {
        $audit = Mockery::mock(AuditService::class);
        $settings = new Settings();
        $listener = new ElementEventListener($audit, $settings);

        $method = new \ReflectionMethod($listener, 'calculateFieldChanges');
        $method->setAccessible(true);

        $snapshot = ['title' => 'Same', 'slug' => 'same', 'enabled' => true];
        $result = $method->invoke($listener, $snapshot, $snapshot);
        $this->assertEmpty($result);
    }
}
