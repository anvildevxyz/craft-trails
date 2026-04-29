<?php

return [
    // Navigation
    'Trails' => 'Trails',
    'Activity Logs' => 'アクティビティログ',
    'Export' => 'エクスポート',
    'Settings' => '設定',

    // Permissions
    'View audit logs' => '監査ログを表示',
    'Export audit logs' => '監査ログをエクスポート',
    'Manage settings' => '設定を管理',

    // Logs
    'Log Details' => 'ログの詳細',
    'Back to Logs' => 'ログ一覧に戻る',
    'No audit logs found.' => '監査ログが見つかりませんでした。',
    'Showing {count} of {total} logs' => '{total}件中{count}件のログを表示中',
    'View' => '表示',
    'Filter' => 'フィルター',
    'Reset' => 'リセット',

    // Summary
    'Events (7d)' => 'イベント（7日間）',
    'Logins' => 'ログイン',
    'Created' => '作成',
    'Updated' => '更新',
    'Deleted' => '削除',

    // Filters
    'Event Type' => 'イベントタイプ',
    'Category' => 'カテゴリ',
    'User' => 'ユーザー',
    'Search' => '検索',
    'Search logs...' => 'ログを検索...',
    'All Events' => 'すべてのイベント',
    'All Categories' => 'すべてのカテゴリ',
    'All Users' => 'すべてのユーザー',

    // Log details
    'ID' => 'ID',
    'Timestamp' => 'タイムスタンプ',
    'Event' => 'イベント',
    'User Information' => 'ユーザー情報',
    'User ID' => 'ユーザーID',
    'Username' => 'ユーザー名',
    'Email' => 'メールアドレス',
    'IP Address' => 'IPアドレス',
    'Session ID' => 'セッションID',
    'Element Information' => 'エレメント情報',
    'Element Type' => 'エレメントタイプ',
    'Element ID' => 'エレメントID',
    'Element Title' => 'エレメントタイトル',
    'Site ID' => 'サイトID',
    'Request Information' => 'リクエスト情報',
    'Request Method' => 'リクエストメソッド',
    'Request URL' => 'リクエストURL',
    'User Agent' => 'ユーザーエージェント',
    'Metadata' => 'メタデータ',
    'Changes' => '変更内容',
    'Previous Value' => '変更前の値',
    'New Value' => '変更後の値',
    'Integrity' => '整合性',
    'Hash' => 'ハッシュ',

    // Pagination
    '← Previous' => '← 前へ',
    'Next →' => '次へ →',

    // Export
    'Export Logs' => 'ログをエクスポート',
    'Export Audit Logs' => '監査ログをエクスポート',
    'Export your audit logs for compliance reporting, backup, or analysis.' => 'コンプライアンスレポート、バックアップ、または分析のために監査ログをエクスポートします。',
    'From Date' => '開始日',
    'To Date' => '終了日',
    'Leave empty to include all history' => '空欄にするとすべての履歴を含めます',
    'Leave empty to include up to now' => '空欄にすると現在までを含めます',
    'Export Format' => 'エクスポート形式',
    'Choose the format for your export file' => 'エクスポートファイルの形式を選択してください',
    'Download Export' => 'エクスポートをダウンロード',
    // Settings
    'Trails Settings' => 'Trails設定',
    'General Settings' => '一般設定',
    'Enable Logging' => 'ログを有効にする',
    'When disabled, no new audit events will be recorded.' => '無効にすると、新しい監査イベントは記録されません。',
    'Retention Period (Days)' => '保持期間（日数）',
    'How long to keep logs. Set to 0 to keep forever.' => 'ログの保持期間を設定します。0に設定すると永久に保持します。',
    'Current Storage' => '現在のストレージ',
    'logs' => 'ログ',
    'from' => '以降',
    '{count} logs scheduled for cleanup' => '{count}件のログがクリーンアップ対象として予定されています',
    'What to Log' => '記録する内容',
    'Element Changes' => 'エレメントの変更',
    'Log when entries, assets, users, and other elements are created, updated, or deleted.' => 'エントリ、アセット、ユーザー、その他のエレメントが作成・更新・削除された際に記録します。',
    'User Authentication' => 'ユーザー認証',
    'Log user logins and logouts.' => 'ユーザーのログインとログアウトを記録します。',
    'Failed Login Attempts' => 'ログイン失敗の試み',
    'Log failed authentication attempts for security monitoring.' => 'セキュリティ監視のために認証失敗の試みを記録します。',
    'Config Changes' => '設定の変更',
    'Log when project config changes are applied.' => 'プロジェクト設定の変更が適用された際に記録します。',
    'Asset Operations' => 'アセット操作',
    'Log asset uploads, deletions, and modifications.' => 'アセットのアップロード、削除、変更を記録します。',
    'Data Capture' => 'データ取得',
    'Capture IP Addresses' => 'IPアドレスを取得',
    'Store the IP address of each action.' => '各アクションのIPアドレスを保存します。',
    'Anonymize IPs (GDPR)' => 'IPを匿名化（GDPR）',
    'Replace the last octet of IP addresses with 0 for privacy compliance.' => 'プライバシーコンプライアンスのため、IPアドレスの最後のオクテットを0に置き換えます。',
    'Capture User Agent' => 'ユーザーエージェントを取得',
    'Store the browser/device information.' => 'ブラウザ/デバイスの情報を保存します。',
    'Capture Field Changes' => 'フィールド変更を取得',
    'Store before/after values of field changes. Warning: increases storage usage.' => 'フィールド変更の変更前/変更後の値を保存します。警告：ストレージ使用量が増加します。',
    'This can significantly increase database size.' => 'これによりデータベースのサイズが大幅に増加する可能性があります。',
    'Email Alerts' => 'メールアラート',
    'Enable Alerts' => 'アラートを有効にする',
    'Send email notifications for suspicious activity.' => '不審なアクティビティに対してメール通知を送信します。',
    'Alert Email' => 'アラート用メールアドレス',
    'Email address to receive security alerts.' => 'セキュリティアラートを受信するメールアドレス。',
    'Failed Login Threshold' => 'ログイン失敗のしきい値',
    'Send alert after this many failed logins from the same IP within 1 hour.' => '同一IPから1時間以内にこの回数のログイン失敗があった場合にアラートを送信します。',
    'Save Settings' => '設定を保存',
    'Maintenance' => 'メンテナンス',
    'Run retention cleanup manually to remove old logs based on your retention policy.' => '保持ポリシーに基づいて古いログを削除するため、手動でクリーンアップを実行します。',
    'Run Cleanup Now' => '今すぐクリーンアップを実行',
    'Are you sure you want to delete old logs?' => '古いログを削除してよろしいですか？',
    'Couldn\'t save settings.' => '設定を保存できませんでした。',
    'Settings saved.' => '設定が保存されました。',
    'Deleted {count} old log entries.' => '{count}件の古いログエントリを削除しました。',

    // New settings
    'Permission Changes' => '権限の変更',
    'Log when user permissions or group assignments change.' => 'ユーザーの権限またはグループ割り当てが変更された際に記録します。',
    'Log asset uploads, replacements, and modifications.' => 'アセットのアップロード、置き換え、変更を記録します。',

    // Exclusions
    'Exclusions' => '除外設定',
    'Specify element types and sections to exclude from logging.' => 'ログ記録から除外するエレメントタイプとセクションを指定します。',
    'Excluded Element Types' => '除外するエレメントタイプ',
    'Select element types to exclude from audit logging.' => '監査ログから除外するエレメントタイプを選択します。',
    'Excluded Sections' => '除外するセクション',
    'Select sections to exclude from audit logging (only applies to Entry elements).' => '監査ログから除外するセクションを選択します（エントリエレメントにのみ適用）。',

    // External Log Shipping
    'External Log Shipping' => '外部ログ送信',
    'Send audit logs to external services for centralized monitoring.' => '集中監視のために監査ログを外部サービスに送信します。',
    'Enable External Shipping' => '外部送信を有効にする',
    'Send logs to an external service via queue.' => 'キュー経由でログを外部サービスに送信します。',
    'Provider' => 'プロバイダー',
    'Select the external logging provider.' => '外部ログプロバイダーを選択します。',
    'Endpoint URL' => 'エンドポイントURL',
    'The URL to send logs to (e.g., Splunk HEC endpoint, Datadog intake, or custom webhook).' => 'ログの送信先URL（例：Splunk HECエンドポイント、Datadogインテーク、またはカスタムWebhook）。',
    'API Key' => 'APIキー',
    'API key or token for authentication with the external service.' => '外部サービスとの認証に使用するAPIキーまたはトークン。',

    // CLI
    'CLI: php craft trails/retention/cleanup' => 'CLI: php craft trails/retention/cleanup',

    // Job
    'Shipping audit log to {provider}' => '{provider}に監査ログを送信中',

    // v1.1 — Date filters
    'Date From' => '開始日',
    'Date To' => '終了日',

    // v1.1 — Scheduled retention
    'Run cleanup automatically on a daily schedule' => '日次スケジュールでクリーンアップを自動実行する',
    'GC-based cleanup runs automatically regardless. This option adds a predictable daily schedule via the queue.' => 'GCベースのクリーンアップはいずれにしても自動的に実行されます。このオプションは、キュー経由で予測可能な日次スケジュールを追加します。',
    'Scheduled audit log retention cleanup' => '監査ログ保持のスケジュールクリーンアップ',

    // v1.1 — Dashboard widget
    'Audit Activity' => '監査アクティビティ',
    'Events' => 'イベント',
    'Insufficient permissions to view audit data.' => '監査データを表示する権限が不足しています。',
    'Lookback Period' => '振り返り期間',
    '7 days' => '7日間',
    '14 days' => '14日間',
    '30 days' => '30日間',
    'View all logs →' => 'すべてのログを表示 →',

    // v1.2 — Pagination
    'Per page' => '1ページあたり',
    'Page {current} of {total}' => '{total}ページ中{current}ページ',
    '« First' => '« 最初',
    '‹ Prev' => '‹ 前へ',
    'Next ›' => '次へ ›',
    'Last »' => '最後 »',

    // v1.2 — Batch shipping
    'Shipping {count} audit logs to {provider}' => '{count}件の監査ログを{provider}に送信中',

    // v1.3 — Integrity check
    'Integrity Check' => '整合性チェック',
    'Log Integrity Verification' => 'ログ整合性の検証',
    'Walks every audit log and verifies its HMAC-SHA256 integrity hash. Tampered records are reported here and via the CLI.' => 'すべての監査ログを走査し、HMAC-SHA256整合性ハッシュを検証します。改ざんされたレコードはここおよびCLI経由で報告されます。',
    'Last Run' => '最終実行',
    'Run At' => '実行日時',
    'Verified' => '検証済み',
    'Tampered' => '改ざん',
    'None' => 'なし',
    'records' => '件',
    'IDs:' => 'ID：',
    'No verification runs yet.' => 'まだ検証が実行されていません。',
    'Verify All Logs Now' => 'すべてのログを今すぐ検証',
    'May take a few minutes for large log tables.' => '大きなログテーブルの場合、数分かかることがあります。',
    'Verified — record integrity intact' => '検証済み — レコードの整合性は正常です',
    'WARNING — record may have been tampered with' => '警告 — レコードが改ざんされた可能性があります',
    'Not verified' => '未検証',
    'Verification' => '検証',
    'Verification complete. {count} tampered records found.' => '検証が完了しました。{count}件の改ざんされたレコードが見つかりました。',
    'All {total} logs verified OK.' => '{total}件すべてのログの検証が正常に完了しました。',

    // v1.3 — Webhook signature
    'Webhook Secret' => 'Webhookシークレット',
    'Webhook Signature' => 'Webhook署名',
    'Sign outgoing webhook payloads with a shared secret so receivers can verify authenticity.' => '受信者が真正性を検証できるよう、送信Webhookペイロードを共有シークレットで署名します。',
    'Optional shared secret for signing webhook payloads. Supports $ENV_VAR syntax.' => 'Webhookペイロードの署名に使用するオプションの共有シークレット。$ENV_VAR構文をサポートします。',

    // v1.3 — Settings navigation & descriptions
    'General' => '一般',
    'Logging' => 'ログ記録',
    'Alerts' => 'アラート',
    'External Shipping' => '外部送信',
    'Alert Settings' => 'アラート設定',
    'Logging Settings' => 'ログ記録設定',
    'Configure basic audit trail settings.' => '監査トレイルの基本設定を構成します。',
    'Configure which events should be tracked.' => '追跡するイベントを設定します。',
    'Configure email notifications for suspicious activity.' => '不審なアクティビティに対するメール通知を設定します。',
    'Configure what additional data to capture with each audit event.' => '各監査イベントで取得する追加データを設定します。',

    // v1.3 — Log view extras
    'Location' => '場所',
    'Element' => 'エレメント',
    'No changes detected.' => '変更は検出されませんでした。',
    'Field' => 'フィールド',
    'Old' => '旧',
    'New' => '新',

    // v1.3 — IP geolocation
    'IP Geolocation' => 'IPジオロケーション',
    'Enable geolocation' => 'ジオロケーションを有効にする',
    'Resolve IP addresses to country/region/city via an external API. Adds a small delay per log write.' => '外部APIを使用してIPアドレスを国/地域/都市に解決します。ログ書き込みごとにわずかな遅延が追加されます。',
    'Geolocation endpoint' => 'ジオロケーションエンドポイント',
    'URL template. Default: http://ip-api.com/json/ (free, rate-limited).' => 'URLテンプレート。デフォルト：http://ip-api.com/json/（無料、レート制限あり）。',

    // v1.3 — Export validation
    'Invalid "from" date format.' => '「開始」日付の形式が無効です。',
    'Invalid "to" date format.' => '「終了」日付の形式が無効です。',
    '"From" date must be before "to" date.' => '「開始」日付は「終了」日付より前でなければなりません。',

    // v1.3 — Retention warning
    'Retention is set to keep logs forever. Without a scheduled cleanup job, the database will grow indefinitely. Consider setting a retention period or scheduling `php craft trails/retention/cleanup` via cron.' => '保持設定がログを永久に保存するよう設定されています。スケジュールされたクリーンアップジョブがなければ、データベースは際限なく増大します。保持期間を設定するか、cronで`php craft trails/retention/cleanup`をスケジュールすることを検討してください。',
];
