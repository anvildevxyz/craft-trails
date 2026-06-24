# Changelog

All notable changes to this project will be documented in this file.

## 1.1.0 - 2026-06-24

### Added
- Read-only craft-mcp integration exposing the audit trail to AI assistants via 14 tools (log search/lookup/counts, activity & retention summaries, and integrity verification incl. HMAC, chain, Merkle, anchors, inclusion proofs, and signed compliance certificates). The dependency is soft (`class_exists`-guarded) — Trails runs unchanged when craft-mcp is absent.
- All tools are strictly read-only with PII redacted by default (actor email masked; IP/user-agent/metadata/raw before–after values withheld; session ids never exposed). List/verification calls clamp page and batch sizes.

## 1.0.0 - 2026-04-28

Initial public release.