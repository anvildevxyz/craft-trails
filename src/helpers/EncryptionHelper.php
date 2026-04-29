<?php

namespace anvildev\trails\helpers;

use Craft;

/**
 * Encryption/decryption of PII fields and session ID hashing.
 */
class EncryptionHelper
{
    private const ENCRYPTED_PREFIX = 'enc:';

    public static function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $key = Craft::$app->getConfig()->getGeneral()->securityKey;
        return self::ENCRYPTED_PREFIX . base64_encode(Craft::$app->getSecurity()->encryptByKey($value, $key));
    }

    /** Returns original string if not encrypted (backward compat). */
    public static function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        if (!str_starts_with($value, self::ENCRYPTED_PREFIX)) {
            return $value;
        }

        $raw = base64_decode(substr($value, strlen(self::ENCRYPTED_PREFIX)), true);
        if ($raw === false) {
            return $value;
        }

        try {
            $key = Craft::$app->getConfig()->getGeneral()->securityKey;
            $decrypted = Craft::$app->getSecurity()->decryptByKey($raw, $key);
            return $decrypted !== false ? $decrypted : $value;
        } catch (\Throwable $e) {
            // Keep backward-compatible fallback, but make key/ciphertext issues visible.
            Craft::warning('Trails EncryptionHelper: failed to decrypt stored value: ' . $e->getMessage(), 'trails');
            return $value;
        }
    }

    /** One-way HMAC-SHA256 hash — session IDs must never be stored in plaintext. */
    public static function hashSessionId(?string $sessionId): ?string
    {
        if ($sessionId === null || $sessionId === '') {
            return null;
        }
        return hash_hmac('sha256', $sessionId, Craft::$app->getConfig()->getGeneral()->securityKey);
    }
}
