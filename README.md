# Trails - Enterprise Audit Trail for Craft CMS

**Enterprise-grade audit trail and compliance logging for Craft CMS 5.x**

Track every action in your Craft CMS installation. Perfect for regulated industries (healthcare, finance, legal) that require complete audit trails for compliance.

## Features

### Comprehensive Logging
- **Element Events**: Track creation, updates, and deletion of entries, assets, users, and all element types
- **Authentication**: Log user logins, logouts, and failed login attempts with brute-force detection
- **Config Changes**: Monitor when project config changes are applied
- **Asset Operations**: Track file uploads, modifications, and deletions
- **Field-level Change Tracking**: Before/after diffs with structured comparison view (not raw JSON)
- **Custom Event API**: Third-party plugins can log events via `logCustomEvent()` with reserved-prefix protection

### Enterprise Scale
- **Monthly Table Partitioning**: Automatic rotation of the active log table to monthly archives
- **Cursor-based Pagination**: No offset degradation - fast at any page depth
- **Cross-table Queries**: Transparent querying across partitioned tables via `UnionQueryBuilder`

### Cryptographic Chain-of-Custody
- **Hash Chain**: Every record links to the previous via `prevHash` - insertion, deletion, or reordering breaks the chain
- **Merkle Trees**: Batched Merkle root computation (configurable, default every 256 records)
- **External Anchoring**: Anchor Merkle roots to S3 Object Lock (WORM) or RFC 3161 Timestamp Authority
  > External anchors are independently re-verifiable by auditors using the AWS
  > CLI (`aws s3api head-object`) or `openssl ts -verify` - the plugin uses
  > the same primitives.
- **Certificate of Integrity**: Export signed JSON + PDF proof documents for auditors to verify independently
- **Full Verification**: CLI + CP verification of hashes, chain links, Merkle roots, and external anchors

### Security & Compliance
- **GDPR Compliance**: IP anonymization (/16 IPv4, /48 IPv6), email encryption at rest, auto-anonymization on user deletion
- **Integrity Hashes**: HMAC-SHA256 per record for tamper detection
- **PII Gating**: Sensitive data restricted to admin/settings-manager users
- **SSRF Protection**: External endpoints validated against all private/reserved/metadata IP ranges
- **CSV Injection Protection**: Export fields sanitized against formula injection
- **Webhook HMAC Signing**: Outgoing payloads signed with `X-Trails-Signature` + `X-Trails-Timestamp`

### Developer API
- **REST API**: 8 endpoints at `/actions/trails/api/v1/` with dual auth (CP session + permissions, or bearer token) and rate limiting
- **GraphQL**: Full schema integration - `trailsLogs`, `trailsLog`, `trailsSummary`, `trailsIntegrity` queries
- **Fluent Query Builder**: `Trails::getInstance()->audit->query()->event('element.*')->all()`
- **Typed DTOs**: `AuditLogEntry`, `CursorResult`, `MerkleProof`, `IntegrityReport`
- **Config Overrides**: `config/trails.php` for multi-environment configuration

### Real-time CP
- **htmx-driven Log Viewer**: Filter changes update the table without page reload
- **Live Polling**: 5-second auto-refresh with visual indicator (opt-in toggle)
- **Server-Sent Events**: Optional SSE for sub-second push (opt-in via config)
- **Element Timeline**: Vertical chronology of all events for any element
- **User Timeline**: Chronology of all actions by a specific user

### Reporting & Export
- **Streaming Export**: CSV, JSON, and HTML with no artificial cap - background job for large datasets
- **Activity Dashboard**: Summary stats with filterable log table and sparkline chart
- **Dashboard Widget**: 7/14/30-day summary with sparkline + last 5 events
- **Structured Diff View**: Side-by-side field change comparison with red/green highlighting

### External Integrations
- **Log Shipping**: Send logs to Splunk, Datadog, S3, or generic webhooks with batching
- **IP Geolocation**: Opt-in country/region/city resolution via external API with 24h caching
- **Email Alerts**: Configurable notifications for failed login bursts, deletions, and permission changes

### Configurable
- **Selective Logging**: Choose what to track (elements, auth, config, assets, permissions)
- **Retention Policies**: Auto-cleanup via Craft GC - drops entire archive tables for instant cleanup
- **Field Exclusions**: Skip sensitive field handles from change snapshots
- **Rate Limiting**: Configurable per-second and per-token (API) rate limits
- **Permission System**: Granular permissions for viewing, exporting, and managing

## Requirements

- Craft CMS 5.0+
- PHP 8.2+

## Installation

```bash
composer require anvildev/craft-trails
php craft plugin/install trails
```

## Configuration

Visit **Settings → Plugins → Trails** or use the Trails section in the control panel sidebar.

