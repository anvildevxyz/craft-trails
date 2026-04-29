<?php

declare(strict_types=1);

namespace anvildev\trails\services;

use anvildev\trails\dto\MerkleProof;
use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\records\MerkleRootRecord;
use craft\base\Component;

/**
 * Implements a binary Merkle tree for tamper-evident audit log verification.
 *
 * Static methods are pure functions with no dependencies and are safe to call
 * without a full Craft/Yii2 context. DB-dependent methods require Craft to be
 * initialized and should be accessed via Trails::getInstance()->merkle.
 */
class MerkleService extends Component
{
    // =========================================================================
    // Static / pure methods
    // =========================================================================

    /**
     * Hash a leaf node.
     *
     * Prefixes data with 0x00 to prevent second-preimage attacks (RFC 6962).
     */
    public static function hashLeaf(string $data): string
    {
        return hash('sha256', "\x00" . $data);
    }

    /**
     * Hash an internal (pair) node.
     *
     * Prefixes with 0x01 to distinguish from leaf hashes.
     */
    public static function hashPair(string $left, string $right): string
    {
        return hash('sha256', "\x01" . $left . $right);
    }

    /**
     * Compute the Merkle root for an array of raw record hashes.
     *
     * @param string[] $hashes Raw record hash strings (not yet leaf-hashed).
     *
     * @throws \InvalidArgumentException If the array is empty.
     */
    public static function computeRoot(array $hashes): string
    {
        if (empty($hashes)) {
            throw new \InvalidArgumentException('Cannot compute Merkle root of an empty array.');
        }

        // Hash every raw value into a leaf node.
        $level = array_map([self::class, 'hashLeaf'], array_values($hashes));

        while (count($level) > 1) {
            $level = self::buildNextLevel($level);
        }

        return $level[0];
    }

    /**
     * Generate an inclusion proof for the leaf at $leafIndex.
     *
     * @param string[] $hashes    Raw record hash strings.
     * @param int      $leafIndex 0-based index of the leaf to prove.
     *
     * @return array{leafHash: string, rootHash: string, path: array<int, array{hash: string, position: 'left'|'right'}>, leafIndex: int, treeSize: int}
     *
     * @throws \InvalidArgumentException If $leafIndex is out of bounds.
     */
    public static function generateProof(array $hashes, int $leafIndex): array
    {
        $treeSize = count($hashes);

        if ($treeSize === 0) {
            throw new \InvalidArgumentException('Cannot generate proof for an empty array.');
        }

        if ($leafIndex < 0 || $leafIndex >= $treeSize) {
            throw new \InvalidArgumentException(
                "Leaf index {$leafIndex} is out of bounds for tree of size {$treeSize}."
            );
        }

        // Build the leaf level.
        $level = array_map([self::class, 'hashLeaf'], array_values($hashes));
        $leafHash = $level[$leafIndex];
        $currentIndex = $leafIndex;
        $path = [];

        while (count($level) > 1) {
            $isRightNode = ($currentIndex % 2 === 1);

            if ($isRightNode) {
                // Current node is a right child; its sibling is to the left.
                $siblingIndex = $currentIndex - 1;
                $path[] = ['hash' => $level[$siblingIndex], 'position' => 'left'];
            } else {
                // Current node is a left child; its sibling is to the right.
                // If no right sibling exists (odd count), duplicate the current node.
                $siblingIndex = $currentIndex + 1;
                if ($siblingIndex >= count($level)) {
                    $siblingIndex = $currentIndex; // duplicate
                }
                $path[] = ['hash' => $level[$siblingIndex], 'position' => 'right'];
            }

            $level = self::buildNextLevel($level);
            $currentIndex = intdiv($currentIndex, 2);
        }

        return [
            'leafHash' => $leafHash,
            'rootHash' => $level[0],
            'path' => $path,
            'leafIndex' => $leafIndex,
            'treeSize' => $treeSize,
        ];
    }

    /**
     * Verify an inclusion proof.
     *
     * @param string                                                            $leafHash     Hash of the leaf being proven.
     * @param array<int, array{hash: string, position: 'left'|'right'}> $path         Proof path from generateProof.
     * @param string                                                            $expectedRoot Known good root hash.
     * @param int                                                               $leafIndex    0-based index of the leaf (unused in computation, kept for API symmetry).
     */
    public static function verifyProof(
        string $leafHash,
        array $path,
        string $expectedRoot,
        int $leafIndex,
    ): bool {
        $current = $leafHash;

        foreach ($path as $step) {
            if ($step['position'] === 'left') {
                // Sibling is to the left of the current node.
                $current = self::hashPair($step['hash'], $current);
            } else {
                // Sibling is to the right of the current node.
                $current = self::hashPair($current, $step['hash']);
            }
        }

        return hash_equals($expectedRoot, $current);
    }

    // =========================================================================
    // DB-dependent methods
    // =========================================================================

