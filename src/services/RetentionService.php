<?php

namespace anvildev\trails\services;

use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\Trails;
use Craft;
use craft\base\Component;

class RetentionService extends Component
{
    /** @return int Number of deleted records */
    public function cleanupOldLogs(): int
    {
        $settings = Trails::getInstance()->getSettings();

        if ($settings->retentionDays <= 0) {
            return 0;
        }

        $cutoffDate = date('Y-m-d', strtotime("-{$settings->retentionDays} days"));
        $rotation = Trails::getInstance()->tableRotation;
        $registry = $rotation->getRegistry();
        $totalDeleted = 0;

        // Drop entire archive tables that are fully before the cutoff
        foreach ($registry as $entry) {
            if ($entry['status'] !== 'archived') {
                continue;
            }
            if ($entry['dateTo'] < $cutoffDate) {
                $rowCount = (int) $entry['rowCount'];
                if ($rotation->dropArchive($entry['tableName'])) {
                    $totalDeleted += $rowCount;
                }
            }
        }

        // Row-level delete in the active table for any old records
        $cutoffDateTime = date('Y-m-d H:i:s', strtotime("-{$settings->retentionDays} days"));
        $activeDeleted = AuditLogRecord::deleteAll(['<', 'dateCreated', $cutoffDateTime]);
        $totalDeleted += $activeDeleted;

        if ($totalDeleted > 0) {
            Craft::info("Trails: Deleted {$totalDeleted} old audit log entries", 'trails');
            Trails::getInstance()->audit->log('audit.retention.cleanup', null, null, [
                'deletedCount' => $totalDeleted,
                'retentionDays' => $settings->retentionDays,
                'cutoffDate' => $cutoffDate,
            ]);
        }

        return $totalDeleted;
    }

    /** Single-query stats using MIN/MAX/COUNT + conditional SUM. */
    public function getRetentionStats(): array
    {
        $settings = Trails::getInstance()->getSettings();
        $select = [
            'totalLogs' => 'COUNT(*)',
            'oldestLogDate' => 'MIN([[dateCreated]])',
            'newestLogDate' => 'MAX([[dateCreated]])',
        ];

        if ($settings->retentionDays > 0) {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$settings->retentionDays} days"));
            $select['logsToDelete'] = new \yii\db\Expression(
                'SUM(CASE WHEN [[dateCreated]] < :cutoff THEN 1 ELSE 0 END)',
                [':cutoff' => $cutoff]
            );
        }

        $row = (new \yii\db\Query())->select($select)->from('{{%trails_logs}}')->one();

        return [
            'totalLogs' => (int)($row['totalLogs'] ?? 0),
            'retentionDays' => $settings->retentionDays,
            'oldestLogDate' => $row['oldestLogDate'] ?? null,
            'newestLogDate' => $row['newestLogDate'] ?? null,
            'logsToDelete' => (int)($row['logsToDelete'] ?? 0),
        ];
    }

    /** @return array{exported: int, deleted: int, exportPath: string|null} */
    public function cleanupWithExport(): array
    {
        $settings = Trails::getInstance()->getSettings();

        if ($settings->retentionDays <= 0) {
            return ['exported' => 0, 'deleted' => 0, 'exportPath' => null];
        }

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$settings->retentionDays} days"));
        $json = Trails::getInstance()->export->exportToJson(['dateTo' => $cutoffDate]);

        $exportPath = null;
        $exportedCount = 0;

        if ($json && $json !== '[]') {
            $exportDir = Craft::$app->getPath()->getStoragePath() . '/trails-archives';
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0750, true);
            }
            $exportPath = $exportDir . '/archive-' . date('Y-m-d-His') . '.json';
            if (file_put_contents($exportPath, $json) === false) {
                throw new \RuntimeException('Failed to write archive to ' . $exportPath . ' — cleanup aborted.');
            }
            $data = json_decode($json, true);
            $exportedCount = is_array($data) ? count($data) : 0;
        }

        return [
            'exported' => $exportedCount,
            'deleted' => $this->cleanupOldLogs(),
            'exportPath' => $exportPath,
        ];
    }
}