### Settings

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `enabled` | `bool` | `true` | Turn logging on/off globally |
| `retentionDays` | `int` | `0` | How long to keep logs (days, 0 = forever) |
| `scheduledRetention` | `bool` | `false` | Auto-cleanup via Craft's GC mechanism |
| `logElements` | `bool` | `true` | Track element CRUD operations |
| `logAuthentication` | `bool` | `true` | Track logins/logouts |
| `logFailedLogins` | `bool` | `true` | Track failed auth attempts |
| `logConfigChanges` | `bool` | `true` | Track project config changes |
| `logAssets` | `bool` | `true` | Track asset uploads/deletions |
| `logPermissionChanges` | `bool` | `true` | Track user group/permission changes |
| `captureIpAddress` | `bool` | `true` | Store user IP addresses |
| `anonymizeIp` | `bool` | `false` | GDPR-compliant IP masking (/16 IPv4, /48 IPv6) |
| `captureUserAgent` | `bool` | `true` | Store browser user agent strings |
| `captureFieldChanges` | `bool` | `false` | Record before/after field diffs on element saves |
| `excludedFieldHandles` | `array` | `['password', 'apiKey', ...]` | Field handles excluded from change capture |
| `maxSnapshotBytes` | `int` | `1048576` | Max bytes for a single field snapshot (1 MB) |
| `logRateLimit` | `int` | `0` | Max log writes per second (0 = unlimited) |
| `alertsEnabled` | `bool` | `false` | Enable email alerts |
| `alertEmail` | `string` | | Email address for alert notifications |
| `failedLoginThreshold` | `int` | `5` | Failed logins within 1 hour before alerting |
| `alertCooldownMinutes` | `int` | `15` | Minutes between repeat alerts |
| `externalLoggingEnabled` | `bool` | `false` | Enable external log shipping |
| `externalProvider` | `string` | | Provider: `splunk`, `datadog`, `s3`, or `webhook` |
| `externalEndpoint` | `string` | | HTTPS endpoint URL for the provider |
| `externalApiKey` | `string` | | API key / auth token for the provider |
| `externalBatchSize` | `int` | `50` | Logs per batch when shipping |
| `webhookSecret` | `string` | | Shared secret for HMAC-signing webhook payloads. Supports `$ENV_VAR` |
| `enableGeoIp` | `bool` | `false` | Enable IP geolocation via external API |
| `geoIpEndpoint` | `string` | `https://pro.ip-api.com/json/` | Geolocation API endpoint (HTTPS only) |
| `merkleBatchSize` | `int` | `256` | Records per Merkle tree batch (16–4096) |
| `anchorType` | `string\|null` | `null` | External anchoring: `null`, `'s3'`, or `'rfc3161'` |
| `s3Bucket` | `string` | | S3 bucket for Object Lock anchoring |
| `s3Region` | `string` | | S3 region |
| `s3AccessKeyId` | `string` | | S3 access key (supports `$ENV_VAR`) |
| `s3SecretAccessKey` | `string` | | S3 secret key (supports `$ENV_VAR`) |
| `tsaUrl` | `string` | `https://freetsa.org/tsr` | RFC 3161 TSA endpoint |
| `tsaTrustedCaBundle` | `string` | | Path to a PEM CA bundle trusted by `openssl ts -verify`. Required for RFC 3161 verification unless `tsaCaBundlePem` is set |
| `tsaCaBundlePem` | `string` | | Inline PEM for the TSA trust root(s). Used when `tsaTrustedCaBundle` is empty |
| `enableApi` | `bool` | `true` | Enable REST API |
| `enableGraphql` | `bool` | `true` | Enable GraphQL schema |
| `apiRateLimit` | `int` | `60` | API requests per minute per token (0 = unlimited) |
| `inlineExportLimit` | `int` | `10000` | Records below this threshold export inline; above triggers background job |
| `realtime` | `string` | `'poll'` | Real-time mode: `'poll'` (htmx) or `'sse'` (Server-Sent Events) |
| `maxSseConnections` | `int` | `5` | Max concurrent SSE connections (1–50) |

## External Anchoring

Trails can anchor every batched Merkle root to an external system as durable proof. Two backends ship today.

### Why external anchoring matters

The hash chain and Merkle trees inside `trails_logs` already detect tampering, but every check happens against rows the same database admin can rewrite. External anchoring closes that loop: the Merkle root is committed to a system Trails does not control (an S3 bucket with Object Lock, or a public RFC 3161 timestamp authority), and the audit-trail integrity claim becomes verifiable **by anyone outside the plugin** using widely-deployed tooling. The plugin generates the anchor; the auditor uses `aws s3api head-object` or `openssl ts -verify` to re-check independently - Trails never has to be trusted as the verifier.

### S3 Object Lock (COMPLIANCE retention)

Anchors are written as small JSON manifests to an S3 bucket with versioning + Object Lock COMPLIANCE retention. COMPLIANCE retention is cryptographically enforced by AWS - neither the bucket owner nor AWS root can remove or alter a locked object until the retention window expires (Trails defaults to 7 years).

