<?php

namespace anvildev\trails\controllers\cp;

use anvildev\trails\Trails;
use Craft;
use yii\web\Response;

class StreamController extends BaseCpController
{
    protected function requiredPermission(): string
    {
        return 'trails-viewLogs';
    }

    /**
     * Returns the table partial HTML for htmx swaps.
     * Reads filter params from query string, builds criteria, renders _table partial.
     */
    public function actionTable(): Response
    {
        $request = Craft::$app->getRequest();

        $dateFromParam = $request->getQueryParam('dateFrom');
        $dateToParam = $request->getQueryParam('dateTo');
        $dateFrom = is_string($dateFromParam) ? $dateFromParam : '';
        $dateTo = is_string($dateToParam) ? $dateToParam : '';

        $utc = new \DateTimeZone('UTC');
        $dateFromObj = \DateTime::createFromFormat('!Y-m-d', $dateFrom, $utc);
        $dateToObj = \DateTime::createFromFormat('!Y-m-d', $dateTo, $utc);

        $criteria = array_filter([
            'event' => $request->getQueryParam('event'),
            'category' => $request->getQueryParam('category'),
            'userId' => $request->getQueryParam('userId'),
            'dateFrom' => $dateFromObj ? $dateFromObj->format('Y-m-d 00:00:00') : null,
            'dateTo' => $dateToObj ? $dateToObj->format('Y-m-d 23:59:59') : null,
            'search' => $request->getQueryParam('search'),
            'limit' => min((int)($request->getQueryParam('pageSize') ?: 50), 100),
        ], fn($v) => $v !== null && $v !== '');

        $logs = Trails::getInstance()->audit->getLogs($criteria);

        return $this->renderTemplate('trails/logs/_table', [
            'logs' => $logs,
        ]);
    }

    /**
     * SSE endpoint for real-time audit log streaming.
     * Only active when $settings->realtime === 'sse'.
     */
    public function actionSse(): void
    {
        $settings = Trails::getInstance()->getSettings();

        if (($settings->realtime ?? null) !== 'sse') {
            throw new \yii\web\NotFoundHttpException();
        }

        if (!$this->acquireSseSlot((int) $settings->maxSseConnections)) {
            http_response_code(429);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Maximum SSE connections reached.';
            exit;
        }

        try {
            // Send SSE headers immediately via PHP — bypasses Yii's buffered response
            // so the event stream starts before the generator loop.
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');
            // Emit an initial ping frame so clients can deterministically
            // observe that the SSE handshake succeeded.
            echo ": connected\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            $request = Craft::$app->getRequest();
            $lastEventId = (int)($request->getHeaders()->get('Last-Event-ID') ?: 0);

            $generator = Trails::getInstance()->realtime->subscribe($lastEventId, 30);

            foreach ($generator as $event) {
                echo "id: {$event['id']}\n";
                echo "event: audit\n";
                echo 'data: ' . json_encode($event) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        } finally {
            $this->releaseSseSlot();
        }
        // We have already emitted a manual streaming response. Avoid Yii's
        // normal response pipeline re-sending headers/content.
        exit;
    }

    private function acquireSseSlot(int $maxConnections): bool
    {
        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $lockKey = 'trails:sse:lock';
        $counterKey = 'trails:sse:activeConnections';

        if (!$mutex->acquire($lockKey, 2)) {
            return false;
        }

        try {
            $current = (int) ($cache->get($counterKey) ?: 0);
            if ($current >= $maxConnections) {
                return false;
            }
            $cache->set($counterKey, $current + 1, 120);
            return true;
        } finally {
            $mutex->release($lockKey);
        }
    }

    private function releaseSseSlot(): void
    {
        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $lockKey = 'trails:sse:lock';
        $counterKey = 'trails:sse:activeConnections';

        if (!$mutex->acquire($lockKey, 2)) {
            return;
        }

        try {
            $current = (int) ($cache->get($counterKey) ?: 0);
            $cache->set($counterKey, max(0, $current - 1), 120);
        } finally {
            $mutex->release($lockKey);
        }
    }
}
