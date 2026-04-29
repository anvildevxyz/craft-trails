<?php

return [
    // Navigation
    'Trails' => 'Trails',
    'Activity Logs' => 'Aktivitätsprotokolle',
    'Export' => 'Export',
    'Settings' => 'Einstellungen',

    // Permissions
    'View audit logs' => 'Audit-Protokolle ansehen',
    'Export audit logs' => 'Audit-Protokolle exportieren',
    'Manage settings' => 'Einstellungen verwalten',

    // Logs
    'Log Details' => 'Protokoll-Details',
    'Back to Logs' => 'Zurück zu Protokollen',
    'No audit logs found.' => 'Keine Audit-Protokolle gefunden.',
    'Showing {count} of {total} logs' => 'Zeige {count} von {total} Protokollen',
    'View' => 'Ansehen',
    'Filter' => 'Filter',
    'Reset' => 'Zurücksetzen',

    // Summary
    'Events (7d)' => 'Ereignisse (7T)',
    'Logins' => 'Anmeldungen',
    'Created' => 'Erstellt',
    'Updated' => 'Aktualisiert',
    'Deleted' => 'Gelöscht',

    // Filters
    'Event Type' => 'Ereignistyp',
    'Category' => 'Kategorie',
    'User' => 'Benutzer',
    'Search' => 'Suche',
    'Search logs...' => 'Protokolle durchsuchen...',
    'All Events' => 'Alle Ereignisse',
    'All Categories' => 'Alle Kategorien',
    'All Users' => 'Alle Benutzer',

    // Log details
    'ID' => 'ID',
    'Timestamp' => 'Zeitstempel',
    'Event' => 'Ereignis',
    'User Information' => 'Benutzerinformationen',
    'User ID' => 'Benutzer-ID',
    'Username' => 'Benutzername',
    'Email' => 'E-Mail',
    'IP Address' => 'IP-Adresse',
    'Session ID' => 'Sitzungs-ID',
    'Element Information' => 'Element-Informationen',
    'Element Type' => 'Element-Typ',
    'Element ID' => 'Element-ID',
    'Element Title' => 'Element-Titel',
    'Site ID' => 'Site-ID',
    'Request Information' => 'Anfrage-Informationen',
    'Request Method' => 'Anfrage-Methode',
    'Request URL' => 'Anfrage-URL',
    'User Agent' => 'User Agent',
    'Metadata' => 'Metadaten',
    'Changes' => 'Änderungen',
    'Previous Value' => 'Vorheriger Wert',
    'New Value' => 'Neuer Wert',
    'Integrity' => 'Integrität',
    'Hash' => 'Hash',

    // Pagination
    '← Previous' => '← Zurück',
    'Next →' => 'Weiter →',

    // Export
    'Export Logs' => 'Protokolle exportieren',
    'Export Audit Logs' => 'Audit-Protokolle exportieren',
    'Export your audit logs for compliance reporting, backup, or analysis.' => 'Exportieren Sie Ihre Audit-Protokolle für Compliance-Berichte, Backups oder Analysen.',
    'From Date' => 'Von Datum',
    'To Date' => 'Bis Datum',
    'Leave empty to include all history' => 'Leer lassen, um alle Historie einzuschliessen',
    'Leave empty to include up to now' => 'Leer lassen, um bis jetzt einzuschliessen',
    'Export Format' => 'Export-Format',
    'Choose the format for your export file' => 'Wählen Sie das Format für Ihre Export-Datei',
    'Download Export' => 'Export herunterladen',
    // Settings
    'Trails Settings' => 'Trails Einstellungen',
    'General Settings' => 'Allgemeine Einstellungen',
    'Enable Logging' => 'Protokollierung aktivieren',
    'When disabled, no new audit events will be recorded.' => 'Wenn deaktiviert, werden keine neuen Audit-Ereignisse aufgezeichnet.',
    'Retention Period (Days)' => 'Aufbewahrungsfrist (Tage)',
    'How long to keep logs. Set to 0 to keep forever.' => 'Wie lange Protokolle aufbewahrt werden. Auf 0 setzen für unbegrenzte Aufbewahrung.',
    'Current Storage' => 'Aktueller Speicher',
    'logs' => 'Protokolle',
    'from' => 'von',
    '{count} logs scheduled for cleanup' => '{count} Protokolle zur Bereinigung vorgesehen',
    'What to Log' => 'Was protokollieren',
    'Element Changes' => 'Element-Änderungen',
    'Log when entries, assets, users, and other elements are created, updated, or deleted.' => 'Protokollieren, wenn Einträge, Assets, Benutzer und andere Elemente erstellt, aktualisiert oder gelöscht werden.',
    'User Authentication' => 'Benutzer-Authentifizierung',
    'Log user logins and logouts.' => 'Benutzer-An- und Abmeldungen protokollieren.',
    'Failed Login Attempts' => 'Fehlgeschlagene Anmeldeversuche',
    'Log failed authentication attempts for security monitoring.' => 'Fehlgeschlagene Authentifizierungsversuche für Sicherheitsüberwachung protokollieren.',
    'Config Changes' => 'Konfigurationsänderungen',
    'Log when project config changes are applied.' => 'Protokollieren, wenn Projektkonfigurationsänderungen angewendet werden.',
    'Asset Operations' => 'Asset-Operationen',
    'Log asset uploads, deletions, and modifications.' => 'Asset-Uploads, -Löschungen und -Änderungen protokollieren.',
    'Data Capture' => 'Datenerfassung',
    'Capture IP Addresses' => 'IP-Adressen erfassen',
    'Store the IP address of each action.' => 'Die IP-Adresse jeder Aktion speichern.',
    'Anonymize IPs (GDPR)' => 'IPs anonymisieren (DSGVO)',
    'Replace the last octet of IP addresses with 0 for privacy compliance.' => 'Ersetzen Sie das letzte Oktett der IP-Adressen durch 0 für Datenschutz-Compliance.',
    'Capture User Agent' => 'User Agent erfassen',
    'Store the browser/device information.' => 'Browser-/Geräteinformationen speichern.',
    'Capture Field Changes' => 'Feldänderungen erfassen',
    'Store before/after values of field changes. Warning: increases storage usage.' => 'Vorher-/Nachher-Werte von Feldänderungen speichern. Warnung: erhöht die Speichernutzung.',
    'This can significantly increase database size.' => 'Dies kann die Datenbankgrösse erheblich erhöhen.',
    'Email Alerts' => 'E-Mail-Benachrichtigungen',
    'Enable Alerts' => 'Benachrichtigungen aktivieren',
    'Send email notifications for suspicious activity.' => 'E-Mail-Benachrichtigungen bei verdächtiger Aktivität senden.',
    'Alert Email' => 'Benachrichtigungs-E-Mail',
    'Email address to receive security alerts.' => 'E-Mail-Adresse für Sicherheitswarnungen.',
    'Failed Login Threshold' => 'Schwellenwert für fehlgeschlagene Anmeldungen',
    'Send alert after this many failed logins from the same IP within 1 hour.' => 'Benachrichtigung senden nach dieser Anzahl fehlgeschlagener Anmeldungen von derselben IP innerhalb 1 Stunde.',
    'Save Settings' => 'Einstellungen speichern',
    'Maintenance' => 'Wartung',
    'Run retention cleanup manually to remove old logs based on your retention policy.' => 'Retention-Bereinigung manuell ausführen, um alte Protokolle basierend auf Ihrer Aufbewahrungsrichtlinie zu entfernen.',
    'Run Cleanup Now' => 'Bereinigung jetzt ausführen',
    'Are you sure you want to delete old logs?' => 'Sind Sie sicher, dass Sie alte Protokolle löschen möchten?',
    'Couldn\'t save settings.' => 'Einstellungen konnten nicht gespeichert werden.',
    'Settings saved.' => 'Einstellungen gespeichert.',
    'Deleted {count} old log entries.' => '{count} alte Protokolleinträge gelöscht.',

    // New settings
    'Permission Changes' => 'Berechtigungsänderungen',
    'Log when user permissions or group assignments change.' => 'Protokollieren, wenn Benutzerberechtigungen oder Gruppenzuweisungen geändert werden.',
    'Log asset uploads, replacements, and modifications.' => 'Asset-Uploads, -Ersetzungen und -Änderungen protokollieren.',

    // Exclusions
    'Exclusions' => 'Ausnahmen',
    'Specify element types and sections to exclude from logging.' => 'Elementtypen und Bereiche angeben, die von der Protokollierung ausgeschlossen werden sollen.',
    'Excluded Element Types' => 'Ausgeschlossene Elementtypen',
    'Select element types to exclude from audit logging.' => 'Elementtypen auswählen, die von der Audit-Protokollierung ausgeschlossen werden sollen.',
    'Excluded Sections' => 'Ausgeschlossene Bereiche',
    'Select sections to exclude from audit logging (only applies to Entry elements).' => 'Bereiche auswählen, die von der Audit-Protokollierung ausgeschlossen werden sollen (gilt nur für Entry-Elemente).',

    // External Log Shipping
    'External Log Shipping' => 'Externes Log-Shipping',
    'Send audit logs to external services for centralized monitoring.' => 'Audit-Protokolle an externe Dienste für zentralisierte Überwachung senden.',
    'Enable External Shipping' => 'Externes Shipping aktivieren',
    'Send logs to an external service via queue.' => 'Protokolle über die Warteschlange an einen externen Dienst senden.',
    'Provider' => 'Anbieter',
    'Select the external logging provider.' => 'Externen Protokollierungsanbieter auswählen.',
    'Endpoint URL' => 'Endpunkt-URL',
    'The URL to send logs to (e.g., Splunk HEC endpoint, Datadog intake, or custom webhook).' => 'Die URL, an die Protokolle gesendet werden (z.B. Splunk HEC-Endpunkt, Datadog-Intake oder benutzerdefinierter Webhook).',
    'API Key' => 'API-Schlüssel',
    'API key or token for authentication with the external service.' => 'API-Schlüssel oder Token zur Authentifizierung beim externen Dienst.',

    // CLI
    'CLI: php craft trails/retention/cleanup' => 'CLI: php craft trails/retention/cleanup',

    // Job
    'Shipping audit log to {provider}' => 'Audit-Protokoll an {provider} senden',

    // v1.1 — Date filters
    'Date From' => 'Datum von',
    'Date To' => 'Datum bis',

    // v1.1 — Scheduled retention
    'Run cleanup automatically on a daily schedule' => 'Bereinigung automatisch nach täglichem Zeitplan ausführen',
    'GC-based cleanup runs automatically regardless. This option adds a predictable daily schedule via the queue.' => 'Die GC-basierte Bereinigung läuft ohnehin automatisch. Diese Option fügt einen vorhersehbaren täglichen Zeitplan über die Warteschlange hinzu.',
    'Scheduled audit log retention cleanup' => 'Geplante Bereinigung der Audit-Protokollaufbewahrung',

    // v1.1 — Dashboard widget
    'Audit Activity' => 'Audit-Aktivität',
    'Events' => 'Ereignisse',
    'Insufficient permissions to view audit data.' => 'Unzureichende Berechtigungen zum Anzeigen von Audit-Daten.',
    'Lookback Period' => 'Rückblickzeitraum',
    '7 days' => '7 Tage',
    '14 days' => '14 Tage',
    '30 days' => '30 Tage',
    'View all logs →' => 'Alle Protokolle ansehen →',

    // v1.2 — Pagination
    'Per page' => 'Pro Seite',
    'Page {current} of {total}' => 'Seite {current} von {total}',
    '« First' => '« Erste',
    '‹ Prev' => '‹ Zurück',
    'Next ›' => 'Weiter ›',
    'Last »' => 'Letzte »',

    // v1.2 — Batch shipping
    'Shipping {count} audit logs to {provider}' => '{count} Audit-Protokolle an {provider} senden',

    // v1.3 — Integrity check
    'Integrity Check' => 'Integritätsprüfung',
    'Log Integrity Verification' => 'Protokoll-Integritätsüberprüfung',
    'Walks every audit log and verifies its HMAC-SHA256 integrity hash. Tampered records are reported here and via the CLI.' => 'Durchläuft jedes Audit-Protokoll und überprüft seinen HMAC-SHA256-Integritäts-Hash. Manipulierte Datensätze werden hier und über die CLI gemeldet.',
    'Last Run' => 'Letzter Durchlauf',
    'Run At' => 'Ausgeführt am',
    'Verified' => 'Verifiziert',
    'Tampered' => 'Manipuliert',
    'None' => 'Keine',
    'records' => 'Datensätze',
    'IDs:' => 'IDs:',
    'No verification runs yet.' => 'Noch keine Überprüfungen durchgeführt.',
    'Verify All Logs Now' => 'Alle Protokolle jetzt überprüfen',
    'May take a few minutes for large log tables.' => 'Kann bei grossen Protokolltabellen einige Minuten dauern.',
    'Verified — record integrity intact' => 'Verifiziert — Datensatzintegrität intakt',
    'WARNING — record may have been tampered with' => 'WARNUNG — Datensatz wurde möglicherweise manipuliert',
    'Not verified' => 'Nicht verifiziert',
    'Verification' => 'Überprüfung',
    'Verification complete. {count} tampered records found.' => 'Überprüfung abgeschlossen. {count} manipulierte Datensätze gefunden.',
    'All {total} logs verified OK.' => 'Alle {total} Protokolle erfolgreich verifiziert.',

    // v1.3 — Webhook signature
    'Webhook Secret' => 'Webhook-Geheimnis',
    'Webhook Signature' => 'Webhook-Signatur',
    'Sign outgoing webhook payloads with a shared secret so receivers can verify authenticity.' => 'Ausgehende Webhook-Payloads mit einem gemeinsamen Geheimnis signieren, damit Empfänger die Authentizität überprüfen können.',
    'Optional shared secret for signing webhook payloads. Supports $ENV_VAR syntax.' => 'Optionales gemeinsames Geheimnis zum Signieren von Webhook-Payloads. Unterstützt $ENV_VAR-Syntax.',

    // v1.3 — Settings navigation & descriptions
    'General' => 'Allgemein',
    'Logging' => 'Protokollierung',
    'Alerts' => 'Benachrichtigungen',
    'External Shipping' => 'Externes Shipping',
    'Alert Settings' => 'Benachrichtigungseinstellungen',
    'Logging Settings' => 'Protokollierungseinstellungen',
    'Configure basic audit trail settings.' => 'Grundlegende Audit-Trail-Einstellungen konfigurieren.',
    'Configure which events should be tracked.' => 'Konfigurieren, welche Ereignisse verfolgt werden sollen.',
    'Configure email notifications for suspicious activity.' => 'E-Mail-Benachrichtigungen für verdächtige Aktivitäten konfigurieren.',
    'Configure what additional data to capture with each audit event.' => 'Konfigurieren, welche zusätzlichen Daten mit jedem Audit-Ereignis erfasst werden sollen.',

    // v1.3 — Log view extras
    'Location' => 'Standort',
    'Element' => 'Element',
    'No changes detected.' => 'Keine Änderungen erkannt.',
    'Field' => 'Feld',
    'Old' => 'Alt',
    'New' => 'Neu',

    // v1.3 — IP geolocation
    'IP Geolocation' => 'IP-Geolokalisierung',
    'Enable geolocation' => 'Geolokalisierung aktivieren',
    'Resolve IP addresses to country/region/city via an external API. Adds a small delay per log write.' => 'IP-Adressen über eine externe API nach Land/Region/Stadt auflösen. Fügt eine kleine Verzögerung pro Protokollschreibvorgang hinzu.',
    'Geolocation endpoint' => 'Geolokalisierungs-Endpunkt',
    'URL template. Default: http://ip-api.com/json/ (free, rate-limited).' => 'URL-Vorlage. Standard: http://ip-api.com/json/ (kostenlos, ratenbegrenzt).',

    // v1.3 — Export validation
    'Invalid "from" date format.' => 'Ungültiges „Von"-Datumsformat.',
    'Invalid "to" date format.' => 'Ungültiges „Bis"-Datumsformat.',
    '"From" date must be before "to" date.' => '„Von"-Datum muss vor dem „Bis"-Datum liegen.',

    // v1.3 — Retention warning
    'Retention is set to keep logs forever. Without a scheduled cleanup job, the database will grow indefinitely. Consider setting a retention period or scheduling `php craft trails/retention/cleanup` via cron.' => 'Die Aufbewahrung ist auf unbegrenzt eingestellt. Ohne einen geplanten Bereinigungsjob wird die Datenbank unbegrenzt wachsen. Erwägen Sie, einen Aufbewahrungszeitraum festzulegen oder `php craft trails/retention/cleanup` per Cron zu planen.',
];
