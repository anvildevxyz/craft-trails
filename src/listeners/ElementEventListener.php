<?php

namespace anvildev\trails\listeners;

use anvildev\trails\models\Settings;
use anvildev\trails\services\AuditService;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\events\ElementEvent;
use craft\services\Elements;
use yii\base\Event;

class ElementEventListener
{
    /** @var array<int, array> Element snapshots for field-level change tracking */
    private array $elementSnapshots = [];

    public function __construct(
        private readonly AuditService $audit,
        private readonly Settings $settings,
    ) {
    }

    public function register(): void
    {
        if ($this->settings->logElements) {
            if ($this->settings->captureFieldChanges) {
                Event::on(Elements::class, Elements::EVENT_BEFORE_SAVE_ELEMENT, function(ElementEvent $event) {
                    $element = $event->element;
                    if ($element->firstSave || $element->getIsDraft() || $element->getIsRevision() || $element->propagating) {
                        return;
                    }
                    if (!$this->isElementIncluded($element)) {
                        return;
                    }
                    $this->elementSnapshots[$element->id] = $this->captureElementSnapshot($element);
                });
            }

            Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, function(ElementEvent $event) {
                $element = $event->element;
                $isNew = $event->isNew;

                if ($element->getIsDraft() || $element->getIsRevision() || $element->propagating) {
                    return;
                }
                if (!$this->isElementIncluded($element)) {
                    return;
                }
                if ($element instanceof Entry && $this->settings->excludedSections && ($section = $element->getSection()) && in_array($section->handle, $this->settings->excludedSections, true)) {
                    return;
                }

                $oldValues = $newValues = null;

                if ($this->settings->captureFieldChanges && !$isNew && isset($this->elementSnapshots[$element->id])) {
                    $changes = $this->calculateFieldChanges(
                        $this->elementSnapshots[$element->id],
                        $this->captureElementSnapshot($element)
                    );
                    if ($changes) {
                        [$oldValues, $newValues] = [$changes['old'], $changes['new']];
                    }
                    unset($this->elementSnapshots[$element->id]);
                }

                $context = ['title' => $element->title ?? null, 'isNew' => $isNew];

                if ($element instanceof Asset) {
                    $context += [
                        'filename' => $element->filename,
                        'kind' => $element->kind,
                        'size' => $element->size,
                        'volumeId' => $element->volumeId,
                        'folderId' => $element->folderId,
                    ];
                }

                $eventType = ($element instanceof Asset && $isNew) ? 'asset.uploaded' : ($isNew ? 'element.created' : 'element.updated');
                $this->audit->log($eventType, $element::class, $element->id, $context, $oldValues, $newValues);
            });

            Event::on(Elements::class, Elements::EVENT_AFTER_DELETE_ELEMENT, function(ElementEvent $event) {
                $element = $event->element;
                if ($element->propagating || !$this->isElementIncluded($element)) {
                    return;
                }
                $this->audit->log('element.deleted', $element::class, $element->id, [
                    'title' => $element->title ?? null,
                    'hardDelete' => $event->hardDelete ?? false,
                ]);
            });

            Event::on(Elements::class, Elements::EVENT_AFTER_RESTORE_ELEMENT, function(ElementEvent $event) {
                $element = $event->element;
                if ($element->propagating || !$this->isElementIncluded($element)) {
                    return;
                }
                $this->audit->log('element.restored', $element::class, $element->id, [
                    'title' => $element->title ?? null,
                ]);
            });
        }

        if ($this->settings->logAssets) {
            Event::on(\craft\services\Assets::class, \craft\services\Assets::EVENT_AFTER_REPLACE_ASSET, function(\craft\events\ReplaceAssetEvent $event) {
                $asset = $event->asset;
                $this->audit->log('asset.replaced', Asset::class, $asset->id, [
                    'title' => $asset->title,
                    'filename' => $asset->filename,
                ]);
            });
        }
    }

    private function isElementIncluded(ElementInterface $element): bool
    {
        foreach ($this->settings->excludedElementTypes as $excluded) {
            if (is_a($element, $excluded)) {
                return false;
            }
        }
        return true;
    }

    private function captureElementSnapshot(ElementInterface $element): array
    {
        $snapshot = [
            'title' => $element->title,
            'slug' => $element->slug ?? null,
            'enabled' => $element->enabled,
        ];

        if ($fieldLayout = $element->getFieldLayout()) {
            $excluded = array_flip($this->settings->excludedFieldHandles);
            foreach ($fieldLayout->getCustomFields() as $field) {
                $h = $field->handle;
                $snapshot['fields'][$h] = isset($excluded[$h])
                    ? '[redacted]'
                    : $this->serializeFieldValue($element->getFieldValue($h));
            }
        }

        if ($this->settings->maxSnapshotBytes > 0) {
            $encoded = json_encode($snapshot, JSON_INVALID_UTF8_SUBSTITUTE);
            if ($encoded !== false && strlen($encoded) > $this->settings->maxSnapshotBytes) {
                $snapshot = [
                    'title' => $snapshot['title'],
                    '_truncated' => true,
                    '_reason' => 'Snapshot exceeded ' . $this->settings->maxSnapshotBytes . ' bytes',
                ];
            }
        }

        return $snapshot;
    }

    private function serializeFieldValue(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }
        if (is_array($value)) {
            return array_map($this->serializeFieldValue(...), $value);
        }
        if ($value instanceof \craft\elements\db\ElementQuery) {
            return $value->ids();
        }
        if ($value instanceof Element) {
            return $value->id;
        }
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string)$value;
            }
            if (method_exists($value, 'toArray')) {
                return $value->toArray();
            }
        }
        $json = json_encode($value);
        return $json !== false ? json_decode($json, true) : '[unserializable]';
    }

    private function calculateFieldChanges(array $oldSnapshot, array $newSnapshot): array
    {
        $old = $new = [];

        foreach (['title', 'slug', 'enabled'] as $prop) {
            if (($oldSnapshot[$prop] ?? null) !== ($newSnapshot[$prop] ?? null)) {
                $old[$prop] = $oldSnapshot[$prop] ?? null;
                $new[$prop] = $newSnapshot[$prop] ?? null;
            }
        }

        $oldFields = $oldSnapshot['fields'] ?? [];
        $newFields = $newSnapshot['fields'] ?? [];
        foreach (array_keys($oldFields + $newFields) as $handle) {
            $ov = $oldFields[$handle] ?? null;
            $nv = $newFields[$handle] ?? null;
            if ($ov !== $nv) {
                $old['fields'][$handle] = $ov;
                $new['fields'][$handle] = $nv;
            }
        }

        return ($old || $new) ? ['old' => $old, 'new' => $new] : [];
    }
}
