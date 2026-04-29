<?php

declare(strict_types=1);

namespace anvildev\trails\query;

use anvildev\trails\dto\AuditLogEntry;
use anvildev\trails\dto\CursorResult;
use anvildev\trails\services\TableRotationService;
use Craft;

/**
 * Builds and executes cursor-paginated queries across multiple rotated log tables.
 *
 * The static helper methods (buildWhereClauses, buildCursorCondition) are pure
 * functions with no database dependency and are fully unit-testable.
 */
final class UnionQueryBuilder
{
    /**
     * Converts an associative criteria array into an array of Yii2-style where
     * conditions that can be passed directly to ActiveQuery::andWhere().
     *
     * Exact-match fields: category, userId, elementType, elementId, ipAddress.
     * The `event` field supports a trailing wildcard (e.g. 'element.*').
     * `dateFrom` / `dateTo` produce range conditions on the dateCreated column.
     * `search` produces an OR-LIKE across event, elementTitle, and userName.
     *
     * Empty strings, null, and 0 are all skipped.
     *
     * @param array{
     *   event?: non-empty-string,
     *   category?: non-empty-string,
     *   userId?: positive-int,
     *   elementType?: class-string<\craft\base\ElementInterface>,
     *   elementId?: positive-int,
     *   ipAddress?: non-empty-string,
     *   dateFrom?: non-empty-string,
     *   dateTo?: non-empty-string,
     *   search?: non-empty-string
     * } $criteria
     * @return array<int, mixed> List of Yii2 where-condition arrays
     */
    public static function buildWhereClauses(array $criteria): array
    {
        $conditions = [];

        // --- Exact-match scalar fields ------------------------------------------
        $exactFields = ['category', 'userId', 'elementType', 'elementId', 'ipAddress'];

        foreach ($exactFields as $field) {
            $value = $criteria[$field] ?? null;

            if ($value === null || $value === '' || $value === 0) {
                continue;
            }

            $conditions[] = [$field => $value];
        }

        // --- event (supports trailing wildcard) ----------------------------------
        if (isset($criteria['event']) && $criteria['event'] !== '' && $criteria['event'] !== null) {
            $event = (string) $criteria['event'];

            if (str_ends_with($event, '.*')) {
                // Strip the wildcard character; use prefix LIKE without auto-wrapping.
                $prefix = substr($event, 0, -1); // 'element.*' → 'element.'
                $conditions[] = ['like', 'event', $prefix, false];
            } else {
                $conditions[] = ['event' => $event];
            }
        }

        // --- Date range ----------------------------------------------------------
        if (!empty($criteria['dateFrom'])) {
            $conditions[] = ['>=', 'dateCreated', $criteria['dateFrom']];
        }

        if (!empty($criteria['dateTo'])) {
            $conditions[] = ['<=', 'dateCreated', $criteria['dateTo']];
        }

        // --- Full-text search (OR across three columns) --------------------------
        if (!empty($criteria['search'])) {
            $term = (string) $criteria['search'];
            $conditions[] = [
                'or',
                ['like', 'event', $term],
                ['like', 'elementTitle', $term],
                ['like', 'userName', $term],
            ];
        }

        return $conditions;
    }

    /**
     * Builds the keyset-pagination (cursor) condition for DESC ordering.
     *
     * Returns rows that come after the position identified by ($dateCreated, $id):
     *   dateCreated < $dateCreated
     *   OR (dateCreated = $dateCreated AND id < $id)
     *
     * @return array<mixed> Yii2 where-condition array
     */
    public static function buildCursorCondition(string $dateCreated, int $id): array
    {
        return [
            'or',
            ['<', 'dateCreated', $dateCreated],
            ['and', ['dateCreated' => $dateCreated], ['<', 'id', $id]],
        ];
    }

