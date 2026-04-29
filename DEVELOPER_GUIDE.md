# Trails Developer Guide

API reference for integrating with the Trails audit trail plugin.

## Fluent Query Builder

The recommended way to query audit logs programmatically:

```php
use anvildev\trails\Trails;

// Basic query
$logs = Trails::getInstance()->audit->query()
    ->event('element.created')
    ->limit(25)
    ->all();

// Advanced filtering with cursor pagination
$result = Trails::getInstance()->audit->query()
    ->event('element.*')           // wildcard matching
    ->category('element')
    ->user(42)
    ->after('2026-01-01')
    ->before('2026-04-01')
    ->search('homepage')
    ->limit(50)
    ->cursor($nextCursorFromPreviousPage)
    ->get();                       // returns CursorResult

foreach ($result->items as $entry) {
    // $entry is an AuditLogEntry DTO
    echo $entry->event . ' — ' . $entry->dateCreated;
    $metadata = $entry->decodedMetadata(); // parsed JSON
}

if ($result->hasMore()) {
    $nextPage = $result->nextCursor; // pass to ->cursor() for next page
}

// Element history shorthand
$logs = Trails::getInstance()->audit->query()
    ->forElement($entry) // pass any Craft element
    ->all();

// Single record
$log = Trails::getInstance()->audit->query()
    ->event('user.login')
    ->one(); // returns ?AuditLogEntry

// Count only
$count = Trails::getInstance()->audit->query()
    ->category('element')
    ->after('2026-01-01')
    ->count();
```

### Return Types

| Method | Returns |
|--------|---------|
| `->all()` | `AuditLogEntry[]` |
| `->one()` | `?AuditLogEntry` |
| `->count()` | `int` |
| `->get()` | `CursorResult` (items + nextCursor + totalCount) |

`AuditLogEntry` is a read-only DTO with properties: `id`, `event`, `category`, `elementType`, `elementId`, `elementTitle`, `userId`, `userName`, `userEmail`, `ipAddress`, `country`, `region`, `city`, `userAgent`, `requestUrl`, `requestMethod`, `siteId`, `oldValue`, `newValue`, `metadata`, `sessionId`, `hash`, `chainPosition`, `prevHash`, `dateCreated`.

Helper methods: `decodedMetadata()`, `decodedOldValue()`, `decodedNewValue()` — return parsed arrays from JSON strings.

## Logging Custom Events

```php
Trails::getInstance()->audit->logCustomEvent(
    eventType: 'myplugin.order_shipped',
    category: 'fulfillment',
    description: 'Order #1234 shipped via UPS',
    metadata: ['orderId' => 1234, 'carrier' => 'UPS'],
    elementId: 1234,
    elementType: 'myplugin\\elements\\Order',
    elementTitle: 'Order #1234',
);
```

**Format:** Event types must match `namespace.action` (e.g., `myplugin.user_synced`).

**Reserved prefixes** (throw `InvalidArgumentException`): `element.`, `user.`, `asset.`, `config.`, `audit.`, `trails.`, `auth.`.

**Metadata limit:** 64 KB when JSON-encoded.

## Event Bridge

Register Yii events that Trails logs automatically — no manual `logCustomEvent()` calls needed:

```php
use anvildev\trails\Trails;

if (class_exists(Trails::class) && Trails::getInstance() !== null) {
    Trails::getInstance()->eventBridge->listen(
        MyService::class,
        MyService::EVENT_ORDER_SHIPPED,
        'myplugin.order_shipped',
        function($event) {
            return [
                'description' => "Order #{$event->order->id} shipped",
                'elementId' => $event->order->id,
                'elementType' => get_class($event->order),
                'elementTitle' => $event->order->title,
                'metadata' => ['carrier' => $event->carrier],
            ];
        }
    );
}
```

**Built-in integrations:** Craft Commerce (order completion, payments, status changes) and Booked (reservation create/cancel) — activate automatically when installed.

## Listening to Audit Events