> **Object Lock COMPLIANCE retention cannot be removed.** Anything you anchor sits in the bucket for the full retention window. Use a dedicated bucket - don't co-locate anchors with deletable application data.

#### Prerequisites

- An AWS account you control.
- The AWS CLI v2, configured with credentials for that account: `aws sts get-caller-identity` should return your IAM user / role.
- A working Trails install with the queue worker running.

The IAM principal you use needs at minimum:

```
s3:CreateBucket
s3:PutBucketVersioning
s3:PutBucketObjectLockConfiguration
s3:PutObject
s3:GetObject
s3:GetObjectVersion
s3:HeadObject
s3:GetObjectRetention
s3:GetObjectAttributes
```

If you also want to delete the bucket after the retention window expires (years from now), add `s3:DeleteObject`, `s3:DeleteObjectVersion`, and `s3:DeleteBucket`.

#### 1. Create the bucket (versioning + Object Lock)

Object Lock **must** be enabled at bucket creation - it can't be turned on later. Versioning is implicitly enabled when Object Lock is enabled, but we set it explicitly to be safe.

```bash
BUCKET=trails-anchors-$(date +%s)
REGION=eu-central-1

aws s3api create-bucket \
    --bucket "$BUCKET" \
    --region "$REGION" \
    --create-bucket-configuration "LocationConstraint=$REGION" \
    --object-lock-enabled-for-bucket

aws s3api put-bucket-versioning \
    --bucket "$BUCKET" \
    --versioning-configuration Status=Enabled
```

> **`us-east-1` exception**: the `--create-bucket-configuration` flag is rejected in `us-east-1` (the API treats it as the default region). Drop the flag entirely:
> ```bash
> aws s3api create-bucket --bucket "$BUCKET" --object-lock-enabled-for-bucket
> ```

Confirm the configuration:

```bash
aws s3api get-bucket-versioning --bucket "$BUCKET"
# → {"Status": "Enabled"}
aws s3api get-object-lock-configuration --bucket "$BUCKET"
# → {"ObjectLockConfiguration": {"ObjectLockEnabled": "Enabled"}}
```

Both must report `Enabled`. If either is missing, **delete the bucket and start over** - Trails' anchor write requires both, and a misconfigured bucket will surface as a runtime `S3 PutObject did not return ETag/VersionId - bucket likely does not have versioning enabled` error.

#### 2. Configure Trails

Configure the four S3 settings either through the CP settings page (Settings → Trails → Anchoring) or via `config/trails.php`. The config-file form is preferred for ops automation:

```php
<?php
// config/trails.php
return [
    'anchorType' => 's3',
    's3Bucket' => 'trails-anchors-1714291200',  // your $BUCKET from step 1
    's3Region' => 'eu-central-1',
    's3AccessKeyId' => '$AWS_ACCESS_KEY_ID',     // env-var resolution works
    's3SecretAccessKey' => '$AWS_SECRET_ACCESS_KEY',
];
```

The `$AWS_ACCESS_KEY_ID` syntax uses Craft's `App::parseEnv()` - anything that resolves there is fine.

> **IAM-role deployments**: when running on EC2, ECS, or EKS with an instance role / task role attached, **leave `s3AccessKeyId` and `s3SecretAccessKey` empty**. The AWS SDK's default credential provider chain will pick up the role automatically and you avoid baking long-lived keys into your config.

After saving the config, restart any long-running PHP workers (queue, FPM) so the new settings take effect.

#### 3. Trigger an anchor

Trails anchors a Merkle root every `merkleBatchSize` audit-log writes. The full chain is `audit log write → ComputeMerkleRootJob → MerkleRootRecord → AnchorMerkleRootJob → AnchorService → S3AnchorBackend → S3 PutObject → AnchorRecord`.

1. Generate audit traffic (any CP click counts: log in, edit an entry, change a setting), or run `php craft trails/integrity/backfill` to enqueue a `BackfillChainJob`.
2. Drain the queue:
   ```bash
   php craft queue/run
   ```
   Watch for `Anchoring Merkle root #N` job descriptions - that's `AnchorMerkleRootJob` doing its work.
3. Confirm the anchor row landed:
   ```bash
   php craft trails/integrity/verify --verbose
   ```
   The `Verifying anchors...` block should report `Anchors: N verified, 0 failed`.

The anchor row in `trails_anchors` will have `anchorType=s3`, an `anchorRef` of the form `s3://<bucket>/trails/merkle/YYYY/MM/DD/<hash-prefix>.json`, and an `anchorProof` containing JSON like `{"eTag":"…","versionId":"…","retainUntil":"…"}`.

#### 4. Independent auditor verification

This is what S3 anchoring buys you: an auditor can re-verify the anchor end-to-end without touching the plugin's code. Pull the JSON `anchorProof` from the DB row, then:

