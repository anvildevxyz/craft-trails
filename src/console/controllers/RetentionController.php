<?php

namespace anvildev\trails\console\controllers;

use anvildev\trails\Trails;
use craft\console\Controller;
use yii\console\ExitCode;

class RetentionController extends Controller
{
    public bool $force = false;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        if ($actionID === 'cleanup') {
            $options[] = 'force';
        }
        return $options;
    }

    public function actionCleanup(): int
    {
        $settings = Trails::getInstance()->getSettings();

        if ($settings->retentionDays <= 0) {
            $this->stdout("Retention is set to keep logs forever (0 days). Nothing to clean up.\n");
            return ExitCode::OK;
        }

        $stats = Trails::getInstance()->retention->getRetentionStats();

        $this->stdout("Trails Retention Cleanup\n========================\n\n");
        $this->stdout("Retention policy: {$settings->retentionDays} days\n");
        $this->stdout("Total logs: {$stats['totalLogs']}\nLogs to delete: {$stats['logsToDelete']}\n\n");

        if ($stats['logsToDelete'] === 0) {
            $this->stdout("No logs to clean up.\n");
            return ExitCode::OK;
        }

        if (!$this->force && !$this->confirm("Delete {$stats['logsToDelete']} log entries older than {$settings->retentionDays} days?")) {
            $this->stdout("Aborted.\n");
            return ExitCode::OK;
        }

        $this->stdout("\n✓ Deleted " . Trails::getInstance()->retention->cleanupOldLogs() . " old log entries.\n");
        return ExitCode::OK;
    }

    public function actionStats(): int
    {
        $settings = Trails::getInstance()->getSettings();
        $stats = Trails::getInstance()->retention->getRetentionStats();

        $this->stdout("Trails Retention Statistics\n============================\n\n");
        $this->stdout("Configuration:\n  Retention period: ");
        $this->stdout($settings->retentionDays <= 0 ? "Forever (no auto-cleanup)\n" : "{$settings->retentionDays} days\n");

        $this->stdout("\nStorage:\n  Total logs: {$stats['totalLogs']}\n");

        if ($stats['oldestLogDate']) {
            $this->stdout("  Oldest log: {$stats['oldestLogDate']}\n");
        }
        if ($stats['newestLogDate']) {
            $this->stdout("  Newest log: {$stats['newestLogDate']}\n");
        }
        if ($stats['logsToDelete'] > 0) {
            $this->stdout("\n⚠ {$stats['logsToDelete']} logs are scheduled for cleanup\n");
            $this->stdout("  Run 'php craft trails/retention/cleanup' to delete them.\n");
        }

        return ExitCode::OK;
    }

    public function actionPurge(): int
    {
        $stats = Trails::getInstance()->retention->getRetentionStats();

        $this->stdout("⚠️  WARNING: This will DELETE ALL audit logs!\n\n");
        $this->stdout("Total logs to delete: {$stats['totalLogs']}\n\n");

        if ($stats['totalLogs'] === 0) {
            $this->stdout("No logs to purge.\n");
            return ExitCode::OK;
        }

        if (!$this->force) {
            if (!$this->confirm("Are you ABSOLUTELY sure you want to delete ALL {$stats['totalLogs']} logs?")) {
                $this->stdout("Aborted.\n");
                return ExitCode::OK;
            }
            if (!$this->confirm("This action cannot be undone. Type 'yes' to confirm:")) {
                $this->stdout("Aborted.\n");
                return ExitCode::OK;
            }
        }

        $deleted = \anvildev\trails\records\AuditLogRecord::deleteAll();
        $this->stdout("\n✓ Purged {$deleted} log entries.\n");

        Trails::getInstance()->audit->log('audit.purge', null, null, [
            'deletedCount' => $deleted,
            'description' => 'All audit logs purged via console command',
        ]);

        return ExitCode::OK;
    }
}
