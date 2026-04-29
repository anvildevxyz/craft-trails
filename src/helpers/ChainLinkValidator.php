<?php

declare(strict_types=1);

namespace anvildev\trails\helpers;

/**
 * Pure validation of a single chain link.
 *
 * Genesis is the only position where prevHash may legitimately be NULL. Any
 * other NULL prevHash signals a chain gap (e.g. predecessor row was deleted).
 */
final class ChainLinkValidator
{
    /**
     * @return array{status:'ok'|'failed',message:string}
     */
    public static function validate(int $chainPosition, ?string $prevHash, ?string $expectedPrevHash): array
    {
        if ($prevHash === null) {
            if ($chainPosition <= 1) {
                return ['status' => 'ok', 'message' => 'OK (genesis)'];
            }

            return [
                'status' => 'failed',
                'message' => "FAILED (chain gap: prevHash is NULL at position {$chainPosition})",
            ];
        }

        if ($expectedPrevHash === null) {
            return [
                'status' => 'failed',
                'message' => 'FAILED (previous record at position ' . ($chainPosition - 1) . ' not found)',
            ];
        }

        if (!hash_equals($expectedPrevHash, $prevHash)) {
            return ['status' => 'failed', 'message' => 'FAILED (prevHash mismatch)'];
        }

        return [
            'status' => 'ok',
            'message' => 'OK (linked to position ' . ($chainPosition - 1) . ')',
        ];
    }
}