```php
use anvildev\trails\services\AuditService;
use anvildev\trails\events\AuditEvent;
use yii\base\Event;

// Suppress logging for specific events
Event::on(AuditService::class, AuditService::EVENT_BEFORE_LOG, function(AuditEvent $event) {
    if ($event->elementType === MyTemporaryElement::class) {
        $event->isValid = false;
    }
});

// React to saved audit logs
Event::on(AuditService::class, AuditService::EVENT_AFTER_LOG, function(AuditEvent $event) {
    if ($event->event === 'element.deleted') {
        MySlackService::notify("Deleted: {$event->record->elementTitle}");
    }
});
```

## Merkle Proofs

Verify a single record's inclusion in its Merkle tree:

```php
$proof = Trails::getInstance()->merkle->getInclusionProof($chainPosition);

if ($proof && $proof->verified) {
    echo "Record verified in Merkle tree (root: {$proof->rootHash})";
}
```

Verify all Merkle roots:

```php
$result = Trails::getInstance()->merkle->verifyAllRoots();
// ['verified' => 10, 'failed' => 0, 'failedIds' => []]
```

## Certificate of Integrity

Generate exportable proof documents:

```php
$certificate = Trails::getInstance()->certificate;

// Signed JSON (machine-verifiable)
$json = $certificate->generateJson('2026-01-01', '2026-03-31');

// PDF (human-readable, requires dompdf)
$pdf = $certificate->generatePdf('2026-01-01', '2026-03-31');
```

## REST API

All endpoints require a Craft bearer token with `trails-viewLogs` permission. Rate limited at 60 requests/minute per token (configurable).

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/actions/trails/api/v1/logs` | Paginated log list with filters |
| GET | `/actions/trails/api/v1/log?id=N` | Single log detail |
| GET | `/actions/trails/api/v1/proof?id=N` | Merkle inclusion proof |
| GET | `/actions/trails/api/v1/integrity` | Last verification result |
| POST | `/actions/trails/api/v1/certificate` | Generate Certificate of Integrity |
| GET | `/actions/trails/api/v1/summary?days=7` | Activity summary |
| POST | `/actions/trails/api/v1/export` | Start background export |
| GET | `/actions/trails/api/v1/export-status?id=N` | Poll export status / download |

### Example: Query logs via REST

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://yoursite.com/actions/trails/api/v1/logs?event=element.created&limit=10"
```

Response:
```json
{
  "data": [{"id": 1, "event": "element.created", ...}],
  "pagination": {"nextCursor": "eyJ...", "totalCount": 42, "hasMore": true}
}
```

## GraphQL

Enable in `config/trails.php`: `'enableGraphql' => true`. Admin must enable Trails types per GQL schema in **Settings → GraphQL**.

```graphql
{
  trailsLogs(event: "element.*", limit: 10) {
    id
    event
    userName
    elementTitle
    dateCreated
  }

  trailsSummary(days: 7) {
    totalEvents
    uniqueUsers
    elementsCreated
  }

  trailsIntegrity {
    verified
    total
    lastRunAt
  }
}
```

## Template Variables

```twig
{# Fluent query builder in Twig #}
{% set result = craft.trails.query().event('element.*').limit(10).get() %}
{% for log in result.items %}
    {{ log.event }} — {{ log.dateCreated }}
{% endfor %}

{# Simple queries #}
{% set logs = craft.trails.logs({event: 'user.login', limit: 10}) %}
{% set total = craft.trails.count({category: 'element'}) %}
{% set summary = craft.trails.summary(7) %}
{% set history = craft.trails.elementHistory(entry.id) %}
{% set activity = craft.trails.userActivity(currentUser.id, 25) %}
```

## Configuration Override

Copy `src/config.php` to `config/trails.php` for multi-environment config:

```php
return [
    '*' => [
        'retentionDays' => 365,
        'merkleBatchSize' => 256,
        'realtime' => 'poll',
    ],
    'production' => [
        'externalLoggingEnabled' => true,
        'externalProvider' => 'splunk',
        'externalEndpoint' => App::env('SPLUNK_HEC_URL'),
        'anchorType' => 's3',
        's3Bucket' => App::env('TRAILS_S3_BUCKET'),
        's3Region' => 'eu-central-1',
        'enableApi' => true,
        'apiRateLimit' => 120,
    ],
    'dev' => [
        'alertsEnabled' => false,
        'externalLoggingEnabled' => false,
        'anchorType' => null,
    ],
];
```

