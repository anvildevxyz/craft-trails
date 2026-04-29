<?php

namespace anvildev\trails\jobs;

use anvildev\trails\Trails;
use Craft;
use craft\queue\BaseJob;

class RetentionJob extends BaseJob
{
    public function execute($queue): void
    {
        $plugin = Trails::getInstance();
        $deleted = $plugin->retention->cleanupOldLogs();
        Craft::info("Retention cleanup: deleted {$deleted} logs", 'trails');

        if ($plugin->getSettings()->scheduledRetention) {
            $cache = Craft::$app->getCache();
            $cacheKey = 'trails_retention_job_queued';
            if ($cache->get($cacheKey) === false) {
                $cache->set($cacheKey, true, 82800);
                Craft::$app->getQueue()
                    ->delay(86400)
                    ->push(new self());
            }
        }
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('trails', 'Scheduled audit log retention cleanup');
    }
}
