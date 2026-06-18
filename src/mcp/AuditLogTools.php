<?php

namespace anvildev\trails\mcp;

use anvildev\trails\dto\AuditLogEntry;
use anvildev\trails\mcp\support\Presenter;
use anvildev\trails\Trails;
use Mcp\Capability\Attribute\McpTool;
use stimmt\craft\Mcp\attributes\McpToolMeta;
use stimmt\craft\Mcp\enums\ToolCategory;

/**
 * MCP tools for querying the Trails audit log. Read-only; actor email is masked
 * and change payloads are reduced to changed-field names by default.
 */
class AuditLogTools
{
    use ToolResponseTrait;

    /**
     * Build a filtered audit query from the common search arguments.
     */
    private function buildQuery(
        ?string $event,
        ?string $category,
        ?int $userId,
        ?string $elementType,
        ?int $elementId,
        ?string $ipAddress,
        ?string $search,
        ?string $fromDate,
        ?string $toDate,
    ): \anvildev\trails\query\AuditQuery {
        $query = Trails::getInstance()->audit->query();
        if ($event !== null) {
            $query->event($event);
        }
        if ($category !== null) {
            $query->category($category);
        }
        if ($userId !== null) {
            $query->user($userId);
        }
        if ($elementType !== null) {
            $query->element($elementType, $elementId);
        }
        if ($ipAddress !== null) {
            $query->ipAddress($ipAddress);
        }
        if ($search !== null) {
            $query->search($search);
        }
        if ($fromDate !== null) {
            $query->after($fromDate);
        }
        if ($toDate !== null) {
            $query->before($toDate);
        }

        return $query;
    }

    /**
     * Search the audit trail. Spans rotated/archived monthly tables transparently.
     *
     * @param string|null $event Filter by event (e.g. element.saved, user.loggedIn).
     * @param string|null $category Filter by category.
     * @param int|null $userId Filter by acting user id.
     * @param string|null $elementType Filter by element class (e.g. craft\elements\Entry).
     * @param int|null $elementId Filter by a specific element id (with elementType).
     * @param string|null $ipAddress Filter by source IP.
     * @param string|null $search Free-text search.
     * @param string|null $fromDate Only entries on/after this date/time.
     * @param string|null $toDate Only entries on/before this date/time.
     * @param string|null $cursor Opaque pagination cursor from a previous call.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_search_logs',
        description: 'Search the Trails audit log by event, category, user, element, IP, text and date range. '
            . 'Cursor-paginated; actor email is masked and change values are hidden (only changed-field names) by default.',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function searchLogs(
        ?string $event = null,
        ?string $category = null,
        ?int $userId = null,
        ?string $elementType = null,
        ?int $elementId = null,
        ?string $ipAddress = null,
        ?string $search = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $limit = 50,
        ?string $cursor = null,
    ): array {
        return $this->guard(function() use ($event, $category, $userId, $elementType, $elementId, $ipAddress, $search, $fromDate, $toDate, $limit, $cursor): array {
            $result = $this
                ->buildQuery($event, $category, $userId, $elementType, $elementId, $ipAddress, $search, $fromDate, $toDate)
                ->limit($this->clampLimit($limit))
                ->cursor($cursor)
                ->get();

            return [
                'count' => count($result->items),
                'totalCount' => $result->totalCount,
                'nextCursor' => $result->nextCursor,
                'entries' => array_map(
                    static fn(AuditLogEntry $e) => Presenter::auditEntry($e),
                    $result->items,
                ),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_get_log',
        description: 'Get a single Trails audit log entry by id (actor email masked, change values hidden by default).',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function getLog(int $id): array
    {
        return $this->guard(function() use ($id): array {
            $record = Trails::getInstance()->audit->getLogById($id);
            if ($record === null) {
                return ['error' => "Audit log entry #{$id} not found."];
            }

            return ['entry' => Presenter::auditEntry(AuditLogEntry::fromRecord($record))];
        });
    }

    /**
     * Count audit entries matching the given filters (same filters as search).
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_count_logs',
        description: 'Count Trails audit log entries matching optional filters (event, category, user, element, IP, text, date range).',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function countLogs(
        ?string $event = null,
        ?string $category = null,
        ?int $userId = null,
        ?string $elementType = null,
        ?int $elementId = null,
        ?string $ipAddress = null,
        ?string $search = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): array {
        return $this->guard(function() use ($event, $category, $userId, $elementType, $elementId, $ipAddress, $search, $fromDate, $toDate): array {
            $count = $this
                ->buildQuery($event, $category, $userId, $elementType, $elementId, $ipAddress, $search, $fromDate, $toDate)
                ->count();

            return ['count' => $count];
        });
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_event_types',
        description: 'List the distinct event types present in the Trails audit log.',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function eventTypes(): array
    {
        return $this->guard(static fn(): array => [
            'eventTypes' => Trails::getInstance()->audit->getEventTypes(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_categories',
        description: 'List the distinct event categories present in the Trails audit log.',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function categories(): array
    {
        return $this->guard(static fn(): array => [
            'categories' => Trails::getInstance()->audit->getCategories(),
        ]);
    }
}
