<?php

namespace anvildev\trails\gql\queries;

use anvildev\trails\dto\AuditLogEntry;
use anvildev\trails\gql\types\TrailsIntegrityType;
use anvildev\trails\gql\types\TrailsLogType;
use anvildev\trails\gql\types\TrailsSummaryType;
use anvildev\trails\Trails;
use Craft;
use GraphQL\Type\Definition\Type;

class TrailsQuery
{
    public static function getQueries(): array
    {
        return [
            'trailsLogs' => [
                'type' => Type::listOf(TrailsLogType::getType()),
                'args' => [
                    'event' => ['type' => Type::string()],
                    'category' => ['type' => Type::string()],
                    'userId' => ['type' => Type::int()],
                    'after' => ['type' => Type::string()],
                    'before' => ['type' => Type::string()],
                    'search' => ['type' => Type::string()],
                    'cursor' => ['type' => Type::string()],
                    'limit' => ['type' => Type::int()],
                ],
                'resolve' => function($root, array $args): array {
                    $audit = Trails::getInstance()->audit;
                    $query = $audit->query();

                    if (!empty($args['event'])) {
                        $query->event($args['event']);
                    }
                    if (!empty($args['category'])) {
                        $query->category($args['category']);
                    }
                    if (!empty($args['userId'])) {
                        $query->user((int)$args['userId']);
                    }
                    if (!empty($args['after'])) {
                        $query->after($args['after']);
                    }
                    if (!empty($args['before'])) {
                        $query->before($args['before']);
                    }
                    if (!empty($args['search'])) {
                        $query->search($args['search']);
                    }
                    if (!empty($args['cursor'])) {
                        $query->cursor($args['cursor']);
                    }
                    if (!empty($args['limit'])) {
                        $query->limit((int)$args['limit']);
                    }

                    return array_map(fn($entry) => $entry->toArray(), $query->all());
                },
                'description' => 'Returns a list of audit log entries, optionally filtered and paginated.',
            ],

            'trailsLog' => [
                'type' => TrailsLogType::getType(),
                'args' => [
                    'id' => ['type' => Type::nonNull(Type::int())],
                ],
                'resolve' => function($root, array $args): ?array {
                    $audit = Trails::getInstance()->audit;
                    $record = $audit->getLogById((int)$args['id']);

                    if ($record === null) {
                        return null;
                    }

                    return AuditLogEntry::fromRecord($record)->toArray();
                },
                'description' => 'Returns a single audit log entry by ID.',
            ],

            'trailsSummary' => [
                'type' => TrailsSummaryType::getType(),
                'args' => [
                    'days' => ['type' => Type::int(), 'defaultValue' => 7],
                ],
                'resolve' => function($root, array $args): array {
                    $days = (int)($args['days'] ?? 7);
                    $audit = Trails::getInstance()->audit;
                    $summary = $audit->getActivitySummary($days);
                    $summary['days'] = $days;
                    return $summary;
                },
                'description' => 'Returns an activity summary for the specified number of days (default 7).',
            ],

            'trailsIntegrity' => [
                'type' => TrailsIntegrityType::getType(),
                'args' => [],
                'resolve' => function($root, array $args): ?array {
                    $data = Craft::$app->getCache()->get('trails:integrity:lastRun');

                    if ($data === false || $data === null) {
                        return null;
                    }

                    return [
                        'verified' => $data['verified'] ?? null,
                        'total' => $data['total'] ?? null,
                        'tampered' => $data['tampered'] ?? null,
                        'lastRunAt' => isset($data['at'])
                            ? (new \DateTime('@' . $data['at']))->format('c')
                            : null,
                    ];
                },
                'description' => 'Returns the result of the last integrity verification run, or null if none has been run.',
            ],
        ];
    }
}
