<?php

namespace anvildev\trails\mcp;

use anvildev\trails\Trails;
use Mcp\Capability\Attribute\McpTool;
use stimmt\craft\Mcp\attributes\McpToolMeta;
use stimmt\craft\Mcp\enums\ToolCategory;

/**
 * MCP tools for verifying the tamper-evidence of the Trails audit trail:
 * per-record HMAC integrity, the prev-hash chain, Merkle roots, external
 * anchors, inclusion proofs, and signed compliance certificates. All read-only
 * (verification re-computes and compares; it never mutates). The full-trail
 * checks scan every (non-dropped) table and can be expensive on large logs.
 */
class IntegrityTools
{
    use ToolResponseTrait;

    /**
     * @param int $batchSize Rows per verification batch.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_verify_logs',
        description: 'Verify the HMAC integrity of every audit record (detects tampering). Returns counts plus the ids of any tampered/missing rows. Scans all tables — can be slow on large logs.',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function verifyLogs(int $batchSize = 500): array
    {
        return $this->guard(static fn(): array => [
            'result' => Trails::getInstance()->audit->verifyAllLogs(max(1, $batchSize)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_verify_chain',
        description: 'Verify the prev-hash chain links across the whole audit trail (detects insertions/deletions). Returns verified/failed counts and the first failure position.',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function verifyChain(int $batchSize = 500): array
    {
        return $this->guard(static fn(): array => [
            'result' => Trails::getInstance()->audit->verifyChainLinks(max(1, $batchSize)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_verify_merkle_roots',
        description: 'Recompute and verify every stored Merkle root against its records. Returns verified/failed counts and failed root ids.',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function verifyMerkleRoots(): array
    {
        return $this->guard(static fn(): array => [
            'result' => Trails::getInstance()->merkle->verifyAllRoots(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_verify_anchors',
        description: 'Re-verify every external anchor (S3 / RFC3161 timestamp) for the Merkle roots via the configured backend.',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function verifyAnchors(): array
    {
        return $this->guard(static fn(): array => [
            'result' => Trails::getInstance()->anchor->verifyAll(),
        ]);
    }

    /**
     * @param int $chainPosition The chain position of the entry to prove inclusion for.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_inclusion_proof',
        description: 'Get the Merkle inclusion proof for an audit entry at a given chain position (leaf hash, root hash, proof path, verified flag).',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function inclusionProof(int $chainPosition): array
    {
        return $this->guard(function() use ($chainPosition): array {
            $proof = Trails::getInstance()->merkle->getInclusionProof($chainPosition);
            if ($proof === null) {
                return ['error' => "No inclusion proof available for chain position {$chainPosition} (not yet anchored in a Merkle root)."];
            }

            return ['proof' => $proof->toArray()];
        });
    }

    /**
     * Generate a signed compliance certificate bundling the integrity results,
     * Merkle roots and anchors for a date range.
     *
     * @param string $fromDate Range start (Y-m-d or ISO).
     * @param string $toDate Range end (Y-m-d or ISO).
     * @param string $format One of: json, pdf.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'trails_certificate',
        description: 'Generate a signed compliance certificate for a date range (integrity report + Merkle roots + anchors). Returns the certificate content, content type and file extension.',
    )]
    #[McpToolMeta(category: ToolCategory::PLUGIN)]
    public function certificate(string $fromDate, string $toDate, string $format = 'json'): array
    {
        return $this->guard(static fn(): array => [
            'certificate' => Trails::getInstance()->certificate->generate($fromDate, $toDate, $format),
        ]);
    }
}
