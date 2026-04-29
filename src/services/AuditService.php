<?php

namespace anvildev\trails\services;

use anvildev\trails\events\AuditEvent;
use anvildev\trails\helpers\ChainLinkValidator;
use anvildev\trails\helpers\EncryptionHelper;
use anvildev\trails\helpers\GeoIpResolver;
use anvildev\trails\helpers\HashService;
use anvildev\trails\jobs\BatchShipLogJob;
use anvildev\trails\jobs\ResolveGeoIpJob;
use anvildev\trails\jobs\ShipLogJob;
use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\Trails;
use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;

/**
 * Core service for logging and querying audit events
 */
class AuditService extends Component
{
    /** @event AuditEvent Fired before an audit log is saved. Set isValid=false to suppress. */
    public const EVENT_BEFORE_LOG = 'beforeLog';

    public const MAX_CUSTOM_METADATA_BYTES = 65536; // 64 KB

    public const RESERVED_PREFIXES = [
        'element.', 'user.', 'asset.', 'config.', 'audit.', 'trails.', 'auth.',
    ];

    /** @event AuditEvent Fired after an audit log is saved. */
    public const EVENT_AFTER_LOG = 'afterLog';

    public function log(
        string $event,
        ?string $elementType = null,
        ?int $elementId = null,
        array $context = [],
        ?array $oldValues = null,
        ?array $newValues = null,
    ): bool {
        $settings = Trails::getInstance()->getSettings();

        if ($settings->logRateLimit > 0) {
            $cache = Craft::$app->getCache();
            $rateCacheKey = 'trails_rate_' . date('YmdHis');
            // First writer in this second wins atomically via add().
            if (!$cache->add($rateCacheKey, 1, 2)) {
                // Subsequent writers must increment under a mutex — a naive
                // get/set is racy and lets concurrent requests bypass the limit.
                $mutex = Craft::$app->getMutex();
                $mutexKey = 'trails_rate_lock_' . date('YmdHis');
                if ($mutex->acquire($mutexKey, 1)) {
                    try {
                        $currentCount = (int)$cache->get($rateCacheKey);
                        if ($currentCount >= $settings->logRateLimit) {
                            Craft::warning("Trails rate limit exceeded ({$settings->logRateLimit}/s), log suppressed: {$event}", 'trails');
                            return false;
                        }
                        $cache->set($rateCacheKey, $currentCount + 1, 2);
                    } finally {
                        $mutex->release($mutexKey);
                    }
                } else {
                    // Could not acquire mutex within 1s — fail closed to preserve the limit.
                    Craft::warning("Trails rate limit mutex contended, log suppressed: {$event}", 'trails');
                    return false;
                }
            }
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();

        $ipAddress = $userAgent = $requestUrl = $requestMethod = $sessionId = null;
        $ipWasAnonymized = false;

        if (!$request->getIsConsoleRequest()) {
            if ($settings->captureIpAddress) {
                $ipAddress = $request->getUserIP();
                if ($settings->anonymizeIp && $ipAddress) {
                    $ipAddress = $this->anonymizeIpAddress($ipAddress);
                    $ipWasAnonymized = true;
                }
            }
            if ($settings->captureUserAgent) {
                $userAgent = $request->getUserAgent();
                if ($userAgent && strlen($userAgent) > 500) {
                    $userAgent = substr($userAgent, 0, 497) . '...';
                }
            }
            $requestUrl = $request->getAbsoluteUrl();
            $requestMethod = $request->getMethod();
            $sessionId = Craft::$app->getSession()->getId() ?? null;
        }

        $auditEvent = new AuditEvent([
            'event' => $event,
            'elementType' => $elementType,
            'elementId' => $elementId,
            'context' => $context,
            'oldValues' => $oldValues,
            'newValues' => $newValues,
        ]);
        $this->trigger(self::EVENT_BEFORE_LOG, $auditEvent);

        if (!$auditEvent->isValid) {
            return false;
        }

        $record = new AuditLogRecord();
        $record->event = $event;
        $record->category = $this->getCategoryFromEvent($event);
        $record->elementType = $elementType;
        $record->elementId = $elementId;
        $record->elementTitle = $context['title'] ?? null;
        $record->userId = $currentUser?->id;
        $record->userName = $currentUser?->username;
        $record->userEmail = EncryptionHelper::encrypt($currentUser?->email);
        $record->ipAddress = $ipAddress;
        $record->userAgent = $userAgent;
        $record->requestUrl = $requestUrl;
        $record->requestMethod = $requestMethod;
        $record->siteId = Craft::$app->getSites()->getCurrentSite()->id ?? null;
        $record->oldValue = $oldValues ? json_encode($oldValues, JSON_INVALID_UTF8_SUBSTITUTE) : null;
        $record->newValue = $newValues ? json_encode($newValues, JSON_INVALID_UTF8_SUBSTITUTE) : null;
        $record->metadata = $context ? json_encode($context, JSON_INVALID_UTF8_SUBSTITUTE) : null;
        $record->sessionId = EncryptionHelper::hashSessionId($sessionId);
        $record->dateCreated = new \DateTime();
        $record->hash = $this->generateHash($record);

        // Assign chain position atomically
        $mutex = Craft::$app->getMutex();
        $chainMutexKey = 'trails_chain_position_lock';
        $chainAcquired = $mutex->acquire($chainMutexKey, 2);

        if ($chainAcquired) {
            try {
                $cache = Craft::$app->getCache();
                $posKey = 'trails:chainPosition';
                $lastPosition = (int) ($cache->get($posKey) ?: 0);

                if ($lastPosition === 0) {
                    // First run or cache cleared — read MAX(chainPosition)
                    // across every non-dropped table in the rotation registry.
                    // Querying only the active table loses continuity right
                    // after rotate(): the new (empty) active table would restart
                    // from 1, creating a forked chain that can never be
                    // verified end-to-end.
                    $lastPosition = $this->maxChainPositionAcrossTables();
                }

                $newPosition = $lastPosition + 1;
                $record->chainPosition = $newPosition;

                // Get previous record's hash for chaining. The previous record
                // may live in an archive table (if we're the first write after
                // a rotation), so scan registry order — newest first.
                if ($lastPosition > 0) {
                    $record->prevHash = $this->hashAtChainPosition($lastPosition);
                }

                // Recompute hash to include prevHash
                $record->hash = $this->generateHash($record);

                $cache->set($posKey, $newPosition, 86400 * 35);
            } finally {
                $mutex->release($chainMutexKey);
            }
        } else {
            Craft::warning('Trails: chain position mutex contended, writing without chain link', 'trails');
        }

        if (!$record->save()) {
            Craft::error('Failed to save audit log: ' . json_encode($record->getErrors()), 'trails');
            return false;
        }

        // Don't queue GeoIP lookups for anonymized IPs — the resolver would reject
        // them anyway, but we'd still pay the queue overhead per log write.
        if ($settings->enableGeoIp && !$ipWasAnonymized && !empty($record->ipAddress) && GeoIpResolver::isResolvable($record->ipAddress)) {
            Craft::$app->getQueue()->push(new ResolveGeoIpJob([
                'logId' => $record->id,
                'ip' => $record->ipAddress,
            ]));
        }

        // Trigger Merkle root computation every N writes
        if ($record->chainPosition !== null) {
            $batchSize = $settings->merkleBatchSize;
            if ($batchSize > 0 && $record->chainPosition % $batchSize === 0) {
                $startPos = $record->chainPosition - $batchSize + 1;
                Craft::$app->getQueue()->push(new \anvildev\trails\jobs\ComputeMerkleRootJob([
                    'batchStartPosition' => $startPos,
                    'batchEndPosition' => $record->chainPosition,
                ]));
            }
        }

        $this->trigger(self::EVENT_AFTER_LOG, new AuditEvent([
            'event' => $event,
            'elementType' => $elementType,
            'elementId' => $elementId,
            'context' => $context,
            'oldValues' => $oldValues,
            'newValues' => $newValues,
            'record' => $record,
        ]));

        // Publish to real-time stream
        $recordDateCreated = DateTimeHelper::toDateTime($record->dateCreated);
        Trails::getInstance()->realtime->publish(
            $record->id,
            $record->event,
            $recordDateCreated->format('Y-m-d H:i:s'),
        );

        if ($settings->alertsEnabled) {
            $this->checkForAlerts($event, $record);
        }

        if ($settings->externalLoggingEnabled && $settings->externalEndpoint) {
            $payload = [
                'id' => $record->id,
                'timestamp' => $recordDateCreated->format('c'),
                'event' => $record->event,
                'category' => $record->category,
                'element_type' => $record->elementType,
                'element_id' => $record->elementId,
                'element_title' => $record->elementTitle,
                'user_id' => $record->userId,
                'user_name' => $record->userName,
                'user_email' => $record->userEmail,
                'ip_address' => $record->ipAddress,
                'site_id' => $record->siteId,
                'request_url' => $record->requestUrl,
                'request_method' => $record->requestMethod,
                'metadata' => $record->metadata ? json_decode($record->metadata, true) : null,
                'hash' => $record->hash,
            ];

            $batchSize = $settings->externalBatchSize;
            $cache = Craft::$app->getCache();
            $cacheKey = 'trails_shipping_buffer';

            // The buffer is read-modify-write across concurrent requests, so it
            // MUST run under a mutex — otherwise concurrent log writes silently
            // overwrite each other and audit records are lost.
            $mutex = Craft::$app->getMutex();
            $mutexKey = 'trails_shipping_buffer_lock';
            if ($mutex->acquire($mutexKey, 3)) {
                try {
                    $buffer = $cache->get($cacheKey) ?: [];
                    $buffer[] = $payload;

                    if (count($buffer) >= $batchSize) {
                        $cache->set($cacheKey, [], 300);
                        Craft::$app->getQueue()->push(new BatchShipLogJob([
                            'endpoint' => $settings->externalEndpoint,
                            'provider' => $settings->externalProvider ?? 'webhook',
                            'payloads' => $buffer,
                        ]));
                    } else {
                        $cache->set($cacheKey, $buffer, 300); // 5 min TTL — flush even if batch not full
                    }
                } finally {
                    $mutex->release($mutexKey);
                }
            } else {
                // Mutex contention: ship this single payload directly so it isn't lost.
                Craft::warning('Trails shipping buffer mutex contended, falling back to direct ship', 'trails');
                Craft::$app->getQueue()->push(new ShipLogJob([
                    'endpoint' => $settings->externalEndpoint,
                    'provider' => $settings->externalProvider ?? 'webhook',
                    'payload' => $payload,
                ]));
            }
        }

        return true;
    }

    /**
     * Create a new fluent query builder.
     */
    public function query(): \anvildev\trails\query\AuditQuery
    {
        return new \anvildev\trails\query\AuditQuery();
    }

    /**
     * Derive a category string from an event name.
     * Uses the namespace segment before the first dot, or 'general' for bare event names.
     */
    private function getCategoryFromEvent(string $event): string
    {
        $pos = strpos($event, '.');
        if ($pos === false) {
            return 'general';
        }
        return substr($event, 0, $pos);
    }

    /**
     * Validate that an event type follows the required "namespace.action" format.
     *
     * @throws \InvalidArgumentException if the format is invalid
     */
    public static function assertValidEventType(string $type): void
    {
        if (!preg_match('/^[a-z0-9_\-]+\.[a-z0-9_\-]+$/i', $type)) {
            throw new \InvalidArgumentException(
                'Event type must match "namespace.action" format (e.g. "myplugin.user_synced"). Got: "' . $type . '"'
            );
        }
    }

    /**
     * Log a custom event from a third-party plugin or custom code.
     *
     * @param string $eventType Event type in "plugin-name.action" format, e.g. "booked.reservation_cancelled"
     * @param string $category Event category (e.g. "booking", "payment", "workflow")
     * @param string|null $description Optional human-readable description (stored in context)
     * @param array|null $metadata Arbitrary key/value data to attach to the log
     * @param int|null $elementId ID of a related element
     * @param string|null $elementType Class name of a related element type
     * @param string|null $elementTitle Human-readable title of the related element
     * @throws \InvalidArgumentException if eventType doesn't match "namespace.action" format
     */
    public function logCustomEvent(
        string $eventType,
        string $category,
        ?string $description = null,
        ?array $metadata = null,
        ?int $elementId = null,
        ?string $elementType = null,
        ?string $elementTitle = null,
    ): bool {
        self::assertValidEventType($eventType);

        $lowerType = strtolower($eventType);
        foreach (self::RESERVED_PREFIXES as $prefix) {
            if (str_starts_with($lowerType, $prefix)) {
                throw new \InvalidArgumentException(
                    "Event type prefix '{$prefix}' is reserved for system events."
                );
            }
        }

        if ($metadata !== null) {
            $encoded = json_encode($metadata);
            if ($encoded === false) {
                throw new \InvalidArgumentException('Metadata could not be JSON-encoded.');
            }
            if (strlen($encoded) > self::MAX_CUSTOM_METADATA_BYTES) {
                throw new \InvalidArgumentException(sprintf(
                    'Metadata exceeds %d bytes (got %d).',
                    self::MAX_CUSTOM_METADATA_BYTES,
                    strlen($encoded)
                ));
            }
        }

        $context = array_filter([
            'category' => $category,
            'description' => $description,
            'title' => $elementTitle,
        ], fn($v) => $v !== null);

        if ($metadata !== null) {
            $context = array_merge($context, $metadata);
        }

        return $this->log(
            event: $eventType,
            elementType: $elementType,
            elementId: $elementId,
            context: $context,
        );
    }

    /** IPv4: /16 mask. IPv6: /48 mask. */
    private function anonymizeIpAddress(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+\.\d+$/', '.0.0', $ip);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = str_split(bin2hex(inet_pton($ip)), 4);
            for ($i = 3; $i < 8; $i++) {
                $parts[$i] = '0000';
            }
            return implode(':', $parts);
        }
        return $ip;
    }

