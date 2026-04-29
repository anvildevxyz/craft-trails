<?php

namespace anvildev\trails\dto;

/**
 * Holds the result of a cursor-paginated audit log query.
 */
final class CursorResult
{
    /**
     * @param AuditLogEntry[] $items
     * @param string|null $nextCursor Opaque cursor token for the next page, or null if last page
     * @param int $totalCount Total matching records across all pages
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $nextCursor,
        public readonly int $totalCount,
    ) {
    }

    /**
     * Returns true if there is a next page available.
     */
    public function hasMore(): bool
    {
        return $this->nextCursor !== null;
    }

    /**
     * Returns an empty result representing no records found.
     */
    public static function empty(): self
    {
        return new self([], null, 0);
    }
}
