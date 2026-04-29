# Trails — Cryptographic Audit Trail for Craft CMS

*Marketing description for the Craft Plugin Store. Copy any section into the listing.*

---

## Tagline

**Audit logs you can prove. Tamper-evident, auditor-verifiable, and built for the moments when "trust me" isn't enough.**

---

## Short summary (≤ 280 chars for the store header)

Tamper-evident audit logging for Craft CMS. Every record is hash-chained, batched into Merkle trees, and anchored to S3 Object Lock or RFC 3161 timestamps. Generate signed compliance certificates that auditors can verify independently — no plugin access required.

---

## Long description

Most "audit log" plugins give you a table of changes. Trails gives you a **forensically defensible record**.

Every event is cryptographically linked to the one before it. Insertion, deletion, or in-place edits break the chain — and Trails will tell you exactly which row, when, and how. Periodic Merkle roots are anchored to immutable external storage (Amazon S3 Object Lock with COMPLIANCE retention, or any RFC 3161 Timestamp Authority), giving you third-party-verifiable proof that your log existed in its current form at a specific moment in time.

Anchoring costs almost nothing: Trails writes one small JSON manifest per Merkle batch (default every 256 events). At typical CMS volumes that's pennies per month on AWS S3, and free with a public TSA like FreeTSA.

When auditors come asking, generate a signed Certificate of Integrity covering any date range. Hand them the certificate, the security key, and a 60-line verifier script. They can confirm authenticity without ever touching your CMS — the verifier is a single self-contained Python file that runs on any laptop, can be hosted wherever your auditors prefer (including entirely outside your infrastructure), and uses only standard library cryptography.

---

## What it does

### Tamper-evident audit trail

- **Hash chain** — every record links to the previous via cryptographic hash. Any change invalidates the chain.
- **Merkle trees** — batched root computation (default every 256 records). Membership of any row is provable via an inclusion proof.
- **External anchoring** — Merkle roots written to S3 with Object Lock (COMPLIANCE mode, 7-year default retention) or timestamped via RFC 3161 TSA. Anchors are independently verifiable with `aws s3api head-object` or `openssl ts -verify` — Trails uses the same public primitives. **Cheap to run**: one S3 PutObject per Merkle batch (default every 256 events) — typically pennies per month. RFC 3161 anchoring via FreeTSA is free.
- **Signed Certificate of Integrity** — exportable JSON or PDF with HMAC-SHA256 signature. Includes hash totals, chain status, Merkle roots, anchor references, and an overall pass/fail status.
- **Independent verifier, fully open** — bundled 60-line Python script verifies a certificate's signature using only the standard library. Self-contained: your auditors can host it on their own machine or infrastructure, no Craft, no plugin, no internet access needed.
- **Chain-link validation** — distinguishes genesis rows, valid links, gaps, mismatches, and missing predecessors. Surfaces real problems instead of hiding them.

### Comprehensive event capture

- **Element events** — entries, assets, users, categories, tags, globals: create, update, delete, restore. All element types covered.
- **Authentication** — logins, logouts, failed attempts, with brute-force threshold detection.
- **System events** — plugin install/uninstall, permission changes, project config applies.
- **Field-level snapshots** — before/after diffs with structured side-by-side comparison, not raw JSON dumps.
- **Custom events** — first-class API for third-party plugins to log their own events with reserved-prefix protection.
- **Request context** — IP address, user agent, request URL, session ID — captured per event.

### Privacy & compliance

- **GDPR anonymization** — one-command anonymization across active and archived tables when a user is deleted or requests erasure.
- **IP anonymization** — automatic /16 (IPv4) and /48 (IPv6) truncation, per-site toggle.
- **Email-at-rest encryption** — sensitive fields stored with Craft's secure encryption.
- **Excluded-field allowlist** — skip sensitive field handles from change snapshots (passwords, tokens, secrets).
- **PII gating** — sensitive columns visible only to admin / settings-manager users.
- **Configurable retention** — auto-purge at the table level (drops entire monthly archives instantly — no slow row-by-row delete).
- **CSV injection protection** — exported fields sanitized against formula injection attacks.

### Real-time Control Panel