    private function hashData(AuditLogRecord $record): array
    {
        return $this->hashDataFromRow([
            'event' => $record->event,
            'category' => $record->category,
            'elementType' => $record->elementType,
            'elementId' => $record->elementId,
            'elementTitle' => $record->elementTitle,
            'userId' => $record->userId,
            'userName' => $record->userName,
            'userEmail' => $record->userEmail,
            'ipAddress' => $record->ipAddress,
            'userAgent' => $record->userAgent,
            'requestUrl' => $record->requestUrl,
            'requestMethod' => $record->requestMethod,
            'siteId' => $record->siteId,
            'sessionId' => $record->sessionId,
            'dateCreated' => $record->dateCreated,
            'oldValue' => $record->oldValue,
            'newValue' => $record->newValue,
            'metadata' => $record->metadata,
            'prevHash' => $record->prevHash,
        ]);
    }

    /**
     * Build the hash input from a raw row (array), so integrity checks can run
     * against rows loaded from archive tables via Query (not only the active
     * table's ActiveRecord). Keep this in lock-step with hashData().
     *
     * @param array{
     *   event?: string|null,
     *   category?: string|null,
     *   elementType?: string|null,
     *   elementId?: int|string|null,
     *   elementTitle?: string|null,
     *   userId?: int|string|null,
     *   userName?: string|null,
     *   userEmail?: string|null,
     *   ipAddress?: string|null,
     *   userAgent?: string|null,
     *   requestUrl?: string|null,
     *   requestMethod?: string|null,
     *   siteId?: int|string|null,
     *   sessionId?: string|null,
     *   dateCreated?: \DateTime|string|null,
     *   oldValue?: string|null,
     *   newValue?: string|null,
     *   metadata?: string|null,
     *   prevHash?: string|null,
     *   hash?: string|null
     * } $row
     * @return array{
     *   event: string|null,
     *   category: string|null,
     *   elementType: string|null,
     *   elementId: int|string|null,
     *   elementTitle: string|null,
     *   userId: int|string|null,
     *   userName: string|null,
     *   userEmail: string|null,
     *   ipAddress: string|null,
     *   userAgent: string|null,
     *   requestUrl: string|null,
     *   requestMethod: string|null,
     *   siteId: int|string|null,
     *   sessionId: string|null,
     *   dateCreated: string,
     *   oldValue: string|null,
     *   newValue: string|null,
     *   metadata: string|null,
     *   prevHash: string|null
     * }
     */
    private function hashDataFromRow(array $row): array
    {
        $dateCreated = $row['dateCreated'] ?? '';
        if ($dateCreated instanceof \DateTime) {
            $dateCreated = $dateCreated->format('Y-m-d H:i:s');
        }
        return [
            'event' => $row['event'] ?? null,
            'category' => $row['category'] ?? null,
            'elementType' => $row['elementType'] ?? null,
            'elementId' => $row['elementId'] ?? null,
            'elementTitle' => $row['elementTitle'] ?? null,
            'userId' => $row['userId'] ?? null,
            'userName' => $row['userName'] ?? null,
            'userEmail' => $row['userEmail'] ?? null,
            'ipAddress' => $row['ipAddress'] ?? null,
            'userAgent' => $row['userAgent'] ?? null,
            'requestUrl' => $row['requestUrl'] ?? null,
            'requestMethod' => $row['requestMethod'] ?? null,
            'siteId' => $row['siteId'] ?? null,
            'sessionId' => $row['sessionId'] ?? null,
            'dateCreated' => (string) $dateCreated,
            'oldValue' => $row['oldValue'] ?? null,
            'newValue' => $row['newValue'] ?? null,
            'metadata' => $row['metadata'] ?? null,
            'prevHash' => $row['prevHash'] ?? null,
        ];
    }

