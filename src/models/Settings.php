<?php

namespace anvildev\trails\models;

use craft\base\Model;

class Settings extends Model
{
    public bool $enabled = true;
    public int $retentionDays = 365;

    public bool $logElements = true;
    public bool $logAuthentication = true;
    public bool $logFailedLogins = true;
    public bool $logConfigChanges = true;
    public bool $logAssets = true;
    public bool $logPermissionChanges = true;
    public array $excludedElementTypes = [];
    public array $excludedSections = [];

    public bool $captureIpAddress = true;
    // Off by default — UA strings are a fingerprinting surface and not required
    // for the core audit trail. Operators can opt in for forensic detail.
    public bool $captureUserAgent = false;
    public bool $captureFieldChanges = false;
    public bool $anonymizeIp = true;
    public array $excludedFieldHandles = [
        'password', 'apiKey', 'secretKey', 'secret', 'token',
        'accessToken', 'refreshToken', 'privateKey', 'encryptionKey',
    ];
    public int $maxSnapshotBytes = 1048576;

    public bool $alertsEnabled = false;
    public ?string $alertEmail = null;
    public array $alertEvents = [
        'user.login.failed',
        'element.deleted',
        'user.permissions.changed',
    ];
    public int $failedLoginThreshold = 5;
    public int $alertCooldownMinutes = 60;

    public bool $externalLoggingEnabled = false;
    public ?string $externalProvider = null;
    public ?string $externalEndpoint = null;
    public ?string $externalApiKey = null;
    public int $externalBatchSize = 50;

    public bool $scheduledRetention = false;

    public int $logRateLimit = 0;

    public int $merkleBatchSize = 256;
    public int $inlineExportLimit = 10000;

    /**
     * How long a background-export file lives on disk before garbage collection
     * removes it. Tune down for high-volume installs that produce large files;
     * tune up if operators need more time to download.
     */
    public int $exportRetentionHours = 24;
    public string $realtime = 'poll'; // 'poll' or 'sse'
    public int $maxSseConnections = 5;

    public string $webhookSecret = '';

    public bool $enableApi = true;
    public bool $enableGraphql = true;
    public int $apiRateLimit = 60;

    public bool $enableGeoIp = false;
    // GeoIP lookups MUST go over HTTPS — visitor IPs are sensitive and a MitM
    // could feed back arbitrary geo data that we'd then store as audit metadata.
    // Note: ip-api.com's free tier does not support HTTPS; configure a paid/alternate
    // endpoint (or set this empty to disable lookups entirely) before enabling GeoIP.
    public string $geoIpEndpoint = 'https://pro.ip-api.com/json/';

    public ?string $anchorType = null; // null, 's3', or 'rfc3161'
    public string $s3Bucket = '';
    public string $s3Region = '';
    public string $s3AccessKeyId = '';
    public string $s3SecretAccessKey = '';

    /**
     * Custom S3 endpoint URL (e.g. for MinIO or LocalStack). Empty to target
     * real AWS S3.
     */
    public string $s3Endpoint = '';

    /**
     * Use path-style addressing for S3 requests. Required for MinIO and most
     * S3-compatible servers; AWS S3 supports both styles.
     */
    public bool $s3UsePathStyle = false;

    public string $tsaUrl = 'https://freetsa.org/tsr';

    /** Filesystem path to a PEM bundle containing the TSA's trust root(s). */
    public string $tsaTrustedCaBundle = '';

    /** Inline PEM for the TSA trust root(s). Used when tsaTrustedCaBundle is empty. */
    public string $tsaCaBundlePem = '';

    public function defineRules(): array
    {
        return [
            [['enabled', 'logElements', 'logAuthentication', 'logFailedLogins', 'logConfigChanges', 'logAssets', 'logPermissionChanges', 'scheduledRetention'], 'boolean'],
            [['captureIpAddress', 'captureUserAgent', 'captureFieldChanges', 'anonymizeIp'], 'boolean'],
            [['alertsEnabled', 'externalLoggingEnabled'], 'boolean'],
            [['retentionDays', 'failedLoginThreshold', 'logRateLimit'], 'integer', 'min' => 0],
            [['externalBatchSize'], 'integer', 'min' => 1],
            [['maxSnapshotBytes'], 'integer', 'min' => 0, 'max' => 10485760],
            [['alertCooldownMinutes'], 'integer', 'min' => 1],
            [['alertEmail'], 'email'],
            [['externalProvider'], 'in', 'range' => ['splunk', 'datadog', 's3', 'webhook', null]],
            [['externalEndpoint'], 'url', 'validSchemes' => ['https']],
            [['webhookSecret'], 'string'],
            [['excludedFieldHandles'], 'each', 'rule' => ['string']],
            [['enableGeoIp'], 'boolean'],
            [['geoIpEndpoint'], 'url', 'validSchemes' => ['https']],
            [['merkleBatchSize'], 'integer', 'min' => 16, 'max' => 4096],
            [['anchorType'], 'in', 'range' => ['s3', 'rfc3161', null]],
            [['s3Bucket', 's3Region', 's3AccessKeyId', 's3SecretAccessKey', 's3Endpoint'], 'string'],
            [['s3UsePathStyle'], 'boolean'],
            [['tsaUrl'], 'url', 'validSchemes' => ['https']],
            [['tsaTrustedCaBundle', 'tsaCaBundlePem'], 'string'],
            [
                'tsaTrustedCaBundle',
                function($attribute) {
                    if ($this->$attribute === '') {
                        return;
                    }
                    if (!is_file($this->$attribute) || !is_readable($this->$attribute)) {
                        $this->addError($attribute, "CA bundle file not readable: {$this->$attribute}");
                    }
                },
            ],
            [['inlineExportLimit'], 'integer', 'min' => 100],
            [['exportRetentionHours'], 'integer', 'min' => 1, 'max' => 8760],
            [['realtime'], 'in', 'range' => ['poll', 'sse']],
            [['maxSseConnections'], 'integer', 'min' => 1, 'max' => 50],
            [['enableApi', 'enableGraphql'], 'boolean'],
            [['apiRateLimit'], 'integer', 'min' => 0],
        ];
    }
}
