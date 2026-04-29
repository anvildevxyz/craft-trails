<?php

namespace anvildev\trails\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;

class TrailsMerkleProofType extends ObjectType
{
    public static function getName(): string
    {
        return 'TrailsMerkleProof';
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
            'leafHash' => ['type' => Type::nonNull(Type::string())],
            'rootHash' => ['type' => Type::nonNull(Type::string())],
            'leafIndex' => ['type' => Type::nonNull(Type::int())],
            'treeSize' => ['type' => Type::nonNull(Type::int())],
            'verified' => ['type' => Type::nonNull(Type::boolean())],
            'path' => ['type' => Type::listOf(Type::string())],
        ];
    }
}