    private function generateHash(AuditLogRecord $record): string
    {
        return HashService::generate(
            $this->hashData($record),
            Craft::$app->getConfig()->getGeneral()->securityKey
        );
    }

    public function recalculateHash(AuditLogRecord $record): string
    {
        return $this->generateHash($record);
    }

    /**
     * Compute the v3 HMAC for a raw row (array form). Used by archive-aware
     * code paths that load rows via Query (e.g. anonymizing PII across
     * partitioned tables) and need to rewrite the hash without going through
     * the active-table-bound AuditLogRecord.
     *
     * @param array<string, mixed> $row Same shape as verifyRowIntegrity().
     */
    public function hashRow(array $row): string
    {
        return HashService::generate(
            $this->hashDataFromRow($row),
            Craft::$app->getConfig()->getGeneral()->securityKey
        );
    }

    public function verifyLogIntegrity(AuditLogRecord $record): bool
    {
        return !empty($record->hash) && HashService::verify(
            $this->hashData($record),
            $record->hash,
            Craft::$app->getConfig()->getGeneral()->securityKey
        );
    }

    /**
     * Verify a raw row (loaded from an archive table via Query). Returns true
     * when the row's stored hash matches a fresh HMAC over the canonical
     * hashData projection.
     *
     * @param array{
     *   event?: string|null,
     *   category?: string|null,
     *   elementType?: string|null,
     *   elementId?: int|string|null,
     *   elementTitle?: string|null,
     *   userId?: int|string|null,
     *   userName?: string|null,
     *   userEmail?: string|null,
     *   ipAddress?: string|null,
     *   userAgent?: string|null,
     *   requestUrl?: string|null,
     *   requestMethod?: string|null,
     *   siteId?: int|string|null,
     *   sessionId?: string|null,
     *   dateCreated?: \DateTime|string|null,
     *   oldValue?: string|null,
     *   newValue?: string|null,
     *   metadata?: string|null,
     *   prevHash?: string|null,
     *   hash?: string|null
     * } $row
     */
    public function verifyRowIntegrity(array $row): bool
    {
        $hash = $row['hash'] ?? null;
        if (empty($hash)) {
            return false;
        }
        return HashService::verify(
            $this->hashDataFromRow($row),
            (string) $hash,
            Craft::$app->getConfig()->getGeneral()->securityKey
        );
    }

