<?php

namespace anvildev\trails\dto;

/**
 * Read-only value object representing a single audit log entry.
 *
 * Replaces raw ActiveRecord exposure in the public query API.
 * Has no Craft/Yii2 dependencies and is safe to use anywhere.
 */
final class AuditLogEntry
{
    public function __construct(
        // Required fields
        public readonly int $id,
        public readonly string $event,
        public readonly string $dateCreated,

        // Event categorisation
        public readonly ?string $category,
        public readonly ?string $elementType,
        public readonly ?int $elementId,
        public readonly ?string $elementTitle,

        // Actor
        public readonly ?int $userId,
        public readonly ?string $userName,
        public readonly ?string $userEmail,

        // Request context
        public readonly ?string $ipAddress,
        public readonly ?string $country,
        public readonly ?string $region,
        public readonly ?string $city,
        public readonly ?string $userAgent,
        public readonly ?string $requestUrl,
        public readonly ?string $requestMethod,

        // Site
        public readonly ?int $siteId,

        // Payload (raw JSON strings)
        public readonly ?string $oldValue,
        public readonly ?string $newValue,
        public readonly ?string $metadata,

        // Session / integrity
        public readonly ?string $sessionId,
        public readonly ?string $hash,

        // Chain fields
        public readonly ?int $chainPosition,
        public readonly ?string $prevHash,
    ) {
    }

    /**
     * Construct from an associative array, casting values to their correct types.
     * Missing keys default to null for nullable properties, required fields
     * fall back to safe defaults (id → 0, event/dateCreated → '').
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : 0,
            event: isset($data['event']) ? (string) $data['event'] : '',
            dateCreated: isset($data['dateCreated']) ? (string) $data['dateCreated'] : '',

            category: isset($data['category']) ? (string) $data['category'] : null,
            elementType: isset($data['elementType']) ? (string) $data['elementType'] : null,
            elementId: isset($data['elementId']) ? (int) $data['elementId'] : null,
            elementTitle: isset($data['elementTitle']) ? (string) $data['elementTitle'] : null,

            userId: isset($data['userId']) ? (int) $data['userId'] : null,
            userName: isset($data['userName']) ? (string) $data['userName'] : null,
            userEmail: isset($data['userEmail']) ? (string) $data['userEmail'] : null,

            ipAddress: isset($data['ipAddress']) ? (string) $data['ipAddress'] : null,
            country: isset($data['country']) ? (string) $data['country'] : null,
            region: isset($data['region']) ? (string) $data['region'] : null,
            city: isset($data['city']) ? (string) $data['city'] : null,
            userAgent: isset($data['userAgent']) ? (string) $data['userAgent'] : null,
            requestUrl: isset($data['requestUrl']) ? (string) $data['requestUrl'] : null,
            requestMethod: isset($data['requestMethod']) ? (string) $data['requestMethod'] : null,

            siteId: isset($data['siteId']) ? (int) $data['siteId'] : null,

            oldValue: isset($data['oldValue']) ? (string) $data['oldValue'] : null,
            newValue: isset($data['newValue']) ? (string) $data['newValue'] : null,
            metadata: isset($data['metadata']) ? (string) $data['metadata'] : null,

            sessionId: isset($data['sessionId']) ? (string) $data['sessionId'] : null,
            hash: isset($data['hash']) ? (string) $data['hash'] : null,

            chainPosition: isset($data['chainPosition']) ? (int) $data['chainPosition'] : null,
            prevHash: isset($data['prevHash']) ? (string) $data['prevHash'] : null,
        );
    }

    /**
     * Construct from any object (e.g. an ActiveRecord) by casting it to an array first.
     */
    public static function fromRecord(object $record): self
    {
        return self::fromArray(self::extractRecordData($record));
    }

    /**
     * Convert a record-like object into the canonical audit-entry shape.
     *
     * @return array<string, mixed>
     */
    public static function extractRecordData(object $record): array
    {
        $dateCreated = $record->dateCreated ?? null;

        if ($dateCreated instanceof \DateTimeInterface) {
            $dateCreated = $dateCreated->format('c');
        } elseif ($dateCreated !== null) {
            $dateCreated = (string) $dateCreated;
        }

        return [
            'id' => $record->id ?? null,
            'event' => $record->event ?? null,
            'dateCreated' => $dateCreated,
            'category' => $record->category ?? null,
            'elementType' => $record->elementType ?? null,
            'elementId' => $record->elementId ?? null,
            'elementTitle' => $record->elementTitle ?? null,
            'userId' => $record->userId ?? null,
            'userName' => $record->userName ?? null,
            'userEmail' => $record->userEmail ?? null,
            'ipAddress' => $record->ipAddress ?? null,
            'country' => $record->country ?? null,
            'region' => $record->region ?? null,
            'city' => $record->city ?? null,
            'userAgent' => $record->userAgent ?? null,
            'requestUrl' => $record->requestUrl ?? null,
            'requestMethod' => $record->requestMethod ?? null,
            'siteId' => $record->siteId ?? null,
            'oldValue' => $record->oldValue ?? null,
            'newValue' => $record->newValue ?? null,
            'metadata' => $record->metadata ?? null,
            'sessionId' => $record->sessionId ?? null,
            'hash' => $record->hash ?? null,
            'chainPosition' => $record->chainPosition ?? null,
            'prevHash' => $record->prevHash ?? null,
        ];
    }

    /**
     * JSON-decode the metadata string.
     *
     * @return array<string, mixed>|null
     */
    public function decodedMetadata(): ?array
    {
        if ($this->metadata === null) {
            return null;
        }

        $decoded = json_decode($this->metadata, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * JSON-decode the oldValue string.
     *
     * @return array<string, mixed>|null
     */
    public function decodedOldValue(): ?array
    {
        if ($this->oldValue === null) {
            return null;
        }

        $decoded = json_decode($this->oldValue, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * JSON-decode the newValue string.
     *
     * @return array<string, mixed>|null
     */
    public function decodedNewValue(): ?array
    {
        if ($this->newValue === null) {
            return null;
        }

        $decoded = json_decode($this->newValue, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Return all properties as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'dateCreated' => $this->dateCreated,
            'category' => $this->category,
            'elementType' => $this->elementType,
            'elementId' => $this->elementId,
            'elementTitle' => $this->elementTitle,
            'userId' => $this->userId,
            'userName' => $this->userName,
            'userEmail' => $this->userEmail,
            'ipAddress' => $this->ipAddress,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'userAgent' => $this->userAgent,
            'requestUrl' => $this->requestUrl,
            'requestMethod' => $this->requestMethod,
            'siteId' => $this->siteId,
            'oldValue' => $this->oldValue,
            'newValue' => $this->newValue,
            'metadata' => $this->metadata,
            'sessionId' => $this->sessionId,
            'hash' => $this->hash,
            'chainPosition' => $this->chainPosition,
            'prevHash' => $this->prevHash,
        ];
    }
}
