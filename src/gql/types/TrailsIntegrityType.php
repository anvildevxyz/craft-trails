<?php

namespace anvildev\trails\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;

class TrailsIntegrityType extends ObjectType
{
    public static function getName(): string
    {
        return 'TrailsIntegrity';
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
            'verified' => ['type' => Type::int()],
            'total' => ['type' => Type::int()],
            'tampered' => ['type' => Type::listOf(Type::int())],
            'lastRunAt' => ['type' => Type::string()],
        ];
    }
}
