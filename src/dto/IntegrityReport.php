<?php

namespace anvildev\trails\dto;

/**
 * Read-only value object summarising the result of a full audit-log integrity check.
 *
 * Has no Craft/Yii2 dependencies and is safe to use anywhere.
 */
final class IntegrityReport
{
    /**
     * @param string[] $invalidHashIds
     */
    public function __construct(
        /** Total number of audit log records inspected. */
        public readonly int $totalRecords,

        /** Number of records whose hash was valid. */
        public readonly int $validHashes,

        /** Number of records whose hash was invalid. */
        public readonly int $invalidHashes,

        /**
         * IDs of records with invalid hashes.
         *
         * IDs are formatted as `<table>#<id>` to support partitioned tables.
         *
         * @var string[]
         */
        public readonly array $invalidHashIds,

        /** Whether the hash chain is unbroken from start to finish. */
        public readonly bool $chainValid,

        /** Chain position (1-based) at which the first break was detected, or null. */
        public readonly ?int $chainBrokenAt,

        /** Number of Merkle roots that were verified successfully. */
        public readonly int $merkleRootsVerified,

        /** Number of Merkle roots that failed verification. */
        public readonly int $merkleRootsFailed,

        /** Number of blockchain anchors that were verified successfully. */
        public readonly int $anchorsVerified,

        /** Number of blockchain anchors that failed verification. */
        public readonly int $anchorsFailed,

        /**
         * Number of audit records whose dateCreated falls inside the certificate's
         * date range. NULL when the report was built without a date range
         * (e.g. CP "Run Verification" reads the system-wide totals only).
         */
        public readonly ?int $recordsInRange = null,
    ) {
    }

    /**
     * Returns true only when every integrity dimension passes:
     * - no invalid hashes
     * - hash chain is unbroken
     * - all Merkle roots verified
     * - all anchors verified
     */
    public function isClean(): bool
    {
        return $this->invalidHashes === 0
            && $this->chainValid
            && $this->merkleRootsFailed === 0
            && $this->anchorsFailed === 0;
    }

    /**
     * Return all properties as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'totalRecords' => $this->totalRecords,
            'validHashes' => $this->validHashes,
            'invalidHashes' => $this->invalidHashes,
            'invalidHashIds' => $this->invalidHashIds,
            'chainValid' => $this->chainValid,
            'chainBrokenAt' => $this->chainBrokenAt,
            'merkleRootsVerified' => $this->merkleRootsVerified,
            'merkleRootsFailed' => $this->merkleRootsFailed,
            'anchorsVerified' => $this->anchorsVerified,
            'anchorsFailed' => $this->anchorsFailed,
            'recordsInRange' => $this->recordsInRange,
            'overallStatus' => $this->isClean() ? 'passed' : 'failed',
        ];
    }
}