- **Logs index** — filter by event type, element, user, IP, date range. Live-updating with htmx (no page reload).
- **Element timeline** — chronological history of every action against a specific element.
- **User timeline** — chronological history of every action by a specific user.
- **Integrity panel** — single-click full verification (hashes, chain, Merkle roots, anchors). Status badge tells the truth: ALL CLEAR or ISSUES FOUND.
- **Live polling** — 5-second auto-refresh on the logs index (opt-in toggle).
- **Server-Sent Events** — optional sub-second push for high-volume monitoring (opt-in via config).
- **Dashboard widget** — 7/14/30-day summary with sparkline trend and last 5 events.
- **Activity dashboard** — top events, top users, top IPs, with filterable detail.

### Integrations & shipping

- **External log shipping** — Splunk HEC, Datadog, S3, or generic webhooks with HMAC-signed payloads. Batching configurable per provider.
- **GeoIP enrichment** — opt-in country / region / city resolution via your chosen API, 24-hour cache.
- **Email alerts** — failed-login bursts, mass deletions, permission changes. Cooldown-throttled.

### Developer API

- **REST API** — 8 endpoints at `/actions/trails/api/v1/` with dual auth (CP session + permissions, or bearer token + scope), rate-limited per token.
- **GraphQL** — `trailsLogs`, `trailsLog`, `trailsSummary`, `trailsIntegrity` queries integrated into Craft's GraphQL schema.
- **Fluent query builder** — `Trails::getInstance()->audit->query()->event('element.*')->fromIp('192.168.0.0/16')->all()`.
- **Typed DTOs** — `AuditLogEntry`, `CursorResult`, `MerkleProof`, `IntegrityReport` for predictable shapes.
- **Console commands** — `trails/integrity/verify`, `trails/integrity/certificate`, `trails/integrity/backfill`, `trails/integrity/rehash-v3`.
- **Multi-environment config** — full `config/trails.php` overrides, environment-variable interpolation.

### Reporting & export

- **Streaming export** — CSV, JSON, HTML, PDF formats. No row-cap; large datasets queue automatically as background jobs.
- **PDF Certificate of Integrity** — branded, auditor-ready document covering any date range.
- **Structured diff view** — side-by-side field change comparison with red/green highlighting.
- **Filterable log table** — every column searchable; cursor-based pagination stays fast at any depth.

### Operations & scale

- **Monthly table partitioning** — active log table rotates to monthly archives automatically. Cross-table queries are transparent.
- **Cursor-based pagination** — no offset degradation; page 10 is as fast as page 10,000.
- **Chain backfill** — recover legacy rows missing `prevHash` via a queueable background job.
- **Hash version migration** — `rehash-v3` rebuilds older v1/v2 hash format records to current standards (with anchor-safety guards).
- **Granular permissions** — separate permissions for viewing logs, exporting, viewing integrity status, managing settings.
- **Rate limiting** — configurable per-second and per-token API limits.
- **API kill-switch** — disable the entire REST/GraphQL surface with a single setting.

---

## Who it's for

Trails is for Craft sites where the audit log is a **legal, regulatory, or contractual artifact** — not a debugging tool.

- **Agencies serving regulated-adjacent industries** (legal, financial advisory, healthcare-adjacent) where "who edited this, when, and can we prove it" can become a dispute.
- **Government contractors and public-sector teams** with audit-retention requirements.
- **GDPR-conscious EU teams** that need first-class anonymization, retention, and deletion tooling.
- **Editorial and content teams** with attribution disputes or controlled-publication workflows.
- **Universities, nonprofits, grant-funded projects** with accountability requirements.
- **Any team** where "we deleted something by accident and need to prove who" has happened, or might.

If your audit log just needs to show recent changes for debugging, look at simpler free options. Trails is the right call when an external party — auditor, regulator, lawyer, client — might need to verify the log's authenticity.

---

## Requirements

- Craft CMS 5.0 or newer
- PHP 8.2 or newer
- MySQL 8.0+ or PostgreSQL 13+
- A queue runner (the standard Craft queue is fine)

For external anchoring (optional but recommended for production):
- An AWS S3 bucket with Object Lock enabled and versioning, **OR**
- Access to any RFC 3161 Timestamp Authority (FreeTSA works out of the box for free; commercial TSAs are configurable)

---

## License

Trails ships as a single Pro tier at **$199 per Craft installation**, including all features above and one year of updates and support.
