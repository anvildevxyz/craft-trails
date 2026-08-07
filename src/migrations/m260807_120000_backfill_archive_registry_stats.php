<?php

namespace anvildev\trails\migrations;

use craft\db\Migration;
use yii\db\Query;

/**
 * Backfills rowCount / firstChainPosition / lastChainPosition on archived
 * log-month registry entries.
 *
 * The normal rotation path (registry UPDATE) never wrote these stats — only
 * the out-of-sync backstop path did — so every archive created by a regular
 * rotation sits in the registry with rowCount 0. Recomputes the stats from
 * the archive tables that still exist.
 */
class m260807_120000_backfill_archive_registry_stats extends Migration
{
    public function safeUp(): bool
    {
        $rows = (new Query())
            ->select(['id', 'tableName', 'rowCount'])
            ->from('{{%trails_log_months}}')
            ->where(['status' => 'archived'])
            ->all($this->db);

        foreach ($rows as $row) {
            // Rows with a non-zero rowCount were filled by the backstop path
            // and are already correct.
            if ((int) $row['rowCount'] !== 0) {
                continue;
            }

            $table = '{{%' . $row['tableName'] . '}}';
            if (!$this->db->tableExists($table)) {
                continue;
            }

            $count = (int) (new Query())->from($table)->count('*', $this->db);
            $min = $count > 0 ? (int) (new Query())->from($table)->min('[[chainPosition]]', $this->db) : null;
            $max = $count > 0 ? (int) (new Query())->from($table)->max('[[chainPosition]]', $this->db) : null;

            $this->update('{{%trails_log_months}}', [
                'rowCount' => $count,
                'firstChainPosition' => $min ?: null,
                'lastChainPosition' => $max ?: null,
            ], ['id' => $row['id']]);
        }

        return true;
    }

    public function safeDown(): bool
    {
        // Backfilled stats are corrections; nothing to revert.
        return true;
    }
}
