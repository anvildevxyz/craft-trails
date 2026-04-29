<?php

namespace anvildev\trails\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;

class TrailsLogType extends ObjectType
{
    public static function getName(): string
    {
        return 'TrailsLog';
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
            'id' => ['type' => Type::nonNull(Type::int())],
            'event' => ['type' => Type::nonNull(Type::string())],
            'category' => ['type' => Type::string()],
            'elementType' => ['type' => Type::string()],
            'elementId' => ['type' => Type::int()],
            'elementTitle' => ['type' => Type::string()],
            'userId' => ['type' => Type::int()],
            'userName' => ['type' => Type::string()],
            'ipAddress' => ['type' => Type::string()],
            'country' => ['type' => Type::string()],
            'region' => ['type' => Type::string()],
            'city' => ['type' => Type::string()],
            'siteId' => ['type' => Type::int()],
            'requestUrl' => ['type' => Type::string()],
            'requestMethod' => ['type' => Type::string()],
            'metadata' => ['type' => Type::string()],
            'oldValue' => ['type' => Type::string()],
            'newValue' => ['type' => Type::string()],
            'hash' => ['type' => Type::string()],
            'chainPosition' => ['type' => Type::int()],
            'prevHash' => ['type' => Type::string()],
            'dateCreated' => ['type' => Type::nonNull(Type::string())],
        ];
    }
}