    /**
     * Largest chainPosition across every non-dropped table in the rotation
     * registry. Needed right after rotate(): the new active table is empty, so
     * querying it alone would return 0 and restart the chain at 1 — forking it.
     *
     * Executed as a single UNION ALL round-trip so `log()` doesn't hold the
     * chain mutex while issuing one query per table (see code review M4).
     */
    public function maxChainPositionAcrossTables(): int
    {
        $tableNames = array_values(array_map(
            static fn(array $e) => $e['tableName'],
            $this->nonDroppedRegistryEntries()
        ));

        if ($tableNames === []) {
            return 0;
        }

        $db = Craft::$app->getDb();
        $selects = array_map(
            static fn(string $t) => 'SELECT MAX([[chainPosition]]) AS m FROM ' . $db->quoteTableName('{{%' . $t . '}}'),
            $tableNames
        );
        $sql = 'SELECT MAX(m) AS maxPos FROM (' . implode(' UNION ALL ', $selects) . ') u';
        $maxPos = $db->createCommand($sql)->queryScalar();

        return $maxPos ? (int) $maxPos : 0;
    }

    /**
     * Fetch the stored hash at a given chainPosition, searching every
     * non-dropped table in the rotation registry. This matters right after
     * rotate(): the previous-record hash lives in the archive table, not the
     * empty new active table.
     *
     * Globally unique chainPositions are an invariant the chain relies on,
     * so the first match wins — the order in which tables are scanned is
     * irrelevant in a healthy install.
     */
    public function hashAtChainPosition(int $chainPosition): ?string
    {
        foreach ($this->nonDroppedRegistryEntries() as $entry) {
            $hash = (new \yii\db\Query())
                ->from('{{%' . $entry['tableName'] . '}}')
                ->where(['chainPosition' => $chainPosition])
                ->select(['hash'])
                ->scalar();
            if ($hash !== false && $hash !== null) {
                return (string) $hash;
            }
        }
        return null;
    }

