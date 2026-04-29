<?php

declare(strict_types=1);

namespace anvildev\trails\services;

use anvildev\trails\Trails;
use Craft;
use yii\base\Component;
use yii\base\Event;

/**
 * Allows any plugin to register Yii events that Trails should automatically log.
 *
 * Usage from a third-party plugin's init():
 *
 *     use anvildev\trails\Trails;
 *
 *     if (class_exists(Trails::class) && Trails::getInstance() !== null) {
 *         Trails::getInstance()->eventBridge->listen(
 *             MyService::class,
 *             MyService::EVENT_ORDER_SHIPPED,
 *             'myplugin.order_shipped',
 *             function($event) {
 *                 return [
 *                     'description' => "Order #{$event->order->id} shipped",
 *                     'elementId' => $event->order->id,
 *                     'elementType' => get_class($event->order),
 *                     'metadata' => ['carrier' => $event->carrier],
 *                 ];
 *             }
 *         );
 *     }
 */
class EventBridgeService extends Component
{
    /** @var list<array{class: string, event: string, eventType: string, handler: callable|null}> */
    private array $registrations = [];
    private bool $bound = false;

    /**
     * Register an event to be automatically logged by Trails.
     *
     * @param string $class The class that fires the event (e.g., MyService::class)
     * @param string $event The event constant (e.g., MyService::EVENT_SOMETHING)
     * @param string $eventType The audit event type in "namespace.action" format
     * @param callable|null $handler Optional handler that receives the event and returns
     *   an array with any of: description, metadata, elementId, elementType, elementTitle.
     *   If null, a generic log entry is created with just the eventType.
     */
    public function listen(string $class, string $event, string $eventType, ?callable $handler = null): void
    {
        // Validate event type format (same rules as logCustomEvent)
        AuditService::assertValidEventType($eventType);

        // Also reject reserved prefixes
        $lowerType = strtolower($eventType);
        foreach (AuditService::RESERVED_PREFIXES as $prefix) {
            if (str_starts_with($lowerType, $prefix)) {
                throw new \InvalidArgumentException(
                    "Event type prefix '{$prefix}' is reserved for system events."
                );
            }
        }

        $this->registrations[] = [
            'class' => $class,
            'event' => $event,
            'eventType' => $eventType,
            'handler' => $handler,
        ];

        // If we've already bound (init already ran), bind this one immediately
        if ($this->bound) {
            $this->bindRegistration(end($this->registrations));
        }
    }

    /**
     * Bind all registered events. Called once from Trails::init().
     */
    public function bindAll(): void
    {
        if ($this->bound) {
            return;
        }
        foreach ($this->registrations as $reg) {
            $this->bindRegistration($reg);
        }
        $this->bound = true;
    }

    /**
     * @return int Number of registered event bridges
     */
    public function getRegistrationCount(): int
    {
        return count($this->registrations);
    }

    /**
     * @param array{class: string, event: string, eventType: string, handler: callable|null} $reg
     */
    private function bindRegistration(array $reg): void
    {
        Event::on($reg['class'], $reg['event'], function(Event $event) use ($reg): void {
            $settings = Trails::getInstance()->getSettings();
            if (!$settings->enabled) {
                return;
            }

            $data = [];
            if ($reg['handler'] !== null) {
                try {
                    $data = ($reg['handler'])($event) ?? [];
                } catch (\Throwable $e) {
                    Craft::warning("Trails EventBridge handler error for {$reg['eventType']}: " . $e->getMessage(), 'trails');
                    // Don't leak exception details into metadata — log server-side only
                    $data = [];
                }

                // Truncate description and elementTitle to prevent varchar overflow
                if (isset($data['description']) && is_string($data['description'])) {
                    $data['description'] = mb_substr($data['description'], 0, 500, 'UTF-8');
                }
                if (isset($data['elementTitle']) && is_string($data['elementTitle'])) {
                    $data['elementTitle'] = mb_substr($data['elementTitle'], 0, 255, 'UTF-8');
                }
            }

            try {
                Trails::getInstance()->audit->logCustomEvent(
                    eventType: $reg['eventType'],
                    category: explode('.', $reg['eventType'])[0],
                    description: $data['description'] ?? null,
                    metadata: $data['metadata'] ?? null,
                    elementId: $data['elementId'] ?? null,
                    elementType: $data['elementType'] ?? null,
                    elementTitle: $data['elementTitle'] ?? null,
                );
            } catch (\Throwable $e) {
                Craft::error("Trails EventBridge logging failed for {$reg['eventType']}: " . $e->getMessage(), 'trails');
            }
        });
    }
}