    /**
     * Compute and persist a Merkle root for a range of audit log records.
     *
     * @param int $startPosition Inclusive start chainPosition.
     * @param int $endPosition   Inclusive end chainPosition.
     */
    public function computeBatch(int $startPosition, int $endPosition): ?MerkleRootRecord
    {
        /** @var AuditLogRecord[] $records */
        $records = AuditLogRecord::find()
            ->where(['>=', 'chainPosition', $startPosition])
            ->andWhere(['<=', 'chainPosition', $endPosition])
            ->orderBy(['chainPosition' => SORT_ASC])
            ->all();

        if (empty($records)) {
            return null;
        }

        $hashes = array_map(static fn(AuditLogRecord $r) => (string) $r->hash, $records);
        $rootHash = self::computeRoot($hashes);

        $merkleRoot = new MerkleRootRecord();
        $merkleRoot->batchStartPosition = $startPosition;
        $merkleRoot->batchEndPosition = $endPosition;
        $merkleRoot->recordCount = count($records);
        $merkleRoot->rootHash = $rootHash;
        $merkleRoot->tableName = AuditLogRecord::tableName();
        $merkleRoot->dateComputed = date('Y-m-d H:i:s');

        if (!$merkleRoot->save()) {
            return null;
        }

        // Back-fill merkleRootId on the batch records.
        foreach ($records as $record) {
            $record->merkleRootId = $merkleRoot->id;
            $record->save(false);
        }

        return $merkleRoot;
    }

    /**
     * Return an inclusion proof for the audit log record at $chainPosition.
     */
    public function getInclusionProof(int $chainPosition): ?MerkleProof
    {
        // Find the root that covers this position.
        /** @var MerkleRootRecord|null $merkleRoot */
        $merkleRoot = MerkleRootRecord::find()
            ->where(['<=', 'batchStartPosition', $chainPosition])
            ->andWhere(['>=', 'batchEndPosition', $chainPosition])
            ->one();

        if ($merkleRoot === null) {
            return null;
        }

        /** @var AuditLogRecord[] $records */
        $records = AuditLogRecord::find()
            ->where(['>=', 'chainPosition', $merkleRoot->batchStartPosition])
            ->andWhere(['<=', 'chainPosition', $merkleRoot->batchEndPosition])
            ->orderBy(['chainPosition' => SORT_ASC])
            ->all();

        if (empty($records)) {
            return null;
        }

        $hashes = array_map(static fn(AuditLogRecord $r) => (string) $r->hash, $records);

        // Determine the 0-based index of the requested record within the batch.
        $leafIndex = null;
        foreach ($records as $i => $record) {
            if ((int) $record->chainPosition === $chainPosition) {
                $leafIndex = $i;
                break;
            }
        }

        if ($leafIndex === null) {
            return null;
        }

        $proof = self::generateProof($hashes, $leafIndex);
        $verified = self::verifyProof(
            $proof['leafHash'],
            $proof['path'],
            $merkleRoot->rootHash,
            $proof['leafIndex'],
        );

        return new MerkleProof(
            leafHash: $proof['leafHash'],
            rootHash: $merkleRoot->rootHash,
            path: $proof['path'],
            leafIndex: $proof['leafIndex'],
            treeSize: $proof['treeSize'],
            verified: $verified,
        );
    }

    /**
     * Re-verify every persisted Merkle root by recomputing it from the batch records.
     *
     * @return array{verified: int, failed: int, failedIds: int[]}
     */
    public function verifyAllRoots(): array
    {
        /** @var MerkleRootRecord[] $roots */
        $roots = MerkleRootRecord::find()->orderBy(['id' => SORT_ASC])->all();

        $verified = 0;
        $failed = 0;
        $failedIds = [];

        foreach ($roots as $merkleRoot) {
            /** @var AuditLogRecord[] $records */
            $records = AuditLogRecord::find()
                ->where(['>=', 'chainPosition', $merkleRoot->batchStartPosition])
                ->andWhere(['<=', 'chainPosition', $merkleRoot->batchEndPosition])
                ->orderBy(['chainPosition' => SORT_ASC])
                ->all();

            if (empty($records)) {
                $failed++;
                $failedIds[] = $merkleRoot->id;
                continue;
            }

            $hashes = array_map(static fn(AuditLogRecord $r) => (string) $r->hash, $records);

            try {
                $recomputedRoot = self::computeRoot($hashes);
            } catch (\InvalidArgumentException) {
                $failed++;
                $failedIds[] = $merkleRoot->id;
                continue;
            }

            if (hash_equals($merkleRoot->rootHash, $recomputedRoot)) {
                $verified++;
            } else {
                $failed++;
                $failedIds[] = $merkleRoot->id;
            }
        }

        return [
            'verified' => $verified,
            'failed' => $failed,
            'failedIds' => $failedIds,
        ];
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Reduce one level of the tree to the next by pairing adjacent nodes.
     * An odd node at the end is paired with a duplicate of itself.
     *
     * @param string[] $nodes
     *
     * @return string[]
     */
    private static function buildNextLevel(array $nodes): array
    {
        $next = [];
        $count = count($nodes);

        for ($i = 0; $i < $count; $i += 2) {
            $left = $nodes[$i];
            $right = $nodes[$i + 1] ?? $left; // duplicate last node if odd
            $next[] = self::hashPair($left, $right);
        }

        return $next;
    }
}