    /**
     * Verify integrity of all log records.
     * @return array{total: int, valid: int, invalid: int, missing: int, invalidIds: list<string>}
     */
    public function verifyAllIntegrity(?\Closure $progressCallback = null): array
    {
        // Archive-aware: iterate every non-dropped table in the rotation
        // registry. Without this, post-rotation installs silently skip
        // archived logs and report a false "all clean" health check.
        $tables = $this->nonDroppedRegistryEntries();

        $total = 0;
        foreach ($tables as $entry) {
            $total += (int) (new \yii\db\Query())->from('{{%' . $entry['tableName'] . '}}')->count();
        }

        $valid = $invalid = $missing = 0;
        $invalidIds = [];
        $batchSize = 500;

        foreach ($tables as $entry) {
            $tableName = $entry['tableName'];
            $lastId = 0;
            while (true) {
                $rows = (new \yii\db\Query())
                    ->from('{{%' . $tableName . '}}')
                    ->where(['>', 'id', $lastId])
                    ->orderBy(['id' => SORT_ASC])
                    ->limit($batchSize)
                    ->all();

                if (empty($rows)) {
                    break;
                }

                foreach ($rows as $row) {
                    if (empty($row['hash'])) {
                        $missing++;
                    } elseif ($this->verifyRowIntegrity($row)) {
                        $valid++;
                    } else {
                        $invalid++;
                        $invalidIds[] = $tableName . '#' . (int) $row['id'];
                    }

                    if ($progressCallback) {
                        $progressCallback($valid + $invalid + $missing, $total);
                    }
                }

                $lastId = (int) end($rows)['id'];
            }
        }

        return [
            'total' => $total,
            'valid' => $valid,
            'invalid' => $invalid,
            'missing' => $missing,
            'invalidIds' => $invalidIds,
        ];
    }

