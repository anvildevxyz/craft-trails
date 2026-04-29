<?php

return [
    // Navigation
    'Trails' => 'Trails',
    'Activity Logs' => 'Registri attività',
    'Export' => 'Esporta',
    'Settings' => 'Impostazioni',

    // Permissions
    'View audit logs' => 'Visualizza i registri di audit',
    'Export audit logs' => 'Esporta i registri di audit',
    'Manage settings' => 'Gestisci impostazioni',

    // Logs
    'Log Details' => 'Dettagli registro',
    'Back to Logs' => 'Torna ai registri',
    'No audit logs found.' => 'Nessun registro di audit trovato.',
    'Showing {count} of {total} logs' => 'Visualizzazione di {count} su {total} registri',
    'View' => 'Visualizza',
    'Filter' => 'Filtra',
    'Reset' => 'Reimposta',

    // Summary
    'Events (7d)' => 'Eventi (7g)',
    'Logins' => 'Accessi',
    'Created' => 'Creati',
    'Updated' => 'Aggiornati',
    'Deleted' => 'Eliminati',

    // Filters
    'Event Type' => 'Tipo di evento',
    'Category' => 'Categoria',
    'User' => 'Utente',
    'Search' => 'Cerca',
    'Search logs...' => 'Cerca nei registri...',
    'All Events' => 'Tutti gli eventi',
    'All Categories' => 'Tutte le categorie',
    'All Users' => 'Tutti gli utenti',

    // Log details
    'ID' => 'ID',
    'Timestamp' => 'Data e ora',
    'Event' => 'Evento',
    'User Information' => 'Informazioni utente',
    'User ID' => 'ID utente',
    'Username' => 'Nome utente',
    'Email' => 'E-mail',
    'IP Address' => 'Indirizzo IP',
    'Session ID' => 'ID sessione',
    'Element Information' => 'Informazioni elemento',
    'Element Type' => 'Tipo di elemento',
    'Element ID' => 'ID elemento',
    'Element Title' => 'Titolo elemento',
    'Site ID' => 'ID sito',
    'Request Information' => 'Informazioni richiesta',
    'Request Method' => 'Metodo della richiesta',
    'Request URL' => 'URL della richiesta',
    'User Agent' => 'User agent',
    'Metadata' => 'Metadati',
    'Changes' => 'Modifiche',
    'Previous Value' => 'Valore precedente',
    'New Value' => 'Nuovo valore',
    'Integrity' => 'Integrità',
    'Hash' => 'Hash',

    // Pagination
    '← Previous' => '← Precedente',
    'Next →' => 'Successivo →',

    // Export
    'Export Logs' => 'Esporta registri',
    'Export Audit Logs' => 'Esporta registri di audit',
    'Export your audit logs for compliance reporting, backup, or analysis.' => 'Esporta i registri di audit per report di conformità, backup o analisi.',
    'From Date' => 'Data inizio',
    'To Date' => 'Data fine',
    'Leave empty to include all history' => 'Lascia vuoto per includere tutta la cronologia',
    'Leave empty to include up to now' => 'Lascia vuoto per includere fino ad ora',
    'Export Format' => 'Formato di esportazione',
    'Choose the format for your export file' => 'Scegli il formato per il file di esportazione',
    'Download Export' => 'Scarica esportazione',
    // Settings
    'Trails Settings' => 'Impostazioni di Trails',
    'General Settings' => 'Impostazioni generali',
    'Enable Logging' => 'Abilita la registrazione',
    'When disabled, no new audit events will be recorded.' => 'Se disabilitato, non verranno registrati nuovi eventi di audit.',
    'Retention Period (Days)' => 'Periodo di conservazione (giorni)',
    'How long to keep logs. Set to 0 to keep forever.' => 'Per quanto tempo conservare i registri. Imposta 0 per conservarli per sempre.',
    'Current Storage' => 'Spazio attuale',
    'logs' => 'registri',
    'from' => 'da',
    '{count} logs scheduled for cleanup' => '{count} registri programmati per la pulizia',
    'What to Log' => 'Cosa registrare',
    'Element Changes' => 'Modifiche agli elementi',
    'Log when entries, assets, users, and other elements are created, updated, or deleted.' => 'Registra la creazione, l\'aggiornamento o l\'eliminazione di voci, risorse, utenti e altri elementi.',
    'User Authentication' => 'Autenticazione utente',
    'Log user logins and logouts.' => 'Registra gli accessi e le disconnessioni degli utenti.',
    'Failed Login Attempts' => 'Tentativi di accesso falliti',
    'Log failed authentication attempts for security monitoring.' => 'Registra i tentativi di autenticazione falliti per il monitoraggio della sicurezza.',
    'Config Changes' => 'Modifiche alla configurazione',
    'Log when project config changes are applied.' => 'Registra quando vengono applicate modifiche alla configurazione del progetto.',
    'Asset Operations' => 'Operazioni sulle risorse',
    'Log asset uploads, deletions, and modifications.' => 'Registra i caricamenti, le eliminazioni e le modifiche alle risorse.',
    'Data Capture' => 'Acquisizione dati',
    'Capture IP Addresses' => 'Acquisisci indirizzi IP',
    'Store the IP address of each action.' => 'Salva l\'indirizzo IP di ogni azione.',
    'Anonymize IPs (GDPR)' => 'Anonimizza gli IP (GDPR)',
    'Replace the last octet of IP addresses with 0 for privacy compliance.' => 'Sostituisce l\'ultimo ottetto degli indirizzi IP con 0 per la conformità alla privacy.',
    'Capture User Agent' => 'Acquisisci user agent',
    'Store the browser/device information.' => 'Salva le informazioni sul browser/dispositivo.',
    'Capture Field Changes' => 'Acquisisci modifiche ai campi',
    'Store before/after values of field changes. Warning: increases storage usage.' => 'Salva i valori prima/dopo le modifiche ai campi. Attenzione: aumenta l\'utilizzo dello spazio.',
    'This can significantly increase database size.' => 'Questo può aumentare significativamente le dimensioni del database.',
    'Email Alerts' => 'Avvisi e-mail',
    'Enable Alerts' => 'Abilita avvisi',
    'Send email notifications for suspicious activity.' => 'Invia notifiche e-mail per attività sospette.',
    'Alert Email' => 'E-mail di avviso',
    'Email address to receive security alerts.' => 'Indirizzo e-mail per ricevere gli avvisi di sicurezza.',
    'Failed Login Threshold' => 'Soglia tentativi falliti',
    'Send alert after this many failed logins from the same IP within 1 hour.' => 'Invia un avviso dopo questo numero di accessi falliti dallo stesso IP nell\'arco di 1 ora.',
    'Save Settings' => 'Salva impostazioni',
    'Maintenance' => 'Manutenzione',
    'Run retention cleanup manually to remove old logs based on your retention policy.' => 'Esegui manualmente la pulizia della conservazione per rimuovere i vecchi registri in base alla policy di conservazione.',
    'Run Cleanup Now' => 'Esegui pulizia ora',
    'Are you sure you want to delete old logs?' => 'Sei sicuro di voler eliminare i vecchi registri?',
    'Couldn\'t save settings.' => 'Impossibile salvare le impostazioni.',
    'Settings saved.' => 'Impostazioni salvate.',
    'Deleted {count} old log entries.' => 'Eliminate {count} voci di registro obsolete.',

    // New settings
    'Permission Changes' => 'Modifiche ai permessi',
    'Log when user permissions or group assignments change.' => 'Registra le modifiche ai permessi degli utenti o alle assegnazioni di gruppo.',
    'Log asset uploads, replacements, and modifications.' => 'Registra i caricamenti, le sostituzioni e le modifiche alle risorse.',

    // Exclusions
    'Exclusions' => 'Esclusioni',
    'Specify element types and sections to exclude from logging.' => 'Specifica i tipi di elementi e le sezioni da escludere dalla registrazione.',
    'Excluded Element Types' => 'Tipi di elementi esclusi',
    'Select element types to exclude from audit logging.' => 'Seleziona i tipi di elementi da escludere dalla registrazione di audit.',
    'Excluded Sections' => 'Sezioni escluse',
    'Select sections to exclude from audit logging (only applies to Entry elements).' => 'Seleziona le sezioni da escludere dalla registrazione di audit (si applica solo agli elementi voce).',

    // External Log Shipping
    'External Log Shipping' => 'Invio registri esterni',
    'Send audit logs to external services for centralized monitoring.' => 'Invia i registri di audit a servizi esterni per il monitoraggio centralizzato.',
    'Enable External Shipping' => 'Abilita invio esterno',
    'Send logs to an external service via queue.' => 'Invia i registri a un servizio esterno tramite coda.',
    'Provider' => 'Provider',
    'Select the external logging provider.' => 'Seleziona il provider di registrazione esterno.',
    'Endpoint URL' => 'URL endpoint',
    'The URL to send logs to (e.g., Splunk HEC endpoint, Datadog intake, or custom webhook).' => 'L\'URL a cui inviare i registri (es. endpoint HEC di Splunk, intake Datadog o webhook personalizzato).',
    'API Key' => 'Chiave API',
    'API key or token for authentication with the external service.' => 'Chiave API o token per l\'autenticazione con il servizio esterno.',

    // CLI
    'CLI: php craft trails/retention/cleanup' => 'CLI: php craft trails/retention/cleanup',

    // Job
    'Shipping audit log to {provider}' => 'Invio registro di audit a {provider}',

    // v1.1 — Date filters
    'Date From' => 'Data da',
    'Date To' => 'Data a',

    // v1.1 — Scheduled retention
    'Run cleanup automatically on a daily schedule' => 'Esegui la pulizia automaticamente con pianificazione giornaliera',
    'GC-based cleanup runs automatically regardless. This option adds a predictable daily schedule via the queue.' => 'La pulizia basata sul GC viene eseguita automaticamente in ogni caso. Questa opzione aggiunge una pianificazione giornaliera prevedibile tramite la coda.',
    'Scheduled audit log retention cleanup' => 'Pulizia pianificata della conservazione dei registri di audit',

    // v1.1 — Dashboard widget
    'Audit Activity' => 'Attività di audit',
    'Events' => 'Eventi',
    'Insufficient permissions to view audit data.' => 'Permessi insufficienti per visualizzare i dati di audit.',
    'Lookback Period' => 'Periodo di osservazione',
    '7 days' => '7 giorni',
    '14 days' => '14 giorni',
    '30 days' => '30 giorni',
    'View all logs →' => 'Visualizza tutti i registri →',

    // v1.2 — Pagination
    'Per page' => 'Per pagina',
    'Page {current} of {total}' => 'Pagina {current} di {total}',
    '« First' => '« Prima',
    '‹ Prev' => '‹ Prec.',
    'Next ›' => 'Succ. ›',
    'Last »' => 'Ultima »',

    // v1.2 — Batch shipping
    'Shipping {count} audit logs to {provider}' => 'Invio di {count} registri di audit a {provider}',

    // v1.3 — Integrity check
    'Integrity Check' => 'Verifica di integrità',
    'Log Integrity Verification' => 'Verifica dell\'integrità dei registri',
    'Walks every audit log and verifies its HMAC-SHA256 integrity hash. Tampered records are reported here and via the CLI.' => 'Esamina ogni registro di audit e verifica il suo hash di integrità HMAC-SHA256. I record alterati vengono segnalati qui e tramite la CLI.',
    'Last Run' => 'Ultima esecuzione',
    'Run At' => 'Eseguito il',
    'Verified' => 'Verificato',
    'Tampered' => 'Alterato',
    'None' => 'Nessuno',
    'records' => 'record',
    'IDs:' => 'ID:',
    'No verification runs yet.' => 'Nessuna verifica eseguita finora.',
    'Verify All Logs Now' => 'Verifica tutti i registri ora',
    'May take a few minutes for large log tables.' => 'Potrebbe richiedere alcuni minuti per tabelle di registri di grandi dimensioni.',
    'Verified — record integrity intact' => 'Verificato — integrità del record intatta',
    'WARNING — record may have been tampered with' => 'ATTENZIONE — il record potrebbe essere stato alterato',
    'Not verified' => 'Non verificato',
    'Verification' => 'Verifica',
    'Verification complete. {count} tampered records found.' => 'Verifica completata. Trovati {count} record alterati.',
    'All {total} logs verified OK.' => 'Tutti i {total} registri verificati correttamente.',

    // v1.3 — Webhook signature
    'Webhook Secret' => 'Segreto del webhook',
    'Webhook Signature' => 'Firma del webhook',
    'Sign outgoing webhook payloads with a shared secret so receivers can verify authenticity.' => 'Firma i payload dei webhook in uscita con un segreto condiviso in modo che i destinatari possano verificare l\'autenticità.',
    'Optional shared secret for signing webhook payloads. Supports $ENV_VAR syntax.' => 'Segreto condiviso opzionale per firmare i payload dei webhook. Supporta la sintassi $ENV_VAR.',

    // v1.3 — Settings navigation & descriptions
    'General' => 'Generale',
    'Logging' => 'Registrazione',
    'Alerts' => 'Avvisi',
    'External Shipping' => 'Invio esterno',
    'Alert Settings' => 'Impostazioni avvisi',
    'Logging Settings' => 'Impostazioni di registrazione',
    'Configure basic audit trail settings.' => 'Configura le impostazioni di base del registro di audit.',
    'Configure which events should be tracked.' => 'Configura quali eventi devono essere tracciati.',
    'Configure email notifications for suspicious activity.' => 'Configura le notifiche e-mail per attività sospette.',
    'Configure what additional data to capture with each audit event.' => 'Configura quali dati aggiuntivi acquisire con ogni evento di audit.',

    // v1.3 — Log view extras
    'Location' => 'Posizione',
    'Element' => 'Elemento',
    'No changes detected.' => 'Nessuna modifica rilevata.',
    'Field' => 'Campo',
    'Old' => 'Precedente',
    'New' => 'Nuovo',

    // v1.3 — IP geolocation
    'IP Geolocation' => 'Geolocalizzazione IP',
    'Enable geolocation' => 'Abilita geolocalizzazione',
    'Resolve IP addresses to country/region/city via an external API. Adds a small delay per log write.' => 'Risolvi gli indirizzi IP in paese/regione/città tramite un\'API esterna. Aggiunge un piccolo ritardo per ogni scrittura nel registro.',
    'Geolocation endpoint' => 'Endpoint di geolocalizzazione',
    'URL template. Default: http://ip-api.com/json/ (free, rate-limited).' => 'Modello URL. Predefinito: http://ip-api.com/json/ (gratuito, con limiti di velocità).',

    // v1.3 — Export validation
    'Invalid "from" date format.' => 'Formato della data «da» non valido.',
    'Invalid "to" date format.' => 'Formato della data «a» non valido.',
    '"From" date must be before "to" date.' => 'La data «da» deve essere precedente alla data «a».',

    // v1.3 — Retention warning
    'Retention is set to keep logs forever. Without a scheduled cleanup job, the database will grow indefinitely. Consider setting a retention period or scheduling `php craft trails/retention/cleanup` via cron.' => 'La conservazione è impostata per mantenere i registri per sempre. Senza un processo di pulizia pianificato, il database crescerà indefinitamente. Considera di impostare un periodo di conservazione o di pianificare `php craft trails/retention/cleanup` tramite cron.',
];