    /**
     * Executes a cursor-paginated query across all relevant rotated log tables.
     *
     * @param TableRotationService $rotation  Service that resolves which tables to query.
     * @param array{
     *   event?: non-empty-string,
     *   category?: non-empty-string,
     *   userId?: positive-int,
     *   elementType?: class-string<\craft\base\ElementInterface>,
     *   elementId?: positive-int,
     *   ipAddress?: non-empty-string,
     *   dateFrom?: non-empty-string,
     *   dateTo?: non-empty-string,
     *   search?: non-empty-string
     * } $criteria  Filter criteria (see buildWhereClauses).
     * @param int                  $limit     Maximum number of items per page.
     * @param string|null          $cursor    Opaque cursor token from a previous response.
     * @return CursorResult
     */
    public static function execute(
        TableRotationService $rotation,
        array $criteria,
        int $limit,
        ?string $cursor = null,
    ): CursorResult {
        $db = Craft::$app->getDb();

        // 1. Build where clauses from criteria.
        $whereClauses = self::buildWhereClauses($criteria);

        // 2. Decode cursor and build cursor condition.
        $cursorCondition = null;
        $cursorData = Cursor::decode($cursor);

        if ($cursorData !== null) {
            $cursorCondition = self::buildCursorCondition(
                $cursorData['dateCreated'],
                $cursorData['id'],
            );
        }

        // 3. Get relevant tables (active + any archives that overlap with date range).
        $dateFrom = $criteria['dateFrom'] ?? null;
        $dateTo = $criteria['dateTo'] ?? null;

        $tables = $rotation->getTablesForDateRange(
            $dateFrom ?: null,
            $dateTo ?: null,
        );

        // Always include the active table.
        $activeTable = $rotation->getActiveTableName();
        if (!in_array($activeTable, $tables, true)) {
            $tables[] = $activeTable;
        }

        // 4. Count total across all tables (no cursor condition — reflects full filter).
        $totalCount = 0;

        foreach ($tables as $table) {
            $countQuery = $db->createCommand(
                'SELECT COUNT(*) FROM ' . $db->quoteTableName($table),
            );

            // Apply where clauses to count query via a sub-select approach.
            $query = (new \yii\db\Query())
                ->from($table);

            foreach ($whereClauses as $condition) {
                $query->andWhere($condition);
            }

            $totalCount += (int) $query->count('*', $db);
        }

        // 5. Query (limit + 1) rows from each table in reverse order (newest first).
        $fetchLimit = $limit + 1;
        $allRows = [];

        foreach ($tables as $table) {
            $query = (new \yii\db\Query())
                ->from($table)
                ->orderBy(['dateCreated' => SORT_DESC, 'id' => SORT_DESC])
                ->limit($fetchLimit);

            foreach ($whereClauses as $condition) {
                $query->andWhere($condition);
            }

            if ($cursorCondition !== null) {
                $query->andWhere($cursorCondition);
            }

            $rows = $query->all($db);

            foreach ($rows as $row) {
                $allRows[] = $row;
            }
        }

        // 6. Merge and sort DESC by (dateCreated, id), then slice to fetchLimit.
        usort($allRows, static function(array $a, array $b): int {
            $dateCmp = strcmp((string) $b['dateCreated'], (string) $a['dateCreated']);

            if ($dateCmp !== 0) {
                return $dateCmp;
            }

            return (int) $b['id'] <=> (int) $a['id'];
        });

        $allRows = array_slice($allRows, 0, $fetchLimit);

        // 7. Determine hasMore.
        $hasMore = count($allRows) > $limit;

        // Slice to exactly $limit items.
        $pageRows = array_slice($allRows, 0, $limit);

        // 8. Convert rows to AuditLogEntry DTOs.
        $items = array_map(
            static fn(array $row): AuditLogEntry => AuditLogEntry::fromArray($row),
            $pageRows,
        );

        // 9. Build next cursor from the last item.
        $nextCursor = null;

        if ($hasMore && !empty($pageRows)) {
            $lastRow = end($pageRows);
            $nextCursor = Cursor::encode(
                (string) $lastRow['dateCreated'],
                (int)    $lastRow['id'],
            );
        }

        return new CursorResult($items, $nextCursor, $totalCount);
    }
}