    /**
     * Walk all log records (active + non-dropped archive tables) batch-wise,
     * verify each hash, and cache results for CP display.
     *
     * Tampered rows are returned as "<tableName>#<id>" strings so IDs across
     * multiple tables (each with its own auto-increment) stay unambiguous.
     *
     * Rows with a NULL/empty hash are reported in `missing` (never-hashed),
     * separate from `tampered` (hash present but didn't verify), so the CP
     * integrity view can distinguish the two.
     *
     * @param int $batchSize how many records to load per iteration
     * @param callable|null $onProgress called with (verifiedCount, totalCount, tampered) after each batch
     * @return array{verified: int, tampered: list<string>, missing: list<string>, total: int, tables: list<array{tableName: string, verified: int, tampered: int, missing: int}>}
     */
    public function verifyAllLogs(int $batchSize = 500, ?callable $onProgress = null): array
    {
        $tables = $this->nonDroppedRegistryEntries();

        $total = 0;
        foreach ($tables as $entry) {
            $total += (int) (new \yii\db\Query())->from('{{%' . $entry['tableName'] . '}}')->count();
        }

        $verified = 0;
        $tampered = [];
        $missing = [];
        $perTable = [];

        foreach ($tables as $entry) {
            $tableName = $entry['tableName'];
            $tableVerified = 0;
            $tableTampered = 0;
            $tableMissing = 0;
            $lastId = 0;

            while (true) {
                $rows = (new \yii\db\Query())
                    ->from('{{%' . $tableName . '}}')
                    ->where(['>', 'id', $lastId])
                    ->orderBy(['id' => SORT_ASC])
                    ->limit($batchSize)
                    ->all();

                if (empty($rows)) {
                    break;
                }

                foreach ($rows as $row) {
                    $id = $tableName . '#' . (int) $row['id'];
                    if (empty($row['hash'])) {
                        $missing[] = $id;
                        $tableMissing++;
                    } elseif ($this->verifyRowIntegrity($row)) {
                        $verified++;
                        $tableVerified++;
                    } else {
                        $tampered[] = $id;
                        $tableTampered++;
                    }
                }

                $lastId = (int) end($rows)['id'];

                if ($onProgress !== null) {
                    $onProgress($verified, $total, $tampered);
                }
            }

            $perTable[] = [
                'tableName' => $tableName,
                'verified' => $tableVerified,
                'tampered' => $tableTampered,
                'missing' => $tableMissing,
            ];
        }

        Craft::$app->getCache()->set('trails:integrity:lastRun', [
            'at' => time(),
            'verified' => $verified,
            'total' => $total,
            'tampered' => $tampered,
            'missing' => $missing,
            'tables' => $perTable,
        ], 7 * 24 * 60 * 60);

        return [
            'verified' => $verified,
            'tampered' => $tampered,
            'missing' => $missing,
            'total' => $total,
            'tables' => $perTable,
        ];
    }

