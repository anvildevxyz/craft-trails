<?php

namespace anvildev\trails\tests\Unit\Mcp;

use anvildev\trails\dto\AuditLogEntry;
use anvildev\trails\mcp\ActivityTools;
use anvildev\trails\mcp\AuditLogTools;
use anvildev\trails\mcp\IntegrityTools;
use anvildev\trails\mcp\support\Presenter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Verifies Trails' craft-mcp integration: each tool class declares the expected
 * read-only `#[McpTool]` set, the surface exposes no destructive/admin
 * operations, and audit PII (actor email, IP, user-agent, session id and raw
 * before/after values) is redacted by default.
 *
 * Pure unit tests (reflection + the dependency-free Presenter/DTO), so the suite
 * passes whether or not craft-mcp is installed and without a Craft app.
 */
class McpToolsTest extends TestCase
{
    private const MCP_TOOL_ATTR = 'Mcp\\Capability\\Attribute\\McpTool';

    /** @var array<class-string, list<string>> */
    private const EXPECTED_TOOLS = [
        AuditLogTools::class => [
            'trails_search_logs', 'trails_get_log', 'trails_count_logs',
            'trails_event_types', 'trails_categories',
        ],
        ActivityTools::class => [
            'trails_activity_summary', 'trails_daily_activity', 'trails_retention_stats',
        ],
        IntegrityTools::class => [
            'trails_verify_logs', 'trails_verify_chain', 'trails_verify_merkle_roots',
            'trails_verify_anchors', 'trails_inclusion_proof', 'trails_certificate',
        ],
    ];

    /**
     * @dataProvider toolClassProvider
     * @param class-string $class
     * @param list<string> $expected
     */
    public function testToolClassDeclaresExpectedTools(string $class, array $expected): void
    {
        $declared = $this->declaredToolNames($class);
        sort($declared);
        sort($expected);
        $this->assertSame($expected, $declared, "$class should declare exactly its expected MCP tools.");
    }

    public function testTotalToolCountIs14(): void
    {
        $declared = 0;
        foreach (array_keys(self::EXPECTED_TOOLS) as $class) {
            $declared += count($this->declaredToolNames($class));
        }
        $this->assertSame(14, $declared);
    }

    public function testRedactEmailMasksLocalPart(): void
    {
        $this->assertSame('ja***@example.com', Presenter::redactEmail('jane@example.com'));
        $this->assertSame('***', Presenter::redactEmail('notanemail'));
        $this->assertNull(Presenter::redactEmail(null));
    }

    public function testAuditEntryRedactsPiiByDefault(): void
    {
        $out = Presenter::auditEntry($this->sampleEntry());

        // Actor email masked (plain value passes EncryptionHelper::decrypt unchanged, then masked).
        $this->assertSame('al***@example.com', $out['userEmail']);
        // Request fingerprint + arbitrary payloads withheld entirely.
        $this->assertArrayNotHasKey('ipAddress', $out);
        $this->assertArrayNotHasKey('userAgent', $out);
        $this->assertArrayNotHasKey('oldValue', $out);
        $this->assertArrayNotHasKey('newValue', $out);
        $this->assertArrayNotHasKey('metadata', $out);
        // Only the changed field NAMES are surfaced, never their values.
        $this->assertSame(['title', 'slug'], $out['changedFields']);
        $this->assertStringNotContainsString('secret', json_encode($out));
    }

    public function testAuditEntryRevealsValuesWhenOptedOut(): void
    {
        $out = Presenter::auditEntry($this->sampleEntry(), redactPii: false);

        $this->assertSame('alice@example.com', $out['userEmail']);
        $this->assertSame('203.0.113.5', $out['ipAddress']);
        $this->assertSame(['title' => 'Old', 'slug' => 'old'], $out['oldValue']);
        $this->assertSame(['title' => 'New', 'slug' => 'new'], $out['newValue']);
        $this->assertSame(['note' => 'secret-context'], $out['metadata']);
    }

    public function testAuditEntryNeverExposesSessionId(): void
    {
        $this->assertArrayNotHasKey('sessionId', Presenter::auditEntry($this->sampleEntry()));
        $this->assertArrayNotHasKey('sessionId', Presenter::auditEntry($this->sampleEntry(), redactPii: false));
    }

