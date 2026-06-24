<?php

namespace anvildev\trails\mcp\support;

use anvildev\trails\dto\AuditLogEntry;
use anvildev\trails\helpers\EncryptionHelper;
use craft\base\ElementInterface;

/**
 * Serialises Trails audit entries into plain, MCP-friendly arrays.
 *
 * Audit data is highly sensitive, so {@see self::auditEntry()} redacts by
 * default: the actor email is decrypted then masked, the request IP / user-agent
 * / arbitrary metadata are withheld, the session id is never exposed, and the
 * before/after change payloads are reduced to the list of changed field names
 * (their values can contain passwords, tokens or other secrets). A caller must
 * explicitly opt out (`redactPii: false`) to receive the raw values.
 */
final class Presenter
{
    /**
     * Recursively coerce a value into something json_encode can always handle.
     */
    public static function jsonSafe(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 16) {
            return null;
        }
        if ($value instanceof ElementInterface) {
            return ['id' => $value->id, 'title' => $value->title ?? (string)$value];
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }
        if (is_array($value)) {
            return array_map(static fn($v) => self::jsonSafe($v, $depth + 1), $value);
        }
        if ($value instanceof \JsonSerializable) {
            return self::jsonSafe($value->jsonSerialize(), $depth + 1);
        }
        if ($value instanceof \stdClass) {
            return self::jsonSafe(get_object_vars($value), $depth + 1);
        }
        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string)$value : ['_class' => $value::class];
        }
        if (is_float($value) && !is_finite($value)) {
            return null;
        }
        return $value;
    }

    /**
     * Mask an email to its first two chars + domain, e.g. ja***@example.com.
     */
    public static function redactEmail(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return $email;
        }
        $at = strpos($email, '@');
        if ($at === false) {
            return '***';
        }
        return substr($email, 0, 2) . '***' . substr($email, $at);
    }

    /**
     * Present a single audit entry.
     *
     * @param bool $redactPii Mask the actor email and withhold IP / user-agent /
     *                        metadata / raw change values. Defaults to true so a
     *                        forgotten flag fails safe.
     * @return array<string, mixed>
     */
    public static function auditEntry(AuditLogEntry $e, bool $redactPii = true): array
    {
        // userEmail is stored encrypted; decrypt before masking/exposing.
        $email = EncryptionHelper::decrypt($e->userEmail);

        $out = [
            'id' => $e->id,
            'event' => $e->event,
            'category' => $e->category,
            'dateCreated' => $e->dateCreated,
            'elementType' => $e->elementType,
            'elementId' => $e->elementId,
            'elementTitle' => $e->elementTitle,
            'siteId' => $e->siteId,
            'userId' => $e->userId,
            'userName' => $e->userName,
            'userEmail' => $redactPii ? self::redactEmail($email) : $email,
            'requestUrl' => $e->requestUrl,
            'requestMethod' => $e->requestMethod,
            // Integrity references — hashes/positions are not secret.
            'chainPosition' => $e->chainPosition,
            'hash' => $e->hash,
            'prevHash' => $e->prevHash,
            // sessionId is deliberately never exposed.
        ];

        if ($redactPii) {
            // Withhold request fingerprint + arbitrary context; surface only WHICH
            // fields changed, never their (possibly secret) values.
            $out['changedFields'] = self::changedFieldNames($e);
        } else {
            $out['ipAddress'] = $e->ipAddress;
            $out['country'] = $e->country;
            $out['region'] = $e->region;
            $out['city'] = $e->city;
            $out['userAgent'] = $e->userAgent;
            $out['oldValue'] = $e->decodedOldValue();
            $out['newValue'] = $e->decodedNewValue();
            $out['metadata'] = $e->decodedMetadata();
        }

        return $out;
    }

    /**
     * The union of field keys present in the before/after payloads — names only,
     * never values. Returns null when the entry carries no change payload.
     *
     * @return list<string>|null
     */
    private static function changedFieldNames(AuditLogEntry $e): ?array
    {
        $old = $e->decodedOldValue() ?? [];
        $new = $e->decodedNewValue() ?? [];
        if ($old === [] && $new === []) {
            return null;
        }

        return array_values(array_unique([...array_keys($old), ...array_keys($new)]));
    }
}
