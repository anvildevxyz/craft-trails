<?php

namespace anvildev\trails\mcp;

use anvildev\trails\Trails;
use Mcp\Capability\Attribute\McpTool;
use stimmt\craft\Mcp\attributes\McpToolMeta;
use stimmt\craft\Mcp\enums\ToolCategory;

/**
 * MCP tools exposing Trails activity statistics and retention info. Read-only,
 * aggregate data only (no per-entry PII).
 */
class ActivityTools
{
    use ToolResponseTrait;

    /**
     * @param int $days Window in days to summarise.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_activity_summary',
        description: 'Aggregate audit activity over the last N days (total events, unique users, logins, elements created/updated/deleted).',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function activitySummary(int $days = 7): array
    {
        return $this->guard(static fn(): array => [
            'summary' => Trails::getInstance()->audit->getActivitySummary(max(1, $days)),
        ]);
    }

    /**
     * @param int $days Window in days.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_daily_activity',
        description: 'Per-day audit event counts over the last N days (zero-filled), for charting activity trends.',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function dailyActivity(int $days = 7): array
    {
        return $this->guard(static fn(): array => [
            'daily' => Trails::getInstance()->audit->getDailyActivity(max(1, $days)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_retention_stats',
        description: 'Audit-log retention stats: total entries, oldest/newest entry dates, and how many are past the retention window.',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function retentionStats(): array
    {
        return $this->guard(static fn(): array => [
            'retention' => Trails::getInstance()->retention->getRetentionStats(),
        ]);
    }
}
