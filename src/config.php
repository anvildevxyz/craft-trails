<?php

/**
 * Trails config.php
 *
 * Copy this file to config/trails.php and modify as needed.
 * Any settings here override the CP settings.
 *
 * @see anvildev\trails\models\Settings for available settings.
 */

return [
    '*' => [
        // Core
        // 'enabled' => true,
        // 'retentionDays' => 365,
        // 'logRateLimit' => 0,

        // Logging
        // 'logElements' => true,
        // 'logAuthentication' => true,
        // 'logFailedLogins' => true,
        // 'logConfigChanges' => true,
        // 'logAssets' => true,
        // 'logPermissionChanges' => true,

        // Capture
        // 'captureIpAddress' => true,
        // 'captureUserAgent' => true,
        // 'captureFieldChanges' => true,
        // 'anonymizeIp' => true,

        // Integrity
        // 'merkleBatchSize' => 256,

        // Export
        // 'inlineExportLimit' => 10000,

        // Real-time: 'poll' (default) or 'sse'
        // 'realtime' => 'poll',
        // 'maxSseConnections' => 5,

        // External Shipping
        // 'externalLoggingEnabled' => false,
        // 'externalProvider' => null,     // 'splunk', 'datadog', 's3', 'webhook'
        // 'externalEndpoint' => null,
        // 'externalApiKey' => null,
        // 'externalBatchSize' => 50,
        // 'webhookSecret' => '',

        // GeoIP
        // 'enableGeoIp' => false,
        // 'geoIpEndpoint' => 'https://pro.ip-api.com/json/',

        // Alerts
        // 'alertsEnabled' => false,
        // 'alertEmail' => null,
        // 'failedLoginThreshold' => 5,
        // 'alertCooldownMinutes' => 60,

        // API
        // 'enableApi' => true,
        // 'enableGraphql' => true,
        // 'apiRateLimit' => 60,    // requests per minute per token (0 = unlimited)
    ],

    'dev' => [
        // 'alertsEnabled' => false,
        // 'externalLoggingEnabled' => false,
    ],

    'production' => [
        // 'alertsEnabled' => true,
        // 'externalLoggingEnabled' => true,
    ],
];
