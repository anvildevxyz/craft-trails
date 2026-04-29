<?php

declare(strict_types=1);

namespace anvildev\trails\jobs;

use anvildev\trails\Trails;
use Craft;
use craft\queue\BaseJob;

class ComputeMerkleRootJob extends BaseJob
{
    public int $batchStartPosition;
    public int $batchEndPosition;

    public function execute($queue): void
    {
        $root = Trails::getInstance()->merkle->computeBatch(
            $this->batchStartPosition,
            $this->batchEndPosition
        );

        if ($root !== null) {
            $settings = Trails::getInstance()->getSettings();
            if (!empty($settings->anchorType)) {
                Craft::$app->getQueue()->push(new AnchorMerkleRootJob([
                    'merkleRootId' => $root->id,
                ]));
            }
        }
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('trails', 'Computing Merkle root for positions {start}-{end}', [
            'start' => $this->batchStartPosition,
            'end' => $this->batchEndPosition,
        ]);
    }
}
