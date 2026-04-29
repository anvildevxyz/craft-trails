<?php

namespace anvildev\trails\variables;

use anvildev\trails\query\AuditQuery;
use anvildev\trails\Trails;
use Craft;

class TrailsVariable
{
    private function plugin(): Trails
    {
        $plugin = Craft::$app->getPlugins()->getPlugin('trails');
        if (!$plugin instanceof Trails) {
            throw new \RuntimeException('Trails plugin is not available.');
        }

        return $plugin;
    }

    public function logs(array $criteria = []): array
    {
        return $this->plugin()->audit->getLogs($criteria);
    }

    public function count(array $criteria = []): int
    {
        return $this->plugin()->audit->countLogs($criteria);
    }

    public function summary(int $days = 7): array
    {
        return $this->plugin()->audit->getActivitySummary($days);
    }

    public function eventTypes(): array
    {
        return $this->plugin()->audit->getEventTypes();
    }

    public function categories(): array
    {
        return $this->plugin()->audit->getCategories();
    }

    public function elementHistory(int $elementId, ?string $elementType = null): array
    {
        return $this->plugin()->audit->getLogs(
            array_filter(['elementId' => $elementId, 'elementType' => $elementType])
        );
    }

    public function userActivity(int $userId, int $limit = 50): array
    {
        return $this->plugin()->audit->getLogs(['userId' => $userId, 'limit' => $limit]);
    }

    /**
     * Create a fluent query builder for use in Twig templates.
     */
    public function query(): AuditQuery
    {
        return $this->plugin()->audit->query();
    }
}