    /**
     * Walk all records in chain order and verify prevHash links via
     * ChainLinkValidator (also surfaces NULL prevHash on non-genesis rows
     * as chain gaps).
     *
     * @return array{verified: int, failed: int, failedIds: int[], firstFailedAt: int|null}
     */
    public function verifyChainLinks(int $batchSize = 500): array
    {
        $verified = 0;
        $failed = 0;
        $failedIds = [];
        $firstFailedAt = null;
        $lastChainPosition = 0;
        $previousHash = null;

        while (true) {
            $rows = (new \yii\db\Query())
                ->from('{{%trails_logs}}')
                ->select(['id', 'chainPosition', 'prevHash', 'hash'])
                ->where(['is not', 'chainPosition', null])
                ->andWhere(['>', 'chainPosition', $lastChainPosition])
                ->orderBy(['chainPosition' => SORT_ASC])
                ->limit($batchSize)
                ->all();

            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $chainPosition = (int) $row['chainPosition'];
                $prevHash = $row['prevHash'] !== null ? (string) $row['prevHash'] : null;
                $hash = (string) $row['hash'];
                $id = (int) $row['id'];

                $linkResult = ChainLinkValidator::validate(
                    chainPosition: $chainPosition,
                    prevHash: $prevHash,
                    expectedPrevHash: $previousHash,
                );

                if ($linkResult['status'] === 'ok') {
                    $verified++;
                } else {
                    $failed++;
                    $failedIds[] = $id;
                    if ($firstFailedAt === null) {
                        $firstFailedAt = $chainPosition;
                    }
                }

                $previousHash = $hash;
                $lastChainPosition = $chainPosition;
            }
        }

