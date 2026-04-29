<?php

namespace anvildev\trails\tests\Unit\Models;

use anvildev\trails\models\Settings;
use anvildev\trails\tests\Support\TestCase;

class SettingsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $settings = new Settings();

        $this->assertTrue($settings->enabled);
        $this->assertEquals(365, $settings->retentionDays);
        $this->assertTrue($settings->logElements);
        $this->assertTrue($settings->logAuthentication);
        $this->assertTrue($settings->logFailedLogins);
        $this->assertTrue($settings->logConfigChanges);
        $this->assertTrue($settings->logAssets);
        $this->assertTrue($settings->logPermissionChanges);
        $this->assertEmpty($settings->excludedElementTypes);
        $this->assertEmpty($settings->excludedSections);
        $this->assertTrue($settings->captureIpAddress);
        $this->assertFalse($settings->captureUserAgent); // Off by default — fingerprinting surface
        $this->assertFalse($settings->captureFieldChanges);
        $this->assertTrue($settings->anonymizeIp); // GDPR-safe default
        $this->assertFalse($settings->alertsEnabled);
        $this->assertNull($settings->alertEmail);
        $this->assertEquals(5, $settings->failedLoginThreshold);
        $this->assertFalse($settings->externalLoggingEnabled);
        $this->assertNull($settings->externalProvider);
        $this->assertNull($settings->externalEndpoint);
        $this->assertNull($settings->externalApiKey);
    }

    public function testDefaultAlertEvents(): void
    {
        $settings = new Settings();

        $this->assertContains('user.login.failed', $settings->alertEvents);
        $this->assertContains('element.deleted', $settings->alertEvents);
        $this->assertContains('user.permissions.changed', $settings->alertEvents);
    }

    public function testValidationPassesWithDefaults(): void
    {
        $settings = new Settings();

        $this->assertTrue($settings->validate());
    }

    public function testValidationPassesWithValidExternalProvider(): void
    {
        $settings = new Settings();
        $settings->externalProvider = 'splunk';

        $this->assertTrue($settings->validate());
    }

    public function testValidationPassesWithAllProviders(): void
    {
        $settings = new Settings();

        foreach (['splunk', 'datadog', 's3', 'webhook', null] as $provider) {
            $settings->externalProvider = $provider;
            $this->assertTrue($settings->validate(), "Provider '{$provider}' should be valid");
        }
    }

    public function testRetentionDaysRejectsNegative(): void
    {
        $settings = new Settings();
        $settings->retentionDays = -1;

        $this->assertFalse($settings->validate());
        $this->assertArrayHasKey('retentionDays', $settings->getErrors());
    }

    public function testRetentionDaysAcceptsZero(): void
    {
        $settings = new Settings();
        $settings->retentionDays = 0;

        $this->assertTrue($settings->validate());
    }

    public function testFailedLoginThresholdRejectsNegative(): void
    {
        $settings = new Settings();
        $settings->failedLoginThreshold = -1;

        $this->assertFalse($settings->validate());
        $this->assertArrayHasKey('failedLoginThreshold', $settings->getErrors());
    }

    public function testScheduledRetentionDefaultsToFalse(): void
    {
        $settings = new Settings();
        $this->assertFalse($settings->scheduledRetention);
    }

    public function testGeoIpEndpointDefaultIsHttps(): void
    {
        $settings = new Settings();
        $this->assertStringStartsWith(
            'https://',
            $settings->geoIpEndpoint,
            'GeoIP default endpoint must use HTTPS — visitor IPs are sensitive and a MitM could feed back arbitrary geo data'
        );
    }

    public function testGeoIpEndpointRejectsHttpScheme(): void
    {
        $settings = new Settings();
        $settings->geoIpEndpoint = 'http://ip-api.com/json/';

        $this->assertFalse(
            $settings->validate(['geoIpEndpoint']),
            'http:// URLs must be rejected by Settings validation'
        );
        $this->assertArrayHasKey('geoIpEndpoint', $settings->getErrors());
    }

    public function testGeoIpEndpointAcceptsHttpsScheme(): void
    {
        $settings = new Settings();
        $settings->geoIpEndpoint = 'https://example.com/geo/';

        $this->assertTrue($settings->validate(['geoIpEndpoint']));
    }

    public function testExternalEndpointStillRequiresHttps(): void
    {
        // Regression: the existing externalEndpoint HTTPS-only rule must not have
        // been weakened by the GeoIP rule changes.
        $settings = new Settings();
        $settings->externalEndpoint = 'http://example.com/webhook';

        $this->assertFalse($settings->validate(['externalEndpoint']));
        $this->assertArrayHasKey('externalEndpoint', $settings->getErrors());
    }
}