    public function testJsonSafeFormatsDatesAndNonFiniteFloats(): void
    {
        $out = Presenter::jsonSafe([
            'when' => new \DateTimeImmutable('2026-06-18T10:00:00+00:00'),
            'inf' => INF,
            'n' => 5,
        ]);
        $this->assertSame('2026-06-18T10:00:00+00:00', $out['when']);
        $this->assertNull($out['inf']);
        $this->assertSame(5, $out['n']);
    }

    public function testToolSurfaceIsReadOnly(): void
    {
        $trait = file_get_contents($this->srcDir() . '/mcp/ToolResponseTrait.php');
        $this->assertStringContainsString('function guard(', $trait);
        $this->assertStringContainsString('function clampLimit(', $trait);
        // Read-only: there must be no write gate at all.
        $this->assertStringNotContainsString('guardWrite', $trait);
        $this->assertStringNotContainsString('mcpWriteEnabled', $trait);
        foreach ($this->mcpSourceFiles() as $f) {
            $this->assertStringNotContainsString('dangerous: true', file_get_contents($f), basename($f) . ' must declare no dangerous tools.');
        }
    }

    public function testNoDestructiveOrAdminMethodsExposed(): void
    {
        // The MCP surface must never call any write/destructive/admin service method.
        // This is a denylist tripwire, not exhaustive enforcement: when a new
        // destructive service method is added, add its name here too. The generic
        // persistence verbs (->save(/->delete(/->update(/->insert(/->upsert() catch
        // any direct DB mutation regardless of the service method name.
        $forbidden = [
            'cleanupOldLogs', 'cleanupWithExport', 'rotate', 'dropArchive',
            'issueToken', 'revokeToken', 'computeBatch', 'logCustomEvent',
            'flushShippingBuffer', 'startBackgroundExport', 'writeBackgroundExportFile',
            'purgeBefore',
            '->anchor(', '->log(',
            '->save(', '->delete(', '->update(', '->insert(', '->upsert(',
        ];
        $all = '';
        foreach ($this->mcpSourceFiles() as $f) {
            $all .= file_get_contents($f);
        }
        foreach ($forbidden as $needle) {
            $token = str_contains($needle, '(') ? $needle : "->{$needle}(";
            $this->assertStringNotContainsString($token, $all, "Destructive/admin call {$needle} must not appear in the MCP surface.");
        }
    }

    public function testListToolsClampPageSize(): void
    {
        $trait = file_get_contents($this->srcDir() . '/mcp/ToolResponseTrait.php');
        $this->assertStringContainsString('LIST_LIMIT_MAX', $trait);
        $this->assertStringContainsString(
            'clampLimit(',
            file_get_contents($this->srcDir() . '/mcp/AuditLogTools.php'),
            'search_logs must clamp the page size.',
        );
    }

    /**
     * @return array<string, array{class-string, list<string>}>
     */
    public static function toolClassProvider(): array
    {
        $cases = [];
        foreach (self::EXPECTED_TOOLS as $class => $names) {
            $cases[$class] = [$class, $names];
        }
        return $cases;
    }

    private function sampleEntry(): AuditLogEntry
    {
        return AuditLogEntry::fromArray([
            'id' => 42,
            'event' => 'element.saved',
            'dateCreated' => '2026-06-18 10:30:00',
            'category' => 'entries',
            'userId' => 3,
            'userName' => 'alice',
            'userEmail' => 'alice@example.com',
            'ipAddress' => '203.0.113.5',
            'userAgent' => 'Mozilla/5.0',
            'oldValue' => json_encode(['title' => 'Old', 'slug' => 'old']),
            'newValue' => json_encode(['title' => 'New', 'slug' => 'new']),
            'metadata' => json_encode(['note' => 'secret-context']),
            'sessionId' => 'hashed-session-abc',
            'hash' => 'abc123',
            'chainPosition' => 7,
        ]);
    }

    /**
     * @param class-string $class
     * @return list<string>
     */
    private function declaredToolNames(string $class): array
    {
        $names = [];
        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attr = $method->getAttributes(self::MCP_TOOL_ATTR)[0] ?? null;
            $name = $attr?->getArguments()['name'] ?? null;
            if ($name !== null) {
                $names[] = $name;
            }
        }
        return $names;
    }

    private function srcDir(): string
    {
        return dirname(__DIR__, 3) . '/src';
    }

    /**
     * @return list<string>
     */
    private function mcpSourceFiles(): array
    {
        return array_values(array_filter(
            glob($this->srcDir() . '/mcp/*.php') ?: [],
            static fn($f) => basename($f) !== 'ToolResponseTrait.php',
        ));
    }
}