```bash
ETAG="<value-of-anchorProof.eTag>"
VERSION="<value-of-anchorProof.versionId>"
KEY="trails/merkle/2026/04/27/<hash-prefix>.json"

# 1. Head the object - confirms it exists, is locked, and ETag matches.
aws s3api head-object \
    --bucket "$BUCKET" \
    --key   "$KEY" \
    --version-id "$VERSION"
```

In the response, look for:

- `"ObjectLockMode": "COMPLIANCE"` - proves the object is immutable.
- `"ObjectLockRetainUntilDate": "2033-04-27T…"` - must be ≥ the `retainUntil` field in `anchorProof`.
- `"ETag": "\"<value>\""` - must match the `eTag` field in `anchorProof` after stripping the surrounding quotes (Trails strips them automatically; the `aws s3api` output keeps them).

Then fetch the manifest body and confirm `rootHash` matches the corresponding `MerkleRootRecord.rootHash` in the DB:

```bash
aws s3api get-object \
    --bucket "$BUCKET" \
    --key   "$KEY" \
    --version-id "$VERSION" \
    /tmp/manifest.json

cat /tmp/manifest.json
# {
#   "version": "1.0",
#   "rootHash": "<sha256>",
#   "batchStartPosition": 1,
#   "batchEndPosition": 1000,
#   "recordCount": 1000,
#   "dateComputed": "2026-04-27 12:34:56",
#   "anchoredAt": "2026-04-27T12:35:00+00:00"
# }
```

If both checks pass, the anchor is provably (a) the same object Trails wrote, (b) locked from modification by COMPLIANCE retention, and (c) carrying a manifest whose `rootHash` matches the on-record root. That's the full audit chain.

#### Cost estimate

For a production deployment doing the default `merkleBatchSize=256`:

- **Per anchor PUT:** ~$0.000005 (one S3 PUT, charged per request).
- **Per anchor stored, per month:** ~$0.0000005 (~200 B per manifest × $0.023/GB-month).
- **Verification (HEAD + GET):** ~$0.0000004 + ~$0.0000004 (both negligible).

At one anchor per hour (8760/year), the annual total is about **$0.05 in PUTs and $0.05 in storage**. Storage at the 7-year compliance retention reaches ~12 MB - still pennies per year. The constraint on S3 anchoring is the IAM permissions and the inability to delete locked objects, not cost.

### RFC 3161 Timestamp Authority

RFC 3161 anchoring submits each Merkle root to a public (or commercial) timestamp authority and stores the signed timestamp response (`.tsr`) as the proof. Verification re-runs `openssl ts -verify` against the TSA's CA chain - the timestamp's authenticity rests on the TSA's signature, not on transport security.

#### Prerequisites

