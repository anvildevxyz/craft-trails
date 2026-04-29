<?php

declare(strict_types=1);

namespace anvildev\trails\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $merkleRootId
 * @property string $anchorType s3|rfc3161
 * @property string $anchorRef
 * @property string|null $anchorProof
 * @property bool $verified
 * @property string $dateAnchored
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string $uid
 */
class AnchorRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%trails_anchors}}';
    }

    public function rules(): array
    {
        return [
            [['merkleRootId', 'anchorType', 'anchorRef', 'dateAnchored'], 'required'],
            [['merkleRootId'], 'integer'],
            [['anchorType'], 'in', 'range' => ['s3', 'rfc3161']],
            [['anchorRef'], 'string'],
            [['verified'], 'boolean'],
        ];
    }
}
