<?php

namespace anvildev\trails\mcp;

use anvildev\trails\mcp\support\Presenter;
use Craft;
use Throwable;

/**
 * Shared error handling for Trails' MCP tools.
 *
 * Trails' MCP surface is READ-ONLY — it exposes querying, statistics and
 * integrity verification of the audit trail, never creation, mutation,
 * purging, rotation or token issuance. There is therefore no write gate; the
 * controls that matter here are PII redaction (see {@see Presenter}) and the
 * craft-mcp transport boundary (allowed IPs / enabled). The trait references
 * nothing from the craft-mcp package, so a tool class stays usable (and
 * unit-testable) even when that plugin is not installed.
 */
trait ToolResponseTrait
{
    /**
     * Run a read tool body, translating exceptions into an error response.
     *
     * @param \Closure(): array<string, mixed> $fn
     * @return array<string, mixed>
     */
    private function guard(\Closure $fn): array
    {
        try {
            /** @var array<string, mixed> $result */
            $result = Presenter::jsonSafe($fn());
            return $result;
        } catch (Throwable $e) {
            Craft::warning('Trails MCP tool failed: ' . $e->getMessage(), __METHOD__);

            // Only Trails' own typed exceptions carry client-safe messages.
            // Everything else (PDO/Yii/driver/internal) may embed SQL, schema or
            // paths, so it is reduced to a generic message — details stay in logs.
            $isOwnException = str_starts_with($e::class, 'anvildev\\trails\\exceptions\\');

            return [
                'error' => $isOwnException
                    ? $e->getMessage()
                    : 'An internal error occurred while running the tool; see the Trails/Craft logs for details.',
                'type' => (new \ReflectionClass($e))->getShortName(),
            ];
        }
    }

    /**
     * Hard ceiling for list/pagination tools, so a single MCP call can never
     * materialise an unbounded slice of the (potentially huge) audit log.
     */
    private const LIST_LIMIT_MAX = 200;
    private const LIST_LIMIT_DEFAULT = 50;

    private function clampLimit(int $limit, int $max = self::LIST_LIMIT_MAX): int
    {
        if ($limit < 1) {
            return self::LIST_LIMIT_DEFAULT;
        }

        return min($limit, $max);
    }
}