Settings overridden by the config file appear as read-only in the CP with a "Set in config file" indicator.

## Permissions

| Permission | What it gates |
|---|---|
| `trails-viewLogs` | View Activity Logs, log detail, timelines. PII restricted unless also admin/manageSettings |
| `trails-exportLogs` | Download exports. PII always redacted for non-admin users |
| `trails-manageSettings` | Configure all settings. Unlocks PII visibility |

Admins bypass all permission checks. REST API and GraphQL require `trails-viewLogs` on the bearer token.

## GDPR & Hash Chain Integrity

Trails has two requirements that pull in opposite directions: tamper-evident audit (every row must self-verify and chain to its predecessor) and GDPR Article 17 (PII must be erasable on request). Every tamper-evident-log design has to make a trade-off here; this section documents what Trails does and how it stays auditable.

### What happens on user deletion

When a Craft `User` is deleted, `AuthEventListener` queues an `AnonymizeUserLogsJob` for that user id. The job iterates **every non-dropped partition table** — active `trails_logs` plus every `trails_logs_YYYY_MM` archive registered in `trails_log_months` — and for each row attributed to the deleted user:

1. Sets `userName` to the literal sentinel `[deleted]` and `userEmail` to `NULL`.
2. Recomputes the row's v3 HMAC over the new (anonymized) payload via `AuditService::hashRow()` and writes it to the `hash` column.

Step 2 is necessary — without it, every anonymized row would fail its self-check (`verifyLogIntegrity`) forever, drowning real tamper signal in legitimate erasure noise. With it, the row's `hash` matches its current data, but its successor's `prevHash` still points at the row's *original* hash, so the chain link is broken at the anonymization point.

### Why the chain breaks (and why that's acceptable)

A pure HMAC chain has no way to update a row in place without invalidating the chain link out of it. The standard mitigations:

- **Tombstone rehashing** (what Trails does) — rewrite the row's hash so its self-check still passes; accept the broken outbound link.
- **External anchoring** (what Trails *also* does) — Merkle roots anchored to S3 Object Lock or RFC 3161 capture the pre-anonymization state immutably. If the row was anchored before the user requested deletion, the original Merkle leaf hash and the root that committed it are preserved out-of-band, so an auditor can prove the original row existed and what it hashed to — they just can't see the original PII (which is the entire point of erasure).

The practical guidance: **anchor early, anchor often.** A row anchored *before* the user is deleted retains its full cryptographic provenance via the anchor; a row that's anonymized before any anchor was computed loses its pre-anonymization state forever.

### Distinguishing anonymization from tampering

When `AnchorService::verifyAll()` (or `php craft trails/integrity/verify`) reports a chain link mismatch, you need to know whether it's a deletion artifact or a real tamper. Two signals to correlate:

1. **The `user.gdpr.anonymized` event row** — `AuthEventListener` writes one of these every time it queues the job. Find chain breaks that sit between the anonymization event and the next normal row written by the same user.
2. **The anonymized rows themselves** carry `userName = '[deleted]'` and `userEmail = NULL`. A tampered row would have whatever the attacker wrote, not the sentinel.

If a chain break sits between two non-anonymized rows with no `user.gdpr.anonymized` event nearby, treat it as a real tamper finding and investigate.

### Operator guidance

- Document this trade-off in your operator runbook so the first GDPR request doesn't trigger a panic when integrity verify reports broken links.
- Run `trails/integrity/verify` regularly so anonymization-caused breaks are noticed and explained while the context (the corresponding `user.gdpr.anonymized` event) is still fresh — not months later when the auditor asks.
- Tune the Merkle batch + anchor cadence (`merkleBatchSize`, anchor scheduling) so it's tighter than your typical user-deletion latency. Anchored rows survive erasure cryptographically; un-anchored rows do not.
