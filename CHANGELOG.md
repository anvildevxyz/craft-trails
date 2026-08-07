# Changelog

All notable changes to this project will be documented in this file.

## 1.1.1 - 2026-08-07

### Fixed
- Monthly table rotation is now idempotent: a duplicate rotation in the same month (e.g. re-enqueued after a cache flush, or a retried queue job) skips gracefully instead of failing with `Table 'trails_logs_YYYY_MM' already exists`.
- The garbage-collection hook now checks the durable archive state before enqueueing a rotation job instead of relying solely on a flushable cache key.
- Archived log-month registry entries now record their `rowCount` and first/last chain positions on the normal rotation path; a migration backfills existing archives.

## 1.1.0 - 2026-06-24

### Added
- Read-only craft-mcp integration exposing the audit trail to AI assistants via 14 tools (log search/lookup/counts, activity & retention summaries, and integrity verification incl. HMAC, chain, Merkle, anchors, inclusion proofs, and signed compliance certificates). The dependency is soft (`class_exists`-guarded) — Trails runs unchanged when craft-mcp is absent.
- All tools are strictly read-only with PII redacted by default (actor email masked; IP/user-agent/metadata/raw before–after values withheld; session ids never exposed). List/verification calls clamp page and batch sizes.

## 1.0.0 - 2026-04-28

Initial public release.
