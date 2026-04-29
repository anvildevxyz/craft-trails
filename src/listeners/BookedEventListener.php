<?php

declare(strict_types=1);

namespace anvildev\trails\listeners;

use anvildev\trails\services\EventBridgeService;
use Craft;

/**
 * Built-in event bridge for the Booked plugin.
 * Only registers if Booked is installed.
 *
 * Note: Booked event constants are approximate and based on Booked's EVENT_SYSTEM.md.
 * If a registered event class or constant does not exist at runtime, the event simply
 * never fires — no error occurs.
 */
class BookedEventListener
{
    public static function register(EventBridgeService $bridge): void
    {
        if (!class_exists(\anvildev\booked\Booked::class)) {
            return;
        }
        $plugin = Craft::$app->getPlugins()->getPlugin('booked');
        if ($plugin === null) {
            return;
        }

        // Reservation events — use the event class names from Booked's EVENT_SYSTEM.md
        $bridge->listen(
            \anvildev\booked\services\BookingService::class,
            'afterBookingSave',
            'booked.reservation_created',
            function($event) {
                $reservation = $event->reservation ?? $event->sender ?? null;
                return [
                    'description' => 'Reservation created',
                    'elementId' => is_object($reservation) && property_exists($reservation, 'id') ? $reservation->id : null,
                    'elementType' => $reservation ? get_class($reservation) : null,
                    'metadata' => is_object($reservation) ? [
                        'status' => $reservation->status ?? null,
                        'service' => $reservation->serviceId ?? null,
                    ] : null,
                ];
            }
        );

        $bridge->listen(
            \anvildev\booked\services\BookingService::class,
            'afterBookingCancel',
            'booked.reservation_cancelled',
            function($event) {
                $reservation = $event->reservation ?? $event->sender ?? null;
                return [
                    'description' => 'Reservation cancelled',
                    'elementId' => is_object($reservation) && property_exists($reservation, 'id') ? $reservation->id : null,
                    'metadata' => is_object($reservation) ? [
                        'status' => $reservation->status ?? null,
                        'reason' => $event->reason ?? null,
                    ] : null,
                ];
            }
        );
    }
}
