<?php

declare(strict_types=1);

namespace anvildev\trails\jobs;

use anvildev\trails\Trails;
use Craft;
use craft\queue\BaseJob;

/**
 * Rotates the active trails_logs table to a monthly archive.
 * Triggered on the 1st of each month via Craft's GC hook.
 */
class RotateLogTableJob extends BaseJob
{
    public function execute($queue): void
    {
        Trails::getInstance()->tableRotation->rotate();
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('trails', 'Rotating audit log table to monthly archive');
    }
}
