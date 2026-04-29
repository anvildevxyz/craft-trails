<?php

declare(strict_types=1);

namespace anvildev\trails\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $batchStartPosition
 * @property int $batchEndPosition
 * @property int $recordCount
 * @property string $rootHash
 * @property string $tableName
 * @property string $dateComputed
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string $uid
 */
class MerkleRootRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%trails_merkle_roots}}';
    }

    public function rules(): array
    {
        return [
            [['batchStartPosition', 'batchEndPosition', 'recordCount', 'rootHash', 'tableName', 'dateComputed'], 'required'],
            [['batchStartPosition', 'batchEndPosition', 'recordCount'], 'integer'],
            [['rootHash'], 'string', 'max' => 128],
            [['tableName'], 'string', 'max' => 64],
        ];
    }
}
