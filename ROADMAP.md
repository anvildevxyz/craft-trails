# Trails Roadmap

Items deferred from 1.0.0 launch, grouped by priority. This is a living document — file an issue for items not yet listed and we'll triage them in.

## 1.0.x — Launch follow-ups (next 4–8 weeks)

### Documentation (high-value, low-cost)

- **Threat model.** One-page document. What Trails defends against (insider tamper at the row level, hash collision attempts, unauthorized PII access via permissions) and what it does NOT defend against (TSA compromise, AWS region wipe, time-of-check-to-time-of-use vulnerabilities at the audit-call boundary, full-server compromise pre-anchor). Critical for procurement reviews from regulated customers.
- **Compliance control mapping.** SOC 2 / ISO 27001 / HIPAA / GDPR. One table per framework: control reference → "Trails contributes by …". Even a starting draft unblocks vendor-due-diligence questionnaires.
- **Architecture / data-flow diagram.** Single image (mermaid is fine) showing: Craft action → `AuditService.log()` → `AuditLogRecord` → `TableRotationService` → `MerkleService` → `AnchorMerkleRootJob` → `AnchorService` → S3/RFC 3161 → `AnchorRecord` → `CertificateService`.
- **CP screenshots / short demo video.** Marketing + onboarding asset. Cover: Activity Logs page, Log detail, Integrity page, Settings → Security/Anchoring.
- **Performance / sizing guide.** Cost of `captureFieldChanges = true`, recommended `merkleBatchSize` per event-volume tier (low / medium / high), expected disk growth at 10K/100K/1M events/day, rotation cadence guidance.
- **Schema reference.** Human-readable table reference (one section per `trails_*` table, columns + purpose + indexes). Today the source of truth is `src/migrations/Install.php` which is hard for a customer to read.

### Code (small, surfaced during 1.0.0 review)

- **HTTP TSA support.** Widen the `tsaUrl` validator from HTTPS-only to allow HTTP. RFC 3161 doesn't require transport security (the proof's authenticity is in the signed TSR), and several enterprise/regional TSAs are HTTP-only. One-line validator change.
- **`Aws\MockHandler`-based unit tests for `S3AnchorBackend::anchor()` / `verify()`.** The instance methods (~100 lines of business logic — ETag round-trip, retention validation, COMPLIANCE check, retention-downgrade detection) are currently only exercised against real S3. The AWS SDK ships `MockHandler` precisely for this; close the asymmetry with RFC 3161 (which has fixture-based + gated live coverage).
- **Custom S3 endpoint setting.** New `s3Endpoint` setting + `use_path_style_endpoint` plumbing in `S3AnchorBackend::client()`. Unblocks Cloudflare R2, MinIO, Wasabi, AWS GovCloud — all of which support Object Lock. Concrete enterprise compliance ask.
- **`--re-anchor=s3` CLI command.** `IntegrityController::actionAnchor` doesn't exist today; operators with legacy hex-HMAC anchorProof rows have to manually delete and re-queue. Add a console action that bulk-re-anchors any rows matching a type predicate.
- **`needsReanchor` state on `AnchorRecord`.** Currently the integrity report can't visually distinguish "legacy, needs re-anchor" from "tampered". Add a third state so the CP integrity page can guide the operator instead of alarming them.
- **Plugin install/uninstall listener.** `SystemEventListener` covers user-group, permissions, and project-config changes but does not subscribe to `Plugins::EVENT_AFTER_INSTALL_PLUGIN` / `EVENT_AFTER_UNINSTALL_PLUGIN`. Marketing copy lists "plugin install" under system events — close the gap (or drop the claim). Small listener addition; emit `plugin.installed` / `plugin.uninstalled` with handle + version.
- **Excluded-field allowlist setting.** PII gating exists at the viewer level (`LogsController` checks `trails-manageSettings`), but there is no config-driven list of fields to exclude from the captured before/after snapshots in `ElementEventListener`. Add an `excludedFields` setting (handle list, optionally per element type) and apply it in the snapshot/diff path so customer-defined sensitive fields never reach `oldValue` / `newValue`.

## 1.x — Mid-term

### Operator workflows

- **Operator runbooks.** "User requested Article 17 erasure — checklist of actions". "Subpoena for audit logs — export-with-provenance procedure". "Integrity verify reports broken chain — triage flowchart". These are conversion pieces for compliance buyers.
- **CP integrity page distinguishes anonymization vs tamper findings.** Today both look identical. Correlate broken chain links with the `user.gdpr.anonymized` event row + the `[deleted]` sentinel and show the operator a clear classification.

### Integrations (beyond webhook)

- **Native Splunk integration** (HEC). Today shipping is generic webhook + a `splunk` provider hint; first-class Splunk support means HEC token + index + sourcetype + structured field mapping.
- **Native Datadog integration** (Logs API). Same shape.
- **PagerDuty / Slack** for the alerts pipeline (`failedLoginThreshold`, `alertEvents`). Today alerts are email-only.

### Storage backends

- **MySQL native partitioning.** Move from monthly table-per-partition (manual rotation) to MySQL `PARTITION BY RANGE` for installs that prefer it. Trade-off: harder to drop archived data; simpler operator mental model.

## 2.0 — Architectural

- **Cryptographic erasure for GDPR.** Alternative to the current rehash-on-anonymization design. Store PII (`userEmail`, `userName`, `ipAddress`) encrypted with a per-user key; on Article 17, delete the key. Erases data without breaking the hash chain. Bigger architectural change but resolves the rehash trade-off cleanly.
- **External Merkle log integrations.** Anchor into Sigstore / Rekor / Trillian for ecosystems where those are the audit substrate.

---

## Won't fix (decisions made during 1.0.0 design)

- **Hash chain rehash on anonymization breaking the local chain.** Documented in `DEVELOPER_GUIDE.md → GDPR & Hash Chain Integrity`. Mitigated by external Merkle anchoring. The trade-off is fundamental to a simple HMAC chain; cryptographic erasure (above) is the only alternative and is a 2.0 architectural change.
- **`certificate of integrity` JSON does not include `anchorProof`.** By design — the certificate exposes the public `anchorRef` so an auditor re-fetches the proof out-of-band. Including the proof in the export would conflate "what we computed" with "what the external system holds", weakening the independent-verification property.
