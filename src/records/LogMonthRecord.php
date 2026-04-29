<?php

declare(strict_types=1);

namespace anvildev\trails\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $tableName
 * @property string $dateFrom
 * @property string $dateTo
 * @property int $rowCount
 * @property int|null $firstChainPosition
 * @property int|null $lastChainPosition
 * @property string $status active|archived|dropped
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 */
class LogMonthRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%trails_log_months}}';
    }

    public function rules(): array
    {
        return [
            [['tableName', 'dateFrom', 'dateTo', 'status'], 'required'],
            [['tableName'], 'string', 'max' => 64],
            [['rowCount', 'firstChainPosition', 'lastChainPosition'], 'integer'],
            [['status'], 'in', 'range' => ['active', 'archived', 'dropped']],
        ];
    }
}
