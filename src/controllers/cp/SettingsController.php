<?php

namespace anvildev\trails\controllers\cp;

use anvildev\trails\Trails;
use Craft;
use yii\web\Response;

class SettingsController extends BaseCpController
{
    protected function requiredPermission(): string
    {
        return 'trails-manageSettings';
    }

    public function actionGeneral(): Response
    {
        return $this->renderTemplate('trails/settings/general', [
            'settings' => Trails::getInstance()->getSettings(),
            'retentionStats' => Trails::getInstance()->retention->getRetentionStats(),
        ]);
    }

    public function actionSecurity(): Response
    {
        return $this->renderTemplate('trails/settings/security', [
            'settings' => Trails::getInstance()->getSettings(),
        ]);
    }

    public function actionIntegrations(): Response
    {
        return $this->renderTemplate('trails/settings/integrations', [
            'settings' => Trails::getInstance()->getSettings(),
        ]);
    }

    public function actionLogging(): Response
    {
        return $this->renderTemplate('trails/settings/logging', [
            'settings' => Trails::getInstance()->getSettings(),
        ]);
    }

    public function actionCapture(): Response
    {
        return $this->renderTemplate('trails/settings/capture', [
            'settings' => Trails::getInstance()->getSettings(),
        ]);
    }

    public function actionAlerts(): Response
    {
        return $this->renderTemplate('trails/settings/alerts', [
            'settings' => Trails::getInstance()->getSettings(),
        ]);
    }

    public function actionExternal(): Response
    {
        return $this->renderTemplate('trails/settings/external', [
            'settings' => Trails::getInstance()->getSettings(),
        ]);
    }

    public function actionSave(): Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $settings = Trails::getInstance()->getSettings();

        $paramTypes = [
            'bool' => ['enabled', 'logElements', 'logAuthentication', 'logFailedLogins', 'logPermissionChanges', 'logConfigChanges', 'logAssets', 'captureIpAddress', 'captureUserAgent', 'captureFieldChanges', 'anonymizeIp', 'alertsEnabled', 'externalLoggingEnabled', 'scheduledRetention', 'enableGeoIp', 'enableApi', 'enableGraphql'],
            'int' => ['retentionDays', 'failedLoginThreshold', 'logRateLimit', 'alertCooldownMinutes', 'merkleBatchSize', 'externalBatchSize', 'apiRateLimit', 'maxSseConnections'],
            'string' => ['alertEmail', 'externalProvider', 'externalEndpoint', 'externalApiKey', 'webhookSecret', 'geoIpEndpoint', 'anchorType', 's3Bucket', 's3Region', 's3AccessKeyId', 's3SecretAccessKey', 'tsaUrl', 'tsaTrustedCaBundle', 'tsaCaBundlePem', 'realtime'],
            'array' => ['excludedElementTypes', 'excludedSections', 'alertEvents'],
        ];

        foreach ($paramTypes as $type => $params) {
            foreach ($params as $param) {
                if (($v = $request->getBodyParam($param)) !== null) {
                    $settings->$param = match ($type) {
                        'bool' => (bool)$v,
                        'int' => (int)$v,
                        'array' => $v ?: [],
                        default => $v,
                    };
                }
            }
        }

        // excludedFieldHandles comes from an editable table as [{handle: "..."}]
        if (($rawHandles = $request->getBodyParam('excludedFieldHandles')) !== null) {
            $settings->excludedFieldHandles = array_values(array_filter(array_map(
                fn($row) => is_array($row) ? trim($row['handle'] ?? '') : trim((string)$row),
                $rawHandles
            )));
        }

        // Normalize anchorType: empty string from select means null
        if ($settings->anchorType === '') {
            $settings->anchorType = null;
        }

        if (!$settings->validate() || !Craft::$app->getPlugins()->savePluginSettings(Trails::getInstance(), $settings->toArray())) {
            Craft::$app->getSession()->setError(Craft::t('trails', 'Couldn\'t save settings.'));
            return $this->redirectToPostedUrl();
        }

        // Bootstrap retention job if just enabled
        if ($settings->scheduledRetention) {
            $cache = Craft::$app->getCache();
            $cacheKey = 'trails_retention_job_queued';
            if ($cache->get($cacheKey) === false) {
                $cache->set($cacheKey, true, 82800);
                Craft::$app->getQueue()->delay(86400)->push(new \anvildev\trails\jobs\RetentionJob());
            }
        }

        Craft::$app->getSession()->setNotice(Craft::t('trails', 'Settings saved.'));
        return $this->redirectToPostedUrl();
    }

    public function actionCleanup(): Response
    {
        $this->requirePostRequest();

        $deleted = Trails::getInstance()->retention->cleanupOldLogs();
        Craft::$app->getSession()->setNotice(
            Craft::t('trails', 'Deleted {count} old log entries.', ['count' => $deleted])
        );

        return $this->redirect('trails/settings/general');
    }
}
