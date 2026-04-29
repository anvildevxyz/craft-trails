<?php

namespace anvildev\trails\services;

use anvildev\trails\export\CsvStreamWriter;
use anvildev\trails\export\HtmlStreamWriter;
use anvildev\trails\export\JsonStreamWriter;
use anvildev\trails\export\StreamWriterInterface;
use anvildev\trails\helpers\EncryptionHelper;
use anvildev\trails\jobs\GenerateExportJob;
use anvildev\trails\models\Settings;
use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\records\ExportRecord;
use anvildev\trails\Trails;
use Craft;
use craft\base\Component;
use RuntimeException;

class ExportService extends Component
{
    private const CHUNK_SIZE = 500;
    private const LOG_FIELDS = [
        'id', 'dateCreated', 'event', 'category', 'elementType', 'elementId',
        'elementTitle', 'userId', 'userName', 'userEmail', 'ipAddress', 'siteId',
        'requestUrl', 'requestMethod',
    ];

    private const CSV_HEADERS = [
        'ID', 'Timestamp', 'Event', 'Category', 'Element Type', 'Element ID',
        'Element Title', 'User ID', 'Username', 'Email', 'IP Address', 'Site ID',
        'Request URL', 'Request Method',
    ];

    public function exportToCsv(array $criteria = [], bool $redactPii = false): string
    {
        $logs = $this->prepareLogs($criteria, $redactPii);
        $output = fopen('php://temp', 'r+');
        fputcsv($output, self::CSV_HEADERS);

        foreach ($logs as $log) {
            fputcsv($output, array_map(fn($f) => $this->escapeCsvField($log->$f), self::LOG_FIELDS));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    public function exportToJson(array $criteria = [], bool $redactPii = false): string
    {
        $logs = $this->prepareLogs($criteria, $redactPii);

        return json_encode(array_map(function($log) {
            $row = [];
            foreach (self::LOG_FIELDS as $field) {
                $row[$field === 'dateCreated' ? 'timestamp' : $field] = $log->$field;
            }
            foreach (['metadata', 'oldValue', 'newValue'] as $jsonField) {
                $row[$jsonField] = $log->$jsonField ? json_decode($log->$jsonField, true) : null;
            }
            return $row;
        }, $logs), JSON_PRETTY_PRINT);
    }

    public function exportToHtml(array $criteria = [], bool $redactPii = false): string
    {
        $logs = $this->prepareLogs($criteria, $redactPii);
        $count = count($logs);
        $date = date('Y-m-d H:i:s');
        $e = 'htmlspecialchars';

        $rows = '';
        foreach ($logs as $log) {
            $rows .= '<tr>'
                . '<td>' . $e($log->dateCreated) . '</td>'
                . '<td>' . $e($log->event) . '</td>'
                . '<td>' . $e($log->userName ?? 'System') . '</td>'
                . '<td>' . $e($log->elementTitle ?? '-') . '</td>'
                . '<td>' . $e($log->ipAddress ?? '-') . '</td>'
                . '</tr>';
        }

        return <<<HTML
<html><head><style>
body{font-family:Arial,sans-serif;font-size:12px}
h1{color:#333}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{border:1px solid #ddd;padding:8px;text-align:left}
th{background-color:#f4f4f4}
.meta{color:#666;font-size:10px}
</style></head><body>
<h1>Audit Trail Report</h1>
<p class="meta">Generated: {$date}</p>
<p class="meta">Total Records: {$count}</p>
<table>
<tr><th>Timestamp</th><th>Event</th><th>User</th><th>Element</th><th>IP</th></tr>
{$rows}
</table></body></html>
HTML;
    }

    private function escapeCsvField(mixed $value): string
    {
        $str = (string) $value;
        if ($str === '') {
            return $str;
        }
        if (in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $str;
        }
        return $str;
    }

    public function startBackgroundExport(string $format, array $criteria = [], bool $redactPii = false): ExportRecord
    {
        $userId = Craft::$app->getUser()->getId() ?? 0;

        /** @var Settings $settings */
        $settings = Trails::getInstance()->getSettings();
        $retentionSeconds = max(1, $settings->exportRetentionHours) * 3600;

        $record = new ExportRecord();
        $record->userId = $userId;
        $record->status = 'pending';
        $record->format = $format;
        $record->criteria = json_encode(array_merge($criteria, ['redactPii' => $redactPii]));
        $record->progress = 0;
        $record->dateExpires = date('Y-m-d H:i:s', time() + $retentionSeconds);

        if (!$record->save()) {
            throw new RuntimeException('Failed to save ExportRecord: ' . implode(', ', $record->getErrorSummary(true)));
        }

        Craft::$app->getQueue()->push(new GenerateExportJob(['exportId' => $record->id]));

        return $record;
    }

    public function getExport(int $id): ?ExportRecord
    {
        return ExportRecord::findOne($id);
    }

    public function getExportContent(ExportRecord $export): string
    {
        if ($export->filePath && file_exists($export->filePath)) {
            return (string) file_get_contents($export->filePath);
        }
        return '';
    }

    public function cleanupExpiredExports(): int
    {
        $records = ExportRecord::find()
            ->where(['<', 'dateExpires', date('Y-m-d H:i:s')])
            ->all();

        $count = 0;
        $bytesFreed = 0;
        foreach ($records as $record) {
            if ($record->filePath && file_exists($record->filePath)) {
                $bytesFreed += (int) filesize($record->filePath);
                unlink($record->filePath);
            }
            $record->delete();
            $count++;
        }

        if ($count > 0) {
            Craft::info(
                "Trails ExportService: cleaned up {$count} expired export(s); freed " . self::formatBytes($bytesFreed) . ".",
                'trails'
            );
        }

        return $count;
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 ** 2) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1024 ** 3) {
            return round($bytes / 1024 ** 2, 1) . ' MB';
        }
        return round($bytes / 1024 ** 3, 2) . ' GB';
    }

    public function shouldRunInBackground(array $criteria = []): bool
    {
        $plugin = Craft::$app->getPlugins()->getPlugin('trails');
        if (!$plugin instanceof Trails) {
            return false;
        }
        $count = $plugin->audit->countLogs($criteria);
        /** @var Settings $settings */
        $settings = $plugin->getSettings();
        return $count > $settings->inlineExportLimit;
    }

    /**
     * Stream a background export file in chunks to avoid loading all logs in memory.
     *
     * @param callable(int,int):void|null $onProgress receives (processed, total)
     */
    public function writeBackgroundExportFile(ExportRecord $export, array $criteria, bool $redactPii, ?callable $onProgress = null): string
    {
        $plugin = Craft::$app->getPlugins()->getPlugin('trails');
        if (!$plugin instanceof Trails) {
            throw new RuntimeException('Trails plugin is not available.');
        }

        $dir = Craft::$app->getPath()->getStoragePath() . '/trails-exports';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $date = date('Y-m-d');
        $filePath = "{$dir}/export-{$export->id}-{$date}.{$export->format}";
        $writer = $this->createWriter($export->format, $filePath);
        $writer->open();

        $total = (int) $plugin->audit->buildQuery($criteria)->count();
        $lastId = 0;
        $processed = 0;

        while (true) {
            $query = $plugin->audit->buildQuery($criteria);
            $query->orderBy(['id' => SORT_ASC]);
            $query->andWhere(['>', 'id', $lastId]);
            $query->limit(self::CHUNK_SIZE);

            /** @var AuditLogRecord[] $records */
            $records = $query->all();
            if ($records === []) {
                break;
            }

            foreach ($records as $record) {
                $row = $this->recordToRow($record, $redactPii);
                $writer->writeRow($row);
                $lastId = (int) $record->id;
                $processed++;

                if ($onProgress !== null && ($processed % 100 === 0 || $processed === $total)) {
                    $onProgress($processed, $total);
                }
            }

            if (count($records) < self::CHUNK_SIZE) {
                break;
            }
        }

        $writer->close();
        return $filePath;
    }

    private function prepareLogs(array $criteria, bool $redactPii): array
    {
        $plugin = Craft::$app->getPlugins()->getPlugin('trails');
        if (!$plugin instanceof Trails) {
            return [];
        }
        /** @var Settings $settings */
        $settings = $plugin->getSettings();
        $cap = $settings->inlineExportLimit;
        $requested = (int) ($criteria['limit'] ?? $cap);
        $query = $plugin->audit->buildQuery($criteria);
        $query->limit(max(1, min($requested, $cap)));
        $logs = $query->all();

        foreach ($logs as $i => $log) {
            $log = clone $log;
            $log->userEmail = EncryptionHelper::decrypt($log->userEmail);
            if ($redactPii) {
                $log->userEmail = $log->userEmail ? '[redacted]' : null;
                $log->ipAddress = $log->ipAddress ? '[redacted]' : null;
                $log->userAgent = $log->userAgent ? '[redacted]' : null;
                $log->sessionId = null;
            }
            $logs[$i] = $log;
        }

        return $logs;
    }

    private function createWriter(string $format, string $filePath): StreamWriterInterface
    {
        return match ($format) {
            'json' => new JsonStreamWriter($filePath),
            'html' => new HtmlStreamWriter($filePath),
            default => new CsvStreamWriter($filePath),
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function recordToRow(AuditLogRecord $record, bool $redactPii): array
    {
        $email = EncryptionHelper::decrypt($record->userEmail);
        $row = [];

        foreach (self::LOG_FIELDS as $field) {
            if ($field === 'userEmail') {
                $row[$field] = $email;
            } elseif ($record->$field instanceof \DateTime) {
                $row[$field] = $record->$field->format('c');
            } else {
                $row[$field] = $record->$field;
            }
        }

        if ($redactPii) {
            $row['userEmail'] = $row['userEmail'] ? '[redacted]' : null;
            $row['ipAddress'] = $row['ipAddress'] ? '[redacted]' : null;
        }

        return $row;
    }
}
