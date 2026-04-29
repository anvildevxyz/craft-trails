<?php

namespace anvildev\trails\events;

use anvildev\trails\records\AuditLogRecord;
use yii\base\ModelEvent;

/**
 * Before-event: Set $event->isValid = false to suppress logging.
 * After-event: $event->record contains the saved AuditLogRecord.
 */
class AuditEvent extends ModelEvent
{
    public string $event = '';
    public ?string $elementType = null;
    public ?int $elementId = null;
    public array $context = [];
    public ?array $oldValues = null;
    public ?array $newValues = null;
    public ?AuditLogRecord $record = null;
}