        return [
            'verified' => $verified,
            'failed' => $failed,
            'failedIds' => $failedIds,
            'firstFailedAt' => $firstFailedAt,
        ];
    }

    /**
     * Count audit-log rows whose dateCreated falls inside the inclusive
     * [dateFrom, dateTo] window across active and archived tables.
     */
    public function countRecordsInRange(string $dateFrom, string $dateTo): int
    {
        $total = 0;
        foreach ($this->nonDroppedRegistryEntries() as $entry) {
            $total += (int) (new \yii\db\Query())
                ->from('{{%' . $entry['tableName'] . '}}')
                ->where(['>=', 'dateCreated', $dateFrom])
                ->andWhere(['<=', 'dateCreated', $dateTo . ' 23:59:59'])
                ->count();
        }
        return $total;
    }

    /**
     * @return array<int, array{tableName: string, dateFrom?: string, dateTo?: string, status: string, rowCount?: int, firstChainPosition?: int|null, lastChainPosition?: int|null}>
     */
    private function nonDroppedRegistryEntries(): array
    {
        $registry = Trails::getInstance()->tableRotation->getRegistry();
        $tables = array_values(array_filter($registry, static fn(array $e) => $e['status'] !== 'dropped'));

        if ($tables === []) {
            Craft::warning(
                'Trails: no active/archived tables found in trails_log_months. Integrity and chain operations will return empty results until the registry is repaired.',
                'trails'
            );
        }

        return $tables;
    }

    private function canSendAlert(string $eventKey): bool
    {
        $cacheKey = 'trails_alert_' . md5($eventKey);
        $cache = Craft::$app->getCache();

        if ($cache->get($cacheKey) !== false) {
            return false;
        }

        $cache->set($cacheKey, true, Trails::getInstance()->getSettings()->alertCooldownMinutes * 60);
        return true;
    }

    private function checkForAlerts(string $event, AuditLogRecord $record): void
    {
        $settings = Trails::getInstance()->getSettings();

        if (!in_array($event, $settings->alertEvents, true)) {
            return;
        }

        if ($event === 'user.login.failed' && $record->ipAddress) {
            $recentFailures = AuditLogRecord::find()
                ->where(['event' => 'user.login.failed', 'ipAddress' => $record->ipAddress])
                ->andWhere(['>=', 'dateCreated', date('Y-m-d H:i:s', strtotime('-1 hour'))])
                ->count();

            if ($recentFailures >= $settings->failedLoginThreshold && $this->canSendAlert($event . ':' . $record->ipAddress)) {
                $this->sendAlert(
                    'Multiple Failed Login Attempts',
                    "IP {$record->ipAddress} has failed to login {$recentFailures} times in the last hour."
                );
            }
            return;
        }

        if ($this->canSendAlert($event)) {
            $this->sendAlert(
                'Audit Alert: ' . $event,
                "Event: {$event}\nElement: {$record->elementType} #{$record->elementId}\nUser: {$record->userName}\nTime: " . date('Y-m-d H:i:s')
            );
        }
    }

    private function sendAlert(string $subject, string $body): void
    {
        $email = Trails::getInstance()->getSettings()->alertEmail;
        if (!$email) {
            return;
        }

        try {
            Craft::$app->getMailer()
                ->compose()
                ->setTo($email)
                ->setSubject('[Trails] ' . $subject)
                ->setTextBody($body)
                ->send();
        } catch (\Throwable $e) {
            Craft::error('Failed to send audit alert: ' . $e->getMessage(), 'trails');
        }
    }

    public function flushShippingBuffer(): void
    {
        $settings = Trails::getInstance()->getSettings();
        if (!$settings->externalLoggingEnabled || !$settings->externalEndpoint) {
            return;
        }

        $cache = Craft::$app->getCache();
        $buffer = $cache->get('trails_shipping_buffer') ?: [];

        if (!empty($buffer)) {
            $cache->set('trails_shipping_buffer', [], 300);
            Craft::$app->getQueue()->push(new BatchShipLogJob([
                'endpoint' => $settings->externalEndpoint,
                'provider' => $settings->externalProvider ?? 'webhook',
                'payloads' => $buffer,
            ]));
        }
    }

    /** Build a query with common filter criteria. Does NOT apply limit/offset. */
    public function buildQuery(array $criteria = []): \yii\db\ActiveQuery
    {
        $query = AuditLogRecord::find();

        foreach (['event', 'category', 'userId', 'elementType', 'elementId', 'ipAddress'] as $field) {
            if (!empty($criteria[$field])) {
                $query->andWhere([$field => $criteria[$field]]);
            }
        }

        if (!empty($criteria['dateFrom'])) {
            $query->andWhere(['>=', 'dateCreated', $criteria['dateFrom']]);
        }
        if (!empty($criteria['dateTo'])) {
            $query->andWhere(['<=', 'dateCreated', $criteria['dateTo']]);
        }

        if (!empty($criteria['search'])) {
            $query->andWhere([
                'or',
                ['like', 'event', $criteria['search']],
                ['like', 'elementTitle', $criteria['search']],
                ['like', 'userName', $criteria['search']],
            ]);
        }

        return $query->orderBy(['dateCreated' => SORT_DESC]);
    }

    public function getLogs(array $criteria = []): array
    {
        $query = $this->buildQuery($criteria);
        if (!empty($criteria['limit'])) {
            $query->limit($criteria['limit']);
        }
        if (!empty($criteria['offset'])) {
            $query->offset($criteria['offset']);
        }
        return $query->all();
    }

    public function countLogs(array $criteria = []): int
    {
        return $this->buildQuery($criteria)->count();
    }

    public function getLogById(int $id): ?AuditLogRecord
    {
        return AuditLogRecord::findOne($id);
    }

    public function getEventTypes(): array
    {
        return AuditLogRecord::find()->select(['event'])->distinct()->column();
    }

    public function getCategories(): array
    {
        return AuditLogRecord::find()->select(['category'])->distinct()->column();
    }

    /** Single query with conditional aggregates — fewer round-trips than individual COUNTs. */
    public function getActivitySummary(int $days = 7): array
    {
        $row = (new \yii\db\Query())
            ->select([
                'totalEvents' => 'COUNT(*)',
                'uniqueUsers' => 'COUNT(DISTINCT [[userId]])',
                'logins' => 'SUM(CASE WHEN [[event]] = \'user.login\' THEN 1 ELSE 0 END)',
                'elementsCreated' => 'SUM(CASE WHEN [[event]] = \'element.created\' THEN 1 ELSE 0 END)',
                'elementsUpdated' => 'SUM(CASE WHEN [[event]] = \'element.updated\' THEN 1 ELSE 0 END)',
                'elementsDeleted' => 'SUM(CASE WHEN [[event]] = \'element.deleted\' THEN 1 ELSE 0 END)',
            ])
            ->from('{{%trails_logs}}')
            ->where(['>=', 'dateCreated', date('Y-m-d H:i:s', strtotime("-{$days} days"))])
            ->one();

        return array_map('intval', $row ?: array_fill_keys([
            'totalEvents', 'uniqueUsers', 'logins',
            'elementsCreated', 'elementsUpdated', 'elementsDeleted',
        ], 0));
    }

    /** Returns daily event counts for the last N days, with zero-fill for days with no events. */
    public function getDailyActivity(int $days = 7): array
    {
        $rows = (new \yii\db\Query())
            ->select([
                'date' => 'DATE([[dateCreated]])',
                'count' => 'COUNT(*)',
            ])
            ->from('{{%trails_logs}}')
            ->where(['>=', 'dateCreated', date('Y-m-d', strtotime("-{$days} days"))])
            ->groupBy(['DATE([[dateCreated]])'])
            ->orderBy(['date' => SORT_ASC])
            ->all();

        $series = [];
        for ($i = $days; $i >= 0; $i--) {
            $series[date('Y-m-d', strtotime("-{$i} days"))] = 0;
        }
        foreach ($rows as $row) {
            $series[$row['date']] = (int)$row['count'];
        }
        return $series;
    }
}
