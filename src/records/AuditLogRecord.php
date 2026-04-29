<?php

namespace anvildev\trails\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $event
 * @property string|null $category
 * @property string|null $elementType
 * @property int|null $elementId
 * @property string|null $elementTitle
 * @property int|null $userId
 * @property string|null $userName
 * @property string|null $userEmail
 * @property string|null $ipAddress
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property string|null $userAgent
 * @property string|null $requestUrl
 * @property string|null $requestMethod
 * @property int|null $siteId
 * @property string|null $oldValue
 * @property string|null $newValue
 * @property string|null $metadata
 * @property string|null $sessionId
 * @property string|null $hash
 * @property string|null $prevHash
 * @property int|null $merkleRootId
 * @property int|null $chainPosition
 * @property \DateTime $dateCreated
 * @property string $uid
 */
class AuditLogRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%trails_logs}}';
    }

    public function rules(): array
    {
        return [
            [['event'], 'required'],
            [['event'], 'string', 'max' => 100],
            [['category'], 'string', 'max' => 50],
            [['elementType'], 'string', 'max' => 255],
            [['elementId', 'userId', 'siteId'], 'integer'],
            [['elementTitle', 'userName'], 'string', 'max' => 255],
            [['userEmail'], 'string', 'max' => 512],
            [['requestUrl'], 'string', 'max' => 255],
            [['ipAddress'], 'string', 'max' => 45],
            [['country'], 'string', 'max' => 2],
            [['region', 'city'], 'string', 'max' => 100],
            [['userAgent'], 'string', 'max' => 500],
            [['requestMethod'], 'string', 'max' => 10],
            [['sessionId'], 'string', 'max' => 64],
            [['hash'], 'string', 'max' => 128],
            [['prevHash'], 'string', 'max' => 128],
            [['merkleRootId', 'chainPosition'], 'integer'],
            [['oldValue', 'newValue', 'metadata'], 'safe'],
        ];
    }
}
