<?php

declare(strict_types=1);

namespace anvildev\trails\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $tokenHash
 * @property string|null $scopes
 * @property int|null $createdByUserId
 * @property string|null $lastUsedAt
 * @property string|null $expiresAt
 * @property string|null $revokedAt
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 */
class ApiTokenRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%trails_api_tokens}}';
    }
}
