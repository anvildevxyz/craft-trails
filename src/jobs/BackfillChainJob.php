<?php

declare(strict_types=1);

namespace anvildev\trails\jobs;

use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\Trails;
use Craft;
use craft\queue\BaseJob;

class BackfillChainJob extends BaseJob
{
    public int $batchSize = 500;

    public function execute($queue): void
    {
        $audit = Trails::getInstance()->audit;
        $lastId = 0;
        $processed = 0;

        while (true) {
            /** @var AuditLogRecord[] $records */
            $records = AuditLogRecord::find()
                ->where(['>', 'id', $lastId])
                ->andWhere(['prevHash' => null])
                ->andWhere(['is not', 'chainPosition', null])
                ->orderBy(['chainPosition' => SORT_ASC])
                ->limit($this->batchSize)
                ->all();

            if (empty($records)) {
                break;
            }

            foreach ($records as $record) {
                if ($record->chainPosition > 1) {
                    $prev = AuditLogRecord::find()
                        ->where(['chainPosition' => $record->chainPosition - 1])
                        ->select(['hash'])
                        ->scalar();

                    if ($prev) {
                        $record->prevHash = $prev;
                        $record->hash = $audit->recalculateHash($record);
                        $record->save(false);
                    }
                }

                $lastId = $record->id;
                $processed++;
            }

            $this->setProgress($queue, $processed);
        }

        Craft::info("Trails: Backfilled chain for {$processed} records", 'trails');
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('trails', 'Backfilling audit log chain hashes');
    }
}
