<?php

namespace anvildev\trails\dto;

/**
 * Read-only value object representing a Merkle inclusion proof for a single audit log record.
 *
 * Has no Craft/Yii2 dependencies and is safe to use anywhere.
 */
final class MerkleProof
{
    public function __construct(
        /** Hash of the record being proven. */
        public readonly string $leafHash,

        /** Merkle root this proof resolves to. */
        public readonly string $rootHash,

        /**
         * Ordered list of sibling hashes along the proof path.
         * Each element is ['hash' => string, 'position' => 'left'|'right'].
         *
         * @var array<int, array{hash: string, position: 'left'|'right'}>
         */
        public readonly array $path,

        /** 0-based position of the leaf in the tree. */
        public readonly int $leafIndex,

        /** Total number of leaves in the tree. */
        public readonly int $treeSize,

        /** Whether the proof was successfully verified. */
        public readonly bool $verified,
    ) {
    }

    /**
     * Return all properties as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'leafHash' => $this->leafHash,
            'rootHash' => $this->rootHash,
            'path' => $this->path,
            'leafIndex' => $this->leafIndex,
            'treeSize' => $this->treeSize,
            'verified' => $this->verified,
        ];
    }
}
