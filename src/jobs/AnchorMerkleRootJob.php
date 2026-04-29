<?php

declare(strict_types=1);

namespace anvildev\trails\jobs;

use anvildev\trails\records\MerkleRootRecord;
use anvildev\trails\Trails;
use Craft;
use craft\queue\BaseJob;

class AnchorMerkleRootJob extends BaseJob
{
    public int $merkleRootId;

    public function execute($queue): void
    {
        $root = MerkleRootRecord::findOne($this->merkleRootId);
        if (!$root) {
            Craft::warning("Trails: Merkle root #{$this->merkleRootId} not found for anchoring", 'trails');
            return;
        }

        Trails::getInstance()->anchor->anchor($root);
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('trails', 'Anchoring Merkle root #{id}', ['id' => $this->merkleRootId]);
    }
}
