<?php

namespace anvildev\trails\records;

use craft\db\ActiveRecord;

/**
 * ExportRecord — ActiveRecord for {{%trails_exports}}.
 *
 * @property int         $id
 * @property int         $userId
 * @property string      $status       pending|processing|complete|failed
 * @property string      $format       csv|json|html
 * @property string|null $filePath
 * @property int|null    $totalRecords
 * @property int         $progress
 * @property string|null $criteria
 * @property string      $dateExpires
 * @property string      $dateCreated
 * @property string      $dateUpdated
 * @property string      $uid
 */
class ExportRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%trails_exports}}';
    }
}
