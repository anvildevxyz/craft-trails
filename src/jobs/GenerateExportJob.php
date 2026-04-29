<?php

declare(strict_types=1);

namespace anvildev\trails\jobs;

use anvildev\trails\records\ExportRecord;
use anvildev\trails\Trails;
use Craft;
use craft\queue\BaseJob;

class GenerateExportJob extends BaseJob
{
    public int $exportId;

    public function execute($queue): void
    {
        $export = ExportRecord::findOne($this->exportId);

        if ($export === null) {
            Craft::error("GenerateExportJob: ExportRecord #{$this->exportId} not found.", 'trails');
            return;
        }

        try {
            // Mark as processing
            $export->status = 'processing';
            $export->save(false);

            // Decode criteria
            $criteria = $export->criteria ? json_decode($export->criteria, true) : [];
            $redactPii = (bool)($criteria['redactPii'] ?? false);
            $plugin = Craft::$app->getPlugins()->getPlugin('trails');
            if (!$plugin instanceof Trails) {
                throw new \RuntimeException('Trails plugin is not available.');
            }

            // Count total records
            $total = (int)$plugin->audit->buildQuery($criteria)->count();
            $export->totalRecords = $total;
            $export->save(false);
            $filePath = Trails::getInstance()->export->writeBackgroundExportFile(
                $export,
                $criteria,
                $redactPii,
                function(int $processed, int $total) use ($export, $queue): void {
                    if ($total <= 0) {
                        return;
                    }
                    $export->progress = (int) min(99, round(($processed / $total) * 100));
                    $export->save(false);
                    $this->setProgress($queue, $export->progress / 100);
                }
            );

            // Finalise
            $export->filePath = $filePath;
            $export->progress = 100;
            $export->status = 'complete';
            $export->save(false);
        } catch (\Throwable $e) {
            $export->status = 'failed';
            $export->save(false);
            Craft::error(
                "GenerateExportJob: Export #{$this->exportId} failed: " . $e->getMessage(),
                'trails'
            );
        }
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('trails', 'Generating audit log export');
    }
}
