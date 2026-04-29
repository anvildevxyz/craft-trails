<?php

declare(strict_types=1);

namespace anvildev\trails\listeners;

use anvildev\trails\services\EventBridgeService;
use Craft;

/**
 * Built-in event bridge for Craft Commerce.
 * Only registers if Commerce is installed.
 *
 * Note: Commerce event class names and constants are approximate and based on
 * the Commerce 5.x public API. If a registered event class or constant does not
 * exist at runtime, the event simply never fires — no error occurs.
 */
class CommerceEventListener
{
    public static function register(EventBridgeService $bridge): void
    {
        // Only register if Commerce is installed
        if (!class_exists(\craft\commerce\Plugin::class)) {
            return;
        }
        $plugin = Craft::$app->getPlugins()->getPlugin('commerce');
        if ($plugin === null) {
            return;
        }

        $bridge->listen(
            \craft\commerce\services\Orders::class,
            'afterCompleteOrder',
            'commerce.order_completed',
            function($event) {
                $order = $event->sender ?? null;
                return [
                    'description' => $order ? "Order #{$order->number} completed" : 'Order completed',
                    'elementId' => $order?->id,
                    'elementType' => $order ? get_class($order) : null,
                    'elementTitle' => $order?->reference,
                    // Note: email intentionally excluded to avoid logging customer PII
                    'metadata' => $order ? [
                        'number' => $order->number,
                        'total' => (string) $order->totalPrice,
                    ] : null,
                ];
            }
        );

        $bridge->listen(
            \craft\commerce\services\Payments::class,
            'afterProcessPayment',
            'commerce.payment_processed',
            function($event) {
                $order = $event->order ?? $event->sender ?? null;
                return [
                    'description' => $order ? "Payment processed for order #{$order->number}" : 'Payment processed',
                    'elementId' => $order?->id,
                    'metadata' => [
                        'gateway' => $event->transaction->gateway->name ?? 'unknown',
                        'amount' => (string) ($event->transaction->amount ?? ''),
                    ],
                ];
            }
        );

        $bridge->listen(
            \craft\commerce\elements\Order::class,
            'afterOrderStatusChange',
            'commerce.order_status_changed',
            function($event) {
                $order = $event->sender ?? null;
                return [
                    'description' => $order ? "Order #{$order->number} status changed" : 'Order status changed',
                    'elementId' => $order?->id,
                    'metadata' => [
                        'oldStatus' => $event->orderHistory->prevStatusId ?? null,
                        'newStatus' => $order?->orderStatus?->handle,
                    ],
                ];
            }
        );
    }
}
