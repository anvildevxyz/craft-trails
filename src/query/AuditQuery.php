<?php

namespace anvildev\trails\query;

use anvildev\trails\dto\AuditLogEntry;
use anvildev\trails\dto\CursorResult;
use anvildev\trails\Trails;
use craft\base\ElementInterface;

/**
 * Fluent query builder for audit log entries.
 *
 * Stores filter state internally and delegates execution to
 * UnionQueryBuilder::execute(). All setter methods return $this for chaining.
 */
class AuditQuery
{
    public const MAX_LIMIT = 1000;

    private ?string $event = null;
    private ?string $category = null;
    private ?int $userId = null;
    private ?string $elementType = null;
    private ?int $elementId = null;
    private ?string $ipAddress = null;
    private ?string $dateFrom = null;
    private ?string $dateTo = null;
    private ?string $search = null;
    private int $limit = 50;
    private ?string $cursor = null;

    // -------------------------------------------------------------------------
    // Setters
    // -------------------------------------------------------------------------

    public function event(string $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function category(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function user(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function ipAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     * Filter by element type and optionally element ID.
     */
    public function element(string $elementType, ?int $elementId = null): self
    {
        $this->elementType = $elementType;
        $this->elementId = $elementId;
        return $this;
    }

    /**
     * Filter by a concrete element object, extracting its class and ID.
     */
    public function forElement(ElementInterface $element): self
    {
        $this->elementType = $element::class;
        $this->elementId = $element->getId();
        return $this;
    }

    /**
     * Filter entries created after the given date string.
     */
    public function after(string $date): self
    {
        $this->dateFrom = $date;
        return $this;
    }

    /**
     * Filter entries created before the given date string.
     */
    public function before(string $date): self
    {
        $this->dateTo = $date;
        return $this;
    }

    public function search(string $term): self
    {
        $this->search = $term;
        return $this;
    }

    /**
     * No-op for now — only dateCreated DESC ordering is supported.
     * Exists for API completeness.
     */
    public function orderBy(string $column, string $direction = 'desc'): self
    {
        // intentionally a no-op
        return $this;
    }

    /**
     * Set the page size, clamped to [1, MAX_LIMIT].
     */
    public function limit(int $limit): self
    {
        $this->limit = max(1, min($limit, self::MAX_LIMIT));
        return $this;
    }

    public function cursor(?string $cursor): self
    {
        $this->cursor = $cursor;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    // -------------------------------------------------------------------------
    // Building
    // -------------------------------------------------------------------------

    /**
     * Returns an associative array of all non-null filter values.
     *
     * @return array{
     *   event?: non-empty-string,
     *   category?: non-empty-string,
     *   userId?: positive-int,
     *   elementType?: class-string<ElementInterface>,
     *   elementId?: positive-int,
     *   ipAddress?: non-empty-string,
     *   dateFrom?: non-empty-string,
     *   dateTo?: non-empty-string,
     *   search?: non-empty-string
     * }
     */
    public function toCriteria(): array
    {
        $criteria = [];

        if ($this->event !== null) {
            $criteria['event'] = $this->event;
        }
        if ($this->category !== null) {
            $criteria['category'] = $this->category;
        }
        if ($this->userId !== null) {
            $criteria['userId'] = $this->userId;
        }
        if ($this->elementType !== null) {
            $criteria['elementType'] = $this->elementType;
        }
        if ($this->elementId !== null) {
            $criteria['elementId'] = $this->elementId;
        }
        if ($this->ipAddress !== null) {
            $criteria['ipAddress'] = $this->ipAddress;
        }
        if ($this->dateFrom !== null) {
            $criteria['dateFrom'] = $this->dateFrom;
        }
        if ($this->dateTo !== null) {
            $criteria['dateTo'] = $this->dateTo;
        }
        if ($this->search !== null) {
            $criteria['search'] = $this->search;
        }

        return $criteria;
    }

    // -------------------------------------------------------------------------
    // Execution (requires Craft/Trails — not covered by unit tests)
    // -------------------------------------------------------------------------

    /**
     * Execute the query and return a cursor-paginated result.
     */
    public function get(): CursorResult
    {
        $trails = Trails::getInstance();
        return UnionQueryBuilder::execute(
            rotation: $trails->tableRotation,
            criteria: $this->toCriteria(),
            limit: $this->limit,
            cursor: $this->cursor,
        );
    }

    /**
     * Execute the query and return all matching entries.
     *
     * @return AuditLogEntry[]
     */
    public function all(): array
    {
        return $this->get()->items;
    }

    /**
     * Execute the query and return the first matching entry, or null.
     */
    public function one(): ?AuditLogEntry
    {
        $this->limit(1);
        $result = $this->get();
        return $result->items[0] ?? null;
    }

    /**
     * Execute the query and return the total count of matching records.
     */
    public function count(): int
    {
        return $this->get()->totalCount;
    }
}
