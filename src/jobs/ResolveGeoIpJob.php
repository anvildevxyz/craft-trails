<?php

declare(strict_types=1);

namespace anvildev\trails\jobs;

use anvildev\trails\helpers\GeoIpResolver;
use anvildev\trails\models\Settings;
use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\Trails;
use craft\queue\BaseJob;

class ResolveGeoIpJob extends BaseJob
{
    public int $logId = 0;
    public string $ip = '';

    public function execute($queue): void
    {
        $plugin = \Craft::$app->getPlugins()->getPlugin('trails');
        if (!$plugin instanceof Trails) {
            return;
        }
        /** @var Settings $settings */
        $settings = $plugin->getSettings();
        if (!$settings->enableGeoIp) {
            return;
        }
        $endpoint = $settings->geoIpEndpoint;
        // Settings validation enforces https:// on save, but the field can be
        // cleared. Refuse to silently downgrade to http:// — visitor IPs are PII.
        if ($endpoint === '' || !str_starts_with($endpoint, 'https://')) {
            \Craft::warning('GeoIP endpoint is empty or non-HTTPS; skipping lookup for log ' . $this->logId, 'trails');
            return;
        }
        $result = GeoIpResolver::resolve($this->ip, $endpoint);
        if ($result === null) {
            return;
        }
        $record = AuditLogRecord::findOne($this->logId);
        if ($record === null) {
            return;
        }
        $record->country = $result['country'] ?: null;
        $record->region = $result['region'] ?: null;
        $record->city = $result['city'] ?: null;
        $record->save(false, ['country', 'region', 'city']);
    }

    protected function defaultDescription(): ?string
    {
        return 'Resolving geolocation for audit log ' . $this->logId;
    }
}
