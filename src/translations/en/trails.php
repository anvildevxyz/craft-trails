<?php

return [
    // Navigation
    'Trails' => 'Trails',
    'Activity Logs' => 'Activity Logs',
    'Export' => 'Export',
    'Settings' => 'Settings',

    // Permissions
    'View audit logs' => 'View audit logs',
    'Export audit logs' => 'Export audit logs',
    'Manage settings' => 'Manage settings',

    // Logs
    'Log Details' => 'Log Details',
    'Back to Logs' => 'Back to Logs',
    'No audit logs found.' => 'No audit logs found.',
    'Showing {count} of {total} logs' => 'Showing {count} of {total} logs',
    'View' => 'View',
    'Filter' => 'Filter',
    'Reset' => 'Reset',

    // Summary
    'Events (7d)' => 'Events (7d)',
    'Logins' => 'Logins',
    'Created' => 'Created',
    'Updated' => 'Updated',
    'Deleted' => 'Deleted',

    // Filters
    'Event Type' => 'Event Type',
    'Category' => 'Category',
    'User' => 'User',
    'Search' => 'Search',
    'Search logs...' => 'Search logs...',
    'All Events' => 'All Events',
    'All Categories' => 'All Categories',
    'All Users' => 'All Users',

    // Log details
    'ID' => 'ID',
    'Timestamp' => 'Timestamp',
    'Event' => 'Event',
    'User Information' => 'User Information',
    'User ID' => 'User ID',
    'Username' => 'Username',
    'Email' => 'Email',
    'IP Address' => 'IP Address',
    'Session ID' => 'Session ID',
    'Element Information' => 'Element Information',
    'Element Type' => 'Element Type',
    'Element ID' => 'Element ID',
    'Element Title' => 'Element Title',
    'Site ID' => 'Site ID',
    'Request Information' => 'Request Information',
    'Request Method' => 'Request Method',
    'Request URL' => 'Request URL',
    'User Agent' => 'User Agent',
    'Metadata' => 'Metadata',
    'Changes' => 'Changes',
    'Previous Value' => 'Previous Value',
    'New Value' => 'New Value',
    'Integrity' => 'Integrity',
    'Hash' => 'Hash',

    // Pagination
    '← Previous' => '← Previous',
    'Next →' => 'Next →',

    // Export
    'Export Logs' => 'Export Logs',
    'Export Audit Logs' => 'Export Audit Logs',
    'Export your audit logs for compliance reporting, backup, or analysis.' => 'Export your audit logs for compliance reporting, backup, or analysis.',
    'From Date' => 'From Date',
    'To Date' => 'To Date',
    'Leave empty to include all history' => 'Leave empty to include all history',
    'Leave empty to include up to now' => 'Leave empty to include up to now',
    'Export Format' => 'Export Format',
    'Choose the format for your export file' => 'Choose the format for your export file',
    'Download Export' => 'Download Export',
    // Settings
    'Trails Settings' => 'Trails Settings',
    'General Settings' => 'General Settings',
    'Enable Logging' => 'Enable Logging',
    'When disabled, no new audit events will be recorded.' => 'When disabled, no new audit events will be recorded.',
    'Retention Period (Days)' => 'Retention Period (Days)',
    'How long to keep logs. Set to 0 to keep forever.' => 'How long to keep logs. Set to 0 to keep forever.',
    'Current Storage' => 'Current Storage',
    'logs' => 'logs',
    'from' => 'from',
    '{count} logs scheduled for cleanup' => '{count} logs scheduled for cleanup',
    'What to Log' => 'What to Log',
    'Element Changes' => 'Element Changes',
    'Log when entries, assets, users, and other elements are created, updated, or deleted.' => 'Log when entries, assets, users, and other elements are created, updated, or deleted.',
    'User Authentication' => 'User Authentication',
    'Log user logins and logouts.' => 'Log user logins and logouts.',
    'Failed Login Attempts' => 'Failed Login Attempts',
    'Log failed authentication attempts for security monitoring.' => 'Log failed authentication attempts for security monitoring.',
    'Config Changes' => 'Config Changes',
    'Log when project config changes are applied.' => 'Log when project config changes are applied.',
    'Asset Operations' => 'Asset Operations',
    'Log asset uploads, deletions, and modifications.' => 'Log asset uploads, deletions, and modifications.',
    'Data Capture' => 'Data Capture',
    'Capture IP Addresses' => 'Capture IP Addresses',
    'Store the IP address of each action.' => 'Store the IP address of each action.',
    'Anonymize IPs (GDPR)' => 'Anonymize IPs (GDPR)',
    'Replace the last octet of IP addresses with 0 for privacy compliance.' => 'Replace the last octet of IP addresses with 0 for privacy compliance.',
    'Capture User Agent' => 'Capture User Agent',
    'Store the browser/device information.' => 'Store the browser/device information.',
    'Capture Field Changes' => 'Capture Field Changes',
    'Store before/after values of field changes. Warning: increases storage usage.' => 'Store before/after values of field changes. Warning: increases storage usage.',
    'This can significantly increase database size.' => 'This can significantly increase database size.',
    'Email Alerts' => 'Email Alerts',
    'Enable Alerts' => 'Enable Alerts',
    'Send email notifications for suspicious activity.' => 'Send email notifications for suspicious activity.',
    'Alert Email' => 'Alert Email',
    'Email address to receive security alerts.' => 'Email address to receive security alerts.',
    'Failed Login Threshold' => 'Failed Login Threshold',
    'Send alert after this many failed logins from the same IP within 1 hour.' => 'Send alert after this many failed logins from the same IP within 1 hour.',
    'Save Settings' => 'Save Settings',
    'Maintenance' => 'Maintenance',
    'Run retention cleanup manually to remove old logs based on your retention policy.' => 'Run retention cleanup manually to remove old logs based on your retention policy.',
    'Run Cleanup Now' => 'Run Cleanup Now',
    'Are you sure you want to delete old logs?' => 'Are you sure you want to delete old logs?',
    'Couldn\'t save settings.' => 'Couldn\'t save settings.',
    'Settings saved.' => 'Settings saved.',
    'Deleted {count} old log entries.' => 'Deleted {count} old log entries.',

    // New settings
    'Permission Changes' => 'Permission Changes',
    'Log when user permissions or group assignments change.' => 'Log when user permissions or group assignments change.',
    'Log asset uploads, replacements, and modifications.' => 'Log asset uploads, replacements, and modifications.',

    // Exclusions
    'Exclusions' => 'Exclusions',
    'Specify element types and sections to exclude from logging.' => 'Specify element types and sections to exclude from logging.',
    'Excluded Element Types' => 'Excluded Element Types',
    'Select element types to exclude from audit logging.' => 'Select element types to exclude from audit logging.',
    'Excluded Sections' => 'Excluded Sections',
    'Select sections to exclude from audit logging (only applies to Entry elements).' => 'Select sections to exclude from audit logging (only applies to Entry elements).',

    // External Log Shipping
    'External Log Shipping' => 'External Log Shipping',
    'Send audit logs to external services for centralized monitoring.' => 'Send audit logs to external services for centralized monitoring.',
    'Enable External Shipping' => 'Enable External Shipping',
    'Send logs to an external service via queue.' => 'Send logs to an external service via queue.',
    'Provider' => 'Provider',
    'Select the external logging provider.' => 'Select the external logging provider.',
    'Endpoint URL' => 'Endpoint URL',
    'The URL to send logs to (e.g., Splunk HEC endpoint, Datadog intake, or custom webhook).' => 'The URL to send logs to (e.g., Splunk HEC endpoint, Datadog intake, or custom webhook).',
    'API Key' => 'API Key',
    'API key or token for authentication with the external service.' => 'API key or token for authentication with the external service.',

    // CLI
    'CLI: php craft trails/retention/cleanup' => 'CLI: php craft trails/retention/cleanup',

    // Job
    'Shipping audit log to {provider}' => 'Shipping audit log to {provider}',

    // v1.1 — Date filters
    'Date From' => 'Date From',
    'Date To' => 'Date To',

    // v1.1 — Scheduled retention
    'Run cleanup automatically on a daily schedule' => 'Run cleanup automatically on a daily schedule',
    'GC-based cleanup runs automatically regardless. This option adds a predictable daily schedule via the queue.' => 'GC-based cleanup runs automatically regardless. This option adds a predictable daily schedule via the queue.',
    'Scheduled audit log retention cleanup' => 'Scheduled audit log retention cleanup',

    // v1.1 — Dashboard widget
    'Audit Activity' => 'Audit Activity',
    'Events' => 'Events',
    'Insufficient permissions to view audit data.' => 'Insufficient permissions to view audit data.',
    'Lookback Period' => 'Lookback Period',
    '7 days' => '7 days',
    '14 days' => '14 days',
    '30 days' => '30 days',
    'View all logs →' => 'View all logs →',

    // v1.2 — Pagination
    'Per page' => 'Per page',
    'Page {current} of {total}' => 'Page {current} of {total}',
    '« First' => '« First',
    '‹ Prev' => '‹ Prev',
    'Next ›' => 'Next ›',
    'Last »' => 'Last »',

    // v1.2 — Batch shipping
    'Shipping {count} audit logs to {provider}' => 'Shipping {count} audit logs to {provider}',

    // v1.3 — Integrity check
    'Integrity Check' => 'Integrity Check',
    'Log Integrity Verification' => 'Log Integrity Verification',
    'Walks every audit log and verifies its HMAC-SHA256 integrity hash. Tampered records are reported here and via the CLI.' => 'Walks every audit log and verifies its HMAC-SHA256 integrity hash. Tampered records are reported here and via the CLI.',
    'Last Run' => 'Last Run',
    'Run At' => 'Run At',
    'Verified' => 'Verified',
    'Tampered' => 'Tampered',
    'None' => 'None',
    'records' => 'records',
    'IDs:' => 'IDs:',
    'No verification runs yet.' => 'No verification runs yet.',
    'Verify All Logs Now' => 'Verify All Logs Now',
    'May take a few minutes for large log tables.' => 'May take a few minutes for large log tables.',
    'Verified — record integrity intact' => 'Verified — record integrity intact',
    'WARNING — record may have been tampered with' => 'WARNING — record may have been tampered with',
    'Not verified' => 'Not verified',
    'Verification' => 'Verification',
    'Verification complete. {count} tampered records found.' => 'Verification complete. {count} tampered records found.',
    'All {total} logs verified OK.' => 'All {total} logs verified OK.',

    // v1.3 — Webhook signature
    'Webhook Secret' => 'Webhook Secret',
    'Webhook Signature' => 'Webhook Signature',
    'Sign outgoing webhook payloads with a shared secret so receivers can verify authenticity.' => 'Sign outgoing webhook payloads with a shared secret so receivers can verify authenticity.',
    'Optional shared secret for signing webhook payloads. Supports $ENV_VAR syntax.' => 'Optional shared secret for signing webhook payloads. Supports $ENV_VAR syntax.',

    // v1.3 — Settings navigation & descriptions
    'General' => 'General',
    'Logging' => 'Logging',
    'Alerts' => 'Alerts',
    'External Shipping' => 'External Shipping',
    'Alert Settings' => 'Alert Settings',
    'Logging Settings' => 'Logging Settings',
    'Configure basic audit trail settings.' => 'Configure basic audit trail settings.',
    'Configure which events should be tracked.' => 'Configure which events should be tracked.',
    'Configure email notifications for suspicious activity.' => 'Configure email notifications for suspicious activity.',
    'Configure what additional data to capture with each audit event.' => 'Configure what additional data to capture with each audit event.',

    // v1.3 — Log view extras
    'Location' => 'Location',
    'Element' => 'Element',
    'No changes detected.' => 'No changes detected.',
    'Field' => 'Field',
    'Old' => 'Old',
    'New' => 'New',

    // v1.3 — IP geolocation
    'IP Geolocation' => 'IP Geolocation',
    'Enable geolocation' => 'Enable geolocation',
    'Resolve IP addresses to country/region/city via an external API. Adds a small delay per log write.' => 'Resolve IP addresses to country/region/city via an external API. Adds a small delay per log write.',
    'Geolocation endpoint' => 'Geolocation endpoint',
    'URL template. Default: http://ip-api.com/json/ (free, rate-limited).' => 'URL template. Default: http://ip-api.com/json/ (free, rate-limited).',

    // v1.3 — Export validation
    'Invalid "from" date format.' => 'Invalid "from" date format.',
    'Invalid "to" date format.' => 'Invalid "to" date format.',
    '"From" date must be before "to" date.' => '"From" date must be before "to" date.',

    // v1.3 — Retention warning
    'Retention is set to keep logs forever. Without a scheduled cleanup job, the database will grow indefinitely. Consider setting a retention period or scheduling `php craft trails/retention/cleanup` via cron.' => 'Retention is set to keep logs forever. Without a scheduled cleanup job, the database will grow indefinitely. Consider setting a retention period or scheduling `php craft trails/retention/cleanup` via cron.',
];
