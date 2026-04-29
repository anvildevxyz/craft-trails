<?php

namespace anvildev\trails\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;

class TrailsSummaryType extends ObjectType
{
    public static function getName(): string
    {
        return 'TrailsSummary';
    }

    public static function getType(): self
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn() => new self([
            'name' => self::getName(),
            'fields' => fn() => self::getFieldDefinitions(),
        ]));
    }

    public static function getFieldDefinitions(): array
    {
        return [
            'totalEvents' => ['type' => Type::nonNull(Type::int())],
            'uniqueUsers' => ['type' => Type::nonNull(Type::int())],
            'logins' => ['type' => Type::nonNull(Type::int())],
            'elementsCreated' => ['type' => Type::nonNull(Type::int())],
            'elementsUpdated' => ['type' => Type::nonNull(Type::int())],
            'elementsDeleted' => ['type' => Type::nonNull(Type::int())],
            'days' => ['type' => Type::nonNull(Type::int())],
        ];
    }
}