- The `openssl` binary on `PATH` inside the PHP process. `which openssl` must succeed.
- A chosen TSA. Three tiers:
  - **Free / public**: [FreeTSA](https://freetsa.org/) - no rate-limit guarantee; fine for low-volume installs and smoke testing.
  - **Commercial**: DigiCert, GlobalSign, Sectigo. Predictable SLAs, paid per request or via subscription. Required for high-volume installs.
  - **Internal CA**: enterprises running their own RFC 3161 server. Use this when audit policy requires the TSA to be in your trust boundary.
- The TSA's CA bundle in PEM form. FreeTSA publishes one at <https://freetsa.org/files/cacert.pem>; commercial TSAs publish theirs on their website. For an internal CA, export the chain from your PKI.

#### 1. Configure Trails

Set `anchorType` to `rfc3161` and provide both the TSA URL and the CA bundle. The bundle can be supplied as either a filesystem path or inline PEM - if both are set, the path takes precedence.

```php
<?php
// config/trails.php
return [
    'anchorType' => 'rfc3161',
    'tsaUrl' => 'https://freetsa.org/tsr',

    // Either: path to a PEM CA bundle the PHP process can read.
    'tsaTrustedCaBundle' => '/etc/trails/tsa-ca-bundle.pem',

    // OR: inline PEM (used only if tsaTrustedCaBundle is empty).
    // 'tsaCaBundlePem' => "-----BEGIN CERTIFICATE-----\nMIID…\n-----END CERTIFICATE-----\n",
];
```

These can also be set via the CP at Settings → Trails → Security → Anchoring. Restart long-running PHP workers after changing settings.

#### 2. Trigger an anchor

The flow is identical to S3: audit-log writes accumulate into Merkle batches, `AnchorMerkleRootJob` posts the root hash to the configured TSA, and the response is stored as a row in `trails_anchors` with `anchorType=rfc3161`. Drain the queue with `php craft queue/run`, then confirm:

```bash
php craft trails/integrity/verify --verbose
```

The anchor row's `anchorRef` contains the TSA URL and the `anchorProof` is the base64-encoded TSR (timestamp response) that the TSA signed.

#### 3. Independent auditor verification

The auditor needs three things: the Merkle root hash being attested, the TSR file, and the TSA's CA bundle. Trails' verification path is exactly this command, and an external auditor can run the same:

```bash
# Decode the stored TSR proof to a binary file.
echo "$ANCHOR_PROOF_B64" | base64 -d > /tmp/anchor.tsr

# Verify against the Merkle root hash and the TSA CA bundle.
openssl ts -verify \
    -digest "<merkle-root-hash-hex>" \
    -in     /tmp/anchor.tsr \
    -CAfile /etc/trails/tsa-ca-bundle.pem
# → Verification: OK
```

`Verification: OK` proves the TSA signed exactly that root hash at the time embedded in the TSR. Any tamper with the row - flipping a byte in the root, swapping the TSR for a different one - changes the result to `Verification: FAILED`.

> Trails 1.0.0 ships with real CMS/PKCS#7 verification via `openssl ts -verify`. The substring-match-on-output verifier from earlier development builds is gone; operators on 1.0.0 have correct verification by default and don't need to migrate anything.

#### Limitations

- **Public TSAs rate-limit aggressively.** FreeTSA is the canonical example. If your install batches more than a handful of anchors per minute, pick a commercial TSA.
- **The TSA must remain trusted.** If the TSA's signing certificate is later revoked or the TSA itself is compromised, every anchor stamped against it loses its proof. Mitigation: enterprise installs should anchor to two independent TSAs, or pair RFC 3161 with S3.
- **No retention guarantee.** Unlike S3 Object Lock, the TSA doesn't store the anchor - Trails does, in `trails_anchors.anchorProof`. Back up that table.

## Usage

### Control Panel

Access the audit trail via the **Trails** nav item:
- **Activity Logs**: htmx-driven log viewer with live polling, filters update without page reload
- **Log Detail**: Full details with structured diff view, chain position navigation (prev/next), integrity badge
- **Export**: Streaming CSV/JSON/HTML - inline for small exports, background job for large datasets
- **Integrity**: Verify chain + Merkle trees + anchors, generate Certificate of Integrity (JSON/PDF)
- **Timeline**: Element history and user activity timelines (vertical chronology)
- **Settings**: 3 tabs - General, Security & Compliance, Integrations

### Template Variable

```twig
{# Get recent logs #}
{% set logs = craft.trails.logs({limit: 10}) %}

{# Get activity for a specific element #}
{% set history = craft.trails.elementHistory(entry.id) %}

{# Get user activity #}
{% set userLogs = craft.trails.userActivity(currentUser.id) %}

{# Get summary stats #}
{% set summary = craft.trails.summary(7) %}
{{ summary.totalEvents }} events in the last 7 days
```

## Custom Events

Third-party plugins and custom code can log events through Trails:

```php
use anvildev\trails\Trails;

Trails::getInstance()->audit->logCustomEvent(
    eventType: 'myplugin.order_shipped',
    category: 'fulfillment',
    description: 'Order #1234 shipped via UPS',
    metadata: ['orderId' => 1234, 'carrier' => 'UPS', 'tracking' => '...'],
    elementId: 1234,
    elementType: 'myplugin\\elements\\Order',
);
```

Event types must follow `namespace.action` format (e.g., `myplugin.user_synced`). The namespace becomes the event category in the audit log, making it easy to filter custom events by plugin in the CP or exports.

**Reserved prefixes:** The following prefixes are reserved for system events and will throw `InvalidArgumentException` if used with `logCustomEvent()`: `element.`, `user.`, `asset.`, `config.`, `audit.`, `trails.`, `auth.`.

**Metadata size limit:** The `metadata` array is capped at 64 KB when JSON-encoded. Larger payloads throw `InvalidArgumentException`.

You can also validate an event type string without logging:

```php
use anvildev\trails\services\AuditService;

try {
    AuditService::assertValidEventType('myplugin.user_synced');
} catch (\InvalidArgumentException $e) {
    // handle invalid format
}
```

## Event Bridge

The Event Bridge lets any plugin register Yii events that Trails should automatically log - no need to call `logCustomEvent()` manually.

### Registering events from your plugin

In your plugin's `init()` method:

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

The handler receives the raw Yii event and returns an array with any of:
- `description` - human-readable description
- `elementId` - related element ID
- `elementType` - element class name
- `elementTitle` - element title for display
- `metadata` - arbitrary key-value data

If the handler is `null`, a generic log entry is created with just the event type.

Event types must use `namespace.action` format. Reserved prefixes (`element.`, `user.`, `auth.`, `trails.`, etc.) are rejected.

### Built-in integrations

Trails ships with automatic event logging for:

- **Craft Commerce** - order completion, payment processing, status changes
- **Booked** - reservation creation, cancellation

These activate automatically when the respective plugin is installed. No configuration needed.

> **Note:** Commerce and Booked event names are based on their respective public APIs. If an event constant does not exist in the installed version, it simply never fires - no errors occur.

## Permissions

Assign granular permissions per user group in **Settings > Users > User Groups**.

| Permission | What it gates |
|---|---|
| `trails-viewLogs` | View the Activity Logs page and log detail view. PII (emails, IPs, field diffs) is **restricted** unless the user also has `trails-manageSettings` or is an admin |
| `trails-exportLogs` | Download log exports (CSV, JSON, HTML) |
| `trails-manageSettings` | Configure all plugin settings. Also unlocks PII visibility in log detail |

Admins bypass all permission checks.

## Console Commands

```bash
# Integrity verification
php craft trails/integrity/verify                              # Full: hashes + chain + Merkle + anchors
php craft trails/integrity/verify --record=12345               # Single record with Merkle proof
php craft trails/integrity/verify --range=2026-01-01:2026-03-31  # Date range
php craft trails/integrity/verify --verbose                    # Per-batch progress output

# Certificate of Integrity
php craft trails/integrity/certificate --from=2026-01-01 --to=2026-03-31 --format=json
php craft trails/integrity/certificate --from=2026-01-01 --to=2026-03-31 --format=pdf
php craft trails/integrity/certificate --from=2026-01-01 --to=2026-03-31 --format=json,pdf

# Chain backfill (retroactive chaining for pre-migration records)
php craft trails/integrity/backfill

# Retention management
php craft trails/retention/stats                  # Show log counts + oldest/newest dates
php craft trails/retention/cleanup                # Delete expired logs (prompts for confirmation)
php craft trails/retention/cleanup --force        # Skip confirmation
php craft trails/retention/purge                  # Delete ALL logs (double confirmation required)

# API integration tokens
php craft trails/api/issue-token --name=SIEM --scopes=trails:read,trails:write --days=90
```

### REST API authentication modes

- **Craft user session:** Logged-in CP users with `trails-viewLogs` can call API endpoints.
- **Bearer tokens (integrations):** Issue integration tokens via `trails/api/issue-token` and send `Authorization: Bearer <token>`.
- **Scopes:** `trails:read` for read endpoints, `trails:write` for certificate/export actions.
- **Rate limit:** Uses `apiRateLimit` setting (per minute per user/token, `0` disables rate limit).
- **Kill switch:** `enableApi=false` disables all Trails API endpoints.

## IP Geolocation

Trails can optionally resolve IP addresses to country, region, and city using a free external API. This is **opt-in** and disabled by default.

Enable it in **Settings → Data Capture → IP Geolocation**. Once enabled, a background queue job (`ResolveGeoIpJob`) is dispatched after each log write. The job calls the configured endpoint and stores the result back on the log record. Private/reserved IPs and anonymized IPs (last octet `.0`) are never sent to the external API.

### Default endpoint

`http://ip-api.com/json/` - free, no API key required, rate-limited to ~45 requests/minute per IP. For higher volume sites, configure an alternative endpoint (e.g. a self-hosted MaxMind service or ipinfo.io) via the **Geolocation endpoint** field.

### Alternative endpoint

Any endpoint that accepts `GET /{ip}` and returns a JSON object in the ip-api.com format (`status`, `countryCode`, `regionName`, `city`) is compatible. Update the endpoint field:

```
https://my-geoip-proxy.example.com/json/
```

### Geo data in logs

Resolved country (2-letter ISO code), region, and city appear in the **User Information** section of each log detail view. The data is stored in three new columns (`country`, `region`, `city`) added to the `trails_logs` table by the migration `m260405_000000_AddLogGeoColumns`.

## Webhook Signature Verification

When a **Webhook Secret** is configured in **Settings → External Log Shipping**, Trails signs every outgoing webhook payload with HMAC-SHA256. Two headers are added to each request:

| Header | Value |
|--------|-------|
| `X-Trails-Signature` | `sha256=<hex-digest>` |
| `X-Trails-Timestamp` | Unix timestamp (seconds) at send time |

The signature is computed over the string `{timestamp}.{body}` - the dot-joined concatenation of the timestamp and the raw JSON body. Tying the timestamp to the signature prevents replay attacks.

### PHP verification example

```php
$timestamp = $_SERVER['HTTP_X_TRAILS_TIMESTAMP'] ?? '';
$signature = $_SERVER['HTTP_X_TRAILS_SIGNATURE'] ?? '';
$body      = file_get_contents('php://input');
$secret    = getenv('TRAILS_WEBHOOK_SECRET');

$expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
if (!hash_equals($expected, $signature)) {
    http_response_code(401); exit;
}

// Reject stale timestamps (5 min window)
if (abs(time() - (int) $timestamp) > 300) {
    http_response_code(401); exit;
}
```

The `webhookSecret` setting supports `$ENV_VAR` syntax so the secret can be stored outside `project.yaml`.

## Troubleshooting

### Plugin enabled but no logs appearing

**Symptom.** `enabled = true` in settings, CP activity is happening, but `trails_logs` stays empty (or grows much slower than expected).

**Diagnosis.** Check the capture flags and exclusions:

```php
// config/trails.php - confirm the relevant flags are true
'logElements'         => true,
'logAuthentication'   => true,
'logAssets'           => true,
'logConfigChanges'    => true,
'logPermissionChanges'=> true,

// And confirm exclusions aren't shadowing what you expect.
'excludedElementTypes' => [...],
'excludedSections'     => [...],
'excludedFieldHandles' => [...],
```

Then check the rate-limit setting - `logRateLimit` silently drops events beyond N per second:

```bash
php craft trails/retention/stats   # are counts increasing at all?
php craft queue/info               # are jobs stuck?
```

**Fix.** Enable the right capture flags. Confirm `logRateLimit` isn't `0` if you set it as a cap (note: the default `0` means *unlimited*, but a misconfigured low value silently throttles). If the queue is the bottleneck, drain it with `php craft queue/run` and address the underlying worker health.

### Queue jobs piling up

**Symptom.** `php craft queue/info` reports a growing backlog of `ShipLogJob` or `BatchShipLogJob`.

**Diagnosis.**

```bash
php craft queue/info
# Look for: total jobs, failed jobs, jobs older than N seconds.
```

The most common cause is an unreachable external shipping endpoint: `ShipLogJob` retries on failure with exponential backoff and pile up while the endpoint is down. `BatchShipLogJob` follows the same retry pattern at the batch level.

**Fix.** Verify the configured `externalEndpoint` is reachable from the worker host (`curl -I <endpoint>`). If the endpoint is temporarily unavailable, set `externalLoggingEnabled = false` until it's restored - the audit log itself is unaffected; only shipping is paused.

### Hash chain reports broken links after a user deletion

**Symptom.** `php craft trails/integrity/verify` reports broken chain links at row positions corresponding to a recently deleted user.

**Diagnosis.**

```sql
SELECT id, dateLogged, event, userId
FROM trails_logs
WHERE event = 'user.gdpr.anonymized'
ORDER BY dateLogged DESC;
```

If the broken positions correlate with `user.gdpr.anonymized` events, this is expected behaviour, not tampering.

**Fix.** This is documented in `DEVELOPER_GUIDE.md → GDPR & Hash Chain Integrity`. The local hash chain is intentionally invalidated when PII is rewritten on Article 17 erasure; the integrity guarantee is preserved by the external Merkle anchors which were computed before anonymization. Direct auditors to the developer guide section for the full trade-off explanation.

### Anchor verification failing for newly-created anchors (RFC 3161)

**Symptom.** New `trails_anchors` rows with `anchorType=rfc3161` report `verified=0` and `php craft trails/integrity/verify` flags them.

**Diagnosis.**

```bash
which openssl                                              # must resolve
stat /etc/trails/tsa-ca-bundle.pem                         # path readable by the PHP process
openssl x509 -in /etc/trails/tsa-ca-bundle.pem -noout -dates
# notBefore=… notAfter=… - must bracket the anchor's dateAnchored
```

Then check `storage/logs/web-YYYY-MM-DD.log` for Trails warnings of the form `RFC 3161 verification: no CA bundle configured` or `openssl ts -verify failed`.

**Fix.** The most common cause is a missing or unreachable CA bundle - `verify()` returns `false` and logs a warning if both `tsaTrustedCaBundle` and `tsaCaBundlePem` are empty, or if the configured path isn't readable by the PHP process (filesystem permissions, container mount missing, etc.). Set the bundle correctly and re-run verification. If the TSA's CA chain has expired, fetch a current bundle from the TSA's website.

### Anchor verification failing for newly-created anchors (S3)

**Symptom.** New `trails_anchors` rows with `anchorType=s3` report `verified=0`.

**Diagnosis.**

```bash
# 1. AWS credentials match the configured access key.
aws sts get-caller-identity
#    Compare the returned UserId/Arn to s3AccessKeyId in config.

# 2. Bucket has versioning enabled.
aws s3api get-bucket-versioning --bucket <name>
# → {"Status": "Enabled"}     # must be Enabled

# 3. Object Lock retention is COMPLIANCE on the anchor object.
aws s3api get-object-retention \
    --bucket <name> \
    --key    <key> \
    --version-id <vid>
# → {"Retention": {"Mode": "COMPLIANCE", "RetainUntilDate": "..."}}
```

**Fix.** `verify()` returns `false` silently if any check fails - check `storage/logs/web-YYYY-MM-DD.log` for the specific Trails warning that pinpoints which check (ETag mismatch, retention downgrade, missing version, AccessDenied). ETag or retention mismatches on a COMPLIANCE-locked object indicate an audit-trail violation worth investigating, not a config issue.

### `php craft trails/integrity/verify` flags many anchors as legacy

**Symptom.** Verification output reports a large number of anchors as legacy / unverifiable.

**Diagnosis.**

```sql
SELECT COUNT(*) FROM trails_anchors
WHERE LENGTH(anchorProof) = 64
  AND anchorProof REGEXP '^[0-9a-f]{64}$';
```

A non-zero count is the legacy hex-HMAC proof format from pre-1.0.0 development builds. These rows can't be third-party-verified because the proof is a local HMAC, not an external attestation.

**Fix.** Re-anchor: delete the row from `trails_anchors` and push a fresh `AnchorMerkleRootJob` for the corresponding `merkleRootId`. There is no built-in batch CLI for this in 1.0.0 - a `--re-anchor` console action is on the roadmap. For now, re-anchor manually:

```sql
DELETE FROM trails_anchors WHERE id = <legacy-row-id>;
-- then push a new AnchorMerkleRootJob via Craft\queue::push() for merkleRootId
```

## What Makes This Enterprise-Grade

| Capability | What it does | Why it matters |
|-----------|-------------|----------------|
| **Hash chain** | Every record links to the previous via prevHash | Proves no records were inserted, deleted, or reordered |
| **Merkle trees** | Batched cryptographic roots over groups of records | Efficient single-record verification without scanning all records |
| **External anchoring** | Roots stored in S3 Object Lock or RFC 3161 TSA | Even a DB admin can't rewrite history without the anchor disagreeing |
| **Certificate of Integrity** | Signed JSON + PDF export for auditors | Independent verification without access to the Craft site |
| **Table partitioning** | Monthly archive tables with cross-table queries | Scales to millions of records without query degradation |
| **Streaming export** | Background job with progress tracking | Export datasets of any size without browser timeouts |
| **REST API + GraphQL** | Programmatic access with rate limiting | Headless Craft sites, SIEM integration, custom dashboards |

## Developer Setup

This section is for contributors and CI — production users don't need any of it.

### Running tests

```bash
cd plugins/trails
composer test     # PHPUnit (342 tests)
composer check    # ECS + PHPStan
```

### Smoke testing the integrity surface

`bin/smoke-test-integrity.sh` runs a live end-to-end sweep across the integrity verify panel, certificate generation, the ChainLinkValidator matrix, S3 + RFC3161 anchor flows, mixed-anchor verification, and chain backfill. Designed to run in under a minute against a DDEV stack.

```bash
bin/smoke-test-integrity.sh                 # all tests
bin/smoke-test-integrity.sh --only=51       # one test
bin/smoke-test-integrity.sh --skip=32       # all except S3
bin/smoke-test-integrity.sh --keep-snapshots
```

The runner takes a DDEV DB snapshot before any destructive case and restores on exit. Evidence saved under `.smoke-test-evidence/<timestamp>/`. Reads `CRAFT_SECURITY_KEY` from `.env`. Exit code = number of failed assertions (0 = green).

The S3 test (test 32) auto-skips when no S3-compatible endpoint is reachable, so the runner is safe to use without MinIO.

### Optional: MinIO for local S3 anchor testing

To exercise the S3 anchor backend without an AWS account, install the official DDEV MinIO add-on:

```bash
ddev get https://github.com/ddev/ddev-minio/archive/refs/tags/v2.0.3.tar.gz
```

Then add a `.ddev/docker-compose.minio_snmd_override.yaml` so MinIO runs in single-node multi-drive mode (single-drive filesystem mode does **not** support Object Lock, which the anchor flow requires):

```yaml
services:
  minio:
    environment:
      MINIO_VOLUMES: "/data/disk1 /data/disk2 /data/disk3 /data/disk4"
    command: >
      server --console-address :9090 --address :10101
      /data/disk1 /data/disk2 /data/disk3 /data/disk4
```

Restart, then create a bucket with Object Lock enabled:

```bash
ddev restart
ddev exec --raw -s minio bash -lc "mc mb --with-lock minio/trails-anchors"
ddev exec --raw -s minio bash -lc "mc retention set --default compliance 7y minio/trails-anchors"
```

Configure trails to use it (in `config/trails.php`):

```php
return [
    'anchorType' => 's3',
    's3Bucket' => 'trails-anchors',
    's3Region' => 'us-east-1',
    's3AccessKeyId' => 'ddevminio',
    's3SecretAccessKey' => 'ddevminio',
    's3Endpoint' => 'http://minio:10101',
    's3UsePathStyle' => true,
];
```

MinIO console: `https://<your-ddev-url>:9090/login` (login `ddevminio` / `ddevminio`). Useful for inspecting anchor manifests, verifying COMPLIANCE retention, and debugging anchor flow issues.

Full setup steps and negative paths are documented in the smoke test plan at `docs/smoke-tests/plugins/trails/tests/32-anchor-s3.md` (in the parent project), including how to switch between MinIO and real AWS S3.

### Test fixtures

- `tests/fixtures/verify-cert.py` — independent HMAC verifier for compliance certificates. Useful for any external auditor wanting to verify a cert without running the plugin.
- `tests/fixtures/anchor-current-batch.php` — bootstrap script that seeds events, computes a Merkle batch, and anchors via the configured backend. Used by the smoke runner and the anchor test plans.

## License

Proprietary - see LICENSE.md

## Support

- Website: [anvildev.xyz](https://anvildev.xyz)
