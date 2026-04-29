<?php

namespace anvildev\trails\services;

use Craft;
use craft\base\Component;

/**
 * Cache-based pub/sub service for real-time audit log streaming.
 */
class RealtimeService extends Component
{
    private const CACHE_KEY = 'trails:stream';
    private const MAX_EVENTS = 100;
    private const TTL = 60;

    /**
     * Append an event to the stream cache list.
     */
    public function publish(int $recordId, string $event, string $dateCreated): void
    {
        $cache = Craft::$app->getCache();

        $mutex = Craft::$app->getMutex();
        $mutexKey = 'trails:stream:lock';

        if ($mutex->acquire($mutexKey, 3)) {
            try {
                $events = $cache->get(self::CACHE_KEY) ?: [];

                $events[] = [
                    'id' => $recordId,
                    'event' => $event,
                    'dateCreated' => $dateCreated,
                ];

                // Keep max 100 events (trim oldest)
                if (count($events) > self::MAX_EVENTS) {
                    $events = array_slice($events, -self::MAX_EVENTS);
                }

                $cache->set(self::CACHE_KEY, $events, self::TTL);
            } finally {
                $mutex->release($mutexKey);
            }
        } else {
            Craft::warning('Trails RealtimeService: stream mutex contended, event not published', 'trails');
        }
    }

    /**
     * Return all events with id > sinceId from the cache.
     *
     * @return array<int, array{id: int, event: string, dateCreated: string}>
     */
    public function getEventsSince(int $sinceId): array
    {
        $cache = Craft::$app->getCache();
        $events = $cache->get(self::CACHE_KEY) ?: [];

        return array_values(array_filter($events, fn(array $e) => $e['id'] > $sinceId));
    }

    /**
     * Yield new events as they appear, polling cache every 1s for up to $timeout seconds.
     *
     * @return \Generator<int, array{id: int, event: string, dateCreated: string}>
     */
    public function subscribe(int $lastEventId, int $timeout = 30): \Generator
    {
        $deadline = time() + $timeout;

        while (time() < $deadline) {
            $newEvents = $this->getEventsSince($lastEventId);

            foreach ($newEvents as $e) {
                yield $e;
                $lastEventId = max($lastEventId, $e['id']);
            }

            if (time() < $deadline) {
                sleep(1);
            }
        }
    }
}
