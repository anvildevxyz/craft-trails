<?php

declare(strict_types=1);

namespace anvildev\trails\anchors;

use anvildev\trails\records\MerkleRootRecord;

interface AnchorBackendInterface
{
    /** @return array{anchorRef: string, anchorProof: string} */
    public function anchor(MerkleRootRecord $root): array;

    public function verify(string $anchorRef, string $anchorProof, string $rootHash): bool;
}
